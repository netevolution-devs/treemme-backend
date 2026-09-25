<?php

namespace App\Controller;

use App\Entity\Ddt;
use App\Entity\DdtRow;
use App\Entity\Contact;
use App\Entity\DdtReason;
use App\Entity\WarehouseMovement;
use App\Service\StockService;
use App\Service\ClientOrderRowService;
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
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'ddt')]
final class DdtController extends AbstractController
{
    private $createMethodsByInput;
    private $doctrine;
    private $doResponse;
    private $groupSerializer;
    private $validatorOutputFormatter;
    private $stockService;
    private $clientOrderRowService;

    public function __construct(
        CreateMethodsByInput     $createMethodsByInput,
        EntityManagerInterface   $entityManager,
        DoResponseService        $doResponseService,
        GroupSerializerService   $groupSerializer,
        ValidatorOutputFormatter $validatorOutputFormatter,
        StockService             $stockService,
        ClientOrderRowService    $clientOrderRowService
    ) {
        $this->createMethodsByInput = $createMethodsByInput;
        $this->doctrine = $entityManager;
        $this->doResponse = $doResponseService;
        $this->groupSerializer = $groupSerializer;
        $this->validatorOutputFormatter = $validatorOutputFormatter;
        $this->stockService = $stockService;
        $this->clientOrderRowService = $clientOrderRowService;
    }

    #[Route('/ddt/{id}',
        name: 'get_ddt',
        defaults: ['id' => null],
        requirements: ['id' => '\d+'],
        methods: ['GET', 'HEAD'])]
    public function getDdt(?int $id, Request $request): JsonResponse
    {
        $ddtRepository = $this->doctrine->getRepository(Ddt::class);

        if ($id) {
            $ddt = $ddtRepository->find($id);
            if (!$ddt) {
                return $this->doResponse->doErrorJsonResponse('DDT non trovato', 404);
            }
            $results = $this->groupSerializer->serializeGroup([$ddt], 'ddt_detail');
            return new JsonResponse($this->doResponse->doResponse($results[0]));
        }

        $subcontractorId = $request->query->get('subcontractor_id') ? (int)$request->query->get('subcontractor_id') : null;
        $clientId = $request->query->get('client_id') ? (int)$request->query->get('client_id') : null;
        $startDateStr = $request->query->get('start_date');
        $endDateStr = $request->query->get('end_date');

        $startDate = $startDateStr ? \DateTime::createFromFormat('Y-m-d', $startDateStr) : null;
        if ($startDate) $startDate->setTime(0, 0, 0);

        $endDate = $endDateStr ? \DateTime::createFromFormat('Y-m-d', $endDateStr) : null;
        if ($endDate) $endDate->setTime(0, 0, 0);

        if ($subcontractorId || $clientId || $startDate || $endDate) {
            $ddts = $ddtRepository->findByFilters($subcontractorId, $clientId, $startDate, $endDate);
        } else {
            $ddts = $ddtRepository->findBy([], ['ddt_number' => 'ASC']);
        }

        $results = $this->groupSerializer->serializeGroup($ddts, 'ddt_list');
        return new JsonResponse($this->doResponse->doResponse($results));
    }

    #[Route('/ddt',
        name: 'post_ddt',
        methods: ['POST'])]
    public function postDdt(Request $request, ValidatorInterface $validator): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? $request->request->all();

        $ddt = new Ddt();
        try {
            $this->handleRelations($ddt, $data);
            $ddt = $this->createMethodsByInput->createMethods($ddt, $data);
        } catch (\Exception $e) {
            return $this->doResponse->doErrorJsonResponse($e->getMessage(), 400);
        }

        $errors = $validator->validate($ddt);
        if (count($errors) > 0) {
            return $this->doResponse->doErrorJsonResponse($this->validatorOutputFormatter->formatOutput($errors), 400);
        }

        $this->doctrine->persist($ddt);
        $this->doctrine->flush();

        $results = $this->groupSerializer->serializeGroup([$ddt], 'ddt_detail');
        return new JsonResponse($this->doResponse->doResponse($results[0]));
    }

    #[Route('/ddt/{id}',
        name: 'put_ddt',
        requirements: ['id' => '\d+'],
        methods: ['PUT', 'PATCH'])]
    public function putDdt(int $id, Request $request, ValidatorInterface $validator): JsonResponse
    {
        $ddt = $this->doctrine->getRepository(Ddt::class)->find($id);
        if (!$ddt) {
            return $this->doResponse->doErrorJsonResponse('DDT non trovato', 404);
        }

        $data = json_decode($request->getContent(), true) ?? $request->request->all();

        try {
            $this->handleRelations($ddt, $data);
            $this->createMethodsByInput->createMethods($ddt, $data);
        } catch (\Exception $e) {
            return $this->doResponse->doErrorJsonResponse($e->getMessage(), 400);
        }

        $errors = $validator->validate($ddt);
        if (count($errors) > 0) {
            return $this->doResponse->doErrorJsonResponse($this->validatorOutputFormatter->formatOutput($errors), 400);
        }

        $this->doctrine->flush();

        $results = $this->groupSerializer->serializeGroup([$ddt], 'ddt_detail');
        return new JsonResponse($this->doResponse->doResponse($results[0]));
    }

    #[Route('/ddt/{id}',
        name: 'delete_ddt',
        requirements: ['id' => '\d+'],
        methods: ['DELETE'])]
    public function deleteDdt(int $id): JsonResponse
    {
        $ddt = $this->doctrine->getRepository(Ddt::class)->find($id);
        if (!$ddt) {
            return $this->doResponse->doErrorJsonResponse('DDT non trovato', 404);
        }

        $batchesToRecalculate = [];
        $orderRowsToUpdate = [];

        $ddtRows = $ddt->getDdtRows()->toArray();

        foreach ($ddtRows as $ddtRow) {
            $batch = $ddtRow->getBatch();
            if ($batch) {
                $batchesToRecalculate[$batch->getId()] = $batch;
                foreach ($batch->getBatchOrders() as $batchOrder) {
                    $orderRow = $batchOrder->getOrderRow();
                    if ($orderRow) {
                        $orderRowsToUpdate[$orderRow->getId()] = $orderRow;
                    }
                }
            }

            // Rimuoviamo tutti i movimenti di magazzino associati alla riga DDT
            $this->removeWarehouseMovementsForDdtRow($ddtRow);

            $this->doctrine->remove($ddtRow);
        }

        // Rimuoviamo eventuali altri movimenti di magazzino associati direttamente al DDT per numero DDT
        if ($ddt->getDdtNumber()) {
            $movementsByDdtNumber = $this->doctrine->getRepository(WarehouseMovement::class)->findBy([
                'ddt_number' => $ddt->getDdtNumber()
            ]);
            foreach ($movementsByDdtNumber as $wm) {
                if ($wm->getBatch()) {
                    $batchesToRecalculate[$wm->getBatch()->getId()] = $wm->getBatch();
                }
                foreach ($wm->getSonWarehouseMovements() as $sonMovement) {
                    $sonMovement->setFatherMovement(null);
                }
                $this->doctrine->remove($wm);
            }
        }

        $this->doctrine->remove($ddt);
        $this->doctrine->flush();

        // Ricalcolo giacenze per tutti i lotti coinvolti
        foreach ($batchesToRecalculate as $batch) {
            $this->stockService->recalculateBatchStock($batch);
            $this->stockService->updateBatchAverageFromMovements($batch);
        }

        // Aggiorna quantity_to_ship e stato per le righe ordine coinvolte
        foreach ($orderRowsToUpdate as $orderRow) {
            $this->clientOrderRowService->updateQuantityToShip($orderRow);
        }

        $this->doctrine->flush();

        return new JsonResponse($this->doResponse->doResponse(['message' => 'DDT eliminato con successo']));
    }

    private function removeWarehouseMovementsForDdtRow(DdtRow $ddtRow): void
    {
        $qb = $this->doctrine->getRepository(WarehouseMovement::class)->createQueryBuilder('wm');
        $qb->where('wm.movement_note = :exactNote')
           ->orWhere('wm.movement_note LIKE :likeNote1')
           ->orWhere('wm.movement_note LIKE :likeNote2')
           ->setParameter('exactNote', 'Riga DDT ' . $ddtRow->getId())
           ->setParameter('likeNote1', '%riga DDT ' . $ddtRow->getId() . '%')
           ->setParameter('likeNote2', '%Riga DDT ' . $ddtRow->getId() . '%');

        if ($ddtRow->getRowNote() !== null && trim($ddtRow->getRowNote()) !== '') {
            $qb->orWhere('(wm.movement_note = :rowNote AND wm.batch = :batch)')
               ->setParameter('rowNote', $ddtRow->getRowNote())
               ->setParameter('batch', $ddtRow->getBatch());
        }

        $movements = $qb->getQuery()->getResult();
        foreach ($movements as $movement) {
            foreach ($movement->getSonWarehouseMovements() as $sonMovement) {
                $sonMovement->setFatherMovement(null);
            }
            $this->doctrine->remove($movement);
        }
    }

    private function handleRelations(Ddt $ddt, array &$data): void
    {
        if (isset($data['subcontractor_id'])) {
            $subcontractor = $this->doctrine->getRepository(Contact::class)->find($data['subcontractor_id']);
            if ($subcontractor) {
                $ddt->setSubcontractor($subcontractor);
            }
            unset($data['subcontractor_id']);
        }

        if (isset($data['client_id'])) {
            $client = $this->doctrine->getRepository(Contact::class)->find($data['client_id']);
            if ($client) {
                $ddt->setClient($client);
            }
            unset($data['client_id']);
        }

        if (isset($data['reason_id'])) {
            $reason = $this->doctrine->getRepository(DdtReason::class)->find($data['reason_id']);
            if ($reason) {
                $ddt->setReason($reason);
            }
            unset($data['reason_id']);
        }
    }
}

