<?php

namespace App\Controller;

use App\Entity\BatchData;
use App\Entity\LeatherType;
use App\Entity\MeasurementUnitCoefficient;
use App\Entity\Production;
use App\Entity\Machine;
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
use App\Service\PdfGeneratorService;
use App\Service\QrCodeService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class BatchController extends AbstractController
{
    private $createMethodsByInput;
    private $doctrine;
    private $doResponse;
    private $groupSerializer;
    private $validatorOutputFormatter;
    private $pdfGenerator;
    private $qrCodeService;
    private string $subcontractor_tag;

    public function __construct(
        CreateMethodsByInput     $createMethodsByInput,
        EntityManagerInterface   $entityManager,
        DoResponseService        $doResponseService,
        GroupSerializerService   $groupSerializer,
        ValidatorOutputFormatter $validatorOutputFormatter,
        PdfGeneratorService      $pdfGenerator,
        QrCodeService            $qrCodeService,
                                 $subcontractor_tag,
    )
    {
        $this->createMethodsByInput = $createMethodsByInput;
        $this->doctrine = $entityManager;
        $this->doResponse = $doResponseService;
        $this->groupSerializer = $groupSerializer;
        $this->validatorOutputFormatter = $validatorOutputFormatter;
        $this->pdfGenerator = $pdfGenerator;
        $this->qrCodeService = $qrCodeService;
        $this->subcontractor_tag = $subcontractor_tag;
    }

    #[Route('/batch/{id}',
        name: 'get_batch',
        defaults: ['id' => null],
        requirements: ['id' => '\d*'],
        methods: ['GET', 'HEAD'])]
    public function getBatch(?int $id, Request $request): JsonResponse
    {
        $batchRepository = $this->doctrine->getRepository(Batch::class);
        if ($id) {
            $batch = [$batchRepository->find($id)];
            if (!$batch[0]) {
                return $this->doResponse->doErrorJsonResponse('Batch not found', 404);
            }
        } else {
            $code = $request->query->get('code');

            if ($code) {
                $normalizedCode = str_replace('0', '', $code);
                $batch = $batchRepository->createQueryBuilder('b')
                    ->where("REPLACE(b.batch_code, '0', '') LIKE :code")
                    ->setParameter('code', '%' . $normalizedCode . '%')
                    ->orderBy('b.id', 'DESC')
                    ->getQuery()
                    ->getResult();
                if (empty($batch)) {
                    return $this->doResponse->doErrorJsonResponse('Nessun batch trovato contenente il codice ' . $code . ' (ignorando zeri)', 404);
                }
            } else if ($request->query->get('type') || $request->query->get('year')) {
                $type = $request->query->get('type');
                $year = $request->query->get('year');

                $qb = $batchRepository->createQueryBuilder('b');

                if ($type) {
                    $batchType = $this->doctrine->getRepository(BatchType::class)->find($type);
                    if ($batchType) {
                        $qb->andWhere('b.batch_type = :type')
                            ->setParameter('type', $batchType);
                    }
                }

                if ($year) {
                    $qb->andWhere('YEAR(b.batch_date) = :year')
                        ->setParameter('year', $year);
                }

                $batch = $qb->orderBy('b.batch_code', 'ASC')
                    ->getQuery()
                    ->getResult();
            } else {
                $batch = $batchRepository->findBy([], ['batch_code' => 'ASC']);
            }
        }
        $results = $this->groupSerializer->serializeGroup($batch, $id ? 'batch_detail' : 'batch_list');

        if ($id) {
            return new JsonResponse($this->doResponse->doResponse($results[0]));
        }
        return new JsonResponse($this->doResponse->doResponse($results));
    }

    #[Route('/batch/{id}/pdf',
        name: 'get_batch_pdf',
        requirements: ['id' => '\d+'],
        methods: ['GET'])]
    public function generateBatchPdf(int $id): Response
    {
        $batch = $this->doctrine->getRepository(Batch::class)->find($id);

        if (!$batch) {
            return $this->doResponse->doErrorJsonResponse('Batch not found', 404);
        }

        $pdfContent = $this->pdfGenerator->generatePdf('print/batch_pdf.html.twig', [
            'batch' => $batch
        ], 'batch_' . $batch->getBatchCode() . '.pdf');

        return new Response($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="batch_' . $batch->getBatchCode() . '.pdf"'
        ]);
    }

    #[Route('/batch/{id}/subcontractor-pdf',
        name: 'get_batch_subcontractor_pdf',
        requirements: ['id' => '\d+'],
        methods: ['GET'])]
    public function generateSubcontractorPdf(int $id, Request $request): Response
    {
        $batch = $this->doctrine->getRepository(Batch::class)->find($id);

        if (!$batch) {
            return $this->doResponse->doErrorJsonResponse('Batch not found', 404);
        }

        $full = $request->query->get('full', 0);

        $qrCode = null;
        if (!$full) {
            $qrCode = $this->qrCodeService->generateQrCode($this->subcontractor_tag . $batch->getId());
        }

        $pdfContent = $this->pdfGenerator->generatePdf('print/terzisti_pdf.html.twig', [
            'batch' => $batch,
            'qrCode' => $qrCode,
            'full' => $full
        ], 'terzisti_' . $batch->getBatchCode() . '.pdf');

        return new Response($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="terzisti_' . $batch->getBatchCode() . '.pdf"'
        ]);
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

    #[Route('/batch/split/available',
        name: 'get_available_split_batches',
        methods: ['GET', 'HEAD'])]
    public function getAvailableSplitBatches(): JsonResponse
    {
        $batchRepository = $this->doctrine->getRepository(Batch::class);
        $allAvailableBatches = $batchRepository->findAvailableStock();

        $batches = [];
        foreach ($allAvailableBatches as $batch) {
            if($batch->getBatchType()->getName() === 'Spaccato' && $batch->getLeather()->getType()->getName() === "Fiore"){
                $batches[] = $batch;
            }
        }

        $results = $this->groupSerializer->serializeGroup($batches, 'batch_list');

        return new JsonResponse($this->doResponse->doResponse($results));
    }

    #[Route('/batch/dye',
        name: 'post_batch_dye',
        methods: ['POST'])]
    public function createTfBatch(
        Request            $request,
        ValidatorInterface $validator,
    ): JsonResponse
    {
        return $this->createGenericProductionBatch($request, $validator, 'Tintura', 'TF', true);
    }

    #[Route('/batch/refinement',
        name: 'post_batch_refinement',
        methods: ['POST'])]
    public function createUfBatch(
        Request            $request,
        ValidatorInterface $validator,
    ): JsonResponse
    {
        return $this->createGenericProductionBatch($request, $validator, 'Rifinizione', 'UF', false);
    }

    private function createGenericProductionBatch(
        Request            $request,
        ValidatorInterface $validator,
        string             $batchTypeName,
        string             $prefix,
        bool               $createProduction = true
    ): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!$data) {
            $data = $request->request->all();
        }

        if (!isset($data['client_order_row_id'])) {
            return $this->doResponse->doErrorJsonResponse('ID riga ordine mancante', 400);
        }

        if (!isset($data['quantity']) || (float)$data['quantity'] <= 0) {
            return $this->doResponse->doErrorJsonResponse('Quantità non valida', 400);
        }

        try {
            $batchRepo = $this->doctrine->getRepository(Batch::class);
            $batchTypeRepo = $this->doctrine->getRepository(BatchType::class);
            $reasonRepo = $this->doctrine->getRepository(WarehouseMovementReason::class);
            $orderRowRepo = $this->doctrine->getRepository(ClientOrderRow::class);

            $orderRow = $orderRowRepo->find($data['client_order_row_id']);
            if (!$orderRow) {
                throw new \Exception('Riga ordine ' . $data['client_order_row_id'] . ' non trovata');
            }

            $requestedQuantity = (float)$data['quantity'];
            $currentStock = (float)$orderRow->getQuantity();

            if ($currentStock < $requestedQuantity) {
                return $this->doResponse->doErrorJsonResponse('Giacenza insufficiente sulla riga ordine. Disponibile: ' . $currentStock, 400);
            }

            $article = $orderRow->getArticle();
            if (!$article) {
                throw new \Exception('Articolo non associato alla riga ordine');
            }

            $batchType = $batchTypeRepo->findOneBy(['name' => $batchTypeName]);

            if (!$batchType) {
                $batchType = new BatchType();
                $batchType->setName($batchTypeName);
                $batchType->setPrefix($prefix[0]);
                $batchType->setSaleProcess(false);
                $batchType->setCreatedAt(new \DateTimeImmutable());
                $batchType->setUpdatedAt(new \DateTimeImmutable());
                $this->doctrine->persist($batchType);
            }

            $lastBatch = $batchRepo->findLatestBatchByPrefix($prefix);
            $lastCode = $lastBatch ? $lastBatch->getBatchCode() : null;
            $nextCode = $this->nextSequentialCode($lastCode, $prefix, 6);

            $newBatch = new Batch();
            $newBatch->setBatchType($batchType);
            $newBatch->setBatchCode($nextCode);
            $newBatch->setBatchDate(new \DateTime());
            $newBatch->setCompleted(false);
            $newBatch->setChecked(false);
            $newBatch->setSampling(false);
            $newBatch->setSplitSelected(false);

            $newBatch->setArticle($article);

            $product = $article->getProduct();
            $newBatch->setMeasurementUnit($orderRow->getMeasurementUnit() ?? ($product ? $product->getMeasurementUnit() : null));
            $newBatch->setPieces(0);
            $newBatch->setQuantity($requestedQuantity);
            $newBatch->setStockItems(0.0);
            $newBatch->setStockQuantity($requestedQuantity);

            $newBatch->setSqFtAverageExpected(0.0);
            $newBatch->setSqFtAverageFound(0.0);

            $now = new \DateTimeImmutable();
            $newBatch->setCreatedAt($now);
            $newBatch->setUpdatedAt($now);

            $errors = $validator->validate($newBatch);
            if (count($errors) > 0) {
                $errors = $this->validatorOutputFormatter->formatOutput($errors);
                return $this->doResponse->doErrorJsonResponse($errors);
            }

            $this->doctrine->persist($newBatch);

            if ($createProduction && isset($data['scheduled_date']) && isset($data['machine_id'])) {
                $machine = $this->doctrine->getRepository(Machine::class)->find($data['machine_id']);
                if ($machine) {
                    $production = new Production();
                    $production->setBatch($newBatch);
                    $production->setMachine($machine);
                    
                    $scheduledDate = null;
                    try {
                        $scheduledDate = new \DateTime($data['scheduled_date']);
                    } catch (\Exception $e) {
                    }

                    if ($scheduledDate) {
                        $production->setScheduledDate($scheduledDate);
                        $this->doctrine->persist($production);
                    }

                    if($orderRow->getProductionRowNote() || $orderRow->getClientOrder()->getOrderNoteProduction()){
                        $production->setProductionNote($orderRow->getProductionRowNote() ?? $orderRow->getClientOrder()->getOrderNoteProduction());
                    }
                }
            }

            $this->handleRelations($newBatch, $data);

            $alreadyExists = false;
            foreach ($newBatch->getBatchOrders() as $bo) {
                if ($bo->getOrderRow() === $orderRow) {
                    $alreadyExists = true;
                    break;
                }
            }

            if (!$alreadyExists) {
                $batchOrder = new BatchOrder();
                $batchOrder->setBatch($newBatch);
                $batchOrder->setOrderRow($orderRow);
                $this->doctrine->persist($batchOrder);
            }

            // Movimento in entrata nel nuovo lotto (TF o UF)
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
                $inMovement->setPiece(0);
                $inMovement->setQuantity($requestedQuantity);
                $inMovement->setDate(new \DateTime());
                $inMovement->setMovementNote('Entrata lotto ' . $prefix . ' da riga ordine');
                $this->doctrine->persist($inMovement);
            }

            $this->doctrine->flush();

            $result = $this->groupSerializer->serializeGroup($newBatch, 'batch_detail');
            return new JsonResponse($this->doResponse->doResponse($result));

        } catch (\Exception $e) {
            return $this->doResponse->doErrorJsonResponse($e->getMessage());
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
            return $this->doResponse->doErrorJsonResponse('Numero di pelli non valido', 400);
        }

        $batchRepository = $this->doctrine->getRepository(Batch::class);
        $fatherBatch = $batchRepository->findOneBy(['batch_code' => $batchCode]);

        if (!$fatherBatch) {
            return $this->doResponse->doErrorJsonResponse('Batch not found', 404, 404);
        }

        if (!$fatherBatch->getBatchType() || ($fatherBatch->getBatchType()->getName() !== 'Partita' && $fatherBatch->getBatchType()->getName() !== 'Lotto')) {
            return $this->doResponse->doErrorJsonResponse('Solo i lotti di tipo Partita o Lotto possono essere rinverditi', 400);
        }

        $fatherBatchCode = $fatherBatch->getBatchCode();
        if (str_starts_with($fatherBatchCode, 'SF') || str_starts_with($fatherBatchCode, 'SC')) {
            return $this->doResponse->doErrorJsonResponse('Un lotto spaccato (SF/SC) non può essere rinverdito');
        }

        $availablePieces = (float)($fatherBatch->getStockItems() ?? 0);
        $availableQuantity = (float)($fatherBatch->getStockQuantity() ?? 0);

        $newQuantity = ($fatherBatch->getQuantity() / $fatherBatch->getPieces()) * $piecesToRework;

        if ($piecesToRework > $availablePieces) {
            return $this->doResponse->doErrorJsonResponse('Numero di pezzi superiore alla disponibilità (' . $availablePieces . ')', 400);
        }

        $newBatch = $batchRepository->findOneBy(['batch_code' => 'R' . $batchCode]);
        $isNew = false;
        if (!$newBatch) {
            $newBatch = new Batch();
            $newType = $this->doctrine->getRepository(BatchType::class)->findOneBy(['name' => 'Rinverdimento']);
            $newBatch->setBatchType($newType);
            $newBatch->setBatchCode('R' . $fatherBatch->getBatchCode());
            $newBatch->setBatchDate(new \DateTime());
            $newBatch->setPieces(0);
            $newBatch->setQuantity(0);
            $newBatch->setStockItems(0.0);
            $newBatch->setStockQuantity(0.0);
            $newBatch->setLeather($fatherBatch->getLeather());
            $newBatch->setSampling($fatherBatch->isSampling() ?? false);
            $newBatch->setSplitSelected($fatherBatch->isSplitSelected() ?? false);
            $newBatch->setCompleted(false);
            $newBatch->setChecked(false);
            $newBatch->setSqFtAverageExpected($fatherBatch->getSqFtAverageExpected() ?? 0.0);
            $newBatch->setSqFtAverageFound($fatherBatch->getSqFtAverageFound() ?? 0.0);
            $newBatch->setSelectionNote($fatherBatch->getSelectionNote());
            $newBatch->setBatchNote($fatherBatch->getBatchNote());
            $newBatch->setMeasurementUnit($fatherBatch->getMeasurementUnit());
            $now = new \DateTimeImmutable();
            $newBatch->setCreatedAt($now);
            $newBatch->setUpdatedAt($now);
            $isNew = true;
        }

        $newBatch->setPieces($newBatch->getPieces() + $piecesToRework);
        $newBatch->setQuantity($newBatch->getQuantity() + $newQuantity);
        $newBatch->setStockItems($newBatch->getStockItems() + (float)$piecesToRework);
        $newBatch->setStockQuantity($newBatch->getStockQuantity() + $newQuantity);

        $fatherBatch->setStockItems($availablePieces - $piecesToRework);
        $fatherBatch->setStockQuantity($availableQuantity - $newQuantity);

        if ($isNew) {
            $this->doctrine->persist($newBatch);
        }

        $batchComposition = new BatchComposition();
        if(isset($data['date'])){
            $batchComposition->setDate(new \DateTime($data['date']) ?: new \DateTime());
        }
        $batchComposition->setBatch($newBatch);
        $batchComposition->setFatherBatch($fatherBatch);
        $batchComposition->setFatherBatchPiece($piecesToRework);
        $batchComposition->setFatherBatchQuantity($newQuantity);
        $batchComposition->setCompositionNote('Riverdimento da lotto ' . $fatherBatch->getBatchCode());

        $this->doctrine->persist($batchComposition);

        $reasonRepo = $this->doctrine->getRepository(WarehouseMovementReason::class);

        $outReason = $reasonRepo->findOneBy(['name' => 'Scarico']);

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

        $inReason = $reasonRepo->findOneBy(['name' => 'Carico']);

        if ($inReason) {
            $inMovement = new WarehouseMovement();
            $inMovement->setBatch($newBatch);
            $inMovement->setReason($inReason);
            $inMovement->setQuantity($newQuantity);
            $inMovement->setPiece($piecesToRework);
            $inMovement->setDate(new \DateTime());
            $inMovement->setMovementNote('Entrata da riverdimento del lotto ' . $fatherBatch->getBatchCode());
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

        $pieces = isset($data['pieces']) ? (float)$data['pieces'] : null;
        if ($pieces === null || $pieces <= 0) {
            return $this->doResponse->doErrorJsonResponse('Numero di pezzi non valido', 400);
        }

        $batchRepository = $this->doctrine->getRepository(Batch::class);
        $reworkedBatch = $batchRepository->findOneBy(['batch_code' => $batchCode]);
        if (!$reworkedBatch) {
            return $this->doResponse->doErrorJsonResponse('Lotto non trovato', 404, 404);
        }

        $baseCode = (strlen($batchCode) > 1 && $batchCode[0] === 'R') ? substr($batchCode, 1) : $batchCode;
        $sfBatch = $batchRepository->findOneBy(['batch_code' => 'SF' . $baseCode]);
        $scBatch = $batchRepository->findOneBy(['batch_code' => 'SC' . $baseCode]);

        $availablePieces = (float)($reworkedBatch->getStockItems() ?? 0);
        $availableQuantity = (float)($reworkedBatch->getStockQuantity() ?? 0);

        $calculatedQuantity = ($reworkedBatch->getQuantity() / $reworkedBatch->getPieces()) * $pieces ;

        if ($pieces > $availablePieces) {
            return $this->doResponse->doErrorJsonResponse('Numero di pezzi superiore alla disponibilità (' . $availablePieces . ')', 400);
        }

        $reworkedBatch->setStockItems($availablePieces - $pieces);
        $reworkedBatch->setStockQuantity($availableQuantity - $calculatedQuantity);

        $newType = $this->doctrine->getRepository(BatchType::class)->findOneBy(['name' => 'Spaccato']);

        $originalLeather = $reworkedBatch->getLeather();
        $sfLeather = $originalLeather;
        $scLeather = $originalLeather;

        if ($originalLeather) {
            $sfLeather = $this->getOrCreateLeatherForSplit($originalLeather, 'Fiore');
            $scLeather = $this->getOrCreateLeatherForSplit($originalLeather, 'Crosta');
        }

        $isNewSf = false;
        if (!$sfBatch) {
            $sfBatch = new Batch();
            $sfBatch->setBatchType($newType);
            $sfBatch->setBatchCode('SF' . $baseCode);
            $sfBatch->setBatchDate(new \DateTime());
            $sfBatch->setPieces(0);
            $sfBatch->setQuantity(0);
            $sfBatch->setStockItems(0.0);
            $sfBatch->setStockQuantity(0.0);
            $sfBatch->setLeather($sfLeather);
            $sfBatch->setSampling($reworkedBatch->isSampling() ?? false);
            $sfBatch->setSplitSelected($reworkedBatch->isSplitSelected() ?? false);
            $sfBatch->setCompleted(false);
            $sfBatch->setChecked(false);
            $sfBatch->setSqFtAverageExpected($reworkedBatch->getSqFtAverageExpected() ?? 0.0);
            $sfBatch->setSqFtAverageFound($reworkedBatch->getSqFtAverageFound() ?? 0.0);
            $sfBatch->setSelectionNote($reworkedBatch->getSelectionNote());
            $sfBatch->setBatchNote($reworkedBatch->getBatchNote());
            $sfBatch->setMeasurementUnit($reworkedBatch->getMeasurementUnit());
            $now = new \DateTimeImmutable();
            $sfBatch->setCreatedAt($now);
            $sfBatch->setUpdatedAt($now);
            $isNewSf = true;
        }

        $sfBatch->setPieces($sfBatch->getPieces() + (int)$pieces);
        $sfBatch->setQuantity($sfBatch->getQuantity() + $calculatedQuantity);
        $sfBatch->setStockItems($sfBatch->getStockItems() + $pieces);
        $sfBatch->setStockQuantity($sfBatch->getStockQuantity() + $calculatedQuantity);

        if ($isNewSf) {
            $this->doctrine->persist($sfBatch);
        }

        $isNewSc = false;
        if (!$scBatch) {
            $scBatch = new Batch();
            $scBatch->setBatchType($newType);
            $scBatch->setBatchCode('SC' . $baseCode);
            $scBatch->setBatchDate(new \DateTime());
            $scBatch->setPieces(0);
            $scBatch->setQuantity(0);
            $scBatch->setStockItems(0.0);
            $scBatch->setStockQuantity(0.0);
            $scBatch->setLeather($scLeather);
            $scBatch->setSampling($reworkedBatch->isSampling() ?? false);
            $scBatch->setSplitSelected($reworkedBatch->isSplitSelected() ?? false);
            $scBatch->setCompleted(false);
            $scBatch->setChecked(false);
            $scBatch->setSqFtAverageExpected($reworkedBatch->getSqFtAverageExpected() ?? 0.0);
            $scBatch->setSqFtAverageFound($reworkedBatch->getSqFtAverageFound() ?? 0.0);
            $scBatch->setSelectionNote($reworkedBatch->getSelectionNote());
            $scBatch->setBatchNote($reworkedBatch->getBatchNote());
            $scBatch->setMeasurementUnit($reworkedBatch->getMeasurementUnit());
            $scBatchNow = new \DateTimeImmutable();
            $scBatch->setCreatedAt($scBatchNow);
            $scBatch->setUpdatedAt($scBatchNow);
            $isNewSc = true;
        }

        $scBatch->setPieces($scBatch->getPieces() + (int)$pieces);
        $scBatch->setQuantity($scBatch->getQuantity() + $calculatedQuantity);
        $scBatch->setStockItems($scBatch->getStockItems() + $pieces);
        $scBatch->setStockQuantity($scBatch->getStockQuantity() + $calculatedQuantity);

        if ($isNewSc) {
            $this->doctrine->persist($scBatch);
        }

        $sfComp = new BatchComposition();
        if(isset($data['date'])){
            $sfComp->setDate(new \DateTime($data['date']) ?: new \DateTime());
        }
        $sfComp->setBatch($sfBatch);
        $sfComp->setFatherBatch($reworkedBatch);
        $sfComp->setFatherBatchPiece((int)$pieces);
        $sfComp->setFatherBatchQuantity($calculatedQuantity);
        $sfComp->setCompositionNote('Spaccatura lotto ' . $batchCode);
        $this->doctrine->persist($sfComp);

        $scComp = new BatchComposition();
        if(isset($data['date'])){
            $scComp->setDate(new \DateTime($data['date']) ?: new \DateTime());
        }
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
            return $this->doResponse->doErrorJsonResponse('Causale "Carico" non trovata', 400);
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

            if ($batch->getBatchType() && ($batch->getBatchType()->getName() === 'Partita' || $batch->getBatchType()->getName() === 'Lotto')) {
                $lastBatch = $this->doctrine->getRepository(Batch::class)->findOneBy(
                    ['batch_type' => $batch->getBatchType()],
                    ['id' => 'DESC']
                );

                $lastCode = $lastBatch ? $lastBatch->getBatchCode() : null;

                $yearPrefix = $batch->getBatchType()->getPrefix() ?? (new \DateTimeImmutable())->format('y');
                $nextCode = $this->nextSequentialCode($lastCode, $yearPrefix, 4);
                $batch->setBatchCode($nextCode);
            }
            if ($batch->getMeasurementUnit()) {
                $measurementUnit = $batch->getMeasurementUnit();
                $pieces = (float)($data['pieces'] ?? $batch->getPieces() ?? 0);
                $quantity = (float)($data['quantity'] ?? $batch->getQuantity() ?? 0);

                if ($pieces > 0) {
                    $baseSqFtAverage = 0;
                    if ($measurementUnit->getPrefix() === 'SQFT' || $measurementUnit->getPrefix() === 'PQ') {
                        $baseSqFtAverage = $quantity / $pieces;
                    } else {
                        $targetUm = $this->doctrine->getRepository(MeasurementUnit::class)->findOneBy(['prefix' => 'SQFT'])
                            ?? $this->doctrine->getRepository(MeasurementUnit::class)->findOneBy(['prefix' => 'PQ']);

                        if ($targetUm) {
                            $coefficientEntity = $this->doctrine->getRepository(MeasurementUnitCoefficient::class)->findOneBy([
                                'start_um' => $measurementUnit,
                                'end_um' => $targetUm
                            ]);

                            if ($coefficientEntity) {
                                $baseSqFtAverage = ($quantity * $coefficientEntity->getCoefficient()) / $pieces;
                            }
                        }
                    }

                    if ($baseSqFtAverage > 0) {
                        $coefficient = 1.0;
                        $leather = $batch->getLeather();
                        if ($leather && $leather->getProvenance() && $leather->getType()) {
                            $provenance = $leather->getProvenance();
                            $type = $leather->getType();

                            $coefficient = match ($type->getName()) {
                                'Crosta' => $provenance->getRindYieldCoefficient() ?? 1.0,
                                'Crust' => $provenance->getCrustYieldCoefficient() ?? 1.0,
                                'Pieno Spessore' => $provenance->getPspYieldCoefficient() ?? 1.0,
                                'Fiore' => $provenance->getGrainYieldCoefficient() ?? 1.0,
                                'Grezzo' => $provenance->getRawYieldCoefficient() ?? 1.0,
                                default => 1.0,
                            };
                        }
                        $batch->setSqFtAverageExpected($baseSqFtAverage * $coefficient);
                    }
                }
            } else {
                return new JsonResponse(['error' => 'Measurement unit not found'], 400);
            }

            if ($batch->getSqFtAverageExpected() === null || $batch->getSqFtAverageExpected() == 0.0) {
                $batch->setSqFtAverageExpected($batch->getSqFtAverageFound() ?? (float)0);
            }

            if($batch->getSqFtAverageFound() === null || $batch->getSqFtAverageFound() == 0.0){
                $batch->setSqFtAverageFound((float)0);
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
                return $this->doResponse->doErrorJsonResponse($errors);
            }

            $this->doctrine->persist($batch);

            if ($batch->getBatchType() && ($batch->getBatchType()->getName() === 'Partita' || $batch->getBatchType()->getName() === 'Lotto')) {
                $batchData = new BatchData();
                $batchData->setBatch($batch);
                $batchData->setAmount(0.0);
                $this->doctrine->persist($batchData);
            }

            $reasonRepo = $this->doctrine->getRepository(WarehouseMovementReason::class);
            $inReason = $reasonRepo->findOneBy(['name' => 'Carico']);

            if(!$inReason) {
                return $this->doResponse->doErrorJsonResponse('Causale "Carico" non trovata', 400);
            }

            if ($inReason) {
                $movement = new WarehouseMovement();
                $movement->setBatch($batch);
                $movement->setReason($inReason);
                $movement->setQuantity($batch->getQuantity());
                $movement->setPiece($batch->getPieces());
                $movement->setDate(new \DateTime());
                $movement->setMovementNote('Carico iniziale creazione lotto');
                if (isset($data['price'])) {
                    $movement->setPrice((float)$data['price']);
                    $movement->setTotalValue((float)$data['price'] * $batch->getQuantity());
                }
                $this->doctrine->persist($movement);
            }

            $this->doctrine->flush();

            $result = $this->groupSerializer->serializeGroup($batch, 'batch_detail');
            return new JsonResponse($this->doResponse->doResponse($result));

        } catch (\Exception $e) {
            return $this->doResponse->doErrorJsonResponse($e->getMessage());
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
            return $this->doResponse->doErrorJsonResponse('Batch not found', 404);
        }

        try {
            $oldPieces = $batch->getPieces();
            $oldQuantity = $batch->getQuantity();

            $batch = $this->handleRelations($batch, $data);
            $batch = $this->createMethodsByInput->createMethods($batch, $data);

            if($batch->getMeasurementUnit()){
                $measurementUnit = $batch->getMeasurementUnit();

                if ($measurementUnit->getPrefix() == 'MQ') {
                    $coefficientUm = $measurementUnit->getMeasurementUnitCoefficients()->first();
                    if($batch->getPieces() > 0 && $batch->getQuantity() > 0) {
                        $batch->setSqFtAverageFound($batch->getPieces() / ($coefficientUm->getCoefficient() * $batch->getQuantity()));
                    }
                } elseif($batch->getMeasurementUnit()->getPrefix() == 'PQ') {
                    if($batch->getPieces() > 0 && $batch->getQuantity() > 0) {
                        $batch->setSqFtAverageFound($batch->getPieces() / $batch->getQuantity());
                    }
                }
            }

            if ($batch->getPieces() !== $oldPieces) {
                $diffPieces = $batch->getPieces() - $oldPieces;
                $batch->setStockItems($batch->getStockItems() + $diffPieces);
            }

            if ($batch->getQuantity() !== $oldQuantity) {
                $diffQuantity = $batch->getQuantity() - $oldQuantity;
                $batch->setStockQuantity($batch->getStockQuantity() + $diffQuantity);
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
                return $this->doResponse->doErrorJsonResponse($errors);
            }

            $this->doctrine->persist($batch);

            // Gestione Movimento di Magazzino per la modifica
            if ($batch->getPieces() !== $oldPieces || $batch->getQuantity() !== $oldQuantity || isset($data['price'])) {
                $reasonRepo = $this->doctrine->getRepository(WarehouseMovementReason::class);
                $movementRepo = $this->doctrine->getRepository(WarehouseMovement::class);

                // Cerchiamo se esiste già un movimento di carico iniziale per questo lotto
                $initialMovement = $movementRepo->createQueryBuilder('m')
                    ->join('m.reason', 'r')
                    ->where('m.batch = :batch')
                    ->andWhere('r.name LIKE :reasonName')
                    ->setParameter('batch', $batch)
                    ->setParameter('reasonName', '%Carico iniziale%')
                    ->setMaxResults(1)
                    ->getQuery()
                    ->getOneOrNullResult();

                if ($initialMovement) {
                    $initialMovement->setPiece($batch->getPieces());
                    $initialMovement->setQuantity($batch->getQuantity());
                    if (isset($data['price'])) {
                        $initialMovement->setPrice((float)$data['price']);
                        $initialMovement->setTotalValue((float)$data['price'] * $batch->getQuantity());
                    } elseif ($initialMovement->getPrice()) {
                        $initialMovement->setTotalValue($initialMovement->getPrice() * $batch->getQuantity());
                    }
                    $this->doctrine->persist($initialMovement);
                } else {
                    // Se non esiste, ne creiamo uno di rettifica o nuovo carico
                    $adjReason = $reasonRepo->createQueryBuilder('r')
                        ->join('r.reason_type', 't')
                        ->where('r.name = :name')
                        ->setParameter('name', 'Rettifica inventariale')
                        ->getQuery()
                        ->getOneOrNullResult()
                        ?? $reasonRepo->findOneBy(['name' => 'Rettifica']);

                    if ($adjReason) {
                        $movement = new WarehouseMovement();
                        $movement->setBatch($batch);
                        $movement->setReason($adjReason);
                        $movement->setQuantity($batch->getQuantity() - $oldQuantity);
                        $movement->setPiece($batch->getPieces() - $oldPieces);
                        $movement->setDate(new \DateTime());
                        $movement->setMovementNote('Rettifica da modifica lotto');
                        if (isset($data['price'])) {
                            $movement->setPrice((float)$data['price']);
                            $movement->setTotalValue((float)$data['price'] * ($batch->getQuantity() - $oldQuantity));
                        }
                        $this->doctrine->persist($movement);
                    }
                }
            }

            $this->doctrine->flush();

            $result = $this->groupSerializer->serializeGroup($batch, 'batch_detail');
            return new JsonResponse($this->doResponse->doResponse($result));
        } catch (\Exception $e) {
            return $this->doResponse->doErrorJsonResponse($e->getMessage());
        }
    }

    #[Route('/batch/{id}',
        name: 'delete_batch',
        methods: ['DELETE'])]
    public function deleteBatch(int $id): JsonResponse
    {
        $batch = $this->doctrine->getRepository(Batch::class)->find($id);
        if (!$batch) {
            return $this->doResponse->doErrorJsonResponse('Batch not found', 404);
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

        $n = (int)$m[1] + 1;

        return $prefix . str_pad((string)$n, $pad, '0', STR_PAD_LEFT);
    }

    private function getOrCreateLeatherForSplit(Leather $originalLeather, string $newTypeName): Leather
    {
        $leatherRepo = $this->doctrine->getRepository(Leather::class);
        $typeRepo = $this->doctrine->getRepository(LeatherType::class);

        $newType = $typeRepo->findOneBy(['name' => $newTypeName]);
        if (!$newType) {
            $newType = new LeatherType();
            $newType->setName($newTypeName);
            $newType->setCode(mb_substr($newTypeName, 0, 2));
            $this->doctrine->persist($newType);
        }

        $existingLeather = $leatherRepo->findOneBy([
            'species' => $originalLeather->getSpecies(),
            'provenance' => $originalLeather->getProvenance(),
            'weight' => $originalLeather->getWeight(),
            'status' => $originalLeather->getStatus(),
            'thickness' => $originalLeather->getThickness(),
            'flay' => $originalLeather->getFlay(),
            'type' => $newType,
            'supplier' => $originalLeather->getSupplier(),
            'contact' => $originalLeather->getContact(),
        ]);

        if ($existingLeather) {
            return $existingLeather;
        }

        $newLeather = new Leather();
        $newLeather->setType($newType);

        $newLeather->setSpecies($originalLeather->getSpecies());
        $newLeather->setProvenance($originalLeather->getProvenance());
        $newLeather->setWeight($originalLeather->getWeight());
        $newLeather->setStatus($originalLeather->getStatus());
        $newLeather->setThickness($originalLeather->getThickness());
        $newLeather->setFlay($originalLeather->getFlay());
        $newLeather->setContact($originalLeather->getContact());
        $newLeather->setSupplier($originalLeather->getSupplier());

        $newLeather->setName($newLeather->generateName());

        $typeCode = strtoupper(trim((string)$newType->getCode()));
        $typeCode = $typeCode === '' ? '' : (mb_strlen($typeCode) === 1 ? $typeCode . $typeCode : mb_substr($typeCode, 0, 2));

        $speciesCode = strtoupper(trim((string)$originalLeather->getSpecies()?->getCode()));
        $speciesCode = mb_substr($speciesCode, 0, 3);

        $nationCode = strtoupper(trim((string)$originalLeather->getProvenance()?->getNation()?->getName()));
        $nationCode = mb_substr(str_replace(' ', '', $nationCode), 0, 3);

        $weightCode = strtoupper(trim((string)$originalLeather->getWeight()?->getName()));

        $thicknessCode = '';
        if (method_exists($newLeather, 'getThicknessMm')) {
            $thicknessValue = (string)$newLeather->getThicknessMm();
            $thicknessCode = preg_replace('/[^\d]/', '', $thicknessValue) ?? '';
        }
        if ($thicknessCode === '' || (int)$thicknessCode === 0) {
            $thicknessCode = strtoupper(trim((string)$newLeather->getThickness()?->getName()));
        }

        $flayCode = strtoupper(trim((string)$newLeather->getFlay()?->getCode()));

        $newCode = $typeCode . $speciesCode . $nationCode . $weightCode . $thicknessCode . $flayCode;
        $newCode = preg_replace('/[^A-Za-z0-9]/', '', $newCode);
        $newLeather->setCode($newCode);

        $newLeather->setSqftLeatherMin($originalLeather->getSqftLeatherMin());
        $newLeather->setSqftLeatherMax($originalLeather->getSqftLeatherMax());
        $newLeather->setSqftLeatherMedia($originalLeather->getSqftLeatherMedia());
        $newLeather->setSqftLeatherExpected($originalLeather->getSqftLeatherExpected() ?? 0.0);
        $newLeather->setKgLeatherMin($originalLeather->getKgLeatherMin());
        $newLeather->setKgLeatherMax($originalLeather->getKgLeatherMax());
        $newLeather->setKgLeatherMedia($originalLeather->getKgLeatherMedia());
        $newLeather->setKgLeatherExpected($originalLeather->getKgLeatherExpected());
        $newLeather->setContainerPiece($originalLeather->getContainerPiece());
        $newLeather->setStatisticUpdate($originalLeather->isStatisticUpdate());
        $newLeather->setCrustRevenueExpected($originalLeather->getCrustRevenueExpected());

        $this->doctrine->persist($newLeather);

        return $newLeather;
    }
}


