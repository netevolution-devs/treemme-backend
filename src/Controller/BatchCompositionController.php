<?php

namespace App\Controller;

use App\Entity\BatchComposition;
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
                return new JsonResponse($this->doResponse->doErrorResponse('BatchComposition not found', 404));
            }
        } else {
            $batchComposition = $batchCompositionRepository->findBy([], ['id' => 'DESC']);
        }
        $results = $this->groupSerializer->serializeGroup($batchComposition, $id ? 'batch_composition_detail' : 'batch_composition_list');

        if ($id) {
            return new JsonResponse($this->doResponse->doResponse($results[0]));
        }
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
            $batchComposition = $this->createMethodsByInput->createMethods($batchComposition, $data);

            $errors = $validator->validate($batchComposition);
            if (count($errors) > 0) {
                $errors = $this->validatorOutputFormatter->formatOutput($errors);
                return new JsonResponse($this->doResponse->doErrorResponse($errors));
            }

            $em = $this->doctrine;
            $em->persist($batchComposition);
            $em->flush();

            $result = $this->groupSerializer->serializeGroup($batchComposition, 'batch_composition_detail');
            return new JsonResponse($this->doResponse->doResponse($result));

        } catch (\Exception $e) {
            return new JsonResponse($this->doResponse->doErrorResponse($e->getMessage()));
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
            return new JsonResponse($this->doResponse->doErrorResponse('BatchComposition not found', 404));
        }

        try {
            $batchComposition = $this->createMethodsByInput->createMethods($batchComposition, $data);

            $errors = $validator->validate($batchComposition);
            if (count($errors) > 0) {
                $errors = $this->validatorOutputFormatter->formatOutput($errors);
                return new JsonResponse($this->doResponse->doErrorResponse($errors));
            }

            $this->doctrine->persist($batchComposition);
            $this->doctrine->flush();

            $result = $this->groupSerializer->serializeGroup($batchComposition, 'batch_composition_detail');
            return new JsonResponse($this->doResponse->doResponse($result));
        } catch (\Exception $e) {
            return new JsonResponse($this->doResponse->doErrorResponse($e->getMessage()));
        }
    }

    #[Route('/batch-composition/{id}',
        name: 'delete_batch_composition',
        methods: ['DELETE'])]
    public function deleteBatchComposition(int $id): JsonResponse
    {
        $batchComposition = $this->doctrine->getRepository(BatchComposition::class)->find($id);
        if (!$batchComposition) {
            return new JsonResponse($this->doResponse->doErrorResponse('BatchComposition not found', 404));
        }

        $this->doctrine->remove($batchComposition);
        $this->doctrine->flush();

        return new JsonResponse($this->doResponse->doResponse('delete_successfully'));
    }
}
