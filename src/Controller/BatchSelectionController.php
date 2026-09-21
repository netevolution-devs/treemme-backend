<?php

namespace App\Controller;

use App\Entity\Batch;
use App\Entity\BatchComposition;
use App\Entity\BatchSelection;
use App\Entity\LeatherThickness;
use App\Entity\Selection;
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
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'batch')]
class BatchSelectionController extends AbstractController
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

    #[Route('/batch-selection', name: 'post_batch_selection', methods: ['POST'])]
    public function postBatchSelection(
        Request            $request,
        ValidatorInterface $validator,
    ): JsonResponse
    {
        $data = $request->request->all();
        if (empty($data)) {
            $data = $request->toArray();
        }

        $batchSelection = new BatchSelection();

        try {
            $batchSelection->setPieces($data['pieces']);
            $batchSelection->setStockPieces($data['pieces']);
            $batchSelection->setNote($data['note'] ?? null);

            if (isset($data['batch_id'])) {
                $fatherBatch = $this->doctrine->getRepository(Batch::class)->find($data['batch_id']);
                if ($fatherBatch) {
                    $batchSelection->setBatch($fatherBatch);
                }


                unset($data['batch_id']);
            }

            if (isset($data['selection_id'])) {
                $selection = $this->doctrine->getRepository(Selection::class)->find($data['selection_id']);
                if ($selection) {
                    $batchSelection->setSelection($selection);
                }
                unset($data['selection_id']);
            }

            if(isset($data['thickness_id'])){
                $thickness = $this->doctrine->getRepository(LeatherThickness::class)->find($data['thickness_id']);
                if ($thickness) {
                    $batchSelection->setThickness($thickness);
                }
            }

            $fatherBatch = $batchSelection->getBatch();
            $thickness = $batchSelection->getThickness();

            // Calcolo quantità totale prelevata in base alle composizioni (se presenti)
            $calculatedQty = 0.0;
            // Determina la quantità per pezzo da usare, basata prima sui dati del batch padre
            $perPieceFromBatch = null;
            if ($fatherBatch) {
                if (method_exists($fatherBatch, 'getSqFtAverageExpected')) {
                    $avg = $fatherBatch->getSqFtAverageExpected();
                    if ($avg !== null && $avg > 0) {
                        $perPieceFromBatch = (float)$avg;
                    }
                }
                if ($perPieceFromBatch === null) {
                    if (method_exists($fatherBatch, 'getQuantity') && method_exists($fatherBatch, 'getPieces')) {
                        $fbQty = $fatherBatch->getQuantity();
                        $fbPieces = $fatherBatch->getPieces();
                        if ($fbQty !== null && $fbPieces !== null && $fbPieces > 0) {
                            $perPieceFromBatch = (float)$fbQty / (float)$fbPieces;
                        }
                    }
                }
            }

            if ($fatherBatch && $thickness) {
                $compositions = $this->doctrine->getRepository(BatchComposition::class)->findBy([
                    'father_batch' => $fatherBatch,
                    'thickness' => $thickness
                ], ['id' => 'ASC']);

                $remainingPieces = $batchSelection->getPieces();

                foreach ($compositions as $comp) {
                    if ($remainingPieces <= 0) break;

                    $available = $comp->getFatherBatchPieceAvailable();
                    if ($available <= 0) continue;

                    $toTake = min($available, $remainingPieces);

                    $comp->setFatherBatchPieceAvailable($available - $toTake);

                    // Calcola quantità per pezzo: priorità al valore derivato dal batch padre
                    $qtyToTake = 0.0;
                    if ($perPieceFromBatch !== null) {
                        $qtyToTake = $perPieceFromBatch * $toTake;
                        // Aggiorna anche la quantità disponibile della composizione, se valorizzata
                        $currentAvailQty = $comp->getFatherBatchQuantityAvailable();
                        if ($currentAvailQty !== null) {
                            $comp->setFatherBatchQuantityAvailable($currentAvailQty - $qtyToTake);
                        }
                    } else {
                        // Fallback: usa i valori della composizione (available o totali)
                        $piecesAvail = $comp->getFatherBatchPieceAvailable();
                        $qtyAvail = $comp->getFatherBatchQuantityAvailable();
                        $piecesTotal = $comp->getFatherBatchPiece();
                        $qtyTotal = $comp->getFatherBatchQuantity();

                        if ($piecesAvail !== null && $piecesAvail > 0 && $qtyAvail !== null) {
                            $qtyPerPiece = $qtyAvail / $piecesAvail;
                            $qtyToTake = $qtyPerPiece * $toTake;
                            $comp->setFatherBatchQuantityAvailable($qtyAvail - $qtyToTake);
                        } elseif ($piecesTotal !== null && $piecesTotal > 0 && $qtyTotal !== null) {
                            $qtyPerPiece = $qtyTotal / $piecesTotal;
                            $qtyToTake = $qtyPerPiece * $toTake;
                            $currentAvail = $comp->getFatherBatchQuantityAvailable() ?? $qtyTotal;
                            $comp->setFatherBatchQuantityAvailable($currentAvail - $qtyToTake);
                        }
                    }

                    $calculatedQty += $qtyToTake;

                    $remainingPieces -= $toTake;
                    $this->doctrine->persist($comp);
                }
            }

            // Se non sono state trovate composizioni o non è stato possibile determinare la quantità dal ciclo
            // ma abbiamo un valore per pezzo dal batch padre, calcolo comunque la quantità attesa
            if ($calculatedQty <= 0 && $perPieceFromBatch !== null) {
                $calculatedQty = $perPieceFromBatch * (float)$batchSelection->getPieces();
            }

            if ($batchSelection->getStockPieces() === null) {
                $batchSelection->setStockPieces($batchSelection->getPieces());
            }

            if (isset($data['quantity'])) {
                $q = (float)$data['quantity'];
                $batchSelection->setQuantity(round($q, 3));
                $batchSelection->setStockQuantity(round($q, 3));
            } else {
                // Se non abbiamo input esplicito, usiamo la quantità calcolata (0 se non determinabile)
                $batchSelection->setQuantity(round($calculatedQty, 3));
                $batchSelection->setStockQuantity(round($calculatedQty, 3));
            }

            $errors = $validator->validate($batchSelection);
            if (count($errors) > 0) {
                $errors = $this->validatorOutputFormatter->formatOutput($errors);
                return $this->doResponse->doErrorJsonResponse($errors);
            }

            $this->doctrine->persist($batchSelection);
            $this->doctrine->flush();

            $result = $this->groupSerializer->serializeGroup($batchSelection, 'batch_selection_detail');
            return new JsonResponse($this->doResponse->doResponse($result));

        } catch (\Exception $e) {
            return $this->doResponse->doErrorJsonResponse($e->getMessage());
        }
    }

    private function handleRelations(BatchSelection $batchSelection, array &$data): BatchSelection
    {
        if (isset($data['batch_id'])) {
            $batch = $this->doctrine->getRepository(Batch::class)->find($data['batch_id']);
            if ($batch) {
                $batchSelection->setBatch($batch);
            }
            unset($data['batch_id']);
        }

        if (isset($data['selection_id'])) {
            $selection = $this->doctrine->getRepository(Selection::class)->find($data['selection_id']);
            if ($selection) {
                $batchSelection->setSelection($selection);
            }
            unset($data['selection_id']);
        }

        return $batchSelection;
    }
}

