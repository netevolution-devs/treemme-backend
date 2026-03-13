<?php

namespace App\Controller;

use App\Entity\Ddt;
use App\Entity\DdtRow;
use App\Entity\Batch;
use App\Entity\Article;
use App\Entity\MeasurementUnit;
use App\Entity\Currency;
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
    private $doctrine;
    private $doResponse;
    private $groupSerializer;
    private $validatorOutputFormatter;

    public function __construct(
        EntityManagerInterface   $entityManager,
        DoResponseService        $doResponseService,
        GroupSerializerService   $groupSerializer,
        ValidatorOutputFormatter $validatorOutputFormatter
    ) {
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
            $this->handleData($ddtRow, $data);
        } catch (\Exception $e) {
            return new JsonResponse($this->doResponse->doErrorResponse($e->getMessage(), 400));
        }

        $errors = $validator->validate($ddtRow);
        if (count($errors) > 0) {
            return new JsonResponse($this->doResponse->doErrorResponse($this->validatorOutputFormatter->formatErrors($errors), 400));
        }

        $this->doctrine->persist($ddtRow);
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
            $this->handleData($ddtRow, $data);
        } catch (\Exception $e) {
            return new JsonResponse($this->doResponse->doErrorResponse($e->getMessage(), 400));
        }

        $errors = $validator->validate($ddtRow);
        if (count($errors) > 0) {
            return new JsonResponse($this->doResponse->doErrorResponse($this->validatorOutputFormatter->formatErrors($errors), 400));
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

    private function handleData(DdtRow $ddtRow, array $data): void
    {
        if (isset($data['order_note'])) {
            $ddtRow->setOrderNote($data['order_note']);
        }
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
        if (isset($data['pieces'])) {
            $ddtRow->setPieces((int)$data['pieces']);
        }
        if (isset($data['measurement_unit_id'])) {
            $mu = $this->doctrine->getRepository(MeasurementUnit::class)->find($data['measurement_unit_id']);
            if (!$mu) {
                throw new \Exception('Unità di misura non trovata');
            }
            $ddtRow->setMeasurementUnit($mu);
        }
        if (isset($data['quantity'])) {
            $ddtRow->setQuantity((float)$data['quantity']);
        }
        if (isset($data['price'])) {
            $ddtRow->setPrice((float)$data['price']);
        }
        if (isset($data['total_value'])) {
            $ddtRow->setTotalValue((float)$data['total_value']);
        }
        if (isset($data['currency_id'])) {
            $currency = $this->doctrine->getRepository(Currency::class)->find($data['currency_id']);
            if (!$currency) {
                throw new \Exception('Valuta non trovata');
            }
            $ddtRow->setCurrency($currency);
        }
        if (isset($data['currency_price'])) {
            $ddtRow->setCurrencyPrice((float)$data['currency_price']);
        }
        if (isset($data['currency_change'])) {
            $ddtRow->setCurrencyChange((float)$data['currency_change']);
        }
        if (isset($data['currency_total_value'])) {
            $ddtRow->setCurrencyTotalValue((float)$data['currency_total_value']);
        }
        if (isset($data['KG_weight'])) {
            $ddtRow->setKGWeight((float)$data['KG_weight']);
        }
        if (isset($data['row_note'])) {
            $ddtRow->setRowNote($data['row_note']);
        }
        if (isset($data['whole_piece'])) {
            $ddtRow->setWholePiece((int)$data['whole_piece']);
        }
        if (isset($data['half_piece'])) {
            $ddtRow->setHalfPiece((int)$data['half_piece']);
        }
    }
}
