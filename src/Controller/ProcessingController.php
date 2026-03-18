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

    #[Route('/processing', name: 'app_processing_index', methods: ['GET'])]
    #[Route('/processing/{id}', name: 'app_processing_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function index(?int $id = null): JsonResponse
    {
        $repository = $this->doctrine->getRepository(Processing::class);
        if ($id) {
            $processing = $repository->find($id);
            if (!$processing) {
                return new JsonResponse($this->doResponse->doErrorResponse('Lavorazione non trovata', status_code: 404));
            }
            $results = $this->groupSerializer->serializeGroup($processing, 'processing_detail');
            return new JsonResponse($this->doResponse->doResponse($results));
        } else {
            $processings = $repository->findBy([], ['id' => 'DESC']);
            $results = $this->groupSerializer->serializeGroup($processings, 'processing_list');
            return new JsonResponse($this->doResponse->doResponse($results));
        }
    }

    #[Route('/processing', name: 'app_processing_create', methods: ['POST'])]
    public function create(Request $request, ValidatorInterface $validator): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? $request->request->all();
        $processing = new Processing();

        try {
            $this->mapDataToEntity($processing, $data);
        } catch (\Exception $e) {
            return new JsonResponse($this->doResponse->doErrorResponse($e->getMessage()));
        }

        $errors = $validator->validate($processing);
        if (count($errors) > 0) {
            $formattedErrors = $this->validatorOutputFormatter->formatOutput($errors);
            return new JsonResponse($this->doResponse->doErrorResponse($formattedErrors));
        }

        $this->doctrine->persist($processing);
        $this->doctrine->flush();

        $results = $this->groupSerializer->serializeGroup($processing, 'processing_detail');
        return new JsonResponse($this->doResponse->doResponse($results));
    }

    #[Route('/processing/{id}', name: 'app_processing_update', methods: ['PUT', 'PATCH'])]
    public function update(Request $request, int $id, ValidatorInterface $validator): JsonResponse
    {
        $processing = $this->doctrine->getRepository(Processing::class)->find($id);

        if (!$processing) {
            return new JsonResponse($this->doResponse->doErrorResponse('Lavorazione non trovata', status_code: 404));
        }

        $data = json_decode($request->getContent(), true) ?? $request->request->all();

        try {
            $this->mapDataToEntity($processing, $data);
        } catch (\Exception $e) {
            return new JsonResponse($this->doResponse->doErrorResponse($e->getMessage()));
        }

        $errors = $validator->validate($processing);
        if (count($errors) > 0) {
            $formattedErrors = $this->validatorOutputFormatter->formatOutput($errors);
            return new JsonResponse($this->doResponse->doErrorResponse($formattedErrors));
        }

        $this->doctrine->flush();

        $results = $this->groupSerializer->serializeGroup($processing, 'processing_detail');
        return new JsonResponse($this->doResponse->doResponse($results));
    }

    #[Route('/processing/{id}', name: 'app_processing_delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $processing = $this->doctrine->getRepository(Processing::class)->find($id);
        if (!$processing) {
            return new JsonResponse($this->doResponse->doErrorResponse('Lavorazione non trovata', status_code: 404));
        }

        $this->doctrine->remove($processing);
        $this->doctrine->flush();

        return new JsonResponse($this->doResponse->doResponse(null, status: 'Lavorazione eliminata correttamente'));
    }

    /**
     * @throws \Exception
     */
    private function mapDataToEntity(Processing $processing, array $data): void
    {
        // Mappatura campi semplici
        $this->createMethodsByInput->createMethods($processing, $data);
    }
}
