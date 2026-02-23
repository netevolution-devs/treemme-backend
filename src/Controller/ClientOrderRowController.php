<?php

namespace App\Controller;

use App\Entity\ClientOrderRow;
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

final class ClientOrderRowController extends AbstractController
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
                return new JsonResponse($this->doResponse->doErrorResponse('ClientOrderRow not found', 404));
            }
        } else {
            $clientOrderRow = $clientOrderRowRepository->findBy([], ['id' => 'DESC']);
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
            $clientOrderRow = $this->createMethodsByInput->createMethods($clientOrderRow, $data);

            $errors = $validator->validate($clientOrderRow);
            if (count($errors) > 0) {
                $errors = $this->validatorOutputFormatter->formatOutput($errors);
                return new JsonResponse($this->doResponse->doErrorResponse($errors));
            }

            $em = $this->doctrine;
            $em->persist($clientOrderRow);
            $em->flush();

            $result = $this->groupSerializer->serializeGroup($clientOrderRow, 'client_order_row_detail');
            return new JsonResponse($this->doResponse->doResponse($result));

        } catch (\Exception $e) {
            return new JsonResponse($this->doResponse->doErrorResponse($e->getMessage()));
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
            return new JsonResponse($this->doResponse->doErrorResponse('ClientOrderRow not found', 404));
        }

        try {
            $clientOrderRow = $this->createMethodsByInput->createMethods($clientOrderRow, $data);

            $errors = $validator->validate($clientOrderRow);
            if (count($errors) > 0) {
                $errors = $this->validatorOutputFormatter->formatOutput($errors);
                return new JsonResponse($this->doResponse->doErrorResponse($errors));
            }

            $this->doctrine->persist($clientOrderRow);
            $this->doctrine->flush();

            $result = $this->groupSerializer->serializeGroup($clientOrderRow, 'client_order_row_detail');
            return new JsonResponse($this->doResponse->doResponse($result));
        } catch (\Exception $e) {
            return new JsonResponse($this->doResponse->doErrorResponse($e->getMessage()));
        }
    }

    #[Route('/client-order-row/{id}',
        name: 'delete_client_order_row',
        methods: ['DELETE'])]
    public function deleteClientOrderRow(int $id): JsonResponse
    {
        $clientOrderRow = $this->doctrine->getRepository(ClientOrderRow::class)->find($id);
        if (!$clientOrderRow) {
            return new JsonResponse($this->doResponse->doErrorResponse('ClientOrderRow not found', 404));
        }

        $this->doctrine->remove($clientOrderRow);
        $this->doctrine->flush();

        return new JsonResponse($this->doResponse->doResponse('delete_successfully'));
    }
}
