<?php

namespace App\Controller;

use App\Entity\DdtReason;
use App\Entity\WarehouseMovementReason;
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

final class DdtReasonController extends AbstractController
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

    #[Route('/ddt-reason/{id}',
        name: 'get_ddt_reason',
        defaults: ['id' => null],
        requirements: ['id' => '\d+'],
        methods: ['GET', 'HEAD'])]
    public function getDdtReason(?int $id): JsonResponse
    {
        $repository = $this->doctrine->getRepository(DdtReason::class);

        if ($id) {
            $reason = $repository->find($id);
            if (!$reason) {
                return new JsonResponse($this->doResponse->doErrorResponse('Causale DDT non trovata', 404));
            }
            $results = $this->groupSerializer->serializeGroup([$reason], 'ddt_reason_detail');
            return new JsonResponse($this->doResponse->doResponse($results[0]));
        }

        $reasons = $repository->findBy([], ['name' => 'ASC']);
        $results = $this->groupSerializer->serializeGroup($reasons, 'ddt_reason_list');
        return new JsonResponse($this->doResponse->doResponse($results));
    }

    #[Route('/ddt-reason',
        name: 'post_ddt_reason',
        methods: ['POST'])]
    public function postDdtReason(Request $request, ValidatorInterface $validator): JsonResponse
    {
        $data = $request->request->all();

        $reason = new DdtReason();

        try {
            $this->handleRelations($reason, $data);
            $reason = $this->createMethodsByInput->createMethods($reason, $data);

        } catch (\Exception $e) {
            return new JsonResponse($this->doResponse->doErrorResponse($e->getMessage(), 400));
        }

        $errors = $validator->validate($reason);
        if (count($errors) > 0) {
            return new JsonResponse($this->doResponse->doErrorResponse($this->validatorOutputFormatter->formatOutput($errors), 400));
        }

        $this->doctrine->persist($reason);
        $this->doctrine->flush();

        $results = $this->groupSerializer->serializeGroup([$reason], 'ddt_reason_detail');
        return new JsonResponse($this->doResponse->doResponse($results[0]));
    }

    #[Route('/ddt-reason/{id}',
        name: 'put_ddt_reason',
        requirements: ['id' => '\d+'],
        methods: ['PUT', 'PATCH'])]
    public function putDdtReason(int $id, Request $request, ValidatorInterface $validator): JsonResponse
    {
        $reason = $this->doctrine->getRepository(DdtReason::class)->find($id);
        if (!$reason) {
            return new JsonResponse($this->doResponse->doErrorResponse('Causale DDT non trovata', 404));
        }

        $data = json_decode($request->getContent(), true) ?? $request->request->all();

        try {
            $this->handleRelations($reason, $data);
            $this->createMethodsByInput->createMethods($reason, $data);
        } catch (\Exception $e) {
            return new JsonResponse($this->doResponse->doErrorResponse($e->getMessage(), 400));
        }

        $errors = $validator->validate($reason);
        if (count($errors) > 0) {
            return new JsonResponse($this->doResponse->doErrorResponse($this->validatorOutputFormatter->formatOutput($errors), 400));
        }

        $this->doctrine->flush();

        $results = $this->groupSerializer->serializeGroup([$reason], 'ddt_reason_detail');
        return new JsonResponse($this->doResponse->doResponse($results[0]));
    }

    #[Route('/ddt-reason/{id}',
        name: 'delete_ddt_reason',
        requirements: ['id' => '\d+'],
        methods: ['DELETE'])]
    public function deleteDdtReason(int $id): JsonResponse
    {
        $reason = $this->doctrine->getRepository(DdtReason::class)->find($id);
        if (!$reason) {
            return new JsonResponse($this->doResponse->doErrorResponse('Causale DDT non trovata', 404));
        }

        $this->doctrine->remove($reason);
        $this->doctrine->flush();

        return new JsonResponse($this->doResponse->doResponse(['message' => 'Causale DDT eliminata con successo']));
    }

    private function handleRelations(DdtReason $reason, array &$data): void
    {
        if (isset($data['warehouse_movement_reason_id'])) {
            $wmReason = $this->doctrine->getRepository(WarehouseMovementReason::class)->find($data['warehouse_movement_reason_id']);

            if ($wmReason) {
                $reason->setWarehouseMovementReason($wmReason);
            }

            unset($data['warehouse_movement_reason_id']);
        }
    }
}
