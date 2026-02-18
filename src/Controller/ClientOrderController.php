<?php

namespace App\Controller;

use App\Entity\ClientOrder;
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

final class ClientOrderController extends AbstractController
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
        ValidatorOutputFormatter $validatorOutputFormatter,
    )
    {
        $this->createMethodsByInput = $createMethodsByInput;
        $this->doctrine = $entityManager;
        $this->doResponse = $doResponseService;
        $this->groupSerializer = $groupSerializer;
        $this->validatorOutputFormatter = $validatorOutputFormatter;
    }

    #[Route('/client-order/{id}',
        name: 'get_client_order',
        defaults: ['id' => null],
        requirements: ['id' => '\d*'],
        methods: ['GET', 'HEAD'])]
    public function getClientOrder(?int $id): JsonResponse
    {
        $clientOrderRepository = $this->doctrine->getRepository(ClientOrder::class);

        if ($id) {
            $clientOrder = [$clientOrderRepository->find($id)];
            if (!$clientOrder[0]) {
                return new JsonResponse($this->doResponse->doErrorResponse('ClientOrder not found', 404));
            }
        } else {
            $clientOrder = $clientOrderRepository->findBy([], ['id' => 'DESC']);
        }
        $results = $this->groupSerializer->serializeGroup($clientOrder, $id ? 'client_order_detail' : 'client_order_list');

        if ($id) {
            return new JsonResponse($this->doResponse->doResponse($results[0]));
        }
        return new JsonResponse($this->doResponse->doResponse($results));
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
            $clientOrder = $this->createMethodsByInput->createMethods($clientOrder, $data);

            $errors = $validator->validate($clientOrder);
            if (count($errors) > 0) {
                $errors = $this->validatorOutputFormatter->formatOutput($errors);
                return new JsonResponse($this->doResponse->doErrorResponse($errors));
            }

            $em = $this->doctrine;
            $em->persist($clientOrder);
            $em->flush();

            $result = $this->groupSerializer->serializeGroup($clientOrder, 'client_order_detail');
            return new JsonResponse($this->doResponse->doResponse($result));

        } catch (\Exception $e) {
            return new JsonResponse($this->doResponse->doErrorResponse($e->getMessage()));
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
            return new JsonResponse($this->doResponse->doErrorResponse('ClientOrder not found', 404));
        }

        try {
            $clientOrder = $this->createMethodsByInput->createMethods($clientOrder, $data);

            $errors = $validator->validate($clientOrder);
            if (count($errors) > 0) {
                $errors = $this->validatorOutputFormatter->formatOutput($errors);
                return new JsonResponse($this->doResponse->doErrorResponse($errors));
            }

            $this->doctrine->persist($clientOrder);
            $this->doctrine->flush();

            $result = $this->groupSerializer->serializeGroup($clientOrder, 'client_order_detail');
            return new JsonResponse($this->doResponse->doResponse($result));
        } catch (\Exception $e) {
            return new JsonResponse($this->doResponse->doErrorResponse($e->getMessage()));
        }
    }

    #[Route('/client-order/{id}',
        name: 'delete_client_order',
        methods: ['DELETE'])]
    public function deleteClientOrder(int $id): JsonResponse
    {
        $clientOrder = $this->doctrine->getRepository(ClientOrder::class)->find($id);
        if (!$clientOrder) {
            return new JsonResponse($this->doResponse->doErrorResponse('ClientOrder not found', 404));
        }

        $this->doctrine->remove($clientOrder);
        $this->doctrine->flush();

        return new JsonResponse($this->doResponse->doResponse('delete_successfully'));
    }
}
