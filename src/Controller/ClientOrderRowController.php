<?php

namespace App\Controller;

use App\Entity\Article;
use App\Entity\ClientOrderRow;
use App\Entity\ClientOrder;
use App\Entity\ContactAddress;
use App\Entity\Currency;
use App\Entity\MeasurementUnit;
use App\Entity\Selection;
use App\Service\PdfGeneratorService;
use App\Service\CreateMethodsByInput;
use App\Service\DoResponseService;
use App\Service\GroupSerializerService;
use App\Service\ClientOrderRowService;
use App\Service\ValidatorOutputFormatter;
use App\Service\ActionLoggerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'order')]
final class ClientOrderRowController extends AbstractController
{
    private $createMethodsByInput;
    private $doctrine;
    private $doResponse;
    private $groupSerializer;
    private $validatorOutputFormatter;
    private $actionLogger;
    private $pdfGenerator;
    private $clientOrderRowService;

    public function __construct(
        CreateMethodsByInput     $createMethodsByInput,
        EntityManagerInterface   $entityManager,
        DoResponseService        $doResponseService,
        GroupSerializerService   $groupSerializer,
        ValidatorOutputFormatter $validatorOutputFormatter,
        ActionLoggerService      $actionLogger,
        PdfGeneratorService      $pdfGenerator,
        ClientOrderRowService    $clientOrderRowService,
    )
    {
        $this->createMethodsByInput = $createMethodsByInput;
        $this->doctrine = $entityManager;
        $this->doResponse = $doResponseService;
        $this->groupSerializer = $groupSerializer;
        $this->validatorOutputFormatter = $validatorOutputFormatter;
        $this->actionLogger = $actionLogger;
        $this->pdfGenerator = $pdfGenerator;
        $this->clientOrderRowService = $clientOrderRowService;
    }

    #[Route('/client-order-row/{id}/close', name: 'close_client_order_row', requirements: ['id' => '\d+'], methods: ['PATCH'])]
    public function closeClientOrderRow(int $id): JsonResponse
    {
        $row = $this->doctrine->getRepository(ClientOrderRow::class)->find($id);
        if (!$row) {
            return $this->doResponse->doErrorJsonResponse('Riga ordine non trovata', 404);
        }

        $this->clientOrderRowService->manualCloseRow($row);

        return new JsonResponse($this->doResponse->doResponse(['message' => 'Riga ordine chiusa con successo']));
    }

    #[Route('/client-order-row/weekly-averages',
        name: 'get_client_order_row_weekly_averages',
        methods: ['GET'])]
    #[OA\Get(
        path: '/client-order-row/weekly-averages',
        summary: 'Calcolo prezzi medi per PQ e MQ aggregati per settimana/date, raggruppati per tipo pellame e valuta',
        parameters: [
            new OA\Parameter(
                name: 'start_date',
                in: 'query',
                description: 'Data inizio intervallo (formato YYYY-MM-DD). Default: Lunedì della settimana corrente',
                required: false,
                schema: new OA\Schema(type: 'string', format: 'date')
            ),
            new OA\Parameter(
                name: 'end_date',
                in: 'query',
                description: 'Data fine intervallo (formato YYYY-MM-DD). Default: Domenica della settimana corrente',
                required: false,
                schema: new OA\Schema(type: 'string', format: 'date')
            ),
            new OA\Parameter(
                name: 'client_id',
                in: 'query',
                description: 'Filtra per ID cliente specifico',
                required: false,
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'date_field',
                in: 'query',
                description: 'Campo data su cui filtrare: order_date (default) o delivery_date_confirmed',
                required: false,
                schema: new OA\Schema(type: 'string', enum: ['order_date', 'delivery_date_confirmed'], default: 'order_date')
            ),
            new OA\Parameter(
                name: 'internal',
                in: 'query',
                description: 'Filtro clienti interni: false (default, esclude interni), true (solo interni), all (tutti)',
                required: false,
                schema: new OA\Schema(type: 'string', enum: ['false', 'true', 'all'], default: 'false')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Statistiche e medie ponderate per tipo pellame e valuta, più riepilogo complessivo',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'items',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(
                                        property: 'leather_type',
                                        type: 'object',
                                        nullable: true,
                                        properties: [
                                            new OA\Property(property: 'id', type: 'integer'),
                                            new OA\Property(property: 'name', type: 'string'),
                                            new OA\Property(property: 'code', type: 'string', nullable: true)
                                        ]
                                    ),
                                    new OA\Property(
                                        property: 'currency',
                                        type: 'object',
                                        nullable: true,
                                        properties: [
                                            new OA\Property(property: 'id', type: 'integer'),
                                            new OA\Property(property: 'name', type: 'string'),
                                            new OA\Property(property: 'abbreviation', type: 'string', nullable: true),
                                            new OA\Property(property: 'sign', type: 'string', nullable: true)
                                        ]
                                    ),
                                    new OA\Property(property: 'rows_count', type: 'integer'),
                                    new OA\Property(property: 'total_amount', type: 'number', format: 'float'),
                                    new OA\Property(property: 'total_currency_amount', type: 'number', format: 'float'),
                                    new OA\Property(property: 'total_quantity_mq', type: 'number', format: 'float'),
                                    new OA\Property(property: 'total_quantity_pq', type: 'number', format: 'float'),
                                    new OA\Property(property: 'avg_price_mq', type: 'number', format: 'float'),
                                    new OA\Property(property: 'avg_price_pq', type: 'number', format: 'float')
                                ]
                            )
                        ),
                        new OA\Property(
                            property: 'summary',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'rows_count', type: 'integer'),
                                new OA\Property(property: 'total_amount', type: 'number', format: 'float'),
                                new OA\Property(property: 'total_quantity_mq', type: 'number', format: 'float'),
                                new OA\Property(property: 'total_quantity_pq', type: 'number', format: 'float'),
                                new OA\Property(property: 'avg_price_mq', type: 'number', format: 'float'),
                                new OA\Property(property: 'avg_price_pq', type: 'number', format: 'float')
                            ]
                        ),
                        new OA\Property(property: 'start_date', type: 'string', format: 'date', nullable: true),
                        new OA\Property(property: 'end_date', type: 'string', format: 'date', nullable: true),
                        new OA\Property(property: 'internal', type: 'boolean', nullable: true)
                    ]
                )
            )
        ]
    )]
    public function getWeeklyPriceAverages(Request $request): JsonResponse
    {
        $startDateStr = $request->query->get('start_date');
        $endDateStr = $request->query->get('end_date');
        $clientId = $request->query->get('client_id') ? (int)$request->query->get('client_id') : null;
        $dateField = $request->query->get('date_field', 'order_date');
        $internalParam = $request->query->get('internal'); // 'true', 'false', 'all' o null (default false: esclude interni)

        $internal = false;
        if ($internalParam !== null && $internalParam !== '') {
            if ($internalParam === 'all') {
                $internal = null;
            } else {
                $internal = filter_var($internalParam, FILTER_VALIDATE_BOOLEAN);
            }
        }

        // Se le date non sono specificate, prendiamo di default la settimana corrente (da lunedì a domenica)
        if (!$startDateStr && !$endDateStr) {
            $startDate = new \DateTime('monday this week');
            $endDate = new \DateTime('sunday this week');
        } else {
            $startDate = $startDateStr ? new \DateTime($startDateStr) : null;
            $endDate = $endDateStr ? new \DateTime($endDateStr) : null;
        }

        $averages = $this->doctrine->getRepository(ClientOrderRow::class)->getWeeklyPriceAverages(
            $startDate,
            $endDate,
            $clientId,
            $dateField,
            $internal
        );

        return new JsonResponse($this->doResponse->doResponse($averages));
    }

    #[Route('/client-order-row-report',
        name: 'get_client_order_row_report',
        methods: ['GET'])]
    #[OA\Get(
        path: '/client-order-row-report',
        summary: 'Report righe ordini cliente con filtri stato spedizione, produzione, stampa e clienti interni',
        parameters: [
            new OA\Parameter(name: 'start_date', in: 'query', description: 'Data inizio (YYYY-MM-DD)', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'end_date', in: 'query', description: 'Data fine (YYYY-MM-DD)', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'shipping_status', in: 'query', description: 'Stato spedizione: to_ship, shipped', required: false, schema: new OA\Schema(type: 'string', enum: ['to_ship', 'shipped'])),
            new OA\Parameter(name: 'production_status', in: 'query', description: 'Stato produzione: to_produce, produced', required: false, schema: new OA\Schema(type: 'string', enum: ['to_produce', 'produced'])),
            new OA\Parameter(name: 'print_status', in: 'query', description: 'Stato stampa: to_print, printed', required: false, schema: new OA\Schema(type: 'string', enum: ['to_print', 'printed'])),
            new OA\Parameter(name: 'client_id', in: 'query', description: 'ID Cliente', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'internal', in: 'query', description: 'Filtro clienti interni: true (solo interni), false (escludi interni), all (tutti)', required: false, schema: new OA\Schema(type: 'string', enum: ['true', 'false', 'all']))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Lista righe ordine filtrate')
        ]
    )]
    public function getClientOrderRowsReport(Request $request): JsonResponse
    {
        $startDate = $request->query->get('start_date');
        $endDate = $request->query->get('end_date');
        $shippingStatus = $request->query->get('shipping_status'); // 'to_ship', 'shipped'
        $productionStatus = $request->query->get('production_status'); // 'to_produce', 'produced'
        $printStatus = $request->query->get('print_status'); // 'to_print', 'printed'
        $clientId = $request->query->get('client_id');
        $internalParam = $request->query->get('internal'); // 'true', 'false', 'all' o null

        $qb = $this->doctrine->getRepository(ClientOrderRow::class)->createQueryBuilder('cor');
        $qb->join('cor.client_order', 'co')
           ->join('co.client', 'c');

        if ($startDate) {
            $qb->andWhere('co.order_date >= :startDate')
               ->setParameter('startDate', new \DateTime($startDate));
        }
        if ($endDate) {
            $qb->andWhere('co.order_date <= :endDate')
               ->setParameter('endDate', new \DateTime($endDate));
        }

        if ($clientId) {
            $qb->andWhere('c.id = :clientId')
               ->setParameter('clientId', $clientId);
        }

        if ($internalParam !== null && $internalParam !== '' && $internalParam !== 'all') {
            $isInternal = filter_var($internalParam, FILTER_VALIDATE_BOOLEAN);
            if ($isInternal) {
                $qb->andWhere('c.is_internal = true');
            } else {
                $qb->andWhere('c.is_internal = false OR c.is_internal IS NULL');
            }
        }

        if ($shippingStatus === 'to_ship') {
            $qb->andWhere('cor.quantity_to_ship > 0');
        } elseif ($shippingStatus === 'shipped') {
            $qb->andWhere('cor.quantity_to_ship <= 0 OR cor.quantity_to_ship IS NULL');
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

        // Filtro produzione e preparazione report (lista piatta)
        $report = [];
        foreach ($rows as $row) {
            $missing = (float)$row->getMissingQuantity();
            $isProduced = $missing <= 0;

            if ($productionStatus === 'produced' && !$isProduced) continue;
            if ($productionStatus === 'to_produce' && $isProduced) continue;

            $report[] = $this->groupSerializer->serializeGroup($row, 'client_order_row_list');
        }

        return new JsonResponse($this->doResponse->doResponse($report));
    }

    #[Route('/client/client-order-row-summary-print',
        name: 'get_client_order_row_summary_print',
        methods: ['GET'])]
    #[OA\Get(
        path: '/client/client-order-row-summary-print',
        summary: 'Generazione PDF riepilogo ordini cliente con filtri stato spedizione, produzione, stampa e clienti interni',
        parameters: [
            new OA\Parameter(name: 'client_id', in: 'query', description: 'ID Cliente', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'start_date', in: 'query', description: 'Data inizio (YYYY-MM-DD)', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'end_date', in: 'query', description: 'Data fine (YYYY-MM-DD)', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'shipping_status', in: 'query', description: 'Stato spedizione: to_ship, shipped', required: false, schema: new OA\Schema(type: 'string', enum: ['to_ship', 'shipped'])),
            new OA\Parameter(name: 'production_status', in: 'query', description: 'Stato produzione: to_produce, produced', required: false, schema: new OA\Schema(type: 'string', enum: ['to_produce', 'produced'])),
            new OA\Parameter(name: 'print_status', in: 'query', description: 'Stato stampa: to_print, printed', required: false, schema: new OA\Schema(type: 'string', enum: ['to_print', 'printed'])),
            new OA\Parameter(name: 'internal', in: 'query', description: 'Filtro clienti interni: true (solo interni), false (escludi interni), all (tutti)', required: false, schema: new OA\Schema(type: 'string', enum: ['true', 'false', 'all']))
        ],
        responses: [
            new OA\Response(response: 200, description: 'File PDF generato')
        ]
    )]
    public function getClientSummaryPrint(Request $request): \Symfony\Component\HttpFoundation\Response
    {
        $clientId = $request->query->get('client_id');
        $startDate = $request->query->get('start_date');
        $endDate = $request->query->get('end_date');
        $shippingStatus = $request->query->get('shipping_status'); // 'to_ship', 'shipped'
        $productionStatus = $request->query->get('production_status'); // 'to_produce', 'produced'
        $printStatus = $request->query->get('print_status'); // 'to_print', 'printed'
        $internalParam = $request->query->get('internal'); // 'true', 'false', 'all' o null

        $qb = $this->doctrine->getRepository(ClientOrderRow::class)->createQueryBuilder('cor');
        $qb->join('cor.client_order', 'co')
            ->join('co.client', 'c')
            ->orderBy('c.name', 'ASC')
            ->addOrderBy('co.order_date', 'ASC')
            ->addOrderBy('cor.id', 'ASC');

        if ($startDate) {
            $qb->andWhere('co.order_date >= :startDate')
                ->setParameter('startDate', new \DateTime($startDate));
        }
        if ($endDate) {
            $qb->andWhere('co.order_date <= :endDate')
                ->setParameter('endDate', new \DateTime($endDate));
        }
        if ($clientId) {
            $qb->andWhere('c.id = :clientId')
                ->setParameter('clientId', $clientId);
        }

        if ($internalParam !== null && $internalParam !== '' && $internalParam !== 'all') {
            $isInternal = filter_var($internalParam, FILTER_VALIDATE_BOOLEAN);
            if ($isInternal) {
                $qb->andWhere('c.is_internal = true');
            } else {
                $qb->andWhere('c.is_internal = false OR c.is_internal IS NULL');
            }
        }

        if ($shippingStatus === 'to_ship') {
            $qb->andWhere('cor.quantity_to_ship > 0');
        } elseif ($shippingStatus === 'shipped') {
            $qb->andWhere('cor.quantity_to_ship <= 0 OR cor.quantity_to_ship IS NULL');
        }

        if ($printStatus === 'printed') {
            $qb->andWhere('co.printed = true');
        } elseif ($printStatus === 'to_print') {
            $qb->andWhere('co.printed = false OR co.printed IS NULL');
        }

        /** @var ClientOrderRow[] $rows */
        $rows = $qb->getQuery()->getResult();

        $groupedData = [];
        foreach ($rows as $row) {
            $totalProduced = 0;
            foreach ($row->getBatchOrders() as $bo) {
                $batch = $bo->getBatch();
                if ($batch) {
                    $totalProduced += (float)$batch->getQuantity();
                }
            }

            // Consideriamo "prodotto" se non c'è più differenza tra quantità ordinata e prodotta (lotti)
            $difference = (float)$row->getQuantity() - $totalProduced;
            $isProduced = $difference <= 0;

            if ($productionStatus === 'produced' && !$isProduced) continue;
            if ($productionStatus === 'to_produce' && $isProduced) continue;

            $client = $row->getClientOrder()->getClient();
            if (!$client) continue;

            $cId = $client->getId();
            if (!isset($groupedData[$cId])) {
                // Prendi gli indirizzi SEDE e DEST. DIVERSA
                $sedeAddress = null;
                $destDiversaAddress = null;
                foreach ($client->getContactAddresses() as $addr) {
                    if (strtoupper($addr->getAddressName()) === 'SEDE') {
                        $sedeAddress = $addr;
                    } elseif (strtoupper($addr->getAddressName()) === 'DEST. DIVERSA') {
                        $destDiversaAddress = $addr;
                    }
                }

                // Prendi il primo agente associato e la sua percentuale
                $firstAgent = null;
                $agentPercentage = null;
                $agents = $client->getContactAgents();
                if (!$agents->isEmpty()) {
                    $firstAgentEntity = $agents->first()->getAgent();
                    if ($firstAgentEntity) {
                        $firstAgent = $this->groupSerializer->serializeGroup($firstAgentEntity, 'client_summary_print');
                        $agentPercentage = $firstAgentEntity->getAgentPercentage();
                    }
                }

                $groupedData[$cId] = [
                    'client' => $this->groupSerializer->serializeGroup($client, 'client_summary_print'),
                    'sedeAddress' => $sedeAddress ? $this->groupSerializer->serializeGroup($sedeAddress, 'client_summary_print') : null,
                    'destDiversaAddress' => $destDiversaAddress ? $this->groupSerializer->serializeGroup($destDiversaAddress, 'client_summary_print') : null,
                    'firstAgent' => $firstAgent,
                    'agentPercentage' => $agentPercentage,
                    'orders' => []
                ];
            }

            $order = $row->getClientOrder();
            $orderId = $order->getId();
            if (!isset($groupedData[$cId]['orders'][$orderId])) {
                $groupedData[$cId]['orders'][$orderId] = [
                    'details' => $this->groupSerializer->serializeGroup($order, 'client_summary_print'),
                    'rows' => []
                ];
            }

            // Dati DDT: OrderRow->BatchOrder->Batch->ddtRow solo per ddt di tipo Vendita
            $ddtData = null;
            foreach ($row->getBatchOrders() as $bo) {
                $batch = $bo->getBatch();
                if ($batch) {
                    foreach ($batch->getDdtRows() as $dr) {
                        $ddt = $dr->getDdt();
                        if ($ddt && $ddt->getReason() && $ddt->getReason()->getName() === 'Vendita') {
                            $ddtData = $this->groupSerializer->serializeGroup($dr, 'client_summary_print');
                            break 2;
                        }
                    }
                }
            }

            $rowSerialized = $this->groupSerializer->serializeGroup($row, 'client_summary_print');
            $rowSerialized['ddt_row'] = $ddtData;
            $rowSerialized['batch_orders'] = $this->groupSerializer->serializeGroup($row->getBatchOrders(), 'client_summary_print');

            $groupedData[$cId]['orders'][$orderId]['rows'][] = $rowSerialized;
        }

        // Trasformiamo in array semplice per Twig
        $finalData = [];
        foreach ($groupedData as $clientData) {
            $clientData['orders'] = array_values($clientData['orders']);
            $finalData[] = $clientData;
        }

        $this->doctrine->flush();

        $pdfContent = $this->pdfGenerator->generatePdf('print/client_summary_pdf.html.twig', [
            'data' => $finalData,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'orientation' => 'landscape'
        ], 'riepilogo_clienti.pdf');

        return new \Symfony\Component\HttpFoundation\Response($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="riepilogo_clienti.pdf"'
        ]);
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
            $address = $this->doctrine->getRepository(ContactAddress::class)->find($data['address_id']);
            if ($address) {
                $clientOrderRow->setAddress($address);
            }
            unset($data['address_id']);
        }

        return $clientOrderRow;
    }

    private function calculatePrices(ClientOrderRow $clientOrderRow): void
    {
        $this->clientOrderRowService->calculatePricesAndUnits($clientOrderRow);
    }
}

