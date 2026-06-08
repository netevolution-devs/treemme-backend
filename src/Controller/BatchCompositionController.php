<?php

namespace App\Controller;

use App\Service\StockService;
use App\Entity\BatchSelection;
use App\Entity\MeasurementUnitCoefficient;
use App\Entity\WarehouseMovement;
use App\Entity\WarehouseMovementReason;
use App\Entity\BatchComposition;
use App\Entity\Batch;
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

final class BatchCompositionController extends AbstractController
{
    private $createMethodsByInput;
    private $doctrine;
    private $doResponse;
    private $groupSerializer;
    private $validatorOutputFormatter;
    private $stockService;

    public function __construct(
        CreateMethodsByInput     $createMethodsByInput,
        EntityManagerInterface   $entityManager,
        DoResponseService        $doResponseService,
        GroupSerializerService   $groupSerializer,
        ValidatorOutputFormatter $validatorOutputFormatter,
        StockService             $stockService,
    )
    {
        $this->createMethodsByInput = $createMethodsByInput;
        $this->doctrine = $entityManager;
        $this->doResponse = $doResponseService;
        $this->groupSerializer = $groupSerializer;
        $this->validatorOutputFormatter = $validatorOutputFormatter;
        $this->stockService = $stockService;
    }

    #[Route('/batch-composition/{id}',
        name: 'get_batch_composition',
        defaults: ['id' => null],
        requirements: ['id' => '\d*'],
        methods: ['GET', 'HEAD'])]
    public function getBatchComposition(?int $id): JsonResponse
    {
        $batchCompositionRepository = $this->doctrine->getRepository(BatchComposition::class);

        if ($id) {
            $batchComposition = [$batchCompositionRepository->find($id)];
            if (!$batchComposition[0]) {
                return $this->doResponse->doErrorJsonResponse('BatchComposition not found', 404);
            }
        } else {
            $batchComposition = $batchCompositionRepository->findBy([], ['id' => 'ASC']);
        }
        $results = $this->groupSerializer->serializeGroup($batchComposition, $id ? 'batch_composition_detail' : 'batch_composition_list');

        if ($id) {
            return new JsonResponse($this->doResponse->doResponse($results[0]));
        }
        return new JsonResponse($this->doResponse->doResponse($results));
    }

    #[Route('/batch/{batch_id}/batch-composition',
        name: 'get_batch_composition_by_batch',
        defaults: ['batch_id' => null],
        requirements: ['batch_id' => '\d*'],
        methods: ['GET', 'HEAD'])]
    public function getBatchCompositionByBatch(?int $batch_id): JsonResponse
    {
        $batch = $this->doctrine->getRepository(Batch::class)->find($batch_id);

        if (!$batch) {
            return $this->doResponse->doErrorJsonResponse('Batch not found', 404);
        }

        $batchComposition = $this->doctrine->getRepository(BatchComposition::class)->findBy(['batch' => $batch]);
        $results = $this->groupSerializer->serializeGroup($batchComposition, 'batch_composition_list');

        return new JsonResponse($this->doResponse->doResponse($results));
    }

    #[Route('/batch-composition',
        name: 'post_batch_composition',
        methods: ['POST'])]
    public function postBatchComposition(
        Request            $request,
        ValidatorInterface $validator,
    ): JsonResponse
    {
        $data = $request->request->all();
        $batchComposition = new BatchComposition();

        try {
            $fatherBatch = null;
            $batchSelection = null;
            if (isset($data['batch_selection_id'])) {
                $batchSelection = $this->doctrine->getRepository(BatchSelection::class)->find($data['batch_selection_id']);
                $batchComposition->setSelection($batchSelection);
                if ($batchSelection) {
                    $fatherBatch = $batchSelection->getBatch();
                }

                unset($data['batch_selection_id']);
            }

            if ($fatherBatch && (!isset($data['father_batch_quantity']) || $data['father_batch_quantity'] === null || $data['father_batch_quantity'] === '')) {
                $fatherBatchPiecesToConvert = isset($data['father_batch_piece']) ? (float) $data['father_batch_piece'] : 0.0;
                
                if ($fatherBatch->getSqFtAverageExpected() > 0) {
                    $quantityToConvert = $fatherBatchPiecesToConvert * $fatherBatch->getSqFtAverageExpected();
                } elseif ($batchSelection) {
                    $fatherBatchPiecesTotal = (float) ($batchSelection->getPieces() ?? 0);
                    $fatherBatchQuantityTotal = (float) ($batchSelection->getQuantity() ?? 0);

                    if ($fatherBatchPiecesTotal > 0) {
                        $pieceQuantity = $fatherBatchQuantityTotal / $fatherBatchPiecesTotal;
                        $quantityToConvert = $fatherBatchPiecesToConvert * $pieceQuantity;
                    } else {
                        $quantityToConvert = 0.0;
                    }
                } else {
                    $fatherBatchPiecesTotal = (float) ($fatherBatch->getPieces() ?? 0);
                    $fatherBatchQuantityTotal = (float) ($fatherBatch->getQuantity() ?? 0);

                    if ($fatherBatchPiecesTotal > 0) {
                        $pieceQuantity = $fatherBatchQuantityTotal / $fatherBatchPiecesTotal;
                        $quantityToConvert = $fatherBatchPiecesToConvert * $pieceQuantity;
                    } else {
                        $quantityToConvert = 0.0;
                    }
                }

                if ($quantityToConvert !== null) {
                    $data['father_batch_quantity'] = $quantityToConvert;
                }
            }

            if ($fatherBatch && !isset($data['father_batch_id'])) {
                $data['father_batch_id'] = $fatherBatch->getId();
            }

            $batchComposition = $this->handleRelations($batchComposition, $data);
            $batchComposition = $this->createMethodsByInput->createMethods($batchComposition, $data);

            $batchComposition->setFatherBatchPieceAvailable($batchComposition->getFatherBatchPiece());
            $batchComposition->setFatherBatchQuantityAvailable($batchComposition->getFatherBatchQuantity());

            $batch = $batchComposition->getBatch();
            $childQuantity = (float) ($batchComposition->getFatherBatchQuantity() ?? 0.0);

            if ($fatherBatch && $batch) {
                $fatherUm = $fatherBatch->getMeasurementUnit();
                $childUm = $batch->getMeasurementUnit();

                if ($fatherUm && $childUm && $fatherUm->getId() !== $childUm->getId()) {
                    $coefficient = $this->doctrine->getRepository(MeasurementUnitCoefficient::class)->findOneBy([
                        'start_um' => $fatherUm,
                        'end_um' => $childUm
                    ]);

                    if ($coefficient) {
                        $childQuantity = (float) ($batchComposition->getFatherBatchQuantity() ?? 0.0) * $coefficient->getCoefficient();
                    }
                }

                $this->stockService->updateBatchAndSelectionStock($batch, null, $childQuantity, (float)$batchComposition->getFatherBatchPiece(), true);
            }

            $errors = $validator->validate($batchComposition);
            if (count($errors) > 0) {
                $errors = $this->validatorOutputFormatter->formatOutput($errors);
                return $this->doResponse->doErrorJsonResponse($errors);
            }

            if ($fatherBatch) {
                $this->stockService->updateBatchAndSelectionStock($fatherBatch, $batchSelection, -(float)$batchComposition->getFatherBatchQuantity(), -(float)$batchComposition->getFatherBatchPiece());
            }

            $em = $this->doctrine;
            $em->persist($batchComposition);
            $em->flush();

            $this->createMovements($batchComposition);

            $result = $this->groupSerializer->serializeGroup($batchComposition, 'batch_composition_detail');
            return new JsonResponse($this->doResponse->doResponse($result));

        } catch (\Exception $e) {
            return $this->doResponse->doErrorJsonResponse($e->getMessage());
        }
    }

    #[Route('/batch-composition/{id}',
        name: 'put_batch_composition',
        methods: ['PUT'])]
    public function modifyBatchComposition(
        Request            $request,
        ValidatorInterface $validator,
        int                $id,
    ): JsonResponse
    {
        $data = $request->toArray();
        $batchComposition = $this->doctrine->getRepository(BatchComposition::class)->find($id);

        if (!$batchComposition) {
            return $this->doResponse->doErrorJsonResponse('BatchComposition not found', 404);
        }

        try {
            // 1. Ripristiniamo lo stato precedente prima di applicare le modifiche
            $oldFatherBatch = $batchComposition->getFatherBatch();
            $oldBatch = $batchComposition->getBatch();
            $oldSelection = $batchComposition->getSelection();
            $oldPieces = $batchComposition->getFatherBatchPiece();
            $oldQuantity = (float)($batchComposition->getFatherBatchQuantity() ?? 0.0);

            if ($oldFatherBatch && $oldBatch) {
                // Sottraiamo dal figlio (ripristino)
                $oldChildQuantity = $oldQuantity;
                $oldFatherUm = $oldFatherBatch->getMeasurementUnit();
                $oldChildUm = $oldBatch->getMeasurementUnit();

                if ($oldFatherUm && $oldChildUm && $oldFatherUm->getId() !== $oldChildUm->getId()) {
                    $coefficient = $this->doctrine->getRepository(MeasurementUnitCoefficient::class)->findOneBy([
                        'start_um' => $oldFatherUm,
                        'end_um' => $oldChildUm
                    ]);
                    if ($coefficient) {
                        $oldChildQuantity = $oldQuantity * $coefficient->getCoefficient();
                    }
                }

                $this->stockService->updateBatchAndSelectionStock($oldBatch, null, -$oldChildQuantity, -(float)$oldPieces, true);

                // Ripristiniamo nel padre
                $this->stockService->updateBatchAndSelectionStock($oldFatherBatch, $oldSelection, $oldQuantity, (float)$oldPieces);
            }

            // 2. Applichiamo le modifiche
            if (isset($data['batch_selection_id'])) {
                $batchSelection = $this->doctrine->getRepository(BatchSelection::class)->find($data['batch_selection_id']);
                $batchComposition->setSelection($batchSelection);
                if ($batchSelection) {
                    $data['father_batch_id'] = $batchSelection->getBatch()?->getId();
                }
                unset($data['batch_selection_id']);
            }

            $batchComposition = $this->handleRelations($batchComposition, $data);
            $batchComposition = $this->createMethodsByInput->createMethods($batchComposition, $data);

            $batchComposition->setFatherBatchPieceAvailable($batchComposition->getFatherBatchPiece());
            $batchComposition->setFatherBatchQuantityAvailable($batchComposition->getFatherBatchQuantity());

            // 3. Applichiamo le nuove quantità
            $newFatherBatch = $batchComposition->getFatherBatch();
            $newBatch = $batchComposition->getBatch();
            $newSelection = $batchComposition->getSelection();
            $newPieces = $batchComposition->getFatherBatchPiece();
            $newQuantity = (float)($batchComposition->getFatherBatchQuantity() ?? 0.0);

            if ($newFatherBatch && $newBatch) {
                $newChildQuantity = $newQuantity;
                $newFatherUm = $newFatherBatch->getMeasurementUnit();
                $newChildUm = $newBatch->getMeasurementUnit();

                if ($newFatherUm && $newChildUm && $newFatherUm->getId() !== $newChildUm->getId()) {
                    $coefficient = $this->doctrine->getRepository(MeasurementUnitCoefficient::class)->findOneBy([
                        'start_um' => $newFatherUm,
                        'end_um' => $newChildUm
                    ]);
                    if ($coefficient) {
                        $newChildQuantity = $newQuantity * $coefficient->getCoefficient();
                    }
                }

                $this->stockService->updateBatchAndSelectionStock($newBatch, null, $newChildQuantity, (float)$newPieces, true);

                $this->stockService->updateBatchAndSelectionStock($newFatherBatch, $newSelection, -$newQuantity, -(float)$newPieces);
            }

            $errors = $validator->validate($batchComposition);
            if (count($errors) > 0) {
                $errors = $this->validatorOutputFormatter->formatOutput($errors);
                return $this->doResponse->doErrorJsonResponse($errors);
            }

            $this->deleteExistingMovements($batchComposition);
            $this->doctrine->persist($batchComposition);
            $this->doctrine->flush();

            $this->createMovements($batchComposition);

            $result = $this->groupSerializer->serializeGroup($batchComposition, 'batch_composition_detail');
            return new JsonResponse($this->doResponse->doResponse($result));
        } catch (\Exception $e) {
            return $this->doResponse->doErrorJsonResponse($e->getMessage());
        }
    }

    #[Route('/batch-composition/{id}',
        name: 'delete_batch_composition',
        methods: ['DELETE'])]
    public function deleteBatchComposition(int $id): JsonResponse
    {
        $batchComposition = $this->doctrine->getRepository(BatchComposition::class)->find($id);
        if (!$batchComposition) {
            return $this->doResponse->doErrorJsonResponse('BatchComposition not found', 404);
        }

        try {
            $fatherBatch = $batchComposition->getFatherBatch();
            $batch = $batchComposition->getBatch();
            $selection = $batchComposition->getSelection();
            $pieces = $batchComposition->getFatherBatchPiece();
            $quantity = (float)($batchComposition->getFatherBatchQuantity() ?? 0.0);

            if ($fatherBatch && $batch) {
                // Sottraiamo dal figlio
                $childQuantity = $quantity;
                $fatherUm = $fatherBatch->getMeasurementUnit();
                $childUm = $batch->getMeasurementUnit();

                if ($fatherUm && $childUm && $fatherUm->getId() !== $childUm->getId()) {
                    $coefficient = $this->doctrine->getRepository(MeasurementUnitCoefficient::class)->findOneBy([
                        'start_um' => $fatherUm,
                        'end_um' => $childUm
                    ]);
                    if ($coefficient) {
                        $childQuantity = $quantity * $coefficient->getCoefficient();
                    }
                }

                $this->stockService->updateBatchAndSelectionStock($batch, null, -$childQuantity, -(float)$pieces, true);

                // Ripristiniamo nel padre
                $this->stockService->updateBatchAndSelectionStock($fatherBatch, $selection, $quantity, (float)$pieces);
            }

            $this->deleteExistingMovements($batchComposition);
            $this->doctrine->remove($batchComposition);
            $this->doctrine->flush();

            return new JsonResponse($this->doResponse->doResponse('delete_successfully'));
        } catch (\Exception $e) {
            return $this->doResponse->doErrorJsonResponse($e->getMessage());
        }
    }

    private function createMovements(BatchComposition $batchComposition): void
    {
        $fatherBatch = $batchComposition->getFatherBatch();
        $batch = $batchComposition->getBatch();
        $pieces = $batchComposition->getFatherBatchPiece();
        $quantity = $batchComposition->getFatherBatchQuantity();

        if (!$fatherBatch || !$batch || $quantity === null) {
            return;
        }

        $childQuantity = $quantity;
        $fatherUm = $fatherBatch->getMeasurementUnit();
        $childUm = $batch->getMeasurementUnit();

        if ($fatherUm && $childUm && $fatherUm->getId() !== $childUm->getId()) {
            $coefficient = $this->doctrine->getRepository(MeasurementUnitCoefficient::class)->findOneBy([
                'start_um' => $fatherUm,
                'end_um' => $childUm
            ]);

            if ($coefficient) {
                $childQuantity = $quantity * $coefficient->getCoefficient();
            }
        }

        $reasonRepo = $this->doctrine->getRepository(WarehouseMovementReason::class);

        // Scarico dal padre
        $outReason = $reasonRepo->findOneBy(['name' => 'Scarico']);

        if ($outReason) {
            $outMovement = new WarehouseMovement();
            $outMovement->setBatch($fatherBatch);
            $outMovement->setReason($outReason);
            $outMovement->setQuantity($quantity);
            $outMovement->setPiece($pieces);
            $outMovement->setDate(new \DateTime());
            $outMovement->setMovementNote('Scarico per composizione lotto ' . $batch->getBatchCode() . ' (ID Comp: ' . $batchComposition->getId() . ')');
            $this->doctrine->persist($outMovement);
        }

        // Carico nel figlio
        $inReason =  $reasonRepo->findOneBy(['name' => 'Carico']);

        if ($inReason) {
            $inMovement = new WarehouseMovement();
            $inMovement->setBatch($batch);
            $inMovement->setReason($inReason);
            $inMovement->setQuantity($childQuantity);
            $inMovement->setPiece($pieces);
            $inMovement->setDate(new \DateTime());
            $inMovement->setMovementNote('Carico da composizione lotto ' . $fatherBatch->getBatchCode() . ' (ID Comp: ' . $batchComposition->getId() . ')');
            $this->doctrine->persist($inMovement);
        }

        $this->doctrine->flush();
    }

    private function deleteExistingMovements(BatchComposition $batchComposition): void
    {
        $movementRepo = $this->doctrine->getRepository(WarehouseMovement::class);
        $noteToSearch = '(ID Comp: ' . $batchComposition->getId() . ')';

        $movements = $movementRepo->createQueryBuilder('m')
            ->where('m.movement_note LIKE :note')
            ->setParameter('note', '%' . $noteToSearch . '%')
            ->getQuery()
            ->getResult();

        foreach ($movements as $movement) {
            $this->doctrine->remove($movement);
        }
        $this->doctrine->flush();
    }

    private function handleRelations(BatchComposition $batchComposition, array &$data): BatchComposition
    {
        if (isset($data['batch_id'])) {
            $batch = $this->doctrine->getRepository(Batch::class)->find($data['batch_id']);
            if ($batch) {
                $batchComposition->setBatch($batch);
            }
            unset($data['batch_id']);
        }

        if (isset($data['father_batch_id'])) {
            $fatherBatch = $this->doctrine->getRepository(Batch::class)->find($data['father_batch_id']);
            if ($fatherBatch) {
                $batchComposition->setFatherBatch($fatherBatch);
            }
            unset($data['father_batch_id']);
        }

        return $batchComposition;
    }
}

