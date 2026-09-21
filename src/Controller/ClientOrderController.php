<?php

namespace App\Controller;

use App\Entity\Article;
use App\Entity\ClientOrder;
use App\Entity\ClientOrderRow;
use App\Entity\Contact;
use App\Entity\ContactAddress;
use App\Entity\Payment;
use App\Entity\Product;
use App\Entity\ShipmentCondition;
use App\Entity\ShippingCarrier;
use App\Entity\User;
use App\Service\CreateMethodsByInput;
use App\Service\DoResponseService;
use App\Service\GroupSerializerService;
use App\Service\ClientOrderRowService;
use App\Service\ValidatorOutputFormatter;
use App\Service\PdfGeneratorService;
use App\Service\ActionLoggerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'order')]
final class ClientOrderController extends AbstractController
{
    private $createMethodsByInput;
    private $doctrine;
    private $doResponse;
    private $groupSerializer;
    private $validatorOutputFormatter;
    private $pdfGenerator;
    private $actionLogger;
    private $clientOrderRowService;

    public function __construct(
        CreateMethodsByInput     $createMethodsByInput,
        EntityManagerInterface   $entityManager,
        DoResponseService        $doResponseService,
        GroupSerializerService   $groupSerializer,
        ValidatorOutputFormatter $validatorOutputFormatter,
        PdfGeneratorService      $pdfGenerator,
        ActionLoggerService      $actionLogger,
        ClientOrderRowService    $clientOrderRowService,
    )
    {
        $this->createMethodsByInput = $createMethodsByInput;
        $this->doctrine = $entityManager;
        $this->doResponse = $doResponseService;
        $this->groupSerializer = $groupSerializer;
        $this->validatorOutputFormatter = $validatorOutputFormatter;
        $this->pdfGenerator = $pdfGenerator;
        $this->actionLogger = $actionLogger;
        $this->clientOrderRowService = $clientOrderRowService;
    }

    #[Route('/client-order/{id}/close', name: 'close_client_order', requirements: ['id' => '\d+'], methods: ['PATCH'])]
    public function closeClientOrder(int $id): JsonResponse
    {
        $order = $this->doctrine->getRepository(ClientOrder::class)->find($id);
        if (!$order) {
            return $this->doResponse->doErrorJsonResponse('Ordine non trovato', 404);
        }

        $this->clientOrderRowService->manualCloseOrder($order);

        return new JsonResponse($this->doResponse->doResponse(['message' => 'Ordine chiuso con successo']));
    }

    #[Route('/client-order/{id}',
        name: 'get_client_order',
        defaults: ['id' => null],
        requirements: ['id' => '\d*'],
        methods: ['GET', 'HEAD'])]
    public function getClientOrder(?int $id, Request $request): JsonResponse
    {
        $clientOrderRepository = $this->doctrine->getRepository(ClientOrder::class);

        if ($id) {
            $clientOrder = [$clientOrderRepository->find($id)];
            if (!$clientOrder[0]) {
                return $this->doResponse->doErrorJsonResponse('ClientOrder not found', 404);
            }
        } else {
            $orderNumber = $request->query->get('order_number') ?? $request->query->get('code');
            $year = $request->query->get('year');
            $client = $request->query->get('client_id') ?? $request->query->get('client');
            $agent = $request->query->get('agent_id') ?? $request->query->get('agent');
            $payment = $request->query->get('payment_id') ?? $request->query->get('payment');
            $shipmentCondition = $request->query->get('shipment_condition_id') ?? $request->query->get('shipment_condition');
            $shippingCarrier = $request->query->get('shipping_carrier_id') ?? $request->query->get('shipping_carrier');
            $startDate = $request->query->get('start_date');
            $endDate = $request->query->get('end_date');
            $clientOrderNumber = $request->query->get('client_order_number');
            $agentOrderNumber = $request->query->get('agent_order_number');
            $article = $request->query->get('article_id') ?? $request->query->get('article');
            $product = $request->query->get('product_id') ?? $request->query->get('product');
            $processed = $request->query->get('processed');
            $cancelled = $request->query->get('cancelled');
            $checked = $request->query->get('checked');
            $printed = $request->query->get('printed') ?? $request->query->get('print_status');

            $hasFilters = $orderNumber !== null ||
                $year !== null ||
                $client !== null ||
                $agent !== null ||
                $payment !== null ||
                $shipmentCondition !== null ||
                $shippingCarrier !== null ||
                $startDate !== null ||
                $endDate !== null ||
                $clientOrderNumber !== null ||
                $agentOrderNumber !== null ||
                $article !== null ||
                $product !== null ||
                $processed !== null ||
                $cancelled !== null ||
                $checked !== null ||
                $printed !== null;

            if ($hasFilters) {
                $qb = $clientOrderRepository->createQueryBuilder('c')
                    ->select('DISTINCT c');

                if ($orderNumber !== null && $orderNumber !== '') {
                    $normalizedNumber = str_replace('0', '', (string)$orderNumber);
                    $qb->andWhere("REPLACE(c.order_number, '0', '') LIKE :order_number")
                        ->setParameter('order_number', '%' . $normalizedNumber . '%');
                }

                if ($year !== null && $year !== '') {
                    $qb->andWhere('YEAR(c.order_date) = :year')
                        ->setParameter('year', $year);
                }

                if ($startDate !== null && $startDate !== '') {
                    try {
                        $qb->andWhere('c.order_date >= :startDate')
                            ->setParameter('startDate', new \DateTime($startDate));
                    } catch (\Exception $e) {
                    }
                }

                if ($endDate !== null && $endDate !== '') {
                    try {
                        $qb->andWhere('c.order_date <= :endDate')
                            ->setParameter('endDate', new \DateTime($endDate));
                    } catch (\Exception $e) {
                    }
                }

                if ($client !== null && $client !== '') {
                    $clientEntity = $this->doctrine->getRepository(Contact::class)->find($client);
                    if ($clientEntity) {
                        $qb->andWhere('c.client = :client')
                            ->setParameter('client', $clientEntity);
                    } else {
                        $qb->andWhere('1 = 0');
                    }
                }

                if ($agent !== null && $agent !== '') {
                    $agentEntity = $this->doctrine->getRepository(Contact::class)->find($agent);
                    if ($agentEntity) {
                        $qb->andWhere('c.agent = :agent')
                            ->setParameter('agent', $agentEntity);
                    } else {
                        $qb->andWhere('1 = 0');
                    }
                }

                if ($payment !== null && $payment !== '') {
                    $paymentEntity = $this->doctrine->getRepository(Payment::class)->find($payment);
                    if ($paymentEntity) {
                        $qb->andWhere('c.payment = :payment')
                            ->setParameter('payment', $paymentEntity);
                    } else {
                        $qb->andWhere('1 = 0');
                    }
                }

                if ($shipmentCondition !== null && $shipmentCondition !== '') {
                    $shipmentConditionEntity = $this->doctrine->getRepository(ShipmentCondition::class)->find($shipmentCondition);
                    if ($shipmentConditionEntity) {
                        $qb->andWhere('c.shipment_condition = :shipment_condition')
                            ->setParameter('shipment_condition', $shipmentConditionEntity);
                    } else {
                        $qb->andWhere('1 = 0');
                    }
                }

                if ($shippingCarrier !== null && $shippingCarrier !== '') {
                    $shippingCarrierEntity = $this->doctrine->getRepository(ShippingCarrier::class)->find($shippingCarrier);
                    if ($shippingCarrierEntity) {
                        $qb->andWhere('c.shipping_carrier = :shipping_carrier')
                            ->setParameter('shipping_carrier', $shippingCarrierEntity);
                    } else {
                        $qb->andWhere('1 = 0');
                    }
                }

                if ($clientOrderNumber !== null && $clientOrderNumber !== '') {
                    $qb->andWhere('c.client_order_number LIKE :client_order_number')
                        ->setParameter('client_order_number', '%' . $clientOrderNumber . '%');
                }

                if ($agentOrderNumber !== null && $agentOrderNumber !== '') {
                    $qb->andWhere('c.agent_order_number LIKE :agent_order_number')
                        ->setParameter('agent_order_number', '%' . $agentOrderNumber . '%');
                }

                if ($processed !== null && $processed !== '') {
                    $isProcessed = filter_var($processed, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                    if ($isProcessed !== null) {
                        $qb->andWhere('c.processed = :processed')
                            ->setParameter('processed', $isProcessed);
                    }
                }

                if ($cancelled !== null && $cancelled !== '') {
                    $isCancelled = filter_var($cancelled, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                    if ($isCancelled !== null) {
                        $qb->andWhere('c.cancelled = :cancelled')
                            ->setParameter('cancelled', $isCancelled);
                    }
                }

                if ($checked !== null && $checked !== '') {
                    $isChecked = filter_var($checked, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                    if ($isChecked !== null) {
                        $qb->andWhere('c.checked = :checked')
                            ->setParameter('checked', $isChecked);
                    }
                }

                if ($printed !== null && $printed !== '') {
                    if ($printed === 'printed' || $printed === '1' || $printed === 'true' || $printed === true) {
                        $qb->andWhere('c.printed = true');
                    } elseif ($printed === 'to_print' || $printed === '0' || $printed === 'false' || $printed === false) {
                        $qb->andWhere('c.printed = false OR c.printed IS NULL');
                    }
                }

                if (($article !== null && $article !== '') || ($product !== null && $product !== '')) {
                    $qb->leftJoin('c.clientOrderRows', 'cor');
                    if ($article !== null && $article !== '') {
                        $articleEntity = $this->doctrine->getRepository(Article::class)->find($article);
                        if ($articleEntity) {
                            $qb->andWhere('cor.article = :article')
                                ->setParameter('article', $articleEntity);
                        } else {
                            $qb->andWhere('1 = 0');
                        }
                    }
                    if ($product !== null && $product !== '') {
                        $productEntity = $this->doctrine->getRepository(Product::class)->find($product);
                        if ($productEntity) {
                            $qb->leftJoin('cor.article', 'a')
                                ->andWhere('a.product = :product')
                                ->setParameter('product', $productEntity);
                        } else {
                            $qb->andWhere('1 = 0');
                        }
                    }
                }

                $clientOrder = $qb->orderBy('c.order_number', 'ASC')
                    ->getQuery()
                    ->getResult();
            } else {
                $clientOrder = $clientOrderRepository->findBy([], ['order_number' => 'ASC']);
            }
        }
        $results = $this->groupSerializer->serializeGroup($clientOrder, $id ? 'client_order_detail' : 'client_order_list');

        if ($id) {
            return new JsonResponse($this->doResponse->doResponse($results[0]));
        }
        return new JsonResponse($this->doResponse->doResponse($results));
    }

    #[Route('/client-order/{id}/pdf',
        name: 'get_client_order_pdf',
        requirements: ['id' => '\d+'],
        methods: ['GET'])]
    public function generateClientOrderPdf(int $id): Response
    {
        $order = $this->doctrine->getRepository(ClientOrder::class)->find($id);

        if (!$order) {
            return $this->doResponse->doErrorJsonResponse('ClientOrder not found', 404);
        }

        $pdfContent = $this->pdfGenerator->generatePdf('print/client_order_confirmation_pdf.html.twig', [
            'order' => $order,
            'app_root' => $this->getParameter('kernel.project_dir')
        ], 'conferma_ordine_' . $order->getOrderNumber() . '.pdf');

        return new Response($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="conferma_ordine_' . $order->getOrderNumber() . '.pdf"'
        ]);
    }

    #[Route('/client-order/production-report/pdf',
        name: 'get_client_order_production_report_pdf',
        methods: ['GET'])]
    public function generateProductionReportPdf(Request $request): Response
    {
        $startDateStr = $request->query->get('start_date');
        $endDateStr = $request->query->get('end_date');
        $printedStatus = $request->query->get('print_status', 'to_print');

        $startDate = null;
        if ($startDateStr) {
            try {
                $startDate = new \DateTime($startDateStr);
            } catch (\Exception $e) {
            }
        }

        $endDate = null;
        if ($endDateStr) {
            try {
                $endDate = new \DateTime($endDateStr);
            } catch (\Exception $e) {
            }
        }

        $rows = $this->doctrine->getRepository(ClientOrderRow::class)->findByProductionCriteria($startDate, $endDate, $printedStatus);

        $groupedRows = [];
        foreach ($rows as $row) {
            $typeName = 'Nessun Tipo';
            if ($row->getArticle() && $row->getArticle()->getArticleType()) {
                $typeName = $row->getArticle()->getArticleType()->getName();
            }
            $groupedRows[$typeName][] = $row;
        }

        $pdfContent = $this->pdfGenerator->generatePdf('print/production_report_pdf.html.twig', [
            'groupedRows' => $groupedRows,
            'date' => new \DateTime(),
            'app_root' => $this->getParameter('kernel.project_dir')
        ], 'programma_produzione.pdf');

        // Segna gli ordini coinvolti come stampati (solo se non lo erano già)
        $em = $this->doctrine;
        $ordersToUpdate = [];
        foreach ($rows as $row) {
            $order = $row->getClientOrder();
            if ($order && !$order->isPrinted()) {
                $ordersToUpdate[$order->getId()] = $order;
            }
        }
        foreach ($ordersToUpdate as $order) {
            $order->setPrinted(true);
            $order->setPrintDate(new \DateTime());
        }
        $em->flush();

        return new Response($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="programma_produzione.pdf"'
        ]);
    }

    #[Route('/client-order',
        name: 'post_client_order',
        methods: ['POST'])]
    public function postClientOrder(
        Request            $request,
        ValidatorInterface $validator,
    ): JsonResponse
    {
        $data = $request->request->all();
        $clientOrder = new ClientOrder();

        try {
            $clientOrder = $this->handleRelations($clientOrder, $data);
            $clientOrder = $this->createMethodsByInput->createMethods($clientOrder, $data);

            if (!$clientOrder->getOrderNumber()) {
                $clientOrder->setOrderNumber($this->doctrine->getRepository(ClientOrder::class)->generateNextOrderNumber());
            }

            $errors = $validator->validate($clientOrder);
            if (count($errors) > 0) {
                $errors = $this->validatorOutputFormatter->formatOutput($errors);
                return $this->doResponse->doErrorJsonResponse($errors);
            }

            $em = $this->doctrine;
            $em->persist($clientOrder);
            $em->flush();

            $result = $this->groupSerializer->serializeGroup($clientOrder, 'client_order_detail');
            return new JsonResponse($this->doResponse->doResponse($result));

        } catch (\Exception $e) {
            return $this->doResponse->doErrorJsonResponse($e->getMessage());
        }
    }

    #[Route('/client-order/{id}',
        name: 'put_client_order',
        methods: ['PUT'])]
    public function modifyClientOrder(
        Request            $request,
        ValidatorInterface $validator,
        int                $id,
    ): JsonResponse
    {
        $data = $request->toArray();
        $clientOrder = $this->doctrine->getRepository(ClientOrder::class)->find($id);

        if (!$clientOrder) {
            return $this->doResponse->doErrorJsonResponse('ClientOrder not found', 404);
        }

        if ($clientOrder->isChecked()) {
            return $this->doResponse->doErrorJsonResponse('Non è possibile modificare un ordine già controllato', 403);
        }

        try {
            $clientOrder = $this->handleRelations($clientOrder, $data);
            $clientOrder = $this->createMethodsByInput->createMethods($clientOrder, $data);

            $errors = $validator->validate($clientOrder);
            if (count($errors) > 0) {
                $errors = $this->validatorOutputFormatter->formatOutput($errors);
                return $this->doResponse->doErrorJsonResponse($errors);
            }

            $this->doctrine->persist($clientOrder);
            $this->doctrine->flush();

            $result = $this->groupSerializer->serializeGroup($clientOrder, 'client_order_detail');
            return new JsonResponse($this->doResponse->doResponse($result));
        } catch (\Exception $e) {
            return $this->doResponse->doErrorJsonResponse($e->getMessage());
        }
    }

    #[Route('/client-order/{id}/check',
        name: 'put_client_order_check',
        methods: ['PUT'])]
    public function checkClientOrder(int $id): JsonResponse
    {
        $clientOrder = $this->doctrine->getRepository(ClientOrder::class)->find($id);

        if (!$clientOrder) {
            return $this->doResponse->doErrorJsonResponse('ClientOrder not found', 404);
        }

        try {
            $newStatus = !$clientOrder->isChecked();
            $clientOrder->setChecked($newStatus);

            if ($newStatus) {
                $clientOrder->setCheckDate(new \DateTime());
            } else {
                $clientOrder->setCheckDate(null);
            }

            $this->doctrine->persist($clientOrder);
            $this->doctrine->flush();

            $this->actionLogger->logAction('check_client_order', [
                'id' => $clientOrder->getId(),
                'order_number' => $clientOrder->getOrderNumber(),
                'checked' => $newStatus
            ]);

            $result = $this->groupSerializer->serializeGroup($clientOrder, 'client_order_detail');
            return new JsonResponse($this->doResponse->doResponse($result));
        } catch (\Exception $e) {
            return $this->doResponse->doErrorJsonResponse($e->getMessage());
        }
    }

    #[Route('/client-order/{id}',
        name: 'delete_client_order',
        methods: ['DELETE'])]
    public function deleteClientOrder(int $id): JsonResponse
    {
        $clientOrder = $this->doctrine->getRepository(ClientOrder::class)->find($id);
        if (!$clientOrder) {
            return $this->doResponse->doErrorJsonResponse('ClientOrder not found', 404);
        }

        $this->doctrine->remove($clientOrder);
        $this->doctrine->flush();

        return new JsonResponse($this->doResponse->doResponse('delete_successfully'));
    }

    private function handleRelations(ClientOrder $clientOrder, array &$data): ClientOrder
    {
        if (isset($data['client_id'])) {
            $client = $this->doctrine->getRepository(Contact::class)->find($data['client_id']);
            if ($client) {
                $clientOrder->setClient($client);
            }
            unset($data['client_id']);
        }

        if (isset($data['agent_id'])) {
            $agent = $this->doctrine->getRepository(Contact::class)->find($data['agent_id']);
            if ($agent) {
                $clientOrder->setAgent($agent);
            }
            unset($data['agent_id']);
        }

        if (isset($data['payment_id'])) {
            $payment = $this->doctrine->getRepository(Payment::class)->find($data['payment_id']);
            if ($payment) {
                $clientOrder->setPayment($payment);
            }
            unset($data['payment_id']);
        }

        if (isset($data['check_user_id'])) {
            $user = $this->doctrine->getRepository(User::class)->find($data['check_user_id']);
            if ($user) {
                $clientOrder->setCheckUser($user);
            }
            unset($data['check_user_id']);
        }

        if(isset($data['shipment_condition_id'])) {
            $shipmentCondition = $this->doctrine->getRepository(ShipmentCondition::class)->find($data['shipment_condition_id']);
            if ($shipmentCondition) {
                $clientOrder->setShipmentCondition($shipmentCondition);
            }
            unset($data['shipment_condition_id']);
        }
        if(isset($data['address_id'])) {
            $address = $this->doctrine->getRepository(ContactAddress::class)->find($data['address_id']);
            if ($address) {
                $clientOrder->setAddress($address);
            }
            unset($data['address_id']);
        }
        if(isset($data['shipping_carrier_id'])) {
            $carrier = $this->doctrine->getRepository(ShippingCarrier::class)->find($data['shipping_carrier_id']);
            if ($carrier) {
                $clientOrder->setShippingCarrier($carrier);
            }
            unset($data['shipping_carrier_id']);
        }

        return $clientOrder;
    }
}

