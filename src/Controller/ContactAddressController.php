<?php

namespace App\Controller;

use App\Entity\Contact;
use App\Entity\ContactAddress;
use App\Entity\Nation;
use App\Entity\Town;
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
                return $this->doResponse->doErrorJsonResponse('ContactAddress not found', 404);
            }
        } else {
            $address = $contactAddressRepository->findBy([], ['address_name' => 'ASC']);
        }

        $results = $this->groupSerializer->serializeGroup($address, $id ? 'contact_address_detail' : 'contact_address_list');

        if ($id) {
            return new JsonResponse($this->doResponse->doResponse($results[0]));
        }
        return new JsonResponse($this->doResponse->doResponse($results));
    }

    #[Route('/contact/{id}/contact-address',
        name: 'get_contact_address_list',
        defaults: ['id' => null],
        requirements: ['id' => '\d*'],
        methods: ['GET', 'HEAD'])]
    public function getContactAddressList(int $id): JsonResponse
    {
        $contactRepository = $this->doctrine->getRepository(Contact::class);

        $contact = $contactRepository->find($id);
        if (!$contact) {
            return $this->doResponse->doErrorJsonResponse('Contact not found', 404);
        }

        $results = [];
        $address = $contact->getContactAddresses();

        foreach ($address as $addressItem) {
            if ($addressItem->getDifferentDestination() != null){
                $differentDestination = $addressItem->getDifferentDestination();
                $nationSerialized = $this->groupSerializer->serializeGroup($differentDestination->getNation(), 'contact_address_list');
                $clientOrderSerialized = $this->groupSerializer->serializeGroup($addressItem->getClientOrders(), 'contact_address_list');
                $contactSerialized = $this->groupSerializer->serializeGroup($differentDestination->getContact(), 'contact_address_list');
                $results[] = [
                    'id' => $addressItem->getId(),
                    'different_destination_id' => $differentDestination->getId(),
                    'different_destination' => true,
                    'contact' => $contactSerialized,
                    'address_name' => $differentDestination->getAddressName(),
                    'address' => $differentDestination->getAddress(),
                    'address_2' => $differentDestination->getAddress2(),
                    'address_3' => $differentDestination->getAddress3(),
                    'address_4' => $differentDestination->getAddress4(),
                    'client_orders' => $clientOrderSerialized,
                    'nation' => $nationSerialized,
                    'zip_code' => $differentDestination->getZipCode(),
                    'default_address' => $addressItem->isDefaultAddress()
                ];
            } else{
                $serializedAddress = $this->groupSerializer->serializeGroup($addressItem, 'contact_address_list');
                $serializedAddress['different_destination'] = false;

                $results[] = $serializedAddress;
            }

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

            if(isset($data['default_address'])){
                if( $data['default_address'] == 'true'){
                    $address->setDefaultAddress(true);
                } else {
                    $address->setDefaultAddress(false);
                }
                unset($data['default_address']);
            }

            if(isset($data['different_destination_id'])) {
                $differentAddress = $this->doctrine->getRepository(ContactAddress::class)->find($data['different_destination_id']);
                $address->setDifferentDestination($differentAddress);

                $address->setAddressName('DEST. DIVERSA');
                $address->setAddress($differentAddress->getAddress());
                $address->setAddress2($differentAddress->getAddress2());
                $address->setAddress3($differentAddress->getAddress3());
                $address->setAddress4($differentAddress->getAddress4());
                $address->setZipCode($differentAddress->getZipCode());
                $address->setNation($differentAddress->getNation());

                unset($data['different_destination_id']);
            } else {
                $address = $this->createMethodsByInput->createMethods($address, $data);
            }

            $now = new \DateTimeImmutable();
            $address->setCreatedAt($now);
            $address->setUpdatedAt($now);

            $errors = $validator->validate($address);
            if (count($errors) > 0) {
                $errors = $this->validatorOutputFormatter->formatOutput($errors);
                return $this->doResponse->doErrorJsonResponse($errors);
            }

            $this->doctrine->persist($address);
            $this->doctrine->flush();

            $result = $this->groupSerializer->serializeGroup($address, 'contact_address_detail');
            return new JsonResponse($this->doResponse->doResponse($result));

        } catch (\Exception $e) {
            return $this->doResponse->doErrorJsonResponse($e->getMessage());
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
            return $this->doResponse->doErrorJsonResponse('ContactAddress not found', 404);
        }

        try {
            if(isset($data['default_address']) && $data['default_address'] === true) {
                $allAddress = $address->getContact()->getContactAddresses();

                foreach ($allAddress as $addr) {
                    $addr->setDefaultAddress(false);

                    $this->doctrine->persist($addr);
                }

                $address->setDefaultAddress(true);
            }
            $address = $this->handleRelations($address, $data);

            if(isset($data['different_destination_id'])) {
                $differentAddress = $this->doctrine->getRepository(ContactAddress::class)->find($data['different_destination_id']);
                $address->setDifferentDestination($differentAddress);

                $address->setAddressName('DEST. DIVERSA');
                $address->setAddress($differentAddress->getAddress());
                $address->setAddress2($differentAddress->getAddress2());
                $address->setAddress3($differentAddress->getAddress3());
                $address->setAddress4($differentAddress->getAddress4());
                $address->setZipCode($differentAddress->getZipCode());
                $address->setNation($differentAddress->getNation());

                unset($data['different_destination_id']);
            } else {
                $address = $this->createMethodsByInput->createMethods($address, $data);
            }

            $address->setUpdatedAt(new \DateTimeImmutable());

            $errors = $validator->validate($address);
            if (count($errors) > 0) {
                $errors = $this->validatorOutputFormatter->formatOutput($errors);
                return $this->doResponse->doErrorJsonResponse($errors);
            }

            $this->doctrine->persist($address);
            $this->doctrine->flush();

            $result = $this->groupSerializer->serializeGroup($address, 'contact_address_detail');
            return new JsonResponse($this->doResponse->doResponse($result));
        } catch (\Exception $e) {
            return $this->doResponse->doErrorJsonResponse($e->getMessage());
        }
    }

    #[Route('/contact-address/{id}',
        name: 'delete_contact_address',
        methods: ['DELETE'])]
    public function deleteContactAddress(int $id): JsonResponse
    {
        $address = $this->doctrine->getRepository(ContactAddress::class)->find($id);
        if (!$address) {
            return $this->doResponse->doErrorJsonResponse('ContactAddress not found', 404);
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

        if (isset($data['nation_id'])) {
            $nation = $this->doctrine->getRepository(Nation::class)->find($data['nation_id']);
            if ($nation) {
                $address->setNation($nation);
            }
            unset($data['nation_id']);
        }

        return $address;
    }
}

