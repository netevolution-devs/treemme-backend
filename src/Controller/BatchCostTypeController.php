<?php

namespace App\Controller;

use App\Entity\BatchCostType;
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

final class BatchCostTypeController extends AbstractController
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

    #[Route('/batch-cost-type/{id}',
        name: 'get_batch_cost_type',
        defaults: ['id' => null],
        requirements: ['id' => '\d*'],
        methods: ['GET', 'HEAD'])]
    public function getBatchCostType(?int $id): JsonResponse
    {
        $batchCostTypeRepository = $this->doctrine->getRepository(BatchCostType::class);

        if ($id) {
            $batchCostType = [$batchCostTypeRepository->find($id)];
            if (!$batchCostType[0]) {
                return new JsonResponse($this->doResponse->doErrorResponse('BatchCostType not found', 404));
            }
        } else {
            $batchCostType = $batchCostTypeRepository->findBy([], ['id' => 'DESC']);
        }
        $results = $this->groupSerializer->serializeGroup($batchCostType, $id ? 'batch_cost_type_detail' : 'batch_cost_type_list');

        if ($id) {
            return new JsonResponse($this->doResponse->doResponse($results[0]));
        }
        return new JsonResponse($this->doResponse->doResponse($results));
    }

    #[Route('/batch-cost-type',
        name: 'post_batch_cost_type',
        methods: ['POST'])]
    public function postBatchCostType(
        Request            $request,
        ValidatorInterface $validator,
    ): JsonResponse
    {
        $data = $request->request->all();
        $batchCostType = new BatchCostType();

        try {
            $batchCostType = $this->createMethodsByInput->createMethods($batchCostType, $data);

            $errors = $validator->validate($batchCostType);
            if (count($errors) > 0) {
                $errors = $this->validatorOutputFormatter->formatOutput($errors);
                return new JsonResponse($this->doResponse->doErrorResponse($errors));
            }

            $em = $this->doctrine;
            $em->persist($batchCostType);
            $em->flush();

            $result = $this->groupSerializer->serializeGroup($batchCostType, 'batch_cost_type_detail');
            return new JsonResponse($this->doResponse->doResponse($result));

        } catch (\Exception $e) {
            return new JsonResponse($this->doResponse->doErrorResponse($e->getMessage()));
        }
    }

    #[Route('/batch-cost-type/{id}',
        name: 'put_batch_cost_type',
        methods: ['PUT'])]
    public function modifyBatchCostType(
        Request            $request,
        ValidatorInterface $validator,
        int                $id,
    ): JsonResponse
    {
        $data = $request->toArray();
        $batchCostType = $this->doctrine->getRepository(BatchCostType::class)->find($id);

        if (!$batchCostType) {
            return new JsonResponse($this->doResponse->doErrorResponse('BatchCostType not found', 404));
        }

        try {
            $batchCostType = $this->createMethodsByInput->createMethods($batchCostType, $data);

            $errors = $validator->validate($batchCostType);
            if (count($errors) > 0) {
                $errors = $this->validatorOutputFormatter->formatOutput($errors);
                return new JsonResponse($this->doResponse->doErrorResponse($errors));
            }

            $this->doctrine->persist($batchCostType);
            $this->doctrine->flush();

            $result = $this->groupSerializer->serializeGroup($batchCostType, 'batch_cost_type_detail');
            return new JsonResponse($this->doResponse->doResponse($result));
        } catch (\Exception $e) {
            return new JsonResponse($this->doResponse->doErrorResponse($e->getMessage()));
        }
    }

    #[Route('/batch-cost-type/{id}',
        name: 'delete_batch_cost_type',
        methods: ['DELETE'])]
    public function deleteBatchCostType(int $id): JsonResponse
    {
        $batchCostType = $this->doctrine->getRepository(BatchCostType::class)->find($id);
        if (!$batchCostType) {
            return new JsonResponse($this->doResponse->doErrorResponse('BatchCostType not found', 404));
        }

        $this->doctrine->remove($batchCostType);
        $this->doctrine->flush();

        return new JsonResponse($this->doResponse->doResponse('delete_successfully'));
    }
}
