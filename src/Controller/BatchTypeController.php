<?php

namespace App\Controller;

use App\Entity\BatchType;
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

final class BatchTypeController extends AbstractController
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

    #[Route('/batch-type/{id}',
        name: 'get_batch_type',
        defaults: ['id' => null],
        requirements: ['id' => '\d*'],
        methods: ['GET', 'HEAD'])]
    public function getBatchType(?int $id): JsonResponse
    {
        $batchTypeRepository = $this->doctrine->getRepository(BatchType::class);

        if ($id) {
            $batchType = [$batchTypeRepository->find($id)];
            if (!$batchType[0]) {
                return new JsonResponse($this->doResponse->doErrorResponse('BatchType not found', 404));
            }
        } else {
            $batchType = $batchTypeRepository->findBy([], ['id' => 'DESC']);
        }
        $results = $this->groupSerializer->serializeGroup($batchType, $id ? 'batch_type_detail' : 'batch_type_list');

        if ($id) {
            return new JsonResponse($this->doResponse->doResponse($results[0]));
        }
        return new JsonResponse($this->doResponse->doResponse($results));
    }

    #[Route('/batch-type',
        name: 'post_batch_type',
        methods: ['POST'])]
    public function postBatchType(
        Request            $request,
        ValidatorInterface $validator,
    ): JsonResponse
    {
        $data = $request->request->all();
        $batchType = new BatchType();

        try {
            $batchType = $this->createMethodsByInput->createMethods($batchType, $data);

            $now = new \DateTimeImmutable();
            $batchType->setCreatedAt($now);
            $batchType->setUpdatedAt($now);

            $errors = $validator->validate($batchType);
            if (count($errors) > 0) {
                $errors = $this->validatorOutputFormatter->formatOutput($errors);
                return new JsonResponse($this->doResponse->doErrorResponse($errors));
            }

            $em = $this->doctrine;
            $em->persist($batchType);
            $em->flush();

            $result = $this->groupSerializer->serializeGroup($batchType, 'batch_type_detail');
            return new JsonResponse($this->doResponse->doResponse($result));

        } catch (\Exception $e) {
            return new JsonResponse($this->doResponse->doErrorResponse($e->getMessage()));
        }
    }

    #[Route('/batch-type/{id}',
        name: 'put_batch_type',
        methods: ['PUT'])]
    public function modifyBatchType(
        Request            $request,
        ValidatorInterface $validator,
        int                $id,
    ): JsonResponse
    {
        $data = $request->toArray();
        $batchType = $this->doctrine->getRepository(BatchType::class)->find($id);

        if (!$batchType) {
            return new JsonResponse($this->doResponse->doErrorResponse('BatchType not found', 404));
        }

        try {
            $batchType = $this->createMethodsByInput->createMethods($batchType, $data);
            $batchType->setUpdatedAt(new \DateTimeImmutable());

            $errors = $validator->validate($batchType);
            if (count($errors) > 0) {
                $errors = $this->validatorOutputFormatter->formatOutput($errors);
                return new JsonResponse($this->doResponse->doErrorResponse($errors));
            }

            $this->doctrine->persist($batchType);
            $this->doctrine->flush();

            $result = $this->groupSerializer->serializeGroup($batchType, 'batch_type_detail');
            return new JsonResponse($this->doResponse->doResponse($result));
        } catch (\Exception $e) {
            return new JsonResponse($this->doResponse->doErrorResponse($e->getMessage()));
        }
    }

    #[Route('/batch-type/{id}',
        name: 'delete_batch_type',
        methods: ['DELETE'])]
    public function deleteBatchType(int $id): JsonResponse
    {
        $batchType = $this->doctrine->getRepository(BatchType::class)->find($id);
        if (!$batchType) {
            return new JsonResponse($this->doResponse->doErrorResponse('BatchType not found', 404));
        }

        $this->doctrine->remove($batchType);
        $this->doctrine->flush();

        return new JsonResponse($this->doResponse->doResponse('delete_successfully'));
    }
}
