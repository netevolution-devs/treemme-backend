<?php

namespace App\Controller;

use App\Entity\Client;
use App\Entity\Contact;
use App\Entity\ContactAddress;
use App\Entity\User;
use App\Entity\Payment;
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
            $client = $this->handleRelations($client, $data);
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
            $client = $this->handleRelations($client, $data);
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

    private function handleRelations(Client $client, array &$data): Client
    {
        if (isset($data['contact_id'])) {
            $contact = $this->doctrine->getRepository(Contact::class)->find($data['contact_id']);
            if ($contact) {
                $client->setContact($contact);
            }
            unset($data['contact_id']);
        }

        if (isset($data['address_id'])) {
            $address = $this->doctrine->getRepository(ContactAddress::class)->find($data['address_id']);
            if ($address) {
                $client->setAddress($address);
            }
            unset($data['address_id']);
        }

        if (isset($data['check_user_id'])) {
            $user = $this->doctrine->getRepository(User::class)->find($data['check_user_id']);
            if ($user) {
                $client->setCheckUser($user);
            }
            unset($data['check_user_id']);
        }

        if (isset($data['payment_id'])) {
            $payment = $this->doctrine->getRepository(Payment::class)->find($data['payment_id']);
            if ($payment) {
                $client->setPayment($payment);
            }
            unset($data['payment_id']);
        }

        return $client;
    }
}
