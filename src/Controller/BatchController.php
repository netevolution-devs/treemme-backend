<?php

namespace App\Controller;

use App\Entity\Batch;
use App\Entity\BatchComposition;
use App\Entity\BatchType;
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

    #[Route('/batch/rework/{id}',
        name: 'rework_batch',
        requirements: ['id' => '\d+'],
        methods: ['POST'])]
    public function reworkBatch(int $id, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $piecesToRework = isset($data['pieces']) ? (int)$data['pieces'] : null;

        if ($piecesToRework === null || $piecesToRework <= 0) {
            return new JsonResponse($this->doResponse->doErrorResponse('Numero di pelli non valido', 400));
        }

        $batchRepository = $this->doctrine->getRepository(Batch::class);
        $fatherBatch = $batchRepository->find($id);

        if (!$fatherBatch) {
            return new JsonResponse($this->doResponse->doErrorResponse('Batch not found', 404));
        }

        // Calcolo pelli disponibili
        $totalInputPieces = 0;
        $totalOutputPieces = 0;

        foreach ($fatherBatch->getWarehouseMovements() as $movement) {
            $reason = $movement->getReason();
            if ($reason && $reason->getReasonType()) {
                $type = $reason->getReasonType()->getMovementType();
                if ($type === 'IN') {
                    $totalInputPieces += $movement->getPiece() ?? 0;
                } elseif ($type === 'OUT') {
                    $totalOutputPieces += $movement->getPiece() ?? 0;
                }
            }
        }

        $availablePieces = $totalInputPieces - $totalOutputPieces;

        if ($piecesToRework > $availablePieces) {
            return new JsonResponse($this->doResponse->doErrorResponse('Numero di pelli superiore a quelle disponibili (' . $availablePieces . ')', 400));
        }

        // Calcolo quantità proporzionale
        $quantityToRework = 0.0;
        if ($fatherBatch->getPieces() > 0) {
            $quantityToRework = ($fatherBatch->getQuantity() / $fatherBatch->getPieces()) * $piecesToRework;
        }

        $newBatch = new Batch();
        $newBatch->setBatchType($fatherBatch->getBatchType());
        $newBatch->setBatchCode('R' . $fatherBatch->getBatchCode());
        $newBatch->setBatchDate(new \DateTime());
        $newBatch->setPieces($piecesToRework);
        $newBatch->setMeasurementUnit($fatherBatch->getMeasurementUnit());
        $newBatch->setQuantity($quantityToRework);
        $newBatch->setStockItems($quantityToRework);
        $newBatch->setStorage($quantityToRework);
        $newBatch->setLeather($fatherBatch->getLeather());
        $newBatch->setSampling($fatherBatch->isSampling() ?? false);
        $newBatch->setSplitSelected($fatherBatch->isSplitSelected() ?? false);
        $newBatch->setCompleted(false);
        $newBatch->setChecked(false);

        $now = new \DateTimeImmutable();
        $newBatch->setCreatedAt($now);
        $newBatch->setUpdatedAt($now);

        $this->doctrine->persist($newBatch);

        // Batch Composition
        $batchComposition = new BatchComposition();
        $batchComposition->setBatch($newBatch);
        $batchComposition->setFatherBatch($fatherBatch);
        $batchComposition->setFatherBatchPiece($piecesToRework);
        $batchComposition->setFatherBatchQuantity($quantityToRework);
        $batchComposition->setCompositionNote('Riverdimento da lotto ' . $fatherBatch->getBatchCode());

        $this->doctrine->persist($batchComposition);

        // Warehouse Movements
        $reasonRepo = $this->doctrine->getRepository(WarehouseMovementReason::class);

        // Scarico lotto padre
        $outReason = $reasonRepo->findOneBy(['name' => 'Scarico per lavorazione esterna'])
            ?? $reasonRepo->findOneBy(['name' => 'Lavorazione Esterna (Uscita)'])
            ?? $reasonRepo->findOneBy(['name' => 'Scarico Lavorazione']);

        if ($outReason) {
            $outMovement = new WarehouseMovement();
            $outMovement->setBatch($fatherBatch);
            $outMovement->setReason($outReason);
            $outMovement->setQuantity($quantityToRework);
            $outMovement->setPiece($piecesToRework);
            $outMovement->setDate(new \DateTime());
            $outMovement->setMovementNote('Uscita per riverdimento (Lotto R' . $fatherBatch->getBatchCode() . ')');
            $this->doctrine->persist($outMovement);
        }

        // Carico nuovo lotto
        $inReason = $reasonRepo->findOneBy(['name' => 'Carico da lavorazione esterna'])
            ?? $reasonRepo->findOneBy(['name' => 'Lavorazione Esterna (Entrata)'])
            ?? $reasonRepo->findOneBy(['name' => 'Carico Lavorazione']);

        if ($inReason) {
            $inMovement = new WarehouseMovement();
            $inMovement->setBatch($newBatch);
            $inMovement->setReason($inReason);
            $inMovement->setQuantity($quantityToRework);
            $inMovement->setPiece($piecesToRework);
            $inMovement->setDate(new \DateTime());
            $inMovement->setMovementNote('Entrata da riverdimento');
            $this->doctrine->persist($inMovement);
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

        $quantity = isset($data['quantity']) ? (float)$data['quantity'] : null;
        if ($quantity === null || $quantity <= 0) {
            return new JsonResponse($this->doResponse->doErrorResponse('Quantità non valida', 400));
        }

        if (!(strlen($batchCode) > 1 && $batchCode[0] === 'R')) {
            return new JsonResponse($this->doResponse->doErrorResponse('Il codice lotto deve iniziare con R', 400));
        }

        $batchRepository = $this->doctrine->getRepository(Batch::class);
        /** @var Batch|null $reworkedBatch */
        $reworkedBatch = $batchRepository->findOneBy(['batchCode' => $batchCode]);
        if (!$reworkedBatch) {
            return new JsonResponse($this->doResponse->doErrorResponse('Lotto R non trovato', 404));
        }

        // Controllo: la quantità richiesta non deve superare il numero di pelli del lotto R
        $availablePieces = (int)($reworkedBatch->getPieces() ?? 0);
        if ($quantity > $availablePieces) {
            return new JsonResponse($this->doResponse->doErrorResponse('Quantità superiore al numero di pelli disponibili (' . $availablePieces . ')', 400));
        }

        $baseCode = substr($batchCode, 1); // rimuove il prefisso R

        // Crea lotto SF
        $sfBatch = new Batch();
        $sfBatch->setBatchType($reworkedBatch->getBatchType());
        $sfBatch->setBatchCode('SF' . $baseCode);
        $sfBatch->setBatchDate(new \DateTime());
        $sfBatch->setPieces((int)$quantity);
        $sfBatch->setMeasurementUnit($reworkedBatch->getMeasurementUnit());
        $sfBatch->setQuantity($quantity);
        $sfBatch->setStockItems($quantity);
        $sfBatch->setStorage($quantity);
        $sfBatch->setLeather($reworkedBatch->getLeather());
        $sfBatch->setSampling($reworkedBatch->isSampling() ?? false);
        $sfBatch->setSplitSelected($reworkedBatch->isSplitSelected() ?? false);
        $sfBatch->setCompleted(false);
        $sfBatch->setChecked(false);
        $now = new \DateTimeImmutable();
        $sfBatch->setCreatedAt($now);
        $sfBatch->setUpdatedAt($now);
        $this->doctrine->persist($sfBatch);

        // Crea lotto SC
        $scBatch = new Batch();
        $scBatch->setBatchType($reworkedBatch->getBatchType());
        $scBatch->setBatchCode('SC' . $baseCode);
        $scBatch->setBatchDate(new \DateTime());
        $scBatch->setPieces((int)$quantity);
        $scBatch->setMeasurementUnit($reworkedBatch->getMeasurementUnit());
        $scBatch->setQuantity($quantity);
        $scBatch->setStockItems($quantity);
        $scBatch->setStorage($quantity);
        $scBatch->setLeather($reworkedBatch->getLeather());
        $scBatch->setSampling($reworkedBatch->isSampling() ?? false);
        $scBatch->setSplitSelected($reworkedBatch->isSplitSelected() ?? false);
        $scBatch->setCompleted(false);
        $scBatch->setChecked(false);
        $scBatchNow = new \DateTimeImmutable();
        $scBatch->setCreatedAt($scBatchNow);
        $scBatch->setUpdatedAt($scBatchNow);
        $this->doctrine->persist($scBatch);

        // Composizioni (padre = lotto R)
        $sfComp = new BatchComposition();
        $sfComp->setBatch($sfBatch);
        $sfComp->setFatherBatch($reworkedBatch);
        $sfComp->setFatherBatchPiece((int)$quantity);
        $sfComp->setFatherBatchQuantity($quantity);
        $sfComp->setCompositionNote('Spaccatura lotto ' . $batchCode);
        $this->doctrine->persist($sfComp);

        $scComp = new BatchComposition();
        $scComp->setBatch($scBatch);
        $scComp->setFatherBatch($reworkedBatch);
        $scComp->setFatherBatchPiece((int)$quantity);
        $scComp->setFatherBatchQuantity($quantity);
        $scComp->setCompositionNote('Spaccatura lotto ' . $batchCode);
        $this->doctrine->persist($scComp);

        // Movimenti: causale Carico
        $reasonRepo = $this->doctrine->getRepository(WarehouseMovementReason::class);
        $inReason = $reasonRepo->findOneBy(['name' => 'Carico']);
        if (!$inReason) {
            return new JsonResponse($this->doResponse->doErrorResponse('Causale "Carico" non trovata', 400));
        }

        $note = 'Spaccatura lotto ' . $batchCode;

        $sfMov = new WarehouseMovement();
        $sfMov->setBatch($sfBatch);
        $sfMov->setReason($inReason);
        $sfMov->setQuantity($quantity);
        $sfMov->setPiece((int)$quantity);
        $sfMov->setDate(new \DateTime());
        $sfMov->setMovementNote($note);
        $this->doctrine->persist($sfMov);

        $scMov = new WarehouseMovement();
        $scMov->setBatch($scBatch);
        $scMov->setReason($inReason);
        $scMov->setQuantity($quantity);
        $scMov->setPiece((int)$quantity);
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
                    ['batchType' => $batch->getBatchType()],
                    ['id' => 'DESC']
                );

                $lastCode = $lastBatch ? $lastBatch->getCode() : null;


                $nextCode = $this->nextSequentialCode($lastCode, 'S', 4);
                $batch->setBatchCode($nextCode);
            }

            $batch = $this->createMethodsByInput->createMethods($batch, $data);

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
