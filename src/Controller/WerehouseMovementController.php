<?php

namespace App\Controller;

use App\Service\StockService;
use App\Entity\Contact;
use App\Entity\WarehouseMovement;
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
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class WerehouseMovementController extends AbstractController
{
    private $createMethodsByInput;
    private $doctrine;
    private $doResponse;
    private $groupSerializer;
    private $validatorOutputFormatter;
    private $stockService;

    public function __construct(
        CreateMethodsByInput     $createMethodsByInput,
        EntityManagerInterface   $entityManager,
        DoResponseService        $doResponseService,
        GroupSerializerService   $groupSerializer,
        ValidatorOutputFormatter $validatorOutputFormatter,
        StockService             $stockService
    ) {
        $this->createMethodsByInput = $createMethodsByInput;
        $this->doctrine = $entityManager;
        $this->doResponse = $doResponseService;
        $this->groupSerializer = $groupSerializer;
        $this->validatorOutputFormatter = $validatorOutputFormatter;
        $this->stockService = $stockService;
    }

    #[Route('/warehouse-movement/{id}',
        name: 'get_warehouse_movement',
        defaults: ['id' => null],
        requirements: ['id' => '\d+'],
        methods: ['GET', 'HEAD'])]
    public function getWarehouseMovement(?int $id, Request $request): JsonResponse
    {
        $repository = $this->doctrine->getRepository(WarehouseMovement::class);

        if ($id) {
            $movement = $repository->find($id);
            if (!$movement) {
                return $this->doResponse->doErrorJsonResponse('Movimento magazzino non trovato', 404);
            }
            $results = $this->groupSerializer->serializeGroup([$movement], 'movement_detail');
            return new JsonResponse($this->doResponse->doResponse($results[0]));
        }

        $batchCode = $request->query->get('batch_code');

        if ($batchCode) {
            $batchRepository = $this->doctrine->getRepository(\App\Entity\Batch::class);
            $batch = $batchRepository->findOneBy(['batch_code' => $batchCode]);

            if (!$batch) {
                return $this->doResponse->doErrorJsonResponse('Lotto non trovato', 404);
            }

            $allBatches = [];
            $this->collectFatherBatches($batch, $allBatches);

            $movements = $repository->findBy(['batch' => $allBatches], ['date' => 'DESC']);
            $results = $this->groupSerializer->serializeGroup($movements, 'warehouse_movement_list');
            return new JsonResponse($this->doResponse->doResponse($results));
        }

        $movements = $repository->findBy([], ['id' => 'ASC']);
        $results = $this->groupSerializer->serializeGroup($movements, 'warehouse_movement_list');
        return new JsonResponse($this->doResponse->doResponse($results));
    }

    #[Route('/warehouse-movement/subcontracting-return',
        name: 'post_warehouse_movement_subcontracting_return',
        methods: ['POST'])]
    public function postSubcontractingReturn(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['batchIds']) || !is_array($data['batchIds'])) {
            return $this->doResponse->doErrorJsonResponse('Parametro batchIds mancante o non valido', 400);
        }

        $batchRepository = $this->doctrine->getRepository(\App\Entity\Batch::class);
        $reasonRepository = $this->doctrine->getRepository(WarehouseMovementReason::class);
        $reasonTypeRepository = $this->doctrine->getRepository(WarehouseMovementReasonType::class);
        $contactRepository = $this->doctrine->getRepository(Contact::class);

        // Cerchiamo la causale di rientro
        $reasonReturn = $reasonRepository->findOneBy(['name' => 'Rientro da Lavorazione']);
        if (!$reasonReturn) {
            $reasonTypeIn = $reasonTypeRepository->findOneBy(['movement_type' => 'Carico']);
            if ($reasonTypeIn) {
                $reasonReturn = $reasonRepository->findOneBy(['reason_type' => $reasonTypeIn]);
            }
        }

        if (!$reasonReturn) {
            return $this->doResponse->doErrorJsonResponse('Causale di magazzino per il rientro non trovata', 400);
        }

        $contact = null;
        if (isset($data['contactId'])) {
            $contact = $contactRepository->find($data['contactId']);
        }

        $movements = [];
        foreach ($data['batchIds'] as $batchId) {
            $batch = $batchRepository->find($batchId);
            if (!$batch) {
                continue;
            }

            // Calcoliamo la quantità massima disponibile per il rientro.
            // In base alla richiesta "la quantità per tutta le righe è il massimo",
            // assumiamo che si riferisca alla quantità totale del lotto (pieces e quantity)
            // che deve essere caricata/rientrata. 
            // Spesso in questi sistemi il rientro dal contolavoro reintegra lo stock.
            
            $pieces = $batch->getPieces();
            $quantity = $batch->getQuantity();

            $movement = new WarehouseMovement();
            $movement->setBatch($batch);
            $movement->setDate(new \DateTime());
            $movement->setReason($reasonReturn);
            $movement->setPiece($pieces);
            $movement->setQuantity($quantity);
            $movement->setContact($contact);
            $movement->setDdtNumber($data['ddtNumber'] ?? null);
            if (isset($data['ddtDate'])) {
                $movement->setDdtDate(new \DateTime($data['ddtDate']));
            }
            $movement->setMovementNote($data['note'] ?? 'Rientro da contolavoro massivo');

            $this->doctrine->persist($movement);
            
            $movements[] = $movement;
        }

        $this->doctrine->flush();

        $results = $this->groupSerializer->serializeGroup($movements, 'warehouse_movement_list');
        return new JsonResponse($this->doResponse->doResponse($results));
    }

    private function collectFatherBatches(\App\Entity\Batch $batch, array &$collectedBatches): void
    {
        if (in_array($batch, $collectedBatches, true)) {
            return;
        }

        $collectedBatches[] = $batch;

        foreach ($batch->getBatchCompositions() as $composition) {
            $fatherBatch = $composition->getFatherBatch();
            if ($fatherBatch) {
                $this->collectFatherBatches($fatherBatch, $collectedBatches);
            }
        }
    }
}
