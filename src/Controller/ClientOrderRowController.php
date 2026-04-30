<?php

namespace App\Controller;

use App\Entity\Article;
use App\Entity\ClientOrderRow;
use App\Entity\ClientOrder;
use App\Entity\Currency;
use App\Entity\Product;
use App\Entity\MeasurementUnit;
use App\Entity\Selection;
use App\Service\CreateMethodsByInput;
use App\Service\DoResponseService;
use App\Service\GroupSerializerService;
use App\Service\ValidatorOutputFormatter;
use App\Service\ActionLoggerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class ClientOrderRowController extends AbstractController
{
    private $createMethodsByInput;
    private $doctrine;
    private $doResponse;
    private $groupSerializer;
    private $validatorOutputFormatter;
    private $actionLogger;

    public function __construct(
        CreateMethodsByInput     $createMethodsByInput,
        EntityManagerInterface   $entityManager,
        DoResponseService        $doResponseService,
        GroupSerializerService   $groupSerializer,
        ValidatorOutputFormatter $validatorOutputFormatter,
        ActionLoggerService      $actionLogger,
    )
    {
        $this->createMethodsByInput = $createMethodsByInput;
        $this->doctrine = $entityManager;
        $this->doResponse = $doResponseService;
        $this->groupSerializer = $groupSerializer;
        $this->validatorOutputFormatter = $validatorOutputFormatter;
        $this->actionLogger = $actionLogger;
    }

    #[Route('/client-order-row-report',
        name: 'get_client_order_row_report',
        methods: ['GET'])]
    public function getClientOrderRowsReport(Request $request): JsonResponse
    {
        $startDate = $request->query->get('startDate');
        $endDate = $request->query->get('endDate');
        $shippingStatus = $request->query->get('shippingStatus'); // 'to_ship', 'shipped'
        $productionStatus = $request->query->get('productionStatus'); // 'to_produce', 'produced'
        $printStatus = $request->query->get('printStatus'); // 'to_print', 'printed'

        $qb = $this->doctrine->getRepository(ClientOrderRow::class)->createQueryBuilder('cor');
        $qb->join('cor.client_order', 'co')
           ->join('co.client', 'c');

        if ($startDate) {
            $qb->andWhere('co.orderDate >= :startDate')
               ->setParameter('startDate', new \DateTime($startDate));
        }
        if ($endDate) {
            $qb->andWhere('co.orderDate <= :endDate')
               ->setParameter('endDate', new \DateTime($endDate));
        }

        if ($shippingStatus === 'to_ship') {
            $qb->andWhere('cor.quantityToShip > 0');
        } elseif ($shippingStatus === 'shipped') {
            $qb->andWhere('cor.quantityToShip <= 0 OR cor.quantityToShip IS NULL');
        }

        // Filtro produzione tramite query per efficienza se possibile, 
        // ma la logica della somma quantità lotti è complessa per DQL puro in questo contesto.
        // Manteniamo la logica post-query per precisione sulla somma delle quantità dei lotti.

        if ($printStatus === 'printed') {
            $qb->andWhere('co.printed = true');
        } elseif ($printStatus === 'to_print') {
            $qb->andWhere('co.printed = false OR co.printed IS NULL');
        }

        $rows = $qb->getQuery()->getResult();

        // Filtro produzione e raggruppamento per cliente
        $report = [];
        foreach ($rows as $row) {
            $totalProduced = 0;
            foreach ($row->getBatchOrders() as $bo) {
                $batch = $bo->getBatch();
                if ($batch) {
                    $totalProduced += (float)$batch->getQuantity();
                }
            }

            // Consideriamo "prodotto" se la quantità totale associata ai lotti è >= alla quantità ordinata
            $isProduced = $totalProduced >= (float)$row->getQuantity();

            if ($productionStatus === 'produced' && !$isProduced) continue;
            if ($productionStatus === 'to_produce' && $isProduced) continue;

            $client = $row->getClientOrder()->getClient();
            $clientId = $client ? $client->getId() : 0;
            $clientName = $client ? $client->getName() : 'Sconosciuto';

            if (!isset($report[$clientId])) {
                $report[$clientId] = [
                    'client' => $client ? $this->groupSerializer->serializeGroup($client, 'client_order_row_list') : null,
                    'rows' => []
                ];
            }

            $serializedRow = $this->groupSerializer->serializeGroup($row, 'client_order_row_list');
            $report[$clientId]['rows'][] = $serializedRow;
        }

        return new JsonResponse($this->doResponse->doResponse(array_values($report)));
    }

    #[Route('/client-order-row/{id}',
        name: 'get_client_order_row',
        defaults: ['id' => null],
        requirements: ['id' => '\d*'],
        methods: ['GET', 'HEAD'])]
    public function getClientOrderRow(?int $id): JsonResponse
    {
        $clientOrderRowRepository = $this->doctrine->getRepository(ClientOrderRow::class);

        if ($id) {
            $clientOrderRow = [$clientOrderRowRepository->find($id)];
            if (!$clientOrderRow[0]) {
                return $this->doResponse->doErrorJsonResponse('ClientOrderRow not found', 404);
            }
        } else {
            $clientOrderRow = $clientOrderRowRepository->findBy([], ['id' => 'ASC']);
        }
        $results = $this->groupSerializer->serializeGroup($clientOrderRow, $id ? 'client_order_row_detail' : 'client_order_row_list');

        if ($id) {
            return new JsonResponse($this->doResponse->doResponse($results[0]));
        }
        return new JsonResponse($this->doResponse->doResponse($results));
    }

    #[Route('/client-order-row',
        name: 'post_client_order_row',
        methods: ['POST'])]
    public function postClientOrderRow(
        Request            $request,
        ValidatorInterface $validator,
    ): JsonResponse
    {
        $data = $request->request->all();
        $clientOrderRow = new ClientOrderRow();

        try {
            $clientOrderRow = $this->handleRelations($clientOrderRow, $data);

            $clientOrderRow = $this->createMethodsByInput->createMethods($clientOrderRow, $data);

            if ($clientOrderRow->getClientOrder() && (!$clientOrderRow->getWeight() || $clientOrderRow->getWeight() === 0)) {
                $maxWeight = $this->doctrine->getRepository(ClientOrderRow::class)->findMaxWeightByOrder($clientOrderRow->getClientOrder()->getId());
                $clientOrderRow->setWeight($maxWeight + 1);
            }

            $this->calculatePrices($clientOrderRow);

            $errors = $validator->validate($clientOrderRow);
            if (count($errors) > 0) {
                $errors = $this->validatorOutputFormatter->formatOutput($errors);
                return $this->doResponse->doErrorJsonResponse($errors);
            }

            $em = $this->doctrine;
            $em->persist($clientOrderRow);
            $em->flush();

            $result = $this->groupSerializer->serializeGroup($clientOrderRow, 'client_order_row_detail');
            return new JsonResponse($this->doResponse->doResponse($result));

        } catch (\Exception $e) {
            return $this->doResponse->doErrorJsonResponse($e->getMessage());
        }
    }

    #[Route('/client-order-row/{id}',
        name: 'put_client_order_row',
        methods: ['PUT'])]
    public function modifyClientOrderRow(
        Request            $request,
        ValidatorInterface $validator,
        int                $id,
    ): JsonResponse
    {
        $data = $request->toArray();
        $clientOrderRow = $this->doctrine->getRepository(ClientOrderRow::class)->find($id);

        if (!$clientOrderRow) {
            return $this->doResponse->doErrorJsonResponse('ClientOrderRow not found', 404);
        }

        if ($clientOrderRow->getClientOrder() && $clientOrderRow->getClientOrder()->isChecked()) {
            return $this->doResponse->doErrorJsonResponse('Non è possibile modificare una riga di un ordine già controllato', 403);
        }

        try {
            $clientOrderRow = $this->handleRelations($clientOrderRow, $data);
            $clientOrderRow = $this->createMethodsByInput->createMethods($clientOrderRow, $data);

            if ($clientOrderRow->getClientOrder() && (!$clientOrderRow->getWeight() || $clientOrderRow->getWeight() === 0)) {
                $maxWeight = $this->doctrine->getRepository(ClientOrderRow::class)->findMaxWeightByOrder($clientOrderRow->getClientOrder()->getId());
                $clientOrderRow->setWeight($maxWeight + 1);
            }

            $this->calculatePrices($clientOrderRow);

            $errors = $validator->validate($clientOrderRow);
            if (count($errors) > 0) {
                $errors = $this->validatorOutputFormatter->formatOutput($errors);
                return $this->doResponse->doErrorJsonResponse($errors);
            }

            $this->doctrine->persist($clientOrderRow);
            $this->doctrine->flush();

            $result = $this->groupSerializer->serializeGroup($clientOrderRow, 'client_order_row_detail');
            return new JsonResponse($this->doResponse->doResponse($result));
        } catch (\Exception $e) {
            return $this->doResponse->doErrorJsonResponse($e->getMessage());
        }
    }

    #[Route('/client-order-row/{id}',
        name: 'delete_client_order_row',
        methods: ['DELETE'])]
    public function deleteClientOrderRow(int $id): JsonResponse
    {
        $clientOrderRow = $this->doctrine->getRepository(ClientOrderRow::class)->find($id);
        if (!$clientOrderRow) {
            return $this->doResponse->doErrorJsonResponse('ClientOrderRow not found', 404);
        }

        $this->doctrine->remove($clientOrderRow);
        $this->doctrine->flush();

        return new JsonResponse($this->doResponse->doResponse('delete_successfully'));
    }

    private function handleRelations(ClientOrderRow $clientOrderRow, array &$data): ClientOrderRow
    {
        if (isset($data['client_order_id'])) {
            $clientOrder = $this->doctrine->getRepository(ClientOrder::class)->find($data['client_order_id']);
            if ($clientOrder) {
                $clientOrderRow->setClientOrder($clientOrder);
            }
            unset($data['client_order_id']);
        }

        if (isset($data['article_id'])) {
            $article = $this->doctrine->getRepository(Article::class)->find($data['article_id']);
            if ($article) {
                $clientOrderRow->setArticle($article);
            }
            unset($data['article_id']);
        }

        if (isset($data['measurement_unit_id'])) {
            $unit = $this->doctrine->getRepository(MeasurementUnit::class)->find($data['measurement_unit_id']);
            if ($unit) {
                $clientOrderRow->setMeasurementUnit($unit);
            }
            unset($data['measurement_unit_id']);
        }

        if (isset($data['currency_id'])) {
            $unit = $this->doctrine->getRepository(Currency::class)->find($data['currency_id']);
            if ($unit) {
                $clientOrderRow->setCurrency($unit);
            }
            unset($data['currency_id']);
        }

        if (isset($data['selection_id'])) {
            $selection = $this->doctrine->getRepository(Selection::class)->find($data['selection_id']);
            if ($selection) {
                $clientOrderRow->setSelection($selection);
            }
            unset($data['selection_id']);
        }
        if (isset($data['address_id'])) {
            $address = $this->doctrine->getRepository(Address::class)->find($data['address_id']);
            if ($address) {
                $clientOrderRow->setAddress($address);
            }
            unset($data['address_id']);
        }

        return $clientOrderRow;
    }

    private function calculatePrices(ClientOrderRow $clientOrderRow): void
    {

        $quantity = $clientOrderRow->getQuantity() ?: 0;
        $currencyPrice = $clientOrderRow->getCurrencyPrice(); // Valuta estera per unità
        $currencyExchange = $clientOrderRow->getCurrencyExchange() ?: 1.0; // quanta valuta estera per 1 EUR

        // Se arriva currencyPrice, ricalcola sempre price (EUR)
        if ($currencyPrice !== null) {
            $price = $currencyExchange != 0 ? round($currencyPrice / $currencyExchange, 2) : 0.0;
            $clientOrderRow->setPrice($price);
            $clientOrderRow->setCurrencyExchange($currencyExchange);
            $clientOrderRow->setCurrencyPrice(round($currencyPrice, 2));
        } else {
            $price = $clientOrderRow->getPrice() ?: 0.0;
            $currencyPrice = round($price * $currencyExchange, 2);
            $clientOrderRow->setCurrencyPrice($currencyPrice);
        }

        // Totali
        $totalPrice = round($quantity * $price, 2);
        $totalCurrencyPrice = round($quantity * $currencyPrice, 2);

        $clientOrderRow->setTotalPrice($totalPrice);
        $clientOrderRow->setTotalCurrencyPrice($totalCurrencyPrice);
    }
}

