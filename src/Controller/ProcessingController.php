<?php

namespace App\Controller;

use App\Entity\Processing;
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

class ProcessingController extends AbstractController
{
    public function __construct(
        private CreateMethodsByInput    $createMethodsByInput,
        private EntityManagerInterface  $doctrine,
        private DoResponseService       $doResponse,
        private GroupSerializerService  $groupSerializer,
        private ValidatorOutputFormatter $validatorOutputFormatter
    ) {
    }

    #[Route('/processing/{id}', name: 'get_processing', methods: ['GET'], defaults: ['id' => null])]
    public function getProcessing(?int $id): JsonResponse
    {
        $repository = $this->doctrine->getRepository(Processing::class);
        if ($id) {
            $processing = $repository->find($id);
            if (!$processing) {
                return new JsonResponse($this->doResponse->doErrorResponse('Processing not found', 404));
            }
        } else {
            $processing = $repository->findBy([], ['id' => 'DESC']);
        }
        $results = $this->groupSerializer->serializeGroup($processing, $id ? 'processing_detail' : 'processing_list');

        if ($id) {
            return new JsonResponse($this->doResponse->doResponse($results[0]));
        }
        return new JsonResponse($this->doResponse->doResponse($results));
    }

    #[Route('/processing', name: 'post_processing', methods: ['POST'])]
    public function postProcessing(Request $request, ValidatorInterface $validator): JsonResponse
    {
        $data = $request->request->all();
        $processing = new Processing();

        try {
            $processing = $this->createMethodsByInput->createMethods($processing, $data);

            $errors = $validator->validate($processing);
            if (count($errors) > 0) {
                $errors = $this->validatorOutputFormatter->formatOutput($errors);
                return new JsonResponse($this->doResponse->doErrorResponse($errors));
            }

            $this->doctrine->persist($processing);
            $this->doctrine->flush();

            $result = $this->groupSerializer->serializeGroup($processing, 'processing_detail');
            return new JsonResponse($this->doResponse->doResponse($result));

        } catch (\Exception $e) {
            return new JsonResponse($this->doResponse->doErrorResponse($e->getMessage()));
        }
    }

    #[Route('/processing/{id}', name: 'put_processing', methods: ['PUT'])]
    public function modifyProcessing(Request $request, ValidatorInterface $validator, int $id): JsonResponse
    {
        $data = $request->toArray();
        $processing = $this->doctrine->getRepository(Processing::class)->find($id);

        if (!$processing) {
            return new JsonResponse($this->doResponse->doErrorResponse('Processing not found', 404));
        }

        try {
            $processing = $this->createMethodsByInput->createMethods($processing, $data);

            $errors = $validator->validate($processing);
            if (count($errors) > 0) {
                $errors = $this->validatorOutputFormatter->formatOutput($errors);
                return new JsonResponse($this->doResponse->doErrorResponse($errors));
            }

            $this->doctrine->persist($processing);
            $this->doctrine->flush();

            $result = $this->groupSerializer->serializeGroup($processing, 'processing_detail');
            return new JsonResponse($this->doResponse->doResponse($result));
        } catch (\Exception $e) {
            return new JsonResponse($this->doResponse->doErrorResponse($e->getMessage()));
        }
    }

    #[Route('/processing/{id}', name: 'delete_processing', methods: ['DELETE'])]
    public function deleteProcessing(int $id): JsonResponse
    {
        $processing = $this->doctrine->getRepository(Processing::class)->find($id);
        if (!$processing) {
            return new JsonResponse($this->doResponse->doErrorResponse('Processing not found', 404));
        }

        $this->doctrine->remove($processing);
        $this->doctrine->flush();

        return new JsonResponse($this->doResponse->doResponse('delete_successfully'));
    }
}
