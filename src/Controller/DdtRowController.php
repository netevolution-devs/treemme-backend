<?php

namespace App\Controller;

use App\Entity\Contact;
use App\Entity\Ddt;
use App\Entity\DdtRow;
use App\Entity\Batch;
use App\Entity\MeasurementUnit;
use App\Entity\Currency;
use App\Entity\Processing;
use App\Entity\Selection;
use App\Entity\WarehouseMovement;
use App\Entity\WarehouseMovementReason;
use App\Entity\WarehouseMovementReasonType;
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

final class DdtRowController extends AbstractController
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
        ValidatorOutputFormatter $validatorOutputFormatter
    ) {
        $this->createMethodsByInput = $createMethodsByInput;
        $this->doctrine = $entityManager;
        $this->doResponse = $doResponseService;
        $this->groupSerializer = $groupSerializer;
        $this->validatorOutputFormatter = $validatorOutputFormatter;
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
                return new JsonResponse($this->doResponse->doErrorResponse('Riga DDT non trovata', 404));
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

            // Calcola il nome del movimento di "Reso" atteso
            $resoReasonName = "Reso " . $ddtReasonName;

            // Restituisce il lotto solamente quando l'ultimo movimento ha il movementReason->Name == al ddtReason->Name
            // Oppure se è un "Reso {ddtReason->Name}"
            if ($lastMovementReasonName !== $ddtReasonName && $lastMovementReasonName !== $resoReasonName) {
                continue;
            }

            $firstMovementOut = null;
            $returnedPieces = 0;

            foreach ($movementsArray as $movement) {
                $reason = $movement->getReason();
                $reasonName = $reason?->getName();
                $reasonTypeName = $reason?->getReasonType()?->getName();

                // Identifica il primo movimento in uscita con la causale del DDT
                if ($firstMovementOut === null && $reasonName === $ddtReasonName && $reasonTypeName === 'Scarico') {
                    $firstMovementOut = $movement;
                    continue;
                }

                // Somma i pezzi rientrati per i movimenti di "Reso" corrispondenti
                if ($firstMovementOut !== null && $reasonName === $resoReasonName) {
                    $returnedPieces += abs($movement->getPiece() ?? 0);
                }
            }

            if ($firstMovementOut !== null) {
                $outPieces = abs($firstMovementOut->getPiece() ?? 0);
                $remainingPieces = $outPieces - $returnedPieces;

                if ($lastMovementReasonName === $ddtReasonName || $remainingPieces > 0) {
                    $results = $this->groupSerializer->serializeGroup($ddtRow, 'ddt_row_list');
                    $results['stock_pieces'] = $remainingPieces;
                    $ddtRowsSelected[] = $results;
                }
            }
        }

        return new JsonResponse($this->doResponse->doResponse($ddtRowsSelected));
    }

    #[Route('/ddt-row',
        name: 'post_ddt_row',
        methods: ['POST'])]
    public function postDdtRow(Request $request, ValidatorInterface $validator): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? $request->request->all();
        $ddtRow = new DdtRow();

        try {
            $this->handleRelations($ddtRow, $data);
            $this->createMethodsByInput->createMethods($ddtRow, $data);
        } catch (\Exception $e) {
            return new JsonResponse($this->doResponse->doErrorResponse($e->getMessage(), 400));
        }

        $errors = $validator->validate($ddtRow);
        if (count($errors) > 0) {
            return new JsonResponse($this->doResponse->doErrorResponse($this->validatorOutputFormatter->formatOutput($errors), 400));
        }

        $this->calculatePrices($ddtRow);

        if($ddtRow->getHalfPiece() !== null) {
            $ddtRow->setWholePiece($ddtRow->getPieces() - ($ddtRow->getHalfPiece() * 2));
        } else {
            $ddtRow->setWholePiece($ddtRow->getPieces());
        }

        $this->doctrine->persist($ddtRow);
        $this->doctrine->flush();

        $batch = $ddtRow->getBatch();

        $batch->setStockQuantity($batch->getStockQuantity() - $ddtRow->getQuantity());
        $batch->setStockItems($batch->getStockItems() - $ddtRow->getPieces());

        $this->updateBatchSqFtAverageFound($batch);

        $this->doctrine->persist($batch);

        $ddt = $ddtRow->getDdt();

        $wearhouseMovement = new WarehouseMovement();
        $wearhouseMovement->setBatch($batch);
        $wearhouseMovement->setQuantity($ddtRow->getQuantity());
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
            return new JsonResponse($this->doResponse->doErrorResponse('Riga DDT non trovata', 404));
        }

        $oldPieces = $ddtRow->getPieces();
        $oldQuantity = $ddtRow->getQuantity();
        $oldBatch = $ddtRow->getBatch();

        $data = $request->toArray();

        try {
            $this->handleRelations($ddtRow, $data);
            $this->createMethodsByInput->createMethods($ddtRow, $data);
        } catch (\Exception $e) {
            return new JsonResponse($this->doResponse->doErrorResponse($e->getMessage(), 400));
        }

        $errors = $validator->validate($ddtRow);
        if (count($errors) > 0) {
            return new JsonResponse($this->doResponse->doErrorResponse($this->validatorOutputFormatter->formatOutput($errors), 400));
        }

        $this->calculatePrices($ddtRow);

        if($ddtRow->getHalfPiece() !== null) {
            $ddtRow->setWholePiece($ddtRow->getPieces() - ($ddtRow->getHalfPiece() * 2));
        } else {
            $ddtRow->setWholePiece($ddtRow->getPieces());
        }

        $newBatch = $ddtRow->getBatch();

        if ($oldBatch && $newBatch && $oldBatch->getId() === $newBatch->getId()) {
            $diffPieces = $ddtRow->getPieces() - $oldPieces;
            $diffQuantity = $ddtRow->getQuantity() - $oldQuantity;

            $newBatch->setStockItems($newBatch->getStockItems() - $diffPieces);
            $newBatch->setStockQuantity($newBatch->getStockQuantity() - $diffQuantity);

            $this->updateBatchSqFtAverageFound($newBatch);
            $this->doctrine->persist($newBatch);
        } else {
            if ($oldBatch) {
                $oldBatch->setStockItems($oldBatch->getStockItems() + $oldPieces);
                $oldBatch->setStockQuantity($oldBatch->getStockQuantity() + $oldQuantity);
                $this->updateBatchSqFtAverageFound($oldBatch);
                $this->doctrine->persist($oldBatch);
            }
            if ($newBatch) {
                $newBatch->setStockItems($newBatch->getStockItems() - $ddtRow->getPieces());
                $newBatch->setStockQuantity($newBatch->getStockQuantity() - $ddtRow->getQuantity());
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
        $warehouseMovement->setQuantity($ddtRow->getQuantity());
        $warehouseMovement->setPiece($ddtRow->getPieces());

        $this->doctrine->persist($warehouseMovement);

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
            return new JsonResponse($this->doResponse->doErrorResponse('Riga DDT non trovata', 404));
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
            return new JsonResponse($this->doResponse->doErrorResponse('Riga DDT non trovata', 404));
        }

        $batch = $ddtRow->getBatch();
        if (!$batch) {
            return new JsonResponse($this->doResponse->doErrorResponse('Lotto non trovato per questa riga', 404));
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
            return new JsonResponse($this->doResponse->doErrorResponse('Causale di magazzino "Carico" non trovata', 400));
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

//        $diffPieces = $pieces - $ddtRow->getPieces();
//        if ($diffPieces !== 0) {
//            $reasonName = $diffPieces > 0 ? "Compensazione positiva" : "Compensazione negativa";
//            $compReason = $this->doctrine->getRepository(WarehouseMovementReason::class)->findOneBy(['name' => $reasonName]);
//
//            if (!$compReason) {
//                $compReason = new WarehouseMovementReason();
//                $compReason->setName($reasonName);
//                $movementType = $diffPieces > 0 ? 'Compensazione positiva' : 'Compensazione negativa';
//                $reasonType = $this->doctrine->getRepository(WarehouseMovementReasonType::class)->findOneBy(['movement_type' => $movementType]);
//                if (!$reasonType) {
//                    $reasonType = $this->doctrine->getRepository(WarehouseMovementReasonType::class)->findOneBy(['movement_type' => $movementType === 'Carico' ? 'Carico' : 'Scarico']);
//                }
//                if (!$reasonType) {
//                    $reasonType = new WarehouseMovementReasonType();
//                    $reasonType->setName($movementType);
//                    $reasonType->setMovementType($movementType === 'Compensazione positiva' ? 'Compensazione positiva' : 'Compensazione negativa');
//                    $this->doctrine->persist($reasonType);
//                }
//                $compReason->setReasonType($reasonType);
//                $this->doctrine->persist($compReason);
//            }
//
//            $compMovement = new WarehouseMovement();
//            $compMovement->setBatch($batch);
//            $compMovement->setQuantity(0);
//            $compMovement->setPiece($diffPieces);
//            $compMovement->setReason($compReason);
//            $compMovement->setDdtNumber($ddtRow->getDdt()->getDdtNumber());
//            $compMovement->setDdtDate($ddtRow->getDdt()->getDdtDate());
//            $compMovement->setDate(new \DateTime());
//            $compMovement->setMovementNote('Compensazione riga DDT ' . $ddtRow->getId() . ' del DDT ' . $ddtRow->getDdt()->getDdtNumber());
//
//            if ($ddt->getSubcontractor()) {
//                $compMovement->setContact($ddt->getSubcontractor());
//            } elseif ($ddt->getClient()) {
//                $compMovement->setContact($ddt->getClient());
//            }
//            $this->doctrine->persist($compMovement);
//
//            $batch->setStockItems($batch->getStockItems() + $diffPieces);
//            $this->doctrine->persist($batch);
//        }

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
            return new JsonResponse($this->doResponse->doErrorResponse('Riga DDT non trovata', 404));
        }

        $batch = $ddtRow->getBatch();
        if (!$batch) {
            return new JsonResponse($this->doResponse->doErrorResponse('Lotto non trovato per questa riga', 404));
        }

        $data = json_decode($request->getContent(), true) ?? $request->request->all();

        $subcontractor = $this->doctrine->getRepository(Contact::class)->find($data['subcontractor_id']);
        $quantity = $data['quantity'] ?? $ddtRow->getQuantity();
        $pieces = $data['pieces'] ?? $ddtRow->getPieces();

        $reasonTransfer = $this->doctrine->getRepository(WarehouseMovementReason::class)->findOneBy(['name' => 'Reso C/O Lavorazione']);
        if (!$reasonTransfer) {
            $reasonTypeTransfer = $this->doctrine->getRepository(WarehouseMovementReasonType::class)->findOneBy(['movement_type' => 'Out']);
            if ($reasonTypeTransfer) {
                $reasonTransfer = $this->doctrine->getRepository(WarehouseMovementReason::class)->findOneBy(['reason_type' => $reasonTypeTransfer]);
            }
        }

        if (!$reasonTransfer) {
            return new JsonResponse($this->doResponse->doErrorResponse('Causale di magazzino "Carico" non trovata', 400));
        }

        $warehouseMovement = new WarehouseMovement();
        $warehouseMovement->setBatch($batch);
        $warehouseMovement->setQuantity($quantity);
        $warehouseMovement->setPiece($pieces);
        $warehouseMovement->setReason($reasonTransfer);
        $warehouseMovement->setDdtNumber($ddtRow->getDdt()->getDdtNumber());
        $warehouseMovement->setDdtDate($ddtRow->getDdt()->getDdtDate());
        $warehouseMovement->setDate(new \DateTime());
        $warehouseMovement->setMovementNote($data['row_note'] ?: 'Rientro riga DDT ' . $ddtRow->getId());
        $warehouseMovement->setContact($subcontractor);

        $this->doctrine->persist($warehouseMovement);
        $this->doctrine->flush();

        $results = $this->groupSerializer->serializeGroup([$ddtRow], 'ddt_row_detail');
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
        $currencyChange = $ddtRow->getCurrencyChange() ?: 1.0; // quanta valuta estera per 1 EUR

        // Se arriva currencyPrice, ricalcola sempre price (EUR)
        if ($currencyPrice !== null) {
            $price = $currencyChange != 0 ? round($currencyPrice / $currencyChange, 2) : 0.0;
            $ddtRow->setPrice($price);
            $ddtRow->setCurrencyChange($currencyChange);
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
