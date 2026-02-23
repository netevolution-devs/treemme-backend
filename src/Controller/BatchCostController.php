<?php

namespace App\Controller;

use App\Entity\BatchCost;
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

final class BatchCostController extends AbstractController
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

    #[Route('/batch-cost/{id}',
        name: 'get_batch_cost',
        defaults: ['id' => null],
        requirements: ['id' => '\d*'],
        methods: ['GET', 'HEAD'])]
    public function getBatchCost(?int $id): JsonResponse
    {
        $batchCostRepository = $this->doctrine->getRepository(BatchCost::class);

        if ($id) {
            $batchCost = [$batchCostRepository->find($id)];
            if (!$batchCost[0]) {
                return new JsonResponse($this->doResponse->doErrorResponse('BatchCost not found', 404));
            }
        } else {
            $batchCost = $batchCostRepository->findBy([], ['id' => 'DESC']);
        }
        $results = $this->groupSerializer->serializeGroup($batchCost, $id ? 'batch_cost_detail' : 'batch_cost_list');

        if ($id) {
            return new JsonResponse($this->doResponse->doResponse($results[0]));
        }
        return new JsonResponse($this->doResponse->doResponse($results));
    }

    #[Route('/batch-cost',
        name: 'post_batch_cost',
        methods: ['POST'])]
    public function postBatchCost(
        Request            $request,
        ValidatorInterface $validator,
    ): JsonResponse
    {
        $data = $request->request->all();
        $batchCost = new BatchCost();

        try {
            $batchCost = $this->createMethodsByInput->createMethods($batchCost, $data);

            $errors = $validator->validate($batchCost);
            if (count($errors) > 0) {
                $errors = $this->validatorOutputFormatter->formatOutput($errors);
                return new JsonResponse($this->doResponse->doErrorResponse($errors));
            }

            $em = $this->doctrine;
            $em->persist($batchCost);
            $em->flush();

            $result = $this->groupSerializer->serializeGroup($batchCost, 'batch_cost_detail');
            return new JsonResponse($this->doResponse->doResponse($result));

        } catch (\Exception $e) {
            return new JsonResponse($this->doResponse->doErrorResponse($e->getMessage()));
        }
    }

    #[Route('/batch-cost/{id}',
        name: 'put_batch_cost',
        methods: ['PUT'])]
    public function modifyBatchCost(
        Request            $request,
        ValidatorInterface $validator,
        int                $id,
    ): JsonResponse
    {
        $data = $request->toArray();
        $batchCost = $this->doctrine->getRepository(BatchCost::class)->find($id);

        if (!$batchCost) {
            return new JsonResponse($this->doResponse->doErrorResponse('BatchCost not found', 404));
        }

        try {
            $batchCost = $this->createMethodsByInput->createMethods($batchCost, $data);

            $errors = $validator->validate($batchCost);
            if (count($errors) > 0) {
                $errors = $this->validatorOutputFormatter->formatOutput($errors);
                return new JsonResponse($this->doResponse->doErrorResponse($errors));
            }

            $this->doctrine->persist($batchCost);
            $this->doctrine->flush();

            $result = $this->groupSerializer->serializeGroup($batchCost, 'batch_cost_detail');
            return new JsonResponse($this->doResponse->doResponse($result));
        } catch (\Exception $e) {
            return new JsonResponse($this->doResponse->doErrorResponse($e->getMessage()));
        }
    }

    #[Route('/batch-cost/{id}',
        name: 'delete_batch_cost',
        methods: ['DELETE'])]
    public function deleteBatchCost(int $id): JsonResponse
    {
        $batchCost = $this->doctrine->getRepository(BatchCost::class)->find($id);
        if (!$batchCost) {
            return new JsonResponse($this->doResponse->doErrorResponse('BatchCost not found', 404));
        }

        $this->doctrine->remove($batchCost);
        $this->doctrine->flush();

        return new JsonResponse($this->doResponse->doResponse('delete_successfully'));
    }
}
