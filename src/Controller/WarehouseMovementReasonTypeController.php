<?php

namespace App\Controller;

use App\Entity\WarehouseMovementReasonType;
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

final class WarehouseMovementReasonTypeController extends AbstractController
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

    #[Route('/warehouse-movement-reason-type/{id}',
        name: 'get_warehouse_movement_reason_type',
        defaults: ['id' => null],
        requirements: ['id' => '\d+'],
        methods: ['GET', 'HEAD'])]
    public function getReasonType(?int $id): JsonResponse
    {
        $repository = $this->doctrine->getRepository(WarehouseMovementReasonType::class);

        if ($id) {
            $reasonType = $repository->find($id);
            if (!$reasonType) {
                return $this->doResponse->doErrorJsonResponse('Tipo causale magazzino non trovato', 404);
            }
            $results = $this->groupSerializer->serializeGroup([$reasonType], 'reason_type_detail');
            return new JsonResponse($this->doResponse->doResponse($results[0]));
        }

        $reasonTypes = $repository->findBy([], ['name' => 'ASC']);
        $results = $this->groupSerializer->serializeGroup($reasonTypes, 'reason_type_list');
        return new JsonResponse($this->doResponse->doResponse($results));
    }

    #[Route('/warehouse-movement-reason-type',
        name: 'post_warehouse_movement_reason_type',
        methods: ['POST'])]
    public function postReasonType(Request $request, ValidatorInterface $validator): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? $request->request->all();

        $reasonType = new WarehouseMovementReasonType();
        try {
            $this->handleRelations($reasonType, $data);
            $reasonType = $this->createMethodsByInput->createMethods($reasonType, $data);
        } catch (\Exception $e) {
            return $this->doResponse->doErrorJsonResponse($e->getMessage(), 400);
        }

        $errors = $validator->validate($reasonType);
        if (count($errors) > 0) {
            return $this->doResponse->doErrorJsonResponse($this->validatorOutputFormatter->formatOutput($errors), 400);
        }

        $this->doctrine->persist($reasonType);
        $this->doctrine->flush();

        $results = $this->groupSerializer->serializeGroup([$reasonType], 'reason_type_detail');
        return new JsonResponse($this->doResponse->doResponse($results[0]));
    }

    #[Route('/warehouse-movement-reason-type/{id}',
        name: 'put_warehouse_movement_reason_type',
        requirements: ['id' => '\d+'],
        methods: ['PUT', 'PATCH'])]
    public function putReasonType(int $id, Request $request, ValidatorInterface $validator): JsonResponse
    {
        $reasonType = $this->doctrine->getRepository(WarehouseMovementReasonType::class)->find($id);
        if (!$reasonType) {
            return $this->doResponse->doErrorJsonResponse('Tipo causale magazzino non trovato', 404);
        }

        $data = json_decode($request->getContent(), true) ?? $request->request->all();

        try {
            $this->handleRelations($reasonType, $data);
            $this->createMethodsByInput->createMethods($reasonType, $data);
        } catch (\Exception $e) {
            return $this->doResponse->doErrorJsonResponse($e->getMessage(), 400);
        }

        $errors = $validator->validate($reasonType);
        if (count($errors) > 0) {
            return $this->doResponse->doErrorJsonResponse($this->validatorOutputFormatter->formatOutput($errors), 400);
        }

        $this->doctrine->flush();

        $results = $this->groupSerializer->serializeGroup([$reasonType], 'reason_type_detail');
        return new JsonResponse($this->doResponse->doResponse($results[0]));
    }

    #[Route('/warehouse-movement-reason-type/{id}',
        name: 'delete_warehouse_movement_reason_type',
        requirements: ['id' => '\d+'],
        methods: ['DELETE'])]
    public function deleteReasonType(int $id): JsonResponse
    {
        $reasonType = $this->doctrine->getRepository(WarehouseMovementReasonType::class)->find($id);
        if (!$reasonType) {
            return $this->doResponse->doErrorJsonResponse('Tipo causale magazzino non trovato', 404);
        }

        $this->doctrine->remove($reasonType);
        $this->doctrine->flush();

        return new JsonResponse($this->doResponse->doResponse(['message' => 'Tipo causale magazzino eliminato con successo']));
    }

    private function handleRelations(WarehouseMovementReasonType $reasonType, array &$data): void
    {
    }
}

