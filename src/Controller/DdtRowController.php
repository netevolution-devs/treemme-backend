<?php

namespace App\Controller;

use App\Entity\Ddt;
use App\Entity\DdtRow;
use App\Entity\Batch;
use App\Entity\Article;
use App\Entity\MeasurementUnit;
use App\Entity\Currency;
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
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class DdtRowController extends AbstractController
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

    #[Route('/ddt-row/{id}',
        name: 'get_ddt_row',
        defaults: ['id' => null],
        requirements: ['id' => '\d+'],
        methods: ['GET', 'HEAD'])]
    public function getDdtRow(?int $id): JsonResponse
    {
        $ddtRowRepository = $this->doctrine->getRepository(DdtRow::class);

        if ($id) {
            $ddtRow = $ddtRowRepository->find($id);
            if (!$ddtRow) {
                return new JsonResponse($this->doResponse->doErrorResponse('Riga DDT non trovata', 404));
            }
            $results = $this->groupSerializer->serializeGroup([$ddtRow], 'ddt_row_detail');
            return new JsonResponse($this->doResponse->doResponse($results[0]));
        }

        $ddtRows = $ddtRowRepository->findBy([], ['id' => 'DESC']);
        $results = $this->groupSerializer->serializeGroup($ddtRows, 'ddt_row_list');
        return new JsonResponse($this->doResponse->doResponse($results));
    }

    #[Route('/ddt/{ddtId}/row',
        name: 'post_ddt_row',
        requirements: ['ddtId' => '\d+'],
        methods: ['POST'])]
    public function postDdtRow(int $ddtId, Request $request, ValidatorInterface $validator): JsonResponse
    {
        $ddt = $this->doctrine->getRepository(Ddt::class)->find($ddtId);
        if (!$ddt) {
            return new JsonResponse($this->doResponse->doErrorResponse('DDT non trovato', 404));
        }

        $data = json_decode($request->getContent(), true) ?? $request->request->all();

        $ddtRow = new DdtRow();
        $ddtRow->setDdt($ddt);
        try {
            $this->createMethodsByInput->createMethods($ddtRow, $data);
            $this->handleData($ddtRow, $data);
        } catch (\Exception $e) {
            return new JsonResponse($this->doResponse->doErrorResponse($e->getMessage(), 400));
        }

        $errors = $validator->validate($ddtRow);
        if (count($errors) > 0) {
            return new JsonResponse($this->doResponse->doErrorResponse($this->validatorOutputFormatter->formatOutput($errors), 400));
        }

        $this->doctrine->persist($ddtRow);
        $this->doctrine->flush();

        $batch = $ddtRow->getBatch();

        $batch->setStockQuantity($batch->getStockQuantity() - $ddtRow->getQuantity());
        $batch->setStockItems($batch->getStockItems() - $ddtRow->getPieces());

        $this->doctrine->persist($batch);

        $wearhouseMovement = new WarehouseMovement();
        $wearhouseMovement->setBatch($batch);
        $wearhouseMovement->setQuantity($ddtRow->getQuantity());
        $wearhouseMovement->setPiece($ddtRow->getPieces());
        $wearhouseMovement->setReason($ddtRow->getDdt()->getReason()->getWarehouseMovementReason());
        $wearhouseMovement->setDdtDate($ddt->getDate());
        $wearhouseMovement->setDate($ddt->getDate());
        $wearhouseMovement->setMovementNote('Riga DDT ' . $ddtRow->getId());

        $this->doctrine->persist($wearhouseMovement);
        $this->doctrine->flush();

        $results = $this->groupSerializer->serializeGroup([$ddtRow], 'ddt_row_detail');
        return new JsonResponse($this->doResponse->doResponse($results[0]));
    }

    #[Route('/ddt-row/{id}',
        name: 'put_ddt_row',
        requirements: ['id' => '\d+'],
        methods: ['PUT', 'PATCH'])]
    public function putDdtRow(int $id, Request $request, ValidatorInterface $validator): JsonResponse
    {
        $ddtRow = $this->doctrine->getRepository(DdtRow::class)->find($id);
        if (!$ddtRow) {
            return new JsonResponse($this->doResponse->doErrorResponse('Riga DDT non trovata', 404));
        }

        $data = json_decode($request->getContent(), true) ?? $request->request->all();

        try {
            $this->createMethodsByInput->createMethods($ddtRow, $data);
            $this->handleData($ddtRow, $data);
        } catch (\Exception $e) {
            return new JsonResponse($this->doResponse->doErrorResponse($e->getMessage(), 400));
        }

        $errors = $validator->validate($ddtRow);
        if (count($errors) > 0) {
            return new JsonResponse($this->doResponse->doErrorResponse($this->validatorOutputFormatter->formatOutput($errors), 400));
        }

        $this->doctrine->flush();

        $results = $this->groupSerializer->serializeGroup([$ddtRow], 'ddt_row_detail');
        return new JsonResponse($this->doResponse->doResponse($results[0]));
    }

    #[Route('/ddt-row/{id}',
        name: 'delete_ddt_row',
        requirements: ['id' => '\d+'],
        methods: ['DELETE'])]
    public function deleteDdtRow(int $id): JsonResponse
    {
        $ddtRow = $this->doctrine->getRepository(DdtRow::class)->find($id);
        if (!$ddtRow) {
            return new JsonResponse($this->doResponse->doErrorResponse('Riga DDT non trovata', 404));
        }

        $this->doctrine->remove($ddtRow);
        $this->doctrine->flush();

        return new JsonResponse($this->doResponse->doResponse(['message' => 'Riga DDT eliminata con successo']));
    }

    #[Route('/ddt-row/{id}/return',
        name: 'post_ddt_row_return',
        requirements: ['id' => '\d+'],
        methods: ['POST'])]
    public function postDdtRowReturn(int $id, Request $request): JsonResponse
    {
        $ddtRow = $this->doctrine->getRepository(DdtRow::class)->find($id);
        if (!$ddtRow) {
            return new JsonResponse($this->doResponse->doErrorResponse('Riga DDT non trovata', 404));
        }

        $batch = $ddtRow->getBatch();
        if (!$batch) {
            return new JsonResponse($this->doResponse->doErrorResponse('Lotto non trovato per questa riga', 404));
        }

        $data = json_decode($request->getContent(), true) ?? $request->request->all();
        $quantity = $data['quantity'] ?? $ddtRow->getQuantity();
        $pieces = $data['pieces'] ?? $ddtRow->getPieces();

        $reason = $this->doctrine->getRepository(WarehouseMovementReason::class)->findOneBy(['name' => 'Carico']);
        if (!$reason) {
            $reasonTypeIn = $this->doctrine->getRepository(WarehouseMovementReasonType::class)->findOneBy(['movement_type' => 'In']);
            if ($reasonTypeIn) {
                $reason = $this->doctrine->getRepository(WarehouseMovementReason::class)->findOneBy(['reason_type' => $reasonTypeIn]);
            }
        }

        if (!$reason) {
            return new JsonResponse($this->doResponse->doErrorResponse('Causale di magazzino "Carico" non trovata', 400));
        }

        $warehouseMovement = new WarehouseMovement();
        $warehouseMovement->setBatch($batch);
        $warehouseMovement->setQuantity($quantity);
        $warehouseMovement->setPiece($pieces);
        $warehouseMovement->setReason($reason);
        $warehouseMovement->setDdtNumber($ddtRow->getDdt()->getDdtNumber());
        $warehouseMovement->setDdtDate($ddtRow->getDdt()->getDdtDate());
        $warehouseMovement->setDate(new \DateTime());
        $warehouseMovement->setMovementNote('Rientro riga DDT ' . $ddtRow->getId());

        $this->doctrine->persist($warehouseMovement);

        $batch->setStockQuantity($batch->getStockQuantity() + $quantity);
        $batch->setStockItems($batch->getStockItems() + $pieces);

        $this->doctrine->persist($batch);
        $this->doctrine->flush();

        $results = $this->groupSerializer->serializeGroup([$ddtRow], 'ddt_row_detail');
        return new JsonResponse($this->doResponse->doResponse($results[0]));
    }

    private function handleData(DdtRow $ddtRow, array $data): void
    {
        if (isset($data['batch_id'])) {
            $batch = $this->doctrine->getRepository(Batch::class)->find($data['batch_id']);
            if (!$batch) {
                throw new \Exception('Lotto non trovato');
            }
            $ddtRow->setBatch($batch);
        }
        if (isset($data['article_id'])) {
            $article = $this->doctrine->getRepository(Article::class)->find($data['article_id']);
            if (!$article) {
                throw new \Exception('Articolo non trovato');
            }
            $ddtRow->setArticle($article);
        }
        if (isset($data['measurement_unit_id'])) {
            $mu = $this->doctrine->getRepository(MeasurementUnit::class)->find($data['measurement_unit_id']);
            if (!$mu) {
                throw new \Exception('Unità di misura non trovata');
            }
            $ddtRow->setMeasurementUnit($mu);
        }
        if (isset($data['currency_id'])) {
            $currency = $this->doctrine->getRepository(Currency::class)->find($data['currency_id']);
            if (!$currency) {
                throw new \Exception('Valuta non trovata');
            }
            $ddtRow->setCurrency($currency);
        }
    }
}
