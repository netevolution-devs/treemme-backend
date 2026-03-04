<?php

namespace App\Controller;

use App\Entity\Batch;
use App\Entity\BatchOrder;
use App\Entity\BatchComposition;
use App\Entity\BatchSelection;
use App\Entity\BatchType;
use App\Entity\ClientOrderRow;
use App\Entity\Leather;
use App\Entity\Selection;
use App\Entity\WarehouseMovement;
use App\Entity\WarehouseMovementReason;
use App\Entity\MeasurementUnit;
use App\Entity\User;
use App\Service\CreateMethodsByInput;
use App\Service\DoResponseService;
use App\Service\GroupSerializerService;
use App\Service\ValidatorOutputFormatter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class BatchController extends AbstractController
{
    private $createMethodsByInput;
    private $doctrine;
    private $doResponse;
    private $groupSerializer;
    private $validatorOutputFormatter;

    public function __construct(
        CreateMethodsByInput     $createMethodsByInput,
        EntityManagerInterface   $entityManager,
        DoResponseService        $doResponseService,
        GroupSerializerService   $groupSerializer,
        ValidatorOutputFormatter $validatorOutputFormatter,
    )
    {
        $this->createMethodsByInput = $createMethodsByInput;
        $this->doctrine = $entityManager;
        $this->doResponse = $doResponseService;
        $this->groupSerializer = $groupSerializer;
        $this->validatorOutputFormatter = $validatorOutputFormatter;
    }

    #[Route('/batch/{id}',
        name: 'get_batch',
        defaults: ['id' => null],
        requirements: ['id' => '\d*'],
        methods: ['GET', 'HEAD'])]
    public function getBatch(?int $id): JsonResponse
    {
        $batchRepository = $this->doctrine->getRepository(Batch::class);

        if ($id) {
            $batch = [$batchRepository->find($id)];
            if (!$batch[0]) {
                return new JsonResponse($this->doResponse->doErrorResponse('Batch not found', 404));
            }
        } else {
            $batch = $batchRepository->findBy([], ['id' => 'DESC']);
        }
        $results = $this->groupSerializer->serializeGroup($batch, $id ? 'batch_detail' : 'batch_list');

        if ($id) {
            return new JsonResponse($this->doResponse->doResponse($results[0]));
        }
        return new JsonResponse($this->doResponse->doResponse($results));
    }

    #[Route('/batch/available',
        name: 'get_available_batches',
        methods: ['GET', 'HEAD'])]
    public function getAvailableBatches(): JsonResponse
    {
        $batchRepository = $this->doctrine->getRepository(Batch::class);
        $batches = $batchRepository->findAvailableStock();

        $results = $this->groupSerializer->serializeGroup($batches, 'batch_list');

        return new JsonResponse($this->doResponse->doResponse($results));
    }

    #[Route('/batch/create-tf',
        name: 'post_batch_tf',
        methods: ['POST'])]
    public function createTfBatch(
        Request            $request,
        ValidatorInterface $validator,
    ): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!$data) {
            $data = $request->request->all();
        }

        if (!isset($data['sources']) || !is_array($data['sources'])) {
            return new JsonResponse($this->doResponse->doErrorResponse('Sorgenti non valide', 400));
        }

        try {
            $batchRepo = $this->doctrine->getRepository(Batch::class);
            $batchTypeRepo = $this->doctrine->getRepository(BatchType::class);
            $reasonRepo = $this->doctrine->getRepository(WarehouseMovementReason::class);
            $orderRowRepo = $this->doctrine->getRepository(ClientOrderRow::class);

            $orderRow = null;
            if (isset($data['order_row_id'])) {
                $orderRow = $orderRowRepo->find($data['order_row_id']);
                if (!$orderRow) {
                    throw new \Exception('Riga ordine ' . $data['order_row_id'] . ' non trovata');
                }
            }

            $tfBatchType = $batchTypeRepo->findOneBy(['name' => 'TF'])
                ?? $batchTypeRepo->findOneBy(['prefix' => 'TF']);

            if (!$tfBatchType) {
                $tfBatchType = new BatchType();
                $tfBatchType->setName('TF');
                $tfBatchType->setPrefix('TF');
                $tfBatchType->setSaleProcess(false);
                $tfBatchType->setCreatedAt(new \DateTimeImmutable());
                $tfBatchType->setUpdatedAt(new \DateTimeImmutable());
                $this->doctrine->persist($tfBatchType);
            }

            $lastTfBatch = $batchRepo->findLatestBatchByPrefix('TF');
            $lastCode = $lastTfBatch ? $lastTfBatch->getBatchCode() : null;
            $nextCode = $this->nextSequentialCode($lastCode, 'TF', 6);

            $newBatch = new Batch();
            $newBatch->setBatchType($tfBatchType);
            $newBatch->setBatchCode($nextCode);
            $newBatch->setBatchDate(new \DateTime());
            $newBatch->setCompleted(false);
            $newBatch->setChecked(false);
            $newBatch->setSampling(false);
            $newBatch->setSplitSelected(false);

            $totalPieces = 0;
            $totalQuantity = 0.0;
            $firstLeather = null;
            $firstUnit = null;

            foreach ($data['sources'] as $source) {
                $sourceBatch = $batchRepo->find($source['batch_id']);
                if (!$sourceBatch) {
                    throw new \Exception('Lotto sorgente ' . $source['batch_id'] . ' non trovato');
                }

                $pieces = (int)($source['pieces'] ?? 0);
                $quantity = (float)($source['quantity'] ?? 0.0);

                if ($pieces <= 0) {
                    throw new \Exception('Pezzi non validi per il lotto ' . $sourceBatch->getBatchCode());
                }

                if ($sourceBatch->getStockItems() < $pieces) {
                    throw new \Exception('Disponibilità insufficiente per il lotto ' . $sourceBatch->getBatchCode());
                }

                if (!$firstLeather) $firstLeather = $sourceBatch->getLeather();
                if (!$firstUnit) $firstUnit = $sourceBatch->getMeasurementUnit();

                $sourceBatch->setStockItems($sourceBatch->getStockItems() - $pieces);
                $sourceBatch->setStockQuantity($sourceBatch->getStockQuantity() - $quantity);

                $composition = new BatchComposition();
                $composition->setBatch($newBatch);
                $composition->setFatherBatch($sourceBatch);
                $composition->setFatherBatchPiece($pieces);
                $composition->setFatherBatchQuantity($quantity);
                $composition->setCompositionNote('Composizione lotto TF ' . $nextCode);
                $this->doctrine->persist($composition);

                // Movimento in uscita dal sorgente
                $outReason = $reasonRepo->createQueryBuilder('r')
                    ->join('r.reason_type', 't')
                    ->where('r.name = :name')
                    ->andWhere('t.movement_type = :type')
                    ->setParameter('name', 'Scarico per lavorazione interna')
                    ->setParameter('type', '-')
                    ->getQuery()
                    ->getOneOrNullResult()
                    ?? $reasonRepo->findOneBy(['name' => 'Scarico Lavorazione'])
                    ?? $reasonRepo->findOneBy(['name' => 'Vendita']);

                if ($outReason) {
                    $outMovement = new WarehouseMovement();
                    $outMovement->setBatch($sourceBatch);
                    $outMovement->setReason($outReason);
                    $outMovement->setPiece($pieces);
                    $outMovement->setQuantity($quantity);
                    $outMovement->setDate(new \DateTime());
                    $outMovement->setMovementNote('Uscita per creazione lotto ' . $nextCode);
                    $this->doctrine->persist($outMovement);
                }

                $totalPieces += $pieces;
                $totalQuantity += $quantity;
            }

            $newBatch->setPieces($totalPieces);
            $newBatch->setQuantity($totalQuantity);
            $newBatch->setStockItems((float)$totalPieces);
            $newBatch->setStockQuantity($totalQuantity);
            $newBatch->setLeather($firstLeather);
            $newBatch->setMeasurementUnit($firstUnit);

            $firstSourceId = $data['sources'][0]['batch_id'] ?? null;
            $firstSourceBatch = $firstSourceId ? $batchRepo->find($firstSourceId) : null;
            if ($firstSourceBatch) {
                $newBatch->setSqFtAverageExpected($firstSourceBatch->getSqFtAverageExpected() ?? 0.0);
                $newBatch->setSqFtAverageFound($firstSourceBatch->getSqFtAverageFound() ?? 0.0);
                $newBatch->setSelectionNote($firstSourceBatch->getSelectionNote());
                $newBatch->setBatchNote($firstSourceBatch->getBatchNote());
            } else {
                $newBatch->setSqFtAverageExpected(0.0);
                $newBatch->setSqFtAverageFound(0.0);
            }

            $now = new \DateTimeImmutable();
            $newBatch->setCreatedAt($now);
            $newBatch->setUpdatedAt($now);

            $errors = $validator->validate($newBatch);
            if (count($errors) > 0) {
                $errors = $this->validatorOutputFormatter->formatOutput($errors);
                return new JsonResponse($this->doResponse->doErrorResponse($errors));
            }

            $this->doctrine->persist($newBatch);

            if ($orderRow) {
                $batchOrder = new BatchOrder();
                $batchOrder->setBatch($newBatch);
                $batchOrder->setOrderRow($orderRow);
                $this->doctrine->persist($batchOrder);
            }

            // Movimento in entrata nel nuovo lotto TF
        $inReason = $reasonRepo->createQueryBuilder('r')
            ->join('r.reason_type', 't')
            ->where('r.name = :name')
            ->andWhere('t.movement_type = :type')
            ->setParameter('name', 'Carico da produzione')
            ->setParameter('type', '+')
            ->getQuery()
            ->getOneOrNullResult()
            ?? $reasonRepo->findOneBy(['name' => 'Carico Lavorazione'])
            ?? $reasonRepo->findOneBy(['name' => 'Acquisto']);

            if ($inReason) {
                $inMovement = new WarehouseMovement();
                $inMovement->setBatch($newBatch);
                $inMovement->setReason($inReason);
                $inMovement->setPiece($totalPieces);
                $inMovement->setQuantity($totalQuantity);
                $inMovement->setDate(new \DateTime());
                $inMovement->setMovementNote('Entrata lotto TF da composizione lotti');
                $this->doctrine->persist($inMovement);
            }

            $this->doctrine->flush();

            $result = $this->groupSerializer->serializeGroup($newBatch, 'batch_detail');
            return new JsonResponse($this->doResponse->doResponse($result));

        } catch (\Exception $e) {
            return new JsonResponse($this->doResponse->doErrorResponse($e->getMessage()));
        }
    }

    #[Route('/batch/rework/{batchCode}',
        name: 'rework_batch',
        requirements: ['batchCode' => '.+'],
        methods: ['POST'])]
    public function reworkBatch(string $batchCode, Request $request): JsonResponse
    {
        $data = $request->request->all();
        $piecesToRework = isset($data['pieces']) ? (int)$data['pieces'] : null;

        if ($piecesToRework === null || $piecesToRework <= 0) {
            return new JsonResponse($this->doResponse->doErrorResponse('Numero di pelli non valido'), 400);
        }

        $batchRepository = $this->doctrine->getRepository(Batch::class);
        $fatherBatch = $batchRepository->findOneBy(['batch_code' => $batchCode]);

        if (!$fatherBatch) {
            return new JsonResponse($this->doResponse->doErrorResponse('Batch not found', 404), 404);
        }

        if (!$fatherBatch->getBatchType() || $fatherBatch->getBatchType()->getName() !== 'Partita') {
            return new JsonResponse($this->doResponse->doErrorResponse('Solo i lotti di tipo Partita possono essere rinverditi'), 400);
        }

        $existingRework = $batchRepository->findOneBy(['batch_code' => 'R' . $batchCode]);
        if ($existingRework) {
            return new JsonResponse($this->doResponse->doErrorResponse('Questo lotto è già stato rinverdito (Lotto ' . $existingRework->getBatchCode() . ')'), 400);
        }

        $fatherBatchCode = $fatherBatch->getBatchCode();
        if (str_starts_with($fatherBatchCode, 'SF') || str_starts_with($fatherBatchCode, 'SC')) {
            return new JsonResponse($this->doResponse->doErrorResponse('Un lotto spaccato (SF/SC) non può essere rinverdito'));
        }

        $availablePieces = (float)($fatherBatch->getStockItems() ?? 0);
        $availableQuantity = (float)($fatherBatch->getStockQuantity() ?? 0);

        $newQuantity = ($fatherBatch->getQuantity() / $fatherBatch->getPieces()) * $piecesToRework;

        $newType = $this->doctrine->getRepository(BatchType::class)->findOneBy(['name' => 'Rinverdimento']);

        $newBatch = new Batch();
        $newBatch->setBatchType($newType);
        $newBatch->setBatchCode('R' . $fatherBatch->getBatchCode());
        $newBatch->setBatchDate(new \DateTime());
        $newBatch->setPieces($piecesToRework);
        $newBatch->setMeasurementUnit($fatherBatch->getMeasurementUnit());
        $newBatch->setQuantity($newQuantity);
        $newBatch->setStockItems((float)$piecesToRework);
        $newBatch->setStockQuantity($newQuantity);
        $newBatch->setLeather($fatherBatch->getLeather());
        $newBatch->setSampling($fatherBatch->isSampling() ?? false);
        $newBatch->setSplitSelected($fatherBatch->isSplitSelected() ?? false);
        $newBatch->setCompleted(false);
        $newBatch->setChecked(false);
        $newBatch->setSqFtAverageExpected($fatherBatch->getSqFtAverageExpected() ?? 0.0);
        $newBatch->setSqFtAverageFound($fatherBatch->getSqFtAverageFound() ?? 0.0);
        $newBatch->setSelectionNote($fatherBatch->getSelectionNote());
        $newBatch->setBatchNote($fatherBatch->getBatchNote());

        $fatherBatch->setStockItems($availablePieces - $piecesToRework);
        $fatherBatch->setStockQuantity($availableQuantity - $newQuantity);

        $now = new \DateTimeImmutable();
        $newBatch->setCreatedAt($now);
        $newBatch->setUpdatedAt($now);

        $this->doctrine->persist($newBatch);

        $batchComposition = new BatchComposition();
        $batchComposition->setBatch($newBatch);
        $batchComposition->setFatherBatch($fatherBatch);
        $batchComposition->setFatherBatchPiece($piecesToRework);
        $batchComposition->setFatherBatchQuantity($newQuantity);
        $batchComposition->setCompositionNote('Riverdimento da lotto ' . $fatherBatch->getBatchCode());

        $this->doctrine->persist($batchComposition);

        $reasonRepo = $this->doctrine->getRepository(WarehouseMovementReason::class);

        $outReason = $reasonRepo->createQueryBuilder('r')
            ->join('r.reason_type', 't')
            ->where('r.name = :name')
            ->andWhere('t.movement_type = :type')
            ->setParameter('name', 'Scarico per lavorazione esterna')
            ->setParameter('type', '-')
            ->getQuery()
            ->getOneOrNullResult()
            ?? $reasonRepo->findOneBy(['name' => 'Lavorazione Esterna (Uscita)'])
            ?? $reasonRepo->findOneBy(['name' => 'Scarico Lavorazione']);

        if ($outReason) {
            $outMovement = new WarehouseMovement();
            $outMovement->setBatch($fatherBatch);
            $outMovement->setReason($outReason);
            $outMovement->setQuantity($newQuantity);
            $outMovement->setPiece($piecesToRework);
            $outMovement->setDate(new \DateTime());
            $outMovement->setMovementNote('Uscita per riverdimento (Lotto R' . $fatherBatch->getBatchCode() . ')');
            $this->doctrine->persist($outMovement);
        }

        $inReason = $reasonRepo->createQueryBuilder('r')
            ->join('r.reason_type', 't')
            ->where('r.name = :name')
            ->andWhere('t.movement_type = :type')
            ->setParameter('name', 'Carico da lavorazione esterna')
            ->setParameter('type', '+')
            ->getQuery()
            ->getOneOrNullResult()
            ?? $reasonRepo->findOneBy(['name' => 'Lavorazione Esterna (Entrata)'])
            ?? $reasonRepo->findOneBy(['name' => 'Carico Lavorazione']);

        if ($inReason) {
            $inMovement = new WarehouseMovement();
            $inMovement->setBatch($newBatch);
            $inMovement->setReason($inReason);
            $inMovement->setQuantity($newQuantity);
            $inMovement->setPiece($piecesToRework);
            $inMovement->setDate(new \DateTime());
            $inMovement->setMovementNote('Entrata da riverdimento');
            $this->doctrine->persist($inMovement);
        }

        $outReasonR = $reasonRepo->createQueryBuilder('r')
            ->join('r.reason_type', 't')
            ->where('r.name = :name')
            ->andWhere('t.movement_type = :type')
            ->setParameter('name', 'Scarico')
            ->setParameter('type', '-')
            ->getQuery()
            ->getOneOrNullResult();

        if ($outReasonR) {
            $outMov = new WarehouseMovement();
            $outMov->setBatch($newBatch);
            $outMov->setReason($outReasonR);
            $outMov->setQuantity($newQuantity);
            $outMov->setPiece($piecesToRework);
            $outMov->setDate(new \DateTime());
            $outMov->setMovementNote('Scarico per lavorazione interna (Riverdimento)');
            $this->doctrine->persist($outMov);
        }

        $this->doctrine->flush();

        $result = $this->groupSerializer->serializeGroup($newBatch, 'batch_detail');
        return new JsonResponse($this->doResponse->doResponse($result));
    }

    #[Route('/batch/split/{batchCode}',
        name: 'split_reworked_batch',
        requirements: ['batchCode' => '.+'],
        methods: ['POST'])]
    public function splitReworkedBatch(string $batchCode, Request $request): JsonResponse
    {
        // Accetto JSON o form-data
        $data = json_decode($request->getContent(), true);
        if (!is_array($data) || empty($data)) {
            $data = $request->request->all();
        }

        $pieces = isset($data['pieces']) ? (float)$data['pieces'] : null;
        if ($pieces === null || $pieces <= 0) {
            return new JsonResponse($this->doResponse->doErrorResponse('Numero di pezzi non valido'), 400);
        }

        $batchRepository = $this->doctrine->getRepository(Batch::class);
        $reworkedBatch = $batchRepository->findOneBy(['batch_code' => $batchCode]);
        if (!$reworkedBatch) {
            return new JsonResponse($this->doResponse->doErrorResponse('Lotto non trovato', 404), 404);
        }

        $baseCode = (strlen($batchCode) > 1 && $batchCode[0] === 'R') ? substr($batchCode, 1) : $batchCode;
        $existingSF = $batchRepository->findOneBy(['batch_code' => 'SF' . $baseCode]);
        $existingSC = $batchRepository->findOneBy(['batch_code' => 'SC' . $baseCode]);

        if ($existingSF || $existingSC) {
            $alreadyCreated = $existingSF ? $existingSF->getBatchCode() : $existingSC->getBatchCode();
            return new JsonResponse($this->doResponse->doErrorResponse('Questo lotto è già stato spaccato (Lotto ' . $alreadyCreated . ')'), 400);
        }

        $availablePieces = (float)($reworkedBatch->getStockItems() ?? 0);
        $availableQuantity = (float)($reworkedBatch->getStockQuantity() ?? 0);

        $calculatedQuantity = ($reworkedBatch->getQuantity() / $reworkedBatch->getPieces()) * $pieces ;

        if ($pieces > $availablePieces) {
            return new JsonResponse($this->doResponse->doErrorResponse('Numero di pezzi superiore alla disponibilità (' . $availablePieces . ')'), 400);
        }

        $reworkedBatch->setStockItems($availablePieces - $pieces);
        $reworkedBatch->setStockQuantity($availableQuantity - $calculatedQuantity);

        $newType = $this->doctrine->getRepository(BatchType::class)->findOneBy(['name' => 'Spaccato']);

        $sfBatch = new Batch();
        $sfBatch->setBatchType($newType);
        $sfBatch->setBatchCode('SF' . $baseCode);
        $sfBatch->setBatchDate(new \DateTime());
        $sfBatch->setPieces((int)$pieces);
        $sfBatch->setMeasurementUnit($reworkedBatch->getMeasurementUnit());
        $sfBatch->setQuantity($calculatedQuantity);
        $sfBatch->setStockItems($pieces);
        $sfBatch->setStockQuantity($calculatedQuantity);
        $sfBatch->setLeather($reworkedBatch->getLeather());
        $sfBatch->setSampling($reworkedBatch->isSampling() ?? false);
        $sfBatch->setSplitSelected($reworkedBatch->isSplitSelected() ?? false);
        $sfBatch->setCompleted(false);
        $sfBatch->setChecked(false);
        $sfBatch->setSqFtAverageExpected($reworkedBatch->getSqFtAverageExpected() ?? 0.0);
        $sfBatch->setSqFtAverageFound($reworkedBatch->getSqFtAverageFound() ?? 0.0);
        $sfBatch->setSelectionNote($reworkedBatch->getSelectionNote());
        $sfBatch->setBatchNote($reworkedBatch->getBatchNote());
        $now = new \DateTimeImmutable();
        $sfBatch->setCreatedAt($now);
        $sfBatch->setUpdatedAt($now);
        $this->doctrine->persist($sfBatch);

        // Crea lotto SC
        $scBatch = new Batch();
        $scBatch->setBatchType($newType);
        $scBatch->setBatchCode('SC' . $baseCode);
        $scBatch->setBatchDate(new \DateTime());
        $scBatch->setPieces((int)$pieces);
        $scBatch->setMeasurementUnit($reworkedBatch->getMeasurementUnit());
        $scBatch->setQuantity($calculatedQuantity);
        $scBatch->setStockItems($pieces);
        $scBatch->setStockQuantity($calculatedQuantity);
        $scBatch->setLeather($reworkedBatch->getLeather());
        $scBatch->setSampling($reworkedBatch->isSampling() ?? false);
        $scBatch->setSplitSelected($reworkedBatch->isSplitSelected() ?? false);
        $scBatch->setCompleted(false);
        $scBatch->setChecked(false);
        $scBatch->setSqFtAverageExpected($reworkedBatch->getSqFtAverageExpected() ?? 0.0);
        $scBatch->setSqFtAverageFound($reworkedBatch->getSqFtAverageFound() ?? 0.0);
        $scBatch->setSelectionNote($reworkedBatch->getSelectionNote());
        $scBatch->setBatchNote($reworkedBatch->getBatchNote());
        $scBatchNow = new \DateTimeImmutable();
        $scBatch->setCreatedAt($scBatchNow);
        $scBatch->setUpdatedAt($scBatchNow);
        $this->doctrine->persist($scBatch);

        $sfComp = new BatchComposition();
        $sfComp->setBatch($sfBatch);
        $sfComp->setFatherBatch($reworkedBatch);
        $sfComp->setFatherBatchPiece((int)$pieces);
        $sfComp->setFatherBatchQuantity($calculatedQuantity);
        $sfComp->setCompositionNote('Spaccatura lotto ' . $batchCode);
        $this->doctrine->persist($sfComp);

        $scComp = new BatchComposition();
        $scComp->setBatch($scBatch);
        $scComp->setFatherBatch($reworkedBatch);
        $scComp->setFatherBatchPiece((int)$pieces);
        $scComp->setFatherBatchQuantity($calculatedQuantity);
        $scComp->setCompositionNote('Spaccatura lotto ' . $batchCode);
        $this->doctrine->persist($scComp);

        $reasonRepo = $this->doctrine->getRepository(WarehouseMovementReason::class);
        $inReason = $reasonRepo->createQueryBuilder('r')
            ->join('r.reason_type', 't')
            ->where('r.name = :name')
            ->andWhere('t.movement_type = :type')
            ->setParameter('name', 'Carico')
            ->setParameter('type', '+')
            ->getQuery()
            ->getOneOrNullResult();
        if (!$inReason) {
            return new JsonResponse($this->doResponse->doErrorResponse('Causale "Carico" non trovata'), 400);
        }

        $note = 'Spaccatura lotto ' . $batchCode;

        $outReason = $reasonRepo->createQueryBuilder('r')
            ->join('r.reason_type', 't')
            ->where('r.name = :name')
            ->andWhere('t.movement_type = :type')
            ->setParameter('name', 'Scarico')
            ->setParameter('type', '-')
            ->getQuery()
            ->getOneOrNullResult();

        if ($outReason) {
            $outMov = new WarehouseMovement();
            $outMov->setBatch($reworkedBatch);
            $outMov->setReason($outReason);
            $outMov->setQuantity($calculatedQuantity);
            $outMov->setPiece((int)$pieces);
            $outMov->setDate(new \DateTime());
            $outMov->setMovementNote($note);
            $this->doctrine->persist($outMov);
        }

        $sfMov = new WarehouseMovement();
        $sfMov->setBatch($sfBatch);
        $sfMov->setReason($inReason);
        $sfMov->setQuantity($calculatedQuantity);
        $sfMov->setPiece((int)$pieces);
        $sfMov->setDate(new \DateTime());
        $sfMov->setMovementNote($note);
        $this->doctrine->persist($sfMov);

        $scMov = new WarehouseMovement();
        $scMov->setBatch($scBatch);
        $scMov->setReason($inReason);
        $scMov->setQuantity($calculatedQuantity);
        $scMov->setPiece((int)$pieces);
        $scMov->setDate(new \DateTime());
        $scMov->setMovementNote($note);
        $this->doctrine->persist($scMov);

        $this->doctrine->flush();

        $results = $this->groupSerializer->serializeGroup([$sfBatch, $scBatch], 'batch_list');
        return new JsonResponse($this->doResponse->doResponse($results));
    }

    #[Route('/batch',
        name: 'post_batch',
        methods: ['POST'])]
    public function postBatch(
        Request            $request,
        ValidatorInterface $validator,
    ): JsonResponse
    {
        $data = $request->request->all();
        $batch = new Batch();

        try {
            $batch = $this->handleRelations($batch, $data);

            if ($batch->getBatchType() && $batch->getBatchType()->getName() === 'Partita') {
                $lastBatch = $this->doctrine->getRepository(Batch::class)->findOneBy(
                    ['batch_type' => $batch->getBatchType()],
                    ['id' => 'DESC']
                );

                $lastCode = $lastBatch ? $lastBatch->getBatchCode() : null;

                $yearPrefix = $batch->getBatchType()->getPrefix() ?? (new \DateTimeImmutable())->format('y');
                $nextCode = $this->nextSequentialCode($lastCode, $yearPrefix, 4);
                $batch->setBatchCode($nextCode);
            }
            if ($batch->getSqFtAverageExpected() === null) {
                $batch->setSqFtAverageExpected((float) 0);
            }

            if($batch->getMeasurementUnit()){
                $measurementUnit = $batch->getMeasurementUnit();

                if ($measurementUnit->getPrefix() == 'MQ') {
                    $coefficientUm = $measurementUnit->getMeasurementUnitCoefficients()->first();

                    if(!isset($data['pieces']) || $data['pieces'] == 0) {
                        return new JsonResponse(['error' => 'Pieces is required'], 400);
                    }
                    if(!isset($data['quantity']) || $data['quantity'] == 0) {
                        return new JsonResponse(['error' => 'Quantity is required'], 400);
                    }

                    $batch->setSqFtAverageFound($data['pieces'] / ($coefficientUm->getCoefficient() * $data['quantity']));
                } elseif($batch->getMeasurementUnit()->getPrefix() == 'PQ') {
                    $batch->setSqFtAverageFound($data['pieces'] / $data['quantity']);
                }
            } else {
                return new JsonResponse(['error' => 'Measurement unit not found'], 400);
            }
            
            if ($batch->isCompleted() === null) {
                $batch->setCompleted(false);
            }

            if ($batch->isChecked() === null) {
                $batch->setChecked(false);
            }

            if ($batch->isSampling() === null) {
                $batch->setSampling(false);
            }

            if ($batch->getPieces() === null) {
                $batch->setPieces(0);
            }

            if ($batch->getQuantity() === null) {
                $batch->setQuantity((float) 0);
            }

            $batch->setSplitSelected(false);

            $batch = $this->createMethodsByInput->createMethods($batch, $data);

            if ($batch->getStockItems() === null || $batch->getStockItems() == 0.0) {
                $batch->setStockItems((float)($batch->getPieces() ?? 0));
            }

            if ($batch->getStockQuantity() === null || $batch->getStockQuantity() == 0.0) {
                $batch->setStockQuantity((float)($batch->getQuantity() ?? 0));
            }

            $now = new \DateTimeImmutable();
            $batch->setCreatedAt($now);
            $batch->setUpdatedAt($now);

            $errors = $validator->validate($batch);
            if (count($errors) > 0) {
                $errors = $this->validatorOutputFormatter->formatOutput($errors);
                return new JsonResponse($this->doResponse->doErrorResponse($errors));
            }

            $this->doctrine->persist($batch);
            $this->doctrine->flush();

            $result = $this->groupSerializer->serializeGroup($batch, 'batch_detail');
            return new JsonResponse($this->doResponse->doResponse($result));

        } catch (\Exception $e) {
            return new JsonResponse($this->doResponse->doErrorResponse($e->getMessage()));
        }
    }

    #[Route('/batch/{id}',
        name: 'put_batch',
        methods: ['PUT'])]
    public function modifyBatch(
        Request            $request,
        ValidatorInterface $validator,
        int                $id,
    ): JsonResponse
    {
        $data = $request->toArray();
        $batch = $this->doctrine->getRepository(Batch::class)->find($id);

        if (!$batch) {
            return new JsonResponse($this->doResponse->doErrorResponse('Batch not found', 404));
        }

        try {
            $batch = $this->handleRelations($batch, $data);
            $batch = $this->createMethodsByInput->createMethods($batch, $data);

            if ($batch->getStockItems() === null || $batch->getStockItems() == 0.0) {
                $batch->setStockItems((float)($batch->getPieces() ?? 0));
            }

            if ($batch->getStockQuantity() === null || $batch->getStockQuantity() == 0.0) {
                $batch->setStockQuantity((float)($batch->getQuantity() ?? 0));
            }

            if ($batch->isCompleted() === null) {
                $batch->setCompleted(false);
            }

            if ($batch->isChecked() === null) {
                $batch->setChecked(false);
            }

            if ($batch->isSampling() === null) {
                $batch->setSampling(false);
            }

            if ($batch->getPieces() === null) {
                $batch->setPieces(0);
            }

            if ($batch->getQuantity() === null) {
                $batch->setQuantity((float) 0);
            }

            if ($batch->getSqFtAverageExpected() === null) {
                $batch->setSqFtAverageExpected((float) 0);
            }

            if ($batch->getSqFtAverageFound() === null) {
                $batch->setSqFtAverageFound((float) 0);
            }

            $batch->setUpdatedAt(new \DateTimeImmutable());

            $errors = $validator->validate($batch);
            if (count($errors) > 0) {
                $errors = $this->validatorOutputFormatter->formatOutput($errors);
                return new JsonResponse($this->doResponse->doErrorResponse($errors));
            }

            $this->doctrine->persist($batch);
            $this->doctrine->flush();

            $result = $this->groupSerializer->serializeGroup($batch, 'batch_detail');
            return new JsonResponse($this->doResponse->doResponse($result));
        } catch (\Exception $e) {
            return new JsonResponse($this->doResponse->doErrorResponse($e->getMessage()));
        }
    }

    #[Route('/batch/{id}',
        name: 'delete_batch',
        methods: ['DELETE'])]
    public function deleteBatch(int $id): JsonResponse
    {
        $batch = $this->doctrine->getRepository(Batch::class)->find($id);
        if (!$batch) {
            return new JsonResponse($this->doResponse->doErrorResponse('Batch not found', 404));
        }

        $this->doctrine->remove($batch);
        $this->doctrine->flush();

        return new JsonResponse($this->doResponse->doResponse('delete_successfully'));
    }

    private function handleRelations(Batch $batch, array &$data): Batch
    {
        if (isset($data['batch_type_id'])) {
            $batchType = $this->doctrine->getRepository(BatchType::class)->find($data['batch_type_id']);
            if ($batchType) {
                $batch->setBatchType($batchType);
            }
            unset($data['batch_type_id']);
        }

        if (isset($data['measurement_unit_id'])) {
            $unit = $this->doctrine->getRepository(MeasurementUnit::class)->find($data['measurement_unit_id']);
            if ($unit) {
                $batch->setMeasurementUnit($unit);
            }
            unset($data['measurement_unit_id']);
        }

        if (isset($data['check_user_id'])) {
            $user = $this->doctrine->getRepository(User::class)->find($data['check_user_id']);
            if ($user) {
                $batch->setCheckUser($user);
            }
            unset($data['check_user_id']);
        }

        if (isset($data['leather_id'])) {
            $leather = $this->doctrine->getRepository(Leather::class)->find($data['leather_id']);
            if ($leather) {
                $batch->setLeather($leather);
            }
            unset($data['leather_id']);
        }

        if (isset($data['batch_compositions'])) {
            foreach ($data['batch_compositions'] as $compositionData) {
                if (isset($compositionData['father_batch_id'])) {
                    $fatherBatch = $this->doctrine->getRepository(Batch::class)->find($compositionData['father_batch_id']);
                    if ($fatherBatch) {
                        $composition = new BatchComposition();
                        $composition->setBatch($batch);
                        $composition->setFatherBatch($fatherBatch);
                        $composition = $this->createMethodsByInput->createMethods($composition, $compositionData);
                        $batch->addBatchComposition($composition);
                        $this->doctrine->persist($composition);
                    }
                }
            }
            unset($data['batch_compositions']);
        }

        if (isset($data['batch_selections'])) {
            foreach ($data['batch_selections'] as $selectionData) {
                if (isset($selectionData['selection_id'])) {
                    $selection = $this->doctrine->getRepository(Selection::class)->find($selectionData['selection_id']);
                    if ($selection) {
                        $batchSelection = new BatchSelection();
                        $batchSelection->setBatch($batch);
                        $batchSelection->setSelection($selection);
                        $batchSelection = $this->createMethodsByInput->createMethods($batchSelection, $selectionData);
                        $batch->addBatchSelection($batchSelection);
                        $this->doctrine->persist($batchSelection);
                    }
                }
            }
            unset($data['batch_selections']);
        }

        if (isset($data['batch_orders'])) {
            foreach ($data['batch_orders'] as $orderData) {
                if (isset($orderData['order_row_id'])) {
                    $orderRow = $this->doctrine->getRepository(ClientOrderRow::class)->find($orderData['order_row_id']);
                    if ($orderRow) {
                        $batchOrder = new BatchOrder();
                        $batchOrder->setBatch($batch);
                        $batchOrder->setOrderRow($orderRow);
                        $batchOrder = $this->createMethodsByInput->createMethods($batchOrder, $orderData);
                        $batch->addBatchOrder($batchOrder);
                        $this->doctrine->persist($batchOrder);
                    }
                }
            }
            unset($data['batch_orders']);
        }

        return $batch;
    }

    private function nextSequentialCode(?string $lastCode, string $prefix, int $pad): string
    {
        $lastCode = $lastCode ? trim($lastCode) : '';

        if ($lastCode === '' || !preg_match('/^' . preg_quote($prefix, '/') . '(\d+)$/', $lastCode, $m)) {
            return $prefix . str_pad('1', $pad, '0', STR_PAD_LEFT);
        }

        $n = (int) $m[1] + 1;

        return $prefix . str_pad((string) $n, $pad, '0', STR_PAD_LEFT);
    }
}
