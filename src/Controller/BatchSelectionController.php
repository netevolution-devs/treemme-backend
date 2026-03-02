<?php

namespace App\Controller;

use App\Entity\Batch;
use App\Entity\BatchSelection;
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

class BatchSelectionController extends AbstractController
{
    public function __construct(
        private CreateMethodsByInput     $createMethodsByInput,
        private EntityManagerInterface   $entityManager,
        private DoResponseService        $doResponseService,
        private GroupSerializerService   $groupSerializer,
        private ValidatorOutputFormatter $validatorOutputFormatter
    )
    {
    }

    #[Route('/batch_selection', name: 'post_batch_selection', methods: ['POST'])]
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
            $batchSelection = $this->handleRelations($batchSelection, $data);
            $batchSelection = $this->createMethodsByInput->createMethods($batchSelection, $data);

            // Se stock_pieces o stock_quantity non sono forniti, inizializzarli con i valori di pieces e quantity
            if ($batchSelection->getStockPieces() === null) {
                $batchSelection->setStockPieces($batchSelection->getPieces());
            }
            if ($batchSelection->getStockQuantity() === null) {
                $batchSelection->setStockQuantity($batchSelection->getQuantity());
            }

            $errors = $validator->validate($batchSelection);
            if (count($errors) > 0) {
                $errors = $this->validatorOutputFormatter->formatOutput($errors);
                return new JsonResponse($this->doResponseService->doErrorResponse($errors));
            }

            $this->entityManager->persist($batchSelection);
            $this->entityManager->flush();

            $result = $this->groupSerializer->serializeGroup($batchSelection, 'batch_selection_detail');
            return new JsonResponse($this->doResponseService->doResponse($result));

        } catch (\Exception $e) {
            return new JsonResponse($this->doResponseService->doErrorResponse($e->getMessage()));
        }
    }

    private function handleRelations(BatchSelection $batchSelection, array &$data): BatchSelection
    {
        if (isset($data['batch_id'])) {
            $batch = $this->entityManager->getRepository(Batch::class)->find($data['batch_id']);
            if ($batch) {
                $batchSelection->setBatch($batch);
            }
            unset($data['batch_id']);
        }

        if (isset($data['selection_id'])) {
            $selection = $this->entityManager->getRepository(Selection::class)->find($data['selection_id']);
            if ($selection) {
                $batchSelection->setSelection($selection);
            }
            unset($data['selection_id']);
        }

        return $batchSelection;
    }
}
