<?php

namespace App\Controller;

use App\Entity\Client;
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

final class ClientController extends AbstractController
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

    #[Route('/client/{id}',
        name: 'get_client',
        defaults: ['id' => null],
        requirements: ['id' => '\d*'],
        methods: ['GET', 'HEAD'])]
    public function getClient(?int $id): JsonResponse
    {
        $clientRepository = $this->doctrine->getRepository(Client::class);

        if ($id) {
            $client = [$clientRepository->find($id)];
            if (!$client[0]) {
                return new JsonResponse($this->doResponse->doErrorResponse('Client not found', 404));
            }
        } else {
            $client = $clientRepository->findBy([], ['id' => 'DESC']);
        }
        $results = $this->groupSerializer->serializeGroup($client, $id ? 'client_detail' : 'client_list');

        if ($id) {
            return new JsonResponse($this->doResponse->doResponse($results[0]));
        }
        return new JsonResponse($this->doResponse->doResponse($results));
    }

    #[Route('/client',
        name: 'post_client',
        methods: ['POST'])]
    public function postClient(
        Request            $request,
        ValidatorInterface $validator,
    ): JsonResponse
    {
        $data = $request->request->all();
        $client = new Client();

        try {
            $client = $this->createMethodsByInput->createMethods($client, $data);

            $errors = $validator->validate($client);
            if (count($errors) > 0) {
                $errors = $this->validatorOutputFormatter->formatOutput($errors);
                return new JsonResponse($this->doResponse->doErrorResponse($errors));
            }

            $em = $this->doctrine;
            $em->persist($client);
            $em->flush();

            $result = $this->groupSerializer->serializeGroup($client, 'client_detail');
            return new JsonResponse($this->doResponse->doResponse($result));

        } catch (\Exception $e) {
            return new JsonResponse($this->doResponse->doErrorResponse($e->getMessage()));
        }
    }

    #[Route('/client/{id}',
        name: 'put_client',
        methods: ['PUT'])]
    public function modifyClient(
        Request            $request,
        ValidatorInterface $validator,
        int                $id,
    ): JsonResponse
    {
        $data = $request->toArray();
        $client = $this->doctrine->getRepository(Client::class)->find($id);

        if (!$client) {
            return new JsonResponse($this->doResponse->doErrorResponse('Client not found', 404));
        }

        try {
            $client = $this->createMethodsByInput->createMethods($client, $data);

            $errors = $validator->validate($client);
            if (count($errors) > 0) {
                $errors = $this->validatorOutputFormatter->formatOutput($errors);
                return new JsonResponse($this->doResponse->doErrorResponse($errors));
            }

            $this->doctrine->persist($client);
            $this->doctrine->flush();

            $result = $this->groupSerializer->serializeGroup($client, 'client_detail');
            return new JsonResponse($this->doResponse->doResponse($result));
        } catch (\Exception $e) {
            return new JsonResponse($this->doResponse->doErrorResponse($e->getMessage()));
        }
    }

    #[Route('/client/{id}',
        name: 'delete_client',
        methods: ['DELETE'])]
    public function deleteClient(int $id): JsonResponse
    {
        $client = $this->doctrine->getRepository(Client::class)->find($id);
        if (!$client) {
            return new JsonResponse($this->doResponse->doErrorResponse('Client not found', 404));
        }

        $this->doctrine->remove($client);
        $this->doctrine->flush();

        return new JsonResponse($this->doResponse->doResponse('delete_successfully'));
    }
}
