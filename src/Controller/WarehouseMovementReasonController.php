<?php

namespace App\Controller;

use App\Entity\WarehouseMovementReason;
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

final class WarehouseMovementReasonController extends AbstractController
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

    #[Route('/warehouse-movement-reason/{id}',
        name: 'get_warehouse_movement_reason',
        defaults: ['id' => null],
        requirements: ['id' => '\d+'],
        methods: ['GET', 'HEAD'])]
    public function getReason(?int $id): JsonResponse
    {
        $repository = $this->doctrine->getRepository(WarehouseMovementReason::class);

        if ($id) {
            $reason = $repository->find($id);
            if (!$reason) {
                return new JsonResponse($this->doResponse->doErrorResponse('Causale magazzino non trovata', 404));
            }
            $results = $this->groupSerializer->serializeGroup([$reason], 'reason_detail');
            return new JsonResponse($this->doResponse->doResponse($results[0]));
        }

        $reasons = $repository->findBy([], ['id' => 'DESC']);
        $results = $this->groupSerializer->serializeGroup($reasons, 'reason_list');
        return new JsonResponse($this->doResponse->doResponse($results));
    }

    #[Route('/warehouse-movement-reason',
        name: 'post_warehouse_movement_reason',
        methods: ['POST'])]
    public function postReason(Request $request, ValidatorInterface $validator): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? $request->request->all();

        $reason = new WarehouseMovementReason();
        try {
            $reason = $this->createMethodsByInput->createMethods($reason, $data);
            $this->handleData($reason, $data);
        } catch (\Exception $e) {
            return new JsonResponse($this->doResponse->doErrorResponse($e->getMessage(), 400));
        }

        $errors = $validator->validate($reason);
        if (count($errors) > 0) {
            return new JsonResponse($this->doResponse->doErrorResponse($this->validatorOutputFormatter->formatOutput($errors), 400));
        }

        $this->doctrine->persist($reason);
        $this->doctrine->flush();

        $results = $this->groupSerializer->serializeGroup([$reason], 'reason_detail');
        return new JsonResponse($this->doResponse->doResponse($results[0]));
    }

    #[Route('/warehouse-movement-reason/{id}',
        name: 'put_warehouse_movement_reason',
        requirements: ['id' => '\d+'],
        methods: ['PUT', 'PATCH'])]
    public function putReason(int $id, Request $request, ValidatorInterface $validator): JsonResponse
    {
        $reason = $this->doctrine->getRepository(WarehouseMovementReason::class)->find($id);
        if (!$reason) {
            return new JsonResponse($this->doResponse->doErrorResponse('Causale magazzino non trovata', 404));
        }

        $data = json_decode($request->getContent(), true) ?? $request->request->all();

        try {
            $this->createMethodsByInput->createMethods($reason, $data);
            $this->handleData($reason, $data);
        } catch (\Exception $e) {
            return new JsonResponse($this->doResponse->doErrorResponse($e->getMessage(), 400));
        }

        $errors = $validator->validate($reason);
        if (count($errors) > 0) {
            return new JsonResponse($this->doResponse->doErrorResponse($this->validatorOutputFormatter->formatOutput($errors), 400));
        }

        $this->doctrine->flush();

        $results = $this->groupSerializer->serializeGroup([$reason], 'reason_detail');
        return new JsonResponse($this->doResponse->doResponse($results[0]));
    }

    #[Route('/warehouse-movement-reason/{id}',
        name: 'delete_warehouse_movement_reason',
        requirements: ['id' => '\d+'],
        methods: ['DELETE'])]
    public function deleteReason(int $id): JsonResponse
    {
        $reason = $this->doctrine->getRepository(WarehouseMovementReason::class)->find($id);
        if (!$reason) {
            return new JsonResponse($this->doResponse->doErrorResponse('Causale magazzino non trovata', 404));
        }

        $this->doctrine->remove($reason);
        $this->doctrine->flush();

        return new JsonResponse($this->doResponse->doResponse(['message' => 'Causale magazzino eliminata con successo']));
    }

    private function handleData(WarehouseMovementReason $reason, array $data): void
    {
        if (isset($data['reason_type_id'])) {
            $reasonType = $this->doctrine->getRepository(WarehouseMovementReasonType::class)->find($data['reason_type_id']);
            if (!$reasonType) {
                throw new \Exception('Tipo causale non trovato');
            }
            $reason->setReasonType($reasonType);
        }
    }
}
