<?php

namespace App\Controller;

use App\Entity\Contact;
use App\Entity\Ddt;
use App\Entity\DdtReason;
use App\Entity\DdtRow;
use App\Entity\Batch;
use App\Entity\MeasurementUnit;
use App\Entity\Currency;
use App\Entity\Processing;
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

    public function __construct(
        CreateMethodsByInput     $createMethodsByInput,
        EntityManagerInterface   $entityManager,
        DoResponseService        $doResponseService,
        GroupSerializerService   $groupSerializer,
        ValidatorOutputFormatter $validatorOutputFormatter,
        PdfGeneratorService      $pdfGenerator
    ) {
        $this->createMethodsByInput = $createMethodsByInput;
        $this->doctrine = $entityManager;
        $this->doResponse = $doResponseService;
        $this->groupSerializer = $groupSerializer;
        $this->validatorOutputFormatter = $validatorOutputFormatter;
        $this->pdfGenerator = $pdfGenerator;
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
    public function getDdtRowSubcontractingNotReturned(): JsonResponse
    {
        $ddtRowRepository = $this->doctrine->getRepository(DdtRow::class);
        $ddtRows = $ddtRowRepository->findAll();

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
            if (!$batch) {
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

            // Restituisce il lotto solamente quando l'ultimo movimento ha il movementReason->Name == al ddtReason->Name
            // Oppure se è un "Reso {ddtReason->Name}" o un "Rientro da Lavorazione" (per trasferimento)
            if ($lastMovementReasonName !== $ddtReasonName && 
                $lastMovementReasonName !== $resoReasonName &&
                $lastMovementReasonName !== $rientroTrasferimentoReasonName
            ) {
                continue;
            }

            $firstMovementOut = null;
            $returnedPieces = 0;
            $returnedQuantity = 0;

            foreach ($movementsArray as $movement) {
                $reason = $movement->getReason();
                $reasonName = $reason?->getName();
                $reasonTypeName = $reason?->getReasonType()?->getName();

                // Identifica il primo movimento in uscita con la causale del DDT che corrisponde al numero DDT della riga
                if ($firstMovementOut === null && 
                    $reasonName === $ddtReasonName && 
                    $reasonTypeName === 'Scarico'
                ) {
                    $firstMovementOut = $movement;
                    continue;
                }

                // Somma i pezzi rientrati per i movimenti di "Reso" corrispondenti o rientri per trasferimento
                if ($firstMovementOut !== null) {
                    if ($reasonName === $resoReasonName || $reasonName === $rientroTrasferimentoReasonName) {
                        // Verifichiamo che il rientro sia riferito a questo DDT (tramite note o ddt_number nel movimento)
                        // Nel caso del trasferimento, impostiamo ddt_number del movimento uguale a quello della riga originale
                        if ($movement->getDdtNumber() === $ddt->getDdtNumber()) {
                            $mPieces = abs($movement->getPiece() ?? 0);
                            $returnedPieces += $mPieces;
                            
                            // Nel trasferimento bisogna prendere la quantity di un pezzo e moltiplicarlo per i pezzi trasferiti, questo vale anche per i resi....
                            $unitQuantity = 0;
                            $pOut = $ddtRow->getPiecesOut() ?? 0;
                            $qOut = $ddtRow->getQuantityOut() ?? 0;
                            if ($pOut > 0) {
                                $unitQuantity = $qOut / $pOut;
                            }
                            $returnedQuantity += ($unitQuantity * $mPieces);
                        }
                    }
                }
            }

            if ($firstMovementOut !== null) {
                $outPieces = $ddtRow->getPiecesOut() ?? abs($firstMovementOut->getPiece() ?? 0);
                $outQuantity = $ddtRow->getQuantityOut() ?? abs($firstMovementOut->getQuantity() ?? 0);
                
                $remainingPieces = $outPieces - $returnedPieces;
                $remainingQuantity = $outQuantity - $returnedQuantity;

                if ($lastMovementReasonName === $ddtReasonName || $remainingPieces > 0) {
                    $results = $this->groupSerializer->serializeGroup($ddtRow, 'ddt_row_list');
                    $results['stock_pieces'] = $remainingPieces;
                    $results['stock_quantity'] = $remainingQuantity;
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
        $clientId = $request->query->get('client_id') ? (int)$request->query->get('client_id') : null;
        $startDateStr = $request->query->get('start_date');
        $endDateStr = $request->query->get('end_date');

        $startDate = $startDateStr ? \DateTime::createFromFormat('Y-m-d', $startDateStr) : null;
        if ($startDate) $startDate->setTime(0, 0, 0);

        $endDate = $endDateStr ? \DateTime::createFromFormat('Y-m-d', $endDateStr) : null;
        if ($endDate) $endDate->setTime(0, 0, 0);

        $ddtRowRepository = $this->doctrine->getRepository(DdtRow::class);
        $soldLots = $ddtRowRepository->findSoldLots($clientId, $startDate, $endDate);

        $results = $this->groupSerializer->serializeGroup($soldLots, 'ddt_row_list_sold');

        return new JsonResponse($this->doResponse->doResponse($results));
    }

    #[Route('/ddt-row/sold/pdf',
        name: 'get_sold_lots_pdf',
        methods: ['GET'])]
    public function getSoldLotsPdf(Request $request): \Symfony\Component\HttpFoundation\Response
    {
        $clientId = $request->query->get('client_id') ? (int)$request->query->get('client_id') : null;
        $startDateStr = $request->query->get('start_date');
        $endDateStr = $request->query->get('end_date');

        $startDate = $startDateStr ? \DateTime::createFromFormat('Y-m-d', $startDateStr) : null;
        if ($startDate) $startDate->setTime(0, 0, 0);

        $endDate = $endDateStr ? \DateTime::createFromFormat('Y-m-d', $endDateStr) : null;
        if ($endDate) $endDate->setTime(0, 0, 0);

        $ddtRowRepository = $this->doctrine->getRepository(DdtRow::class);
        $soldLots = $ddtRowRepository->findSoldLots($clientId, $startDate, $endDate);

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

        $batch->setStockQuantity($batch->getStockQuantity() - $convertedQuantity);
        $batch->setStockItems($batch->getStockItems() - $ddtRow->getPieces());

        $this->updateBatchSqFtAverageFound($batch);

        $this->doctrine->persist($batch);

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

        if ($oldBatch && $newBatch && $oldBatch->getId() === $newBatch->getId()) {
            $diffPieces = $ddtRow->getPieces() - $oldPieces;
            
            $oldConvertedQuantity = $this->getConvertedQuantity($oldQuantity, $ddtRow->getMeasurementUnit(), $newBatch->getMeasurementUnit());
            $newConvertedQuantity = $this->getConvertedQuantity($ddtRow->getQuantity(), $ddtRow->getMeasurementUnit(), $newBatch->getMeasurementUnit());
            $diffQuantity = $newConvertedQuantity - $oldConvertedQuantity;

            $newBatch->setStockItems($newBatch->getStockItems() - $diffPieces);
            $newBatch->setStockQuantity($newBatch->getStockQuantity() - $diffQuantity);

            $this->updateBatchSqFtAverageFound($newBatch);
            $this->doctrine->persist($newBatch);
        } else {
            if ($oldBatch) {
                $oldBatch->setStockItems($oldBatch->getStockItems() + $oldPieces);
                $oldBatchConvertedQuantity = $this->getConvertedQuantity($oldQuantity, $ddtRow->getMeasurementUnit(), $oldBatch->getMeasurementUnit());
                $oldBatch->setStockQuantity($oldBatch->getStockQuantity() + $oldBatchConvertedQuantity);
                $this->updateBatchSqFtAverageFound($oldBatch);
                $this->doctrine->persist($oldBatch);
            }
            if ($newBatch) {
                $newBatch->setStockItems($newBatch->getStockItems() - $ddtRow->getPieces());
                $newBatchConvertedQuantity = $this->getConvertedQuantity($ddtRow->getQuantity(), $ddtRow->getMeasurementUnit(), $newBatch->getMeasurementUnit());
                $newBatch->setStockQuantity($newBatch->getStockQuantity() - $newBatchConvertedQuantity);
                $this->updateBatchSqFtAverageFound($newBatch);
                $this->doctrine->persist($newBatch);
            }
        }

        // Aggiornamento movimento di magazzino associato
        $warehouseMovement = $this->doctrine->getRepository(WarehouseMovement::class)->findOneBy(["movement_note" => $ddtRow->getRowNote()]);

        if($warehouseMovement == null){
            $warehouseMovement = $this->doctrine->getRepository(WarehouseMovement::class)->findOneBy(["movement_note" => 'Riga DDT ' . $ddtRow->getId()]);
        }

        $warehouseMovement->setBatch($newBatch);
        $convertedQuantity = $this->getConvertedQuantity($ddtRow->getQuantity(), $ddtRow->getMeasurementUnit(), $newBatch?->getMeasurementUnit());
        $warehouseMovement->setQuantity($convertedQuantity);
        $warehouseMovement->setPiece($ddtRow->getPieces());

        $this->doctrine->persist($warehouseMovement);

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
        if ($batch) {
            $batch->setStockItems($batch->getStockItems() + $ddtRow->getPieces());
            $batch->setStockQuantity($batch->getStockQuantity() + $ddtRow->getQuantity());
            $this->updateBatchSqFtAverageFound($batch);
            $this->doctrine->persist($batch);
        }

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

        $ddt = $ddtRow->getDdt();

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
        $this->doctrine->flush();

        $batch->setStockQuantity($batch->getStockQuantity() + $quantity);
        $batch->setStockItems($batch->getStockItems() + $pieces);

        $this->doctrine->persist($batch);

        $this->updateBatchSqFtAverageFound($batch);

        $this->doctrine->persist($batch);
        $this->doctrine->flush();

        $results = $this->groupSerializer->serializeGroup([$ddtRow], 'ddt_row_detail');
        return new JsonResponse($this->doResponse->doResponse($results[0]));
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

        $quantity = (float)($data['quantity'] ?? $ddtRow->getQuantity());
        $pieces = (int)($data['pieces'] ?? $ddtRow->getPieces());

        $ddt = $ddtRow->getDdt();

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
        $newDdtRow->setMeasurementUnit($ddtRow->getMeasurementUnit());
        $newDdtRow->setCurrency($ddtRow->getCurrency());
        $newDdtRow->setPrice($ddtRow->getPrice());
        $newDdtRow->setCurrencyPrice($ddtRow->getCurrencyPrice());
        $newDdtRow->setCurrencyExchange($ddtRow->getCurrencyExchange());
        $newDdtRow->setSelection($ddtRow->getSelection());

        if (isset($data['processing_id'])) {
            $processing = $this->doctrine->getRepository(Processing::class)->find($data['processing_id']);
            if ($processing) {
                $newDdtRow->setProcessing($processing);
            }
        } else {
            $newDdtRow->setProcessing($ddtRow->getProcessing());
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

            // Aggiorno stock del lotto per l'uscita
            $batch->setStockQuantity($batch->getStockQuantity() - $quantity);
            $batch->setStockItems($batch->getStockItems() - $pieces);
        }

        $this->updateBatchSqFtAverageFound($batch);
        $this->doctrine->persist($batch);

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
        if (isset($data['processing_id'])) {
            $processing = $this->doctrine->getRepository(Processing::class)->find($data['processing_id']);
            if ($processing) {
                $ddtRow->setProcessing($processing);
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
            $price = $currencyChange != 0 ? round($currencyPrice / $currencyChange, 2) : 0.0;
            $ddtRow->setPrice($price);
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

    private function updateBatchSqFtAverageFound(Batch $batch): void
    {
        if ($batch->getMeasurementUnit()) {
            $measurementUnit = $batch->getMeasurementUnit();

            if ($measurementUnit->getPrefix() == 'MQ') {
                $coefficientUm = $measurementUnit->getMeasurementUnitCoefficients()->first();
                if ($batch->getStockItems() > 0 && $batch->getStockQuantity() > 0 && $coefficientUm) {
                    $batch->setSqFtAverageFound($batch->getStockItems() / ($coefficientUm->getCoefficient() * $batch->getStockQuantity()));
                } else {
                    $batch->setSqFtAverageFound(0.0);
                }
            } elseif ($batch->getMeasurementUnit()->getPrefix() == 'PQ') {
                if ($batch->getStockItems() > 0 && $batch->getStockQuantity() > 0) {
                    $batch->setSqFtAverageFound($batch->getStockItems() / $batch->getStockQuantity());
                } else {
                    $batch->setSqFtAverageFound(0.0);
                }
            }
        }
    }
}

