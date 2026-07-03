<?php

namespace App\Controller;

use App\Service\StockService;
use App\Entity\Contact;
use App\Entity\Ddt;
use App\Entity\DdtReason;
use App\Entity\DdtRow;
use App\Entity\Batch;
use App\Entity\MeasurementUnit;
use App\Entity\Currency;
use App\Entity\Processing;
use App\Entity\DdtRowProcessing;
use App\Entity\Selection;
use App\Entity\MeasurementUnitCoefficient;
use App\Entity\WarehouseMovement;
use App\Entity\WarehouseMovementReason;
use App\Entity\WarehouseMovementReasonType;
use App\Repository\MeasurementUnitCoefficientRepository;
use App\Repository\MeasurementUnitRepository;
use App\Service\CreateMethodsByInput;
use App\Service\DoResponseService;
use App\Service\GroupSerializerService;
use App\Service\PdfGeneratorService;
use App\Service\ValidatorOutputFormatter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class DdtRowController extends AbstractController
{
    private $createMethodsByInput;
    private $doctrine;
    private $doResponse;
    private $groupSerializer;
    private $validatorOutputFormatter;
    private $pdfGenerator;
    private $stockService;

    public function __construct(
        CreateMethodsByInput     $createMethodsByInput,
        EntityManagerInterface   $entityManager,
        DoResponseService        $doResponseService,
        GroupSerializerService   $groupSerializer,
        ValidatorOutputFormatter $validatorOutputFormatter,
        PdfGeneratorService      $pdfGenerator,
        StockService             $stockService
    ) {
        $this->createMethodsByInput = $createMethodsByInput;
        $this->doctrine = $entityManager;
        $this->doResponse = $doResponseService;
        $this->groupSerializer = $groupSerializer;
        $this->validatorOutputFormatter = $validatorOutputFormatter;
        $this->pdfGenerator = $pdfGenerator;
        $this->stockService = $stockService;
    }

    #[Route('/ddt-row/{id}',
        name: 'get_ddt_row',
        defaults: ['id' => null],
        requirements: ['id' => '\d+'],
        methods: ['GET', 'HEAD'])]
    public function getDdtRow(?int $id): JsonResponse
    {
        $ddtRowRepository = $this->doctrine->getRepository(DdtRow::class);

        if ($id) {
            $ddtRow = $ddtRowRepository->find($id);
            if (!$ddtRow) {
                return $this->doResponse->doErrorJsonResponse('Riga DDT non trovata', 404);
            }
            $results = $this->groupSerializer->serializeGroup([$ddtRow], 'ddt_row_detail');
            return new JsonResponse($this->doResponse->doResponse($results[0]));
        }

        $ddtRows = $ddtRowRepository->findBy([], ['id' => 'ASC']);
        $results = $this->groupSerializer->serializeGroup($ddtRows, 'ddt_row_list');
        return new JsonResponse($this->doResponse->doResponse($results));
    }

    #[Route('/ddt-row/subcontracting-not-returned',
        name: 'get_ddt_row_subcontracting_not_returned',
        methods: ['GET'])]
    public function getDdtRowSubcontractingNotReturned(Request $request): JsonResponse
    {
        $batchCode = $request->query->get('batch_code') ? (string)$request->query->get('batch_code') : null;
        $startDate = $request->query->get('start_date') ? new \DateTime($request->query->get('start_date')) : null;
        $endDate = $request->query->get('end_date') ? new \DateTime($request->query->get('end_date')) : null;
        $subcontractorId = $request->query->get('subcontractor_id') ? (int)$request->query->get('subcontractor_id') : null;

        $ddtRowRepository = $this->doctrine->getRepository(DdtRow::class);
        $ddtRows = $ddtRowRepository->findSubcontractingNotReturned($subcontractorId, $startDate, $endDate, $batchCode);

        $ddtRowsSelected = [];
        foreach ($ddtRows as $ddtRow) {
            $ddt = $ddtRow->getDdt();
            if (!$ddt) {
                continue;
            }
            $ddtReason = $ddt->getReason();
            $ddtReasonName = $ddtReason?->getName();

            // Esclude i DDT con causale "Vendita" o senza causale
            if (!$ddtReasonName || $ddtReasonName === 'Vendita') {
                continue;
            }

            $batch = $ddtRow->getBatch();
            if (!$batch || $batch->isCompleted()) {
                continue;
            }

            $movements = $batch->getWarehouseMovements();
            if ($movements->isEmpty()) {
                continue;
            }

            $movementsArray = $movements->toArray();
            usort($movementsArray, fn($a, $b) => $a->getId() <=> $b->getId());

            $lastMovement = end($movementsArray);
            $lastMovementReasonName = $lastMovement?->getReason()?->getName();

            $resoReasonName = "Reso " . $ddtReasonName;
            $rientroTrasferimentoReasonName = "Rientro da Lavorazione";

            $firstMovementOut = null;
            
            // Tenta di identificare il movimento di uscita iniziale
            foreach ($movementsArray as $movement) {
                $reason = $movement->getReason();
                $reasonName = $reason?->getName();
                $reasonType = $reason?->getReasonType();
                $reasonTypeName = $reasonType?->getName();
                $movementType = $reasonType?->getMovementType();

                // Un movimento è un'uscita valida se è di tipo Scarico (-)
                if ($movementType === '-') {
                    // Se c'è un match del numero DDT, o se la causale è la stessa, è un ottimo candidato
                    if ($movement->getDdtNumber() === $ddt->getDdtNumber() || $reasonName === $ddtReasonName) {
                        $firstMovementOut = $movement;
                        // Se abbiamo anche il match del numero DDT, abbiamo finito la ricerca dell'uscita
                        if ($movement->getDdtNumber() === $ddt->getDdtNumber()) {
                            break;
                        }
                    }
                    
                    // Fallback: se non abbiamo ancora trovato nulla e il ddt_number è nullo, lo teniamo come candidato
                    if ($firstMovementOut === null && $movement->getDdtNumber() === null) {
                        $firstMovementOut = $movement;
                    }
                }
            }

            $returnedPieces = 0;
            $returnedQuantity = 0;

            foreach ($movementsArray as $movement) {
                $reason = $movement->getReason();
                $reasonName = $reason?->getName();
                $reasonType = $reason?->getReasonType();
                $movementType = $reasonType?->getMovementType();

                // Un movimento è un rientro se è di tipo Carico (+) 
                // e ha il numero di DDT corrispondente, oppure ha una delle causali "note" di rientro
                if ($movementType === '+') {
                    if ($movement->getDdtNumber() === $ddt->getDdtNumber() || 
                        $reasonName === $resoReasonName || 
                        $reasonName === $rientroTrasferimentoReasonName ||
                        (str_starts_with($reasonName, 'Reso ') && $movement->getDdtNumber() === $ddt->getDdtNumber())
                    ) {
                        $mPieces = abs($movement->getPiece() ?? 0);
                        $returnedPieces += $mPieces;
                        
                        // Se il movimento ha una quantità esplicita diversa da zero, usiamo quella
                        $mQuantity = abs($movement->getQuantity() ?? 0);
                        if ($mQuantity > 0) {
                            $returnedQuantity += $mQuantity;
                        } else {
                            // Fallback sul calcolo proporzionale se la quantità nel movimento è mancante
                            $unitQuantity = 0;
                            $pOut = $ddtRow->getPiecesOut() ?? $ddtRow->getPieces() ?? 0;
                            $qOut = $ddtRow->getQuantityOut() ?? $ddtRow->getQuantity() ?? 0;
                            if ($pOut > 0) {
                                $unitQuantity = $qOut / $pOut;
                            }
                            $returnedQuantity += ($unitQuantity * $mPieces);
                        }
                    }
                }
            }

            if ($firstMovementOut !== null || $ddtRow->getPiecesOut() !== null || $ddtRow->getPieces() !== null) {
                $outPieces = $ddtRow->getPiecesOut() ?? $ddtRow->getPieces() ?? abs($firstMovementOut->getPiece() ?? 0);
                $outQuantity = $ddtRow->getQuantityOut() ?? $ddtRow->getQuantity() ?? abs($firstMovementOut->getQuantity() ?? 0);
                
                $remainingPieces = $outPieces - $returnedPieces;
                $remainingQuantity = $outQuantity - $returnedQuantity;

                // Mostriamo la riga se non è ancora stata saldata (rimangono pezzi)
                if ($remainingPieces > 0.01) {
                    $results = $this->groupSerializer->serializeGroup($ddtRow, 'ddt_row_list');
                    $results['pieces_out'] = $outPieces;
                    $results['quantity_out'] = $outQuantity;
                    $results['stock_pieces'] = $remainingPieces;
                    $results['stock_quantity'] = round($remainingQuantity, 3);
                    $ddtRowsSelected[] = $results;
                }
            }
        }

        return new JsonResponse($this->doResponse->doResponse($ddtRowsSelected));
    }

    #[Route('/ddt-row/sold',
        name: 'get_sold_lots',
        methods: ['GET'])]
    public function getSoldLots(Request $request): JsonResponse
    {
        $batchCode = $request->query->get('batch_code') ? (string)$request->query->get('batch_code') : null;
        $clientId = $request->query->get('client_id') ? (int)$request->query->get('client_id') : null;
        $startDateStr = $request->query->get('start_date');
        $endDateStr = $request->query->get('end_date');

        $startDate = $startDateStr ? \DateTime::createFromFormat('Y-m-d', $startDateStr) : null;
        if ($startDate) $startDate->setTime(0, 0, 0);

        $endDate = $endDateStr ? \DateTime::createFromFormat('Y-m-d', $endDateStr) : null;
        if ($endDate) $endDate->setTime(0, 0, 0);

        $ddtRowRepository = $this->doctrine->getRepository(DdtRow::class);
        $soldLots = $ddtRowRepository->findSoldLots($clientId, $startDate, $endDate, $batchCode);

        $results = $this->groupSerializer->serializeGroup($soldLots, 'ddt_row_list_sold');

        return new JsonResponse($this->doResponse->doResponse($results));
    }

    #[Route('/ddt-row/sold/pdf',
        name: 'get_sold_lots_pdf',
        methods: ['GET'])]
    public function getSoldLotsPdf(Request $request): \Symfony\Component\HttpFoundation\Response
    {
        $batchCode = $request->query->get('batch_code') ? (string)$request->query->get('batch_code') : null;
        $clientId = $request->query->get('client_id') ? (int)$request->query->get('client_id') : null;
        $startDateStr = $request->query->get('start_date');
        $endDateStr = $request->query->get('end_date');

        $startDate = $startDateStr ? \DateTime::createFromFormat('Y-m-d', $startDateStr) : null;
        if ($startDate) $startDate->setTime(0, 0, 0);

        $endDate = $endDateStr ? \DateTime::createFromFormat('Y-m-d', $endDateStr) : null;
        if ($endDate) $endDate->setTime(0, 0, 0);

        $ddtRowRepository = $this->doctrine->getRepository(DdtRow::class);
        $soldLots = $ddtRowRepository->findSoldLots($clientId, $startDate, $endDate, $batchCode);

        // Recupero i coefficienti di conversione
        $coeffRepo = $this->doctrine->getRepository(MeasurementUnitCoefficient::class);
        $coefficients = $coeffRepo->findAll();
        
        // Recupero le unità di misura MQ e PQ per identificare i coefficienti corretti
        $umRepo = $this->doctrine->getRepository(MeasurementUnit::class);
        $mqUm = $umRepo->findOneBy(['prefix' => 'MQ']);
        $pqUm = $umRepo->findOneBy(['prefix' => 'PQ']);

        $groupedData = [];
        foreach ($soldLots as $row) {
            $client = $row->getDdt()->getClient();
            if (!$client) continue;

            $cId = $client->getId();
            if (!isset($groupedData[$cId])) {
                $groupedData[$cId] = [
                    'client' => $this->groupSerializer->serializeGroup($client, 'client_summary_print'),
                    'rows' => []
                ];
            }
            $groupedData[$cId]['rows'][] = $this->groupSerializer->serializeGroup($row, 'client_summary_print');
        }

        // Ordina i clienti per nome
        usort($groupedData, fn($a, $b) => $a['client']['name'] <=> $b['client']['name']);

        $pdfContent = $this->pdfGenerator->generatePdf('print/sold_lots_pdf.html.twig', [
            'data' => $groupedData,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'orientation' => 'landscape',
            'coefficients' => $coefficients,
            'mq_um_id' => $mqUm ? $mqUm->getId() : null,
            'pq_um_id' => $pqUm ? $pqUm->getId() : null,
        ], 'lotti_venduti.pdf');

        return new \Symfony\Component\HttpFoundation\Response($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="lotti_venduti.pdf"'
        ]);
    }

    #[Route('/ddt-row/external-processing',
        name: 'get_external_processing_lots',
        methods: ['GET'])]
    public function getExternalProcessingLots(Request $request): JsonResponse
    {
        $batchCode = $request->query->get('batch_code') ? (string)$request->query->get('batch_code') : null;
        $subcontractorId = $request->query->get('subcontractor_id') ? (int)$request->query->get('subcontractor_id') : null;
        $startDateStr = $request->query->get('start_date');
        $endDateStr = $request->query->get('end_date');

        $startDate = $startDateStr ? \DateTime::createFromFormat('Y-m-d', $startDateStr) : null;
        if ($startDate) $startDate->setTime(0, 0, 0);

        $endDate = $endDateStr ? \DateTime::createFromFormat('Y-m-d', $endDateStr) : null;
        if ($endDate) $endDate->setTime(0, 0, 0);

        $ddtRowRepository = $this->doctrine->getRepository(DdtRow::class);
        $lots = $ddtRowRepository->findExternalProcessingLots($subcontractorId, $startDate, $endDate, $batchCode);

        // Come prima: restituisce le righe così come sono (solo filtro repository), senza calcolo residui
        $results = $this->groupSerializer->serializeGroup($lots, ['client_summary_print', 'external_processing_print']);

        return new JsonResponse($this->doResponse->doResponse($results));
    }

    #[Route('/ddt-row/external-processing/pdf',
        name: 'get_external_processing_lots_pdf',
        methods: ['GET'])]
    public function getExternalProcessingLotsPdf(Request $request): \Symfony\Component\HttpFoundation\Response
    {
        $batchCode = $request->query->get('batch_code') ? (string)$request->query->get('batch_code') : null;
        $subcontractorId = $request->query->get('subcontractor_id') ? (int)$request->query->get('subcontractor_id') : null;
        $startDateStr = $request->query->get('start_date');
        $endDateStr = $request->query->get('end_date');

        $startDate = $startDateStr ? \DateTime::createFromFormat('Y-m-d', $startDateStr) : null;
        if ($startDate) $startDate->setTime(0, 0, 0);

        $endDate = $endDateStr ? \DateTime::createFromFormat('Y-m-d', $endDateStr) : null;
        if ($endDate) $endDate->setTime(0, 0, 0);

        $ddtRowRepository = $this->doctrine->getRepository(DdtRow::class);
        // Allineo la stampa alla tabella: stessa sorgente dati dell'endpoint
        // /ddt-row/subcontracting-not-returned
        $lots = $ddtRowRepository->findSubcontractingNotReturned($subcontractorId, $startDate, $endDate, $batchCode);

        $groupedData = [];
        foreach ($lots as $row) {
            $subcontractor = $row->getDdt()->getSubcontractor();
            if (!$subcontractor) continue;

            $sId = $subcontractor->getId();
            if (!isset($groupedData[$sId])) {
                $groupedData[$sId] = [
                    'subcontractor' => $this->groupSerializer->serializeGroup($subcontractor, 'client_summary_print'),
                    'rows' => []
                ];
            }
            $groupedData[$sId]['rows'][] = $this->groupSerializer->serializeGroup($row, ['client_summary_print', 'external_processing_print']);
        }

        // Ordina i terzisti per nome
        usort($groupedData, fn($a, $b) => $a['subcontractor']['name'] <=> $b['subcontractor']['name']);

        $pdfContent = $this->pdfGenerator->generatePdf('print/external_processing_lots_pdf.html.twig', [
            'data' => $groupedData,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'orientation' => 'landscape'
        ], 'lotti_lavorazione_esterna.pdf');

        return new \Symfony\Component\HttpFoundation\Response($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="lotti_lavorazione_esterna.pdf"'
        ]);
    }

    #[Route('/ddt-row/external-processing-returns/pdf',
        name: 'get_external_processing_returns_pdf',
        methods: ['GET'])]
    public function getExternalProcessingReturnsPdf(Request $request): \Symfony\Component\HttpFoundation\Response
    {
        $batchCode = $request->query->get('batch_code') ? (string)$request->query->get('batch_code') : null;
        $subcontractorId = $request->query->get('subcontractor_id') ? (int)$request->query->get('subcontractor_id') : null;
        $startDateStr = $request->query->get('start_date');
        $endDateStr = $request->query->get('end_date');

        $startDate = $startDateStr ? \DateTime::createFromFormat('Y-m-d', $startDateStr) : null;
        if ($startDate) $startDate->setTime(0, 0, 0);

        $endDate = $endDateStr ? \DateTime::createFromFormat('Y-m-d', $endDateStr) : null;
        if ($endDate) $endDate->setTime(0, 0, 0);

        $ddtRowRepository = $this->doctrine->getRepository(DdtRow::class);
        $lots = $ddtRowRepository->findExternalProcessingLots($subcontractorId, $startDate, $endDate, $batchCode);

        $flatData = [];
        $wmRepo = $this->doctrine->getRepository(WarehouseMovement::class);
        $wmReasonRepo = $this->doctrine->getRepository(WarehouseMovementReason::class);

        // Cerchiamo le causali di rientro (tipo 'Carico')
        $returnReasons = $wmReasonRepo->createQueryBuilder('r')
            ->join('r.reason_type', 'rt')
            ->andWhere('rt.movement_type = :type')
            ->setParameter('type', 'Carico')
            ->getQuery()
            ->getResult();

        foreach ($lots as $row) {
            $subcontractor = $row->getDdt()->getSubcontractor();
            if (!$subcontractor) continue;

            $serializedRow = $this->groupSerializer->serializeGroup($row, ['client_summary_print', 'external_processing_print']);
            $serializedRow['subcontractor_name'] = $subcontractor->getName();

            // Cerchiamo i movimenti di rientro per questo lotto e terzista
            $returns = $wmRepo->createQueryBuilder('wm')
                ->andWhere('wm.batch = :batch')
                ->andWhere('wm.contact = :contact')
                ->andWhere('wm.reason IN (:reasons)')
                ->andWhere('wm.date >= :ddtDate')
                ->setParameter('batch', $row->getBatch())
                ->setParameter('contact', $subcontractor)
                ->setParameter('reasons', $returnReasons)
                ->setParameter('ddtDate', $row->getDdt()->getDdtDate())
                ->orderBy('wm.date', 'DESC')
                ->getQuery()
                ->getResult();

            $totalReturnedPieces = 0;
            $lastReturnDate = null;
            foreach ($returns as $return) {
                $totalReturnedPieces += $return->getPiece();
                if (!$lastReturnDate || $return->getDate() > $lastReturnDate) {
                    $lastReturnDate = $return->getDate();
                }
            }

            $serializedRow['returned_pieces'] = $totalReturnedPieces;
            $serializedRow['last_return_date'] = $lastReturnDate ? $lastReturnDate->format('Y-m-d') : null;

            $flatData[] = $serializedRow;
        }

        // Ordina per ddt_date
        usort($flatData, function($a, $b) {
            $dateA = $a['ddt']['ddt_date'] ?? null;
            $dateB = $b['ddt']['ddt_date'] ?? null;
            return $dateA <=> $dateB;
        });

        $pdfContent = $this->pdfGenerator->generatePdf('print/external_processing_returns_pdf.html.twig', [
            'data' => $flatData,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'orientation' => 'landscape'
        ], 'rientri_lavorazione_esterna.pdf');

        return new \Symfony\Component\HttpFoundation\Response($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="rientri_lavorazione_esterna.pdf"'
        ]);
    }

    #[Route('/ddt-row',
        name: 'post_ddt_row',
        methods: ['POST'])]
    public function postDdtRow(Request $request, ValidatorInterface $validator): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? $request->request->all();
        $ddtRow = new DdtRow();

        try {

            if(isset($data['pieces'])){
                $ddtRow->setPiecesOut($data['pieces']);
            }
            if(isset($data['quantity'])){
                $ddtRow->setQuantityOut($data['quantity']);
            }

            $this->handleRelations($ddtRow, $data);
            $this->createMethodsByInput->createMethods($ddtRow, $data);
        } catch (\Exception $e) {
            return $this->doResponse->doErrorJsonResponse($e->getMessage(), 400);
        }

        $errors = $validator->validate($ddtRow);
        if (count($errors) > 0) {
            return $this->doResponse->doErrorJsonResponse($this->validatorOutputFormatter->formatOutput($errors), 400);
        }

        $this->calculatePrices($ddtRow);

        if($ddtRow->getHalfPiece() !== null) {
            $ddtRow->setWholePiece(($ddtRow->getPieces() ?? 0) - ($ddtRow->getHalfPiece() / 2));
        } else {
            $ddtRow->setWholePiece($ddtRow->getPieces() ?? 0);
        }

        $this->doctrine->persist($ddtRow);
        $this->doctrine->flush();

        $batch = $ddtRow->getBatch();

        $convertedQuantity = $this->getConvertedQuantity($ddtRow->getQuantity(), $ddtRow->getMeasurementUnit(), $batch->getMeasurementUnit());

        $ddt = $ddtRow->getDdt();

        $wearhouseMovement = new WarehouseMovement();
        $wearhouseMovement->setBatch($batch);
        $wearhouseMovement->setQuantity($convertedQuantity);
        $wearhouseMovement->setPiece($ddtRow->getPieces());
        $wearhouseMovement->setReason($ddtRow->getDdt()->getReason()->getWarehouseMovementReason());
        $wearhouseMovement->setDdtDate($ddt->getDdtDate());
        $wearhouseMovement->setDate($ddt->getDdtDate());
        $wearhouseMovement->setMovementNote($ddtRow->getRowNote() ?: 'Riga DDT ' . $ddtRow->getId());

        if ($ddt->getSubcontractor()) {
            $wearhouseMovement->setContact($ddt->getSubcontractor());
        } elseif ($ddt->getClient()) {
            $wearhouseMovement->setContact($ddt->getClient());
        }

        $this->doctrine->persist($wearhouseMovement);
        $this->doctrine->flush();

        // Se è un DDT di vendita, aggiorniamo la QuantityToShip sulle righe ordine collegate al lotto
        $ddt = $ddtRow->getDdt();
        $movementReason = $ddt->getReason()?->getWarehouseMovementReason();
        if ($movementReason && $movementReason->getReasonType()?->getMovementType() === 'Scarico') {
            $batch = $ddtRow->getBatch();
            if ($batch) {
                $convertedQuantity = $this->getConvertedQuantity($ddtRow->getQuantity(), $ddtRow->getMeasurementUnit(), $batch->getMeasurementUnit());
                foreach ($batch->getBatchOrders() as $batchOrder) {
                    $orderRow = $batchOrder->getOrderRow();
                    if ($orderRow) {
                        $newToShip = (float)$orderRow->getQuantity() - $convertedQuantity;
                        $orderRow->setQuantityToShip((string)$newToShip);
                        $this->doctrine->persist($orderRow);
                    }
                }
            }
        }

        $this->doctrine->flush();

        $results = $this->groupSerializer->serializeGroup([$ddtRow], 'ddt_row_detail');
        return new JsonResponse($this->doResponse->doResponse($results[0]));
    }

    #[Route('/ddt-row/{id}',
        name: 'put_ddt_row',
        requirements: ['id' => '\d+'],
        methods: ['PUT', 'PATCH'])]
    public function putDdtRow(int $id, Request $request, ValidatorInterface $validator): JsonResponse
    {
        $ddtRow = $this->doctrine->getRepository(DdtRow::class)->find($id);
        if (!$ddtRow) {
            return $this->doResponse->doErrorJsonResponse('Riga DDT non trovata', 404);
        }

        $oldPieces = $ddtRow->getPieces();
        $oldQuantity = $ddtRow->getQuantity();
        $oldBatch = $ddtRow->getBatch();

        $data = $request->toArray();

        try {

            if(isset($data['pieces'])){
                $ddtRow->setPiecesOut($data['pieces']);
            }
            if(isset($data['quantity'])){
                $ddtRow->setQuantityOut($data['quantity']);
            }

            $this->handleRelations($ddtRow, $data);
            $this->createMethodsByInput->createMethods($ddtRow, $data);
        } catch (\Exception $e) {
            return $this->doResponse->doErrorJsonResponse($e->getMessage(), 400);
        }

        $errors = $validator->validate($ddtRow);
        if (count($errors) > 0) {
            return $this->doResponse->doErrorJsonResponse($this->validatorOutputFormatter->formatOutput($errors), 400);
        }

        $this->calculatePrices($ddtRow);

        if($ddtRow->getHalfPiece() !== null) {
            $ddtRow->setWholePiece(($ddtRow->getPieces() ?? 0) - ($ddtRow->getHalfPiece() / 2));
        } else {
            $ddtRow->setWholePiece($ddtRow->getPieces() ?? 0);
        }

        $newBatch = $ddtRow->getBatch();

        // I ricalcoli di stock e media taglia sono gestiti dal WarehouseMovementListener
        // Dobbiamo però assicurarci che il movimento di magazzino esistente venga aggiornato
        $warehouseMovement = $this->doctrine->getRepository(WarehouseMovement::class)->findOneBy(["movement_note" => $ddtRow->getRowNote()]);
        if($warehouseMovement == null){
            $warehouseMovement = $this->doctrine->getRepository(WarehouseMovement::class)->findOneBy(["movement_note" => 'Riga DDT ' . $ddtRow->getId()]);
        }

        if ($warehouseMovement) {
            $convertedQuantity = $this->getConvertedQuantity($ddtRow->getQuantity(), $ddtRow->getMeasurementUnit(), $newBatch->getMeasurementUnit());
            $warehouseMovement->setBatch($newBatch);
            $warehouseMovement->setQuantity($convertedQuantity);
            $warehouseMovement->setPiece($ddtRow->getPieces());
            // Assicuriamoci che la nota rimanga coerente se è stata aggiornata la riga
            $warehouseMovement->setMovementNote($ddtRow->getRowNote() ?: 'Riga DDT ' . $ddtRow->getId());
            $this->doctrine->persist($warehouseMovement);
        }

        if ($oldBatch && $newBatch && $oldBatch->getId() === $newBatch->getId()) {
            // Logica rimossa: lo stock viene aggiornato tramite WarehouseMovementListener quando viene salvato $warehouseMovement sopra.
        } else {
            // Logica rimossa: lo stock viene aggiornato tramite WarehouseMovementListener quando viene salvato $warehouseMovement sopra.
        }

        // Gestione QuantityToShip per DDT di vendita
        $ddt = $ddtRow->getDdt();
        $movementReason = $ddt->getReason()?->getWarehouseMovementReason();
        if ($movementReason && $movementReason->getReasonType()?->getMovementType() === 'Scarico') {
            $batch = $ddtRow->getBatch();
            if ($batch) {
                $convertedQuantity = $this->getConvertedQuantity($ddtRow->getQuantity(), $ddtRow->getMeasurementUnit(), $batch->getMeasurementUnit());
                // Se il lotto è cambiato, dobbiamo ripristinare il vecchio e aggiornare il nuovo
                if ($oldBatch && $newBatch && $oldBatch->getId() !== $newBatch->getId()) {
                    // Ripristiniamo il vecchio lotto/ordine
                    foreach ($oldBatch->getBatchOrders() as $batchOrder) {
                        $orderRow = $batchOrder->getOrderRow();
                        if ($orderRow) {
                            $orderRow->setQuantityToShip((string)$orderRow->getQuantity());
                            $this->doctrine->persist($orderRow);
                        }
                    }
                    // Aggiorniamo il nuovo lotto/ordine
                    foreach ($newBatch->getBatchOrders() as $batchOrder) {
                        $orderRow = $batchOrder->getOrderRow();
                        if ($orderRow) {
                            $newToShip = (float)$orderRow->getQuantity() - $convertedQuantity;
                            $orderRow->setQuantityToShip((string)$newToShip);
                            $this->doctrine->persist($orderRow);
                        }
                    }
                } else {
                    // Stesso lotto, aggiorniamo basandoci sulla quantità totale della riga ordine
                    foreach ($batch->getBatchOrders() as $batchOrder) {
                        $orderRow = $batchOrder->getOrderRow();
                        if ($orderRow) {
                            $newToShip = (float)$orderRow->getQuantity() - $convertedQuantity;
                            $orderRow->setQuantityToShip((string)$newToShip);
                            $this->doctrine->persist($orderRow);
                        }
                    }
                }
            }
        }

        $this->doctrine->flush();

        $results = $this->groupSerializer->serializeGroup([$ddtRow], 'ddt_row_detail');
        return new JsonResponse($this->doResponse->doResponse($results[0]));
    }

    #[Route('/ddt-row/{id}',
        name: 'delete_ddt_row',
        requirements: ['id' => '\d+'],
        methods: ['DELETE'])]
    public function deleteDdtRow(int $id): JsonResponse
    {
        $ddtRow = $this->doctrine->getRepository(DdtRow::class)->find($id);
        if (!$ddtRow) {
            return $this->doResponse->doErrorJsonResponse('Riga DDT non trovata', 404);
        }

        $batch = $ddtRow->getBatch();

        // Rimuoviamo anche il movimento di magazzino associato
        $warehouseMovement = $this->doctrine->getRepository(WarehouseMovement::class)->findOneBy([
            'movement_note' => 'Riga DDT ' . $ddtRow->getId()
        ]);
        if ($warehouseMovement) {
            $this->doctrine->remove($warehouseMovement);
        }

        // Ripristino QuantityToShip per DDT di vendita in caso di eliminazione
        $ddt = $ddtRow->getDdt();
        $movementReason = $ddt->getReason()?->getWarehouseMovementReason();
        if ($movementReason && $movementReason->getReasonType()?->getMovementType() === 'Scarico') {
            $batch = $ddtRow->getBatch();
            if ($batch) {
                foreach ($batch->getBatchOrders() as $batchOrder) {
                    $orderRow = $batchOrder->getOrderRow();
                    if ($orderRow) {
                        $orderRow->setQuantityToShip((string)$orderRow->getQuantity());
                        $this->doctrine->persist($orderRow);
                    }
                }
            }
        }

        $this->doctrine->remove($ddtRow);
        $this->doctrine->flush();

        return new JsonResponse($this->doResponse->doResponse(['message' => 'Riga DDT eliminata con successo']));
    }

    #[Route('/ddt-row/{id}/return',
        name: 'post_ddt_row_return',
        requirements: ['id' => '\d+'],
        methods: ['POST'])]
    public function postDdtRowReturn(int $id, Request $request): JsonResponse
    {
        $ddtRow = $this->doctrine->getRepository(DdtRow::class)->find($id);
        if (!$ddtRow) {
            return $this->doResponse->doErrorJsonResponse('Riga DDT non trovata', 404);
        }

        $batch = $ddtRow->getBatch();
        if (!$batch) {
            return $this->doResponse->doErrorJsonResponse('Lotto non trovato per questa riga', 404);
        }

        $data = json_decode($request->getContent(), true) ?? $request->request->all();
        $quantity = $data['quantity'] ?? $ddtRow->getQuantity();
        $pieces = $data['pieces'] ?? $ddtRow->getPieces();
        $closed = $data['closed'] ?? false;
        $pieces = (float)$pieces;

        $ddt = $ddtRow->getDdt();

        if ($closed) {
            // Calcolo pezzi mancanti per lo scarto
            $outPieces = $ddtRow->getPiecesOut() ?? $ddtRow->getPieces() ?? 0;
            $outQuantity = $ddtRow->getQuantityOut() ?? $ddtRow->getQuantity() ?? 0;
            
            $wastePieces = $outPieces - $pieces;
            $wasteQuantity = $outQuantity - $quantity;

            if ($wastePieces > 0.01) {
                $wasteReason = $this->doctrine->getRepository(WarehouseMovementReason::class)->findOneBy(['name' => 'Scarto']);
                if (!$wasteReason) {
                    $reasonTypeOut = $this->doctrine->getRepository(WarehouseMovementReasonType::class)->findOneBy(['movement_type' => 'Scarico']);
                    if (!$reasonTypeOut) {
                        $reasonTypeOut = $this->doctrine->getRepository(WarehouseMovementReasonType::class)->findOneBy(['movement_type' => '-']);
                    }
                    if ($reasonTypeOut) {
                        $wasteReason = $this->doctrine->getRepository(WarehouseMovementReason::class)->findOneBy(['reason_type' => $reasonTypeOut]);
                    }
                }

                if ($wasteReason) {
                    $wasteMovement = new WarehouseMovement();
                    $wasteMovement->setBatch($batch);
                    $wasteMovement->setQuantity((float)$wasteQuantity);
                    $wasteMovement->setPiece((float)$wastePieces);
                    $wasteMovement->setReason($wasteReason);
                    $wasteMovement->setDdtNumber($ddt->getDdtNumber());
                    $wasteMovement->setDdtDate($ddt->getDdtDate());
                    $wasteMovement->setDate(new \DateTime());
                    $wasteMovement->setMovementNote('Scarto per chiusura anticipata riga DDT ' . $ddtRow->getId());

                    if ($ddt->getSubcontractor()) {
                        $wasteMovement->setContact($ddt->getSubcontractor());
                    } elseif ($ddt->getClient()) {
                        $wasteMovement->setContact($ddt->getClient());
                    }

                    $this->doctrine->persist($wasteMovement);
                }
            }

            $batch->setCompleted(true);
            $this->doctrine->persist($batch);
        }

        $reason = $this->doctrine->getRepository(WarehouseMovementReason::class)->findOneBy(['name' => 'Reso ' . $ddt->getReason()->getName()]);
        if (!$reason) {
            $reasonTypeIn = $this->doctrine->getRepository(WarehouseMovementReasonType::class)->findOneBy(['movement_type' => '+']);
            if (!$reasonTypeIn) {
                $reasonTypeIn = $this->doctrine->getRepository(WarehouseMovementReasonType::class)->findOneBy(['movement_type' => 'Carico']);
            }
            if ($reasonTypeIn) {
                $reason = $this->doctrine->getRepository(WarehouseMovementReason::class)->findOneBy(['reason_type' => $reasonTypeIn]);
            }
        }
        if (!$reason) {
            return $this->doResponse->doErrorJsonResponse('Causale di magazzino "Carico" non trovata', 400);
        }

        $warehouseMovement = new WarehouseMovement();
        $warehouseMovement->setBatch($batch);
        $warehouseMovement->setQuantity($quantity);
        $warehouseMovement->setPiece($pieces);
        $warehouseMovement->setReason($reason);
        $warehouseMovement->setDdtNumber($ddtRow->getDdt()->getDdtNumber());
        $warehouseMovement->setDdtDate($ddtRow->getDdt()->getDdtDate());
        $warehouseMovement->setDate(new \DateTime());
        $warehouseMovement->setMovementNote('Rientro riga DDT ' . $ddtRow->getId() . ' del DDT ' . $ddtRow->getDdt()->getDdtNumber());

        if ($ddt->getSubcontractor()) {
            $warehouseMovement->setContact($ddt->getSubcontractor());
        } elseif ($ddt->getClient()) {
            $warehouseMovement->setContact($ddt->getClient());
        }

        $this->doctrine->persist($warehouseMovement);
        $this->doctrine->flush();

        $results = $this->groupSerializer->serializeGroup([$ddtRow], 'ddt_row_detail');
        return new JsonResponse($this->doResponse->doResponse($results[0]));
    }

    #[Route('/ddt-row/massive-return',
        name: 'post_ddt_row_massive_return',
        methods: ['POST'])]
    public function postDdtRowMassiveReturn(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? $request->request->all();

        if (!isset($data['rows']) || !is_array($data['rows'])) {
            return $this->doResponse->doErrorJsonResponse('Parametro "rows" mancante o non valido', 400);
        }

        $ddtRowRepository = $this->doctrine->getRepository(DdtRow::class);
        $reasonRepository = $this->doctrine->getRepository(WarehouseMovementReason::class);
        $reasonTypeRepository = $this->doctrine->getRepository(WarehouseMovementReasonType::class);

        $processedRows = [];

        foreach ($data['rows'] as $rowData) {
            $id = $rowData['id'] ?? null;
            if (!$id) {
                continue;
            }

            $ddtRow = $ddtRowRepository->find($id);
            if (!$ddtRow) {
                continue;
            }

            $batch = $ddtRow->getBatch();
            if (!$batch) {
                continue;
            }

            $quantity = $rowData['quantity'] ?? $ddtRow->getQuantityOut() ?? $ddtRow->getQuantity();
            $pieces = $rowData['pieces'] ?? $ddtRow->getPiecesOut() ?? $ddtRow->getPieces();
            $pieces = (float)$pieces; // Assicura che sia trattato come float per i calcoli

            $ddt = $ddtRow->getDdt();
            $closed = $rowData['closed'] ?? false;

            if ($closed) {
                $outPieces = $ddtRow->getPiecesOut() ?? $ddtRow->getPieces() ?? 0;
                $outQuantity = $ddtRow->getQuantityOut() ?? $ddtRow->getQuantity() ?? 0;

                $wastePieces = $outPieces - $pieces;
                $wasteQuantity = $outQuantity - $quantity;

                if ($wastePieces > 0.01) {
                    $wasteReason = $this->doctrine->getRepository(WarehouseMovementReason::class)->findOneBy(['name' => 'Scarto']);
                    if (!$wasteReason) {
                        $reasonTypeOut = $this->doctrine->getRepository(WarehouseMovementReasonType::class)->findOneBy(['movement_type' => 'Scarico']);
                        if (!$reasonTypeOut) {
                            $reasonTypeOut = $this->doctrine->getRepository(WarehouseMovementReasonType::class)->findOneBy(['movement_type' => '-']);
                        }
                        if ($reasonTypeOut) {
                            $wasteReason = $this->doctrine->getRepository(WarehouseMovementReason::class)->findOneBy(['reason_type' => $reasonTypeOut]);
                        }
                    }

                    if ($wasteReason) {
                        $wasteMovement = new WarehouseMovement();
                        $wasteMovement->setBatch($batch);
                        $wasteMovement->setQuantity((float)$wasteQuantity);
                        $wasteMovement->setPiece((float)$wastePieces);
                        $wasteMovement->setReason($wasteReason);
                        $wasteMovement->setDdtNumber($ddt->getDdtNumber());
                        $wasteMovement->setDdtDate($ddt->getDdtDate());
                        $wasteMovement->setDate(new \DateTime());
                        $wasteMovement->setMovementNote('Scarto per chiusura anticipata riga DDT ' . $ddtRow->getId() . ' (massivo)');

                        if ($ddt->getSubcontractor()) {
                            $wasteMovement->setContact($ddt->getSubcontractor());
                        } elseif ($ddt->getClient()) {
                            $wasteMovement->setContact($ddt->getClient());
                        }

                        $this->doctrine->persist($wasteMovement);
                    }
                }

                $batch->setCompleted(true);
                $this->doctrine->persist($batch);
            }

            $reason = $this->doctrine->getRepository(WarehouseMovementReason::class)->findOneBy(['name' => 'Reso ' . $ddt->getReason()->getName()]);
            if (!$reason) {
                $reasonTypeIn = $this->doctrine->getRepository(WarehouseMovementReasonType::class)->findOneBy(['movement_type' => 'Carico']);
                if ($reasonTypeIn) {
                    $reason = $this->doctrine->getRepository(WarehouseMovementReason::class)->findOneBy(['reason_type' => $reasonTypeIn]);
                }
            }
            if (!$reason) {
                return $this->doResponse->doErrorJsonResponse('Causale di magazzino "Carico" non trovata', 400);
            }

            $warehouseMovement = new WarehouseMovement();
            $warehouseMovement->setBatch($batch);
            $warehouseMovement->setQuantity($quantity);
            $warehouseMovement->setPiece($pieces);
            $warehouseMovement->setReason($reason);
            $warehouseMovement->setDdtNumber($ddtRow->getDdt()->getDdtNumber());
            $warehouseMovement->setDdtDate($ddtRow->getDdt()->getDdtDate());
            $warehouseMovement->setDate(new \DateTime());
            $warehouseMovement->setMovementNote('Rientro riga DDT ' . $ddtRow->getId() . ' del DDT ' . $ddtRow->getDdt()->getDdtNumber());

            if ($ddt->getSubcontractor()) {
                $warehouseMovement->setContact($ddt->getSubcontractor());
            } elseif ($ddt->getClient()) {
                $warehouseMovement->setContact($ddt->getClient());
            }

            $this->doctrine->persist($warehouseMovement);

            $processedRows[] = $ddtRow;
        }

        $this->doctrine->flush();

        $results = $this->groupSerializer->serializeGroup($processedRows, 'ddt_row_detail');
        return new JsonResponse($this->doResponse->doResponse($results));
    }

    #[Route('/ddt-row/{id}/transfer',
        name: 'post_ddt_row_transfer',
        requirements: ['id' => '\d+'],
        methods: ['POST'])]
    public function postDdtRowTransfer(int $id, Request $request): JsonResponse
    {
        $ddtRow = $this->doctrine->getRepository(DdtRow::class)->find($id);
        if (!$ddtRow) {
            return $this->doResponse->doErrorJsonResponse('Riga DDT non trovata', 404);
        }

        $batch = $ddtRow->getBatch();
        if (!$batch) {
            return $this->doResponse->doErrorJsonResponse('Lotto non trovato per questa riga', 404);
        }

        $data = json_decode($request->getContent(), true) ?? $request->request->all();

        $subcontractor = $this->doctrine->getRepository(Contact::class)->find($data['subcontractor_id']);
        if (!$subcontractor) {
            return $this->doResponse->doErrorJsonResponse('Terzista non trovato', 404);
        }

        $pieces = (int)($data['pieces'] ?? $ddtRow->getPieces());
        $quantity = (float)($data['quantity'] ?? null);
        $closed = $data['closed'] ?? false;

        if ($quantity === null && isset($data['pieces'])) {
            // Se sono stati passati i pezzi ma non la quantità, ricalcolo la quantità proporzionalmente
            $oldPieces = $ddtRow->getPieces();
            $oldQuantity = $ddtRow->getQuantity();
            if ($oldPieces > 0) {
                $quantity = ($oldQuantity / $oldPieces) * $pieces;
            } else {
                $quantity = 0.0;
            }
        } elseif ($quantity === null) {
            $quantity = $ddtRow->getQuantity();
        }

        $ddt = $ddtRow->getDdt();

        if ($closed) {
            // Calcolo pezzi mancanti per lo scarto
            $outPieces = $ddtRow->getPiecesOut() ?? $ddtRow->getPieces() ?? 0;
            $outQuantity = $ddtRow->getQuantityOut() ?? $ddtRow->getQuantity() ?? 0;

            $wastePieces = $outPieces - $pieces;
            $wasteQuantity = $outQuantity - $quantity;

            if ($wastePieces > 0.01) {
                $wasteReason = $this->doctrine->getRepository(WarehouseMovementReason::class)->findOneBy(['name' => 'Scarto']);
                if (!$wasteReason) {
                    $reasonTypeOut = $this->doctrine->getRepository(WarehouseMovementReasonType::class)->findOneBy(['movement_type' => 'Scarico']);
                    if (!$reasonTypeOut) {
                        $reasonTypeOut = $this->doctrine->getRepository(WarehouseMovementReasonType::class)->findOneBy(['movement_type' => '-']);
                    }
                    if ($reasonTypeOut) {
                        $wasteReason = $this->doctrine->getRepository(WarehouseMovementReason::class)->findOneBy(['reason_type' => $reasonTypeOut]);
                    }
                }

                if ($wasteReason) {
                    $wasteMovement = new WarehouseMovement();
                    $wasteMovement->setBatch($batch);
                    $wasteMovement->setQuantity((float)$wasteQuantity);
                    $wasteMovement->setPiece((float)$wastePieces);
                    $wasteMovement->setReason($wasteReason);
                    $wasteMovement->setDdtNumber($ddtRow->getDdt()->getDdtNumber());
                    $wasteMovement->setDdtDate($ddtRow->getDdt()->getDdtDate());
                    $wasteMovement->setDate(new \DateTime());
                    $wasteMovement->setMovementNote('Scarto per chiusura anticipata riga DDT ' . $ddtRow->getId() . ' durante trasferimento');

                    if ($ddt->getSubcontractor()) {
                        $wasteMovement->setContact($ddt->getSubcontractor());
                    } elseif ($ddt->getClient()) {
                        $wasteMovement->setContact($ddt->getClient());
                    }

                    $this->doctrine->persist($wasteMovement);
                }
            }

            $batch->setCompensationWaste(($batch->getCompensationWaste() ?? 0) + $wastePieces);
            $batch->setCompleted(true);
            $this->doctrine->persist($batch);
        }

        // 1. GESTIONE RIENTRO (MOVIMENTO INGRESSO)
        $reasonReturn = $this->doctrine->getRepository(WarehouseMovementReason::class)->findOneBy(['name' => 'Reso ' . $ddt->getReason()->getName()]);
        if (!$reasonReturn) {
            $reasonTypeIn = $this->doctrine->getRepository(WarehouseMovementReasonType::class)->findOneBy(['movement_type' => 'Carico']);
            if ($reasonTypeIn) {
                $reasonReturn = $this->doctrine->getRepository(WarehouseMovementReason::class)->findOneBy(['reason_type' => $reasonTypeIn]);
            }
        }

        if (!$reasonReturn) {
            return $this->doResponse->doErrorJsonResponse('Causale di magazzino per il rientro non trovata', 400);
        }

        $movementIn = new WarehouseMovement();
        $movementIn->setBatch($batch);
        $movementIn->setQuantity($quantity);
        $movementIn->setPiece($pieces);
        $movementIn->setReason($reasonReturn);
        $movementIn->setDdtNumber($ddtRow->getDdt()->getDdtNumber());
        $movementIn->setDdtDate($ddtRow->getDdt()->getDdtDate());
        $movementIn->setDate(new \DateTime());
        $movementIn->setMovementNote('Rientro per trasferimento da riga DDT ' . $ddtRow->getId());
        if ($ddtRow->getDdt()->getSubcontractor()) {
            $movementIn->setContact($ddtRow->getDdt()->getSubcontractor());
        }

        $this->doctrine->persist($movementIn);

        // Aggiorno stock del lotto per il rientro
        $batch->setStockQuantity($batch->getStockQuantity() + $quantity);
        $batch->setStockItems($batch->getStockItems() + $pieces);

        // 2. CREAZIONE NUOVO DDT IN USCITA
        $newDdt = new Ddt();
        $newDdt->setSubcontractor($subcontractor);
        $newDdt->setDdtNumber($data['ddt_number'] ?? ('TRF-' . time()));
        $newDdt->setDdtDate(new \DateTime());

        $ddtReason = $this->doctrine->getRepository(DdtReason::class)->findOneBy(['name' => 'Conto Lavorazione']);
        if (!$ddtReason) {
            $ddtReason = $this->doctrine->getRepository(DdtReason::class)->findOneBy([]); // Prendo la prima se non trovo quella specifica
        }
        if ($ddtReason) {
            $newDdt->setReason($ddtReason);
        }

        $this->doctrine->persist($newDdt);

        // 3. CREAZIONE NUOVA RIGA DDT
        $newDdtRow = new DdtRow();
        $newDdtRow->setDdt($newDdt);
        $newDdtRow->setBatch($batch);
        $newDdtRow->setQuantity($quantity);
        $newDdtRow->setPieces($pieces);
        $newDdtRow->setQuantityOut($quantity);
        $newDdtRow->setPiecesOut($pieces);
        $newDdtRow->setMeasurementUnit($ddtRow->getMeasurementUnit());
        $newDdtRow->setCurrency($ddtRow->getCurrency());
        $newDdtRow->setPrice($ddtRow->getPrice());
        $newDdtRow->setCurrencyPrice($ddtRow->getCurrencyPrice());
        $newDdtRow->setCurrencyExchange($ddtRow->getCurrencyExchange());
        $newDdtRow->setSelection($ddtRow->getSelection());

        if (isset($data['processing_ids']) && is_array($data['processing_ids'])) {
            foreach ($data['processing_ids'] as $pId) {
                $processing = $this->doctrine->getRepository(Processing::class)->find($pId);
                if ($processing) {
                    $ddtRowProcessing = new DdtRowProcessing();
                    $ddtRowProcessing->setDdtRow($newDdtRow);
                    $ddtRowProcessing->setProcessing($processing);
                    $this->doctrine->persist($ddtRowProcessing);
                    $newDdtRow->addDdtRowProcessing($ddtRowProcessing);
                }
            }
        } else {
            foreach ($ddtRow->getDdtRowProcessings() as $oldDrp) {
                $newDrp = new DdtRowProcessing();
                $newDrp->setDdtRow($newDdtRow);
                $newDrp->setProcessing($oldDrp->getProcessing());
                $this->doctrine->persist($newDrp);
                $newDdtRow->addDdtRowProcessing($newDrp);
            }
        }

        $this->calculatePrices($newDdtRow);
        $this->doctrine->persist($newDdtRow);

        // 4. MOVIMENTO USCITA PER NUOVA RIGA
        $reasonTransfer = $this->doctrine->getRepository(WarehouseMovementReason::class)->findOneBy(['name' => 'Invio in Lavorazione']);
        if (!$reasonTransfer) {
            $reasonTypeOut = $this->doctrine->getRepository(WarehouseMovementReasonType::class)->findOneBy(['movement_type' => 'Out']);
            if ($reasonTypeOut) {
                $reasonTransfer = $this->doctrine->getRepository(WarehouseMovementReason::class)->findOneBy(['reason_type' => $reasonTypeOut]);
            }
        }

        if ($reasonTransfer) {
            $movementOut = new WarehouseMovement();
            $movementOut->setBatch($batch);
            $movementOut->setQuantity($quantity);
            $movementOut->setPiece($pieces);
            $movementOut->setReason($reasonTransfer);
            $movementOut->setDdtNumber($newDdt->getDdtNumber());
            $movementOut->setDdtDate($newDdt->getDdtDate());
            $movementOut->setDate(new \DateTime());
            $movementOut->setMovementNote('Trasferimento da riga DDT ' . $ddtRow->getId());
            $movementOut->setContact($subcontractor);
            $this->doctrine->persist($movementOut);
        }

        $this->doctrine->flush();

        $results = $this->groupSerializer->serializeGroup([$newDdtRow], 'ddt_row_detail');
        return new JsonResponse($this->doResponse->doResponse($results[0]));
    }

    private function handleRelations(DdtRow $ddtRow, array &$data): void
    {
        if (isset($data['ddt_id'])) {
            $ddt = $this->doctrine->getRepository(Ddt::class)->find($data['ddt_id']);
            if ($ddt) {
                $ddtRow->setDdt($ddt);
            }
            unset($data['ddt_id']);
        }

        if (isset($data['batch_id'])) {
            $batch = $this->doctrine->getRepository(Batch::class)->find($data['batch_id']);
            if ($batch) {
                $ddtRow->setBatch($batch);
                $batch->setCompleted(false);
                $this->doctrine->persist($batch);
            }
            unset($data['batch_id']);
        }

        if (isset($data['measurement_unit_id'])) {
            $mu = $this->doctrine->getRepository(MeasurementUnit::class)->find($data['measurement_unit_id']);
            if ($mu) {
                $ddtRow->setMeasurementUnit($mu);
            }
            unset($data['measurement_unit_id']);
        }

        if (isset($data['currency_id'])) {
            $currency = $this->doctrine->getRepository(Currency::class)->find($data['currency_id']);
            if ($currency) {
                $ddtRow->setCurrency($currency);
            }
            unset($data['currency_id']);
        }

        if (isset($data['selection_id'])) {
            $selection = $this->doctrine->getRepository(Selection::class)->find($data['selection_id']);
            if ($selection) {
                $ddtRow->setSelection($selection);
            }
            unset($data['selection_id']);
        }

        if (array_key_exists('processing_ids', $data)) {
            foreach ($ddtRow->getDdtRowProcessings() as $oldDrp) {
                $this->doctrine->remove($oldDrp);
            }
            $ddtRow->getDdtRowProcessings()->clear();

            $processingIds = $data['processing_ids'];

            if (is_string($processingIds)) {
                $processingIds = array_filter(
                    array_map('trim', explode(',', $processingIds)),
                    static fn (string $value): bool => $value !== ''
                );
            }

            if (is_array($processingIds)) {
                foreach ($processingIds as $pId) {
                    $processing = $this->doctrine->getRepository(Processing::class)->find((int) $pId);
                    if ($processing) {
                        $ddtRowProcessing = new DdtRowProcessing();
                        $ddtRowProcessing->setDdtRow($ddtRow);
                        $ddtRowProcessing->setProcessing($processing);
                        $this->doctrine->persist($ddtRowProcessing);
                        $ddtRow->addDdtRowProcessing($ddtRowProcessing);
                    }
                }
            }

            unset($data['processing_ids']);
        }

        // Supporto retrocompatibilità se viene inviato un singolo processing_id
        if (array_key_exists('processing_id', $data)) {
            foreach ($ddtRow->getDdtRowProcessings() as $oldDrp) {
                $this->doctrine->remove($oldDrp);
            }
            $ddtRow->getDdtRowProcessings()->clear();

            if ($data['processing_id'] !== null && $data['processing_id'] !== '') {
                $processing = $this->doctrine->getRepository(Processing::class)->find((int) $data['processing_id']);
                if ($processing) {
                    $ddtRowProcessing = new DdtRowProcessing();
                    $ddtRowProcessing->setDdtRow($ddtRow);
                    $ddtRowProcessing->setProcessing($processing);
                    $this->doctrine->persist($ddtRowProcessing);
                    $ddtRow->addDdtRowProcessing($ddtRowProcessing);
                }
            }

            unset($data['processing_id']);
        }
    }

    private function calculatePrices(DdtRow $ddtRow): void
    {
        $quantity = $ddtRow->getQuantity() ?: 0.0;
        $currencyPrice = $ddtRow->getCurrencyPrice(); // Valuta estera per unità
        $currencyChange = $ddtRow->getCurrencyExchange() ?: 1.0; // quanta valuta estera per 1 EUR

        // Se arriva currencyPrice, ricalcola sempre price (EUR)
        if ($currencyPrice !== null) {
            $price = $currencyChange != 0 ? round($currencyPrice / $currencyChange, 5) : 0.0;
            $ddtRow->setPrice(round($price, 2));
            $ddtRow->setCurrencyExchange($currencyChange);
            $ddtRow->setCurrencyPrice(round($currencyPrice, 2));
        } else {
            $price = $ddtRow->getPrice() ?: 0.0;
            $currencyPrice = round($price * $currencyChange, 2);
            $ddtRow->setCurrencyPrice($currencyPrice);
        }

        // Totali - Usiamo quantity per uniformare con le righe ordine
        $ddtRow->setTotalValue(round($price * $quantity, 2));
        $ddtRow->setCurrencyTotalValue(round($currencyPrice * $quantity, 2));
    }

    private function getConvertedQuantity(float $quantity, ?MeasurementUnit $startUm, ?MeasurementUnit $endUm): float
    {
        if (!$startUm || !$endUm || $startUm->getId() === $endUm->getId()) {
            return $quantity;
        }

        $coefficient = $this->doctrine->getRepository(MeasurementUnitCoefficient::class)->findOneBy([
            'start_um' => $startUm,
            'end_um' => $endUm
        ]);

        if ($coefficient) {
            return $quantity * $coefficient->getCoefficient();
        }

        // Fallback per conversioni standard MQ <-> PQ se non trovate nel DB
        if ($startUm->getPrefix() === 'MQ' && $endUm->getPrefix() === 'PQ') {
            return $quantity * 10.764;
        }
        if ($startUm->getPrefix() === 'PQ' && $endUm->getPrefix() === 'MQ') {
            return $quantity / 10.764;
        }

        return $quantity;
    }

    #[Route('/ddt-row/external-processing-movements',
        name: 'get_external_processing_movements',
        methods: ['GET'])]
    public function getExternalProcessingMovements(Request $request): JsonResponse
    {
        $batchCode = $request->query->get('batch_code') ? (string)$request->query->get('batch_code') : null;
        $subcontractorId = $request->query->get('subcontractor_id') ? (int)$request->query->get('subcontractor_id') : null;
        $startDateStr = $request->query->get('start_date');
        $endDateStr = $request->query->get('end_date');

        $startDate = $startDateStr ? \DateTime::createFromFormat('Y-m-d', $startDateStr) : null;
        if ($startDate) $startDate->setTime(0, 0, 0);

        $endDate = $endDateStr ? \DateTime::createFromFormat('Y-m-d', $endDateStr) : null;
        if ($endDate) $endDate->setTime(23, 59, 59);

        $ddtRowRepository = $this->doctrine->getRepository(DdtRow::class);
        $lots = $ddtRowRepository->findExternalProcessingLots($subcontractorId, $startDate, $endDate, $batchCode);

        $groupedData = [];
        $wmRepo = $this->doctrine->getRepository(WarehouseMovement::class);
        $wmReasonRepo = $this->doctrine->getRepository(WarehouseMovementReason::class);

        // Cerchiamo le causali di rientro (tipo 'Carico')
        $returnReasons = $wmReasonRepo->createQueryBuilder('r')
            ->join('r.reason_type', 'rt')
            ->andWhere('rt.movement_type = :type')
            ->setParameter('type', 'Carico')
            ->getQuery()
            ->getResult();

        foreach ($lots as $row) {
            $subcontractor = $row->getDdt()->getSubcontractor();
            if (!$subcontractor) continue;

            $sId = $subcontractor->getId();
            if (!isset($groupedData[$sId])) {
                $groupedData[$sId] = [
                    'subcontractor' => $this->groupSerializer->serializeGroup($subcontractor, 'client_summary_print'),
                    'rows' => []
                ];
            }

            $serializedRow = $this->groupSerializer->serializeGroup($row, ['client_summary_print', 'external_processing_print']);

            // Cerchiamo i movimenti di rientro per questo lotto e terzista
            $returns = $wmRepo->createQueryBuilder('wm')
                ->andWhere('wm.batch = :batch')
                ->andWhere('wm.contact = :contact')
                ->andWhere('wm.reason IN (:reasons)')
                ->andWhere('wm.date >= :ddtDate')
                ->setParameter('batch', $row->getBatch())
                ->setParameter('contact', $subcontractor)
                ->setParameter('reasons', $returnReasons)
                ->setParameter('ddtDate', $row->getDdt()->getDdtDate())
                ->orderBy('wm.date', 'DESC')
                ->getQuery()
                ->getResult();

            $totalReturnedPieces = 0;
            $lastReturnDate = null;
            foreach ($returns as $return) {
                $totalReturnedPieces += $return->getPiece();
                if (!$lastReturnDate || $return->getDate() > $lastReturnDate) {
                    $lastReturnDate = $return->getDate();
                }
            }

            $serializedRow['returned_pieces'] = $totalReturnedPieces;
            $serializedRow['last_return_date'] = $lastReturnDate ? $lastReturnDate->format('Y-m-d') : null;

            $groupedData[$sId]['rows'][] = $serializedRow;
        }

        // Ordina i terzisti per nome
        usort($groupedData, fn($a, $b) => $a['subcontractor']['name'] <=> $b['subcontractor']['name']);

        return new JsonResponse($this->doResponse->doResponse(array_values($groupedData)));
    }

    #[Route('/ddt-row/update-all-out-values',
        name: 'post_ddt_row_update_all_out_values',
        methods: ['POST'])]
    public function updateAllOutValues(): JsonResponse
    {
        $ddtRowRepository = $this->doctrine->getRepository(DdtRow::class);
        $ddtRows = $ddtRowRepository->findAll();

        $updatedCount = 0;
        foreach ($ddtRows as $ddtRow) {
            $ddt = $ddtRow->getDdt();
            $batch = $ddtRow->getBatch();

            if (!$ddt || !$batch) {
                continue;
            }

            $movements = $batch->getWarehouseMovements();
            $firstMovementOut = null;

            foreach ($movements as $movement) {
                $reason = $movement->getReason();
                if ($reason && 
                    $reason->getReasonType() && 
                    $reason->getReasonType()->getMovementType() === 'Scarico' &&
                    $movement->getDdtNumber() === $ddt->getDdtNumber()
                ) {
                    $firstMovementOut = $movement;
                    break; 
                }
            }

            // Fallback se non troviamo il movimento per numero DDT, prendiamo il primo scarico del lotto
            if (!$firstMovementOut) {
                foreach ($movements as $movement) {
                    $reason = $movement->getReason();
                    if ($reason && 
                        $reason->getReasonType() && 
                        $reason->getReasonType()->getMovementType() === 'Scarico'
                    ) {
                        $firstMovementOut = $movement;
                        break;
                    }
                }
            }

            if ($firstMovementOut) {
                $ddtRow->setPiecesOut(abs($firstMovementOut->getPiece() ?? 0));
                $ddtRow->setQuantityOut(abs($firstMovementOut->getQuantity() ?? 0));
                $this->doctrine->persist($ddtRow);
                $updatedCount++;
            }
        }

        $this->doctrine->flush();

        return new JsonResponse($this->doResponse->doResponse(['updated_count' => $updatedCount]));
    }
}

