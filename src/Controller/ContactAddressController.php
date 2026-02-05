<?php

namespace App\Controller;

use App\Entity\Contact;
use App\Entity\ContactAddress;
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

final class ContactAddressController extends AbstractController
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

    #[Route('/contact-address/{id}',
        name: 'get_contact_address',
        defaults: ['id' => null],
        requirements: ['id' => '\d*'],
        methods: ['GET', 'HEAD'])]
    public function getContactAddress(?int $id): JsonResponse
    {
        $contactAddressRepository = $this->doctrine->getRepository(ContactAddress::class);

        if ($id) {
            $address = [$contactAddressRepository->find($id)];
            if (!$address[0]) {
                return new JsonResponse($this->doResponse->doErrorResponse('ContactAddress not found', 404));
            }
        } else {
            $address = $contactAddressRepository->findBy([], ['id' => 'DESC']);
        }
        $results = $this->groupSerializer->serializeGroup($address, $id ? 'contact_address_detail' : 'contact_address_list');

        if ($id) {
            return new JsonResponse($this->doResponse->doResponse($results[0]));
        }
        return new JsonResponse($this->doResponse->doResponse($results));
    }

    #[Route('/contact-address',
        name: 'post_contact_address',
        methods: ['POST'])]
    public function postContactAddress(
        Request            $request,
        ValidatorInterface $validator,
    ): JsonResponse
    {
        $data = $request->request->all();
        $address = new ContactAddress();

        try {
            $address = $this->handleRelations($address, $data);
            $address = $this->createMethodsByInput->createMethods($address, $data);

            $now = new \DateTimeImmutable();
            $address->setCreatedAt($now);
            $address->setUpdatedAt($now);

            $errors = $validator->validate($address);
            if (count($errors) > 0) {
                $errors = $this->validatorOutputFormatter->formatOutput($errors);
                return new JsonResponse($this->doResponse->doErrorResponse($errors));
            }

            $this->doctrine->persist($address);
            $this->doctrine->flush();

            $result = $this->groupSerializer->serializeGroup($address, 'contact_address_detail');
            return new JsonResponse($this->doResponse->doResponse($result));

        } catch (\Exception $e) {
            return new JsonResponse($this->doResponse->doErrorResponse($e->getMessage()));
        }
    }

    #[Route('/contact-address/{id}',
        name: 'put_contact_address',
        methods: ['PUT'])]
    public function modifyContactAddress(
        Request            $request,
        ValidatorInterface $validator,
        int                $id,
    ): JsonResponse
    {
        $data = $request->toArray();
        $address = $this->doctrine->getRepository(ContactAddress::class)->find($id);

        if (!$address) {
            return new JsonResponse($this->doResponse->doErrorResponse('ContactAddress not found', 404));
        }

        try {
            $address = $this->handleRelations($address, $data);
            $address = $this->createMethodsByInput->createMethods($address, $data);
            $address->setUpdatedAt(new \DateTimeImmutable());

            $errors = $validator->validate($address);
            if (count($errors) > 0) {
                $errors = $this->validatorOutputFormatter->formatOutput($errors);
                return new JsonResponse($this->doResponse->doErrorResponse($errors));
            }

            $this->doctrine->persist($address);
            $this->doctrine->flush();

            $result = $this->groupSerializer->serializeGroup($address, 'contact_address_detail');
            return new JsonResponse($this->doResponse->doResponse($result));
        } catch (\Exception $e) {
            return new JsonResponse($this->doResponse->doErrorResponse($e->getMessage()));
        }
    }

    #[Route('/contact-address/{id}',
        name: 'delete_contact_address',
        methods: ['DELETE'])]
    public function deleteContactAddress(int $id): JsonResponse
    {
        $address = $this->doctrine->getRepository(ContactAddress::class)->find($id);
        if (!$address) {
            return new JsonResponse($this->doResponse->doErrorResponse('ContactAddress not found', 404));
        }

        $this->doctrine->remove($address);
        $this->doctrine->flush();

        return new JsonResponse($this->doResponse->doResponse('delete_successfully'));
    }

    private function handleRelations(ContactAddress $address, array &$data): ContactAddress
    {
        if (isset($data['contact_id'])) {
            $contact = $this->doctrine->getRepository(Contact::class)->find($data['contact_id']);
            if ($contact) {
                $address->setContact($contact);
            }
            unset($data['contact_id']);
        }

        return $address;
    }
}
