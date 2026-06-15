<?php

namespace App\Controller;

use App\Entity\Contact;
use App\Entity\ContactAgent;
use App\Entity\ContactSubcontractor;
use App\Entity\ContactType;
use App\Entity\ContactTitle;
use App\Entity\Payment;
use App\Entity\ShipmentCondition;
use App\Entity\Processing;
use App\Service\CreateMethodsByInput;
use App\Service\DoResponseService;
use App\Service\GroupSerializerService;
use App\Service\ValidatorOutputFormatter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class ContactController extends AbstractController
{
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

    #[Route('/contact/agents',
        name: 'get_agents',
        methods: ['GET'])]
    public function getAgents(): JsonResponse
    {
        $contactRepository = $this->doctrine->getRepository(Contact::class);
        $agents = $contactRepository->findBy(['agent' => true], ['name' => 'ASC']);

        $results = $this->groupSerializer->serializeGroup($agents, 'contact_agent_list');
        return new JsonResponse($this->doResponse->doResponse($results));
    }

    #[Route('/contact/{id}',
        name: 'get_contact',
        defaults: ['id' => null],
        requirements: ['id' => '\d*'],
        methods: ['GET', 'HEAD'])]
    public function getContact(
        Request $request,
        ?int    $id,
    ): JsonResponse
    {
        $contactRepository = $this->doctrine->getRepository(Contact::class);

        if ($id) {
            $contact = [$contactRepository->find($id)];
            if (!$contact[0]) {
                return $this->doResponse->doErrorJsonResponse('Contact not found', 404);
            }
        } else {
            $name = $request->query->get('contact_name');
            $detailName = $request->query->get('detail_name');
            $type = $request->query->get('type');

            if ($name || $detailName) {
                $contact = $contactRepository->searchContacts($name, $detailName);
            } else if ($type) {
                if ($type == 'client') {
                    $contact = $contactRepository->findBy(['client' => true], ['name' => 'ASC']);
                } else if ($type == 'supplier') {
                    $contact = $contactRepository->findBy(['supplier' => true], ['name' => 'ASC']);
                } else if ($type == 'agent') {
                    $contact = $contactRepository->findBy(['agent' => true], ['name' => 'ASC']);
                } else if ($type == 'subcontractor') {
                    $contact = $contactRepository->findBy(['subcontractor' => true], ['name' => 'ASC']);
                }
            } else {
                $contact = $contactRepository->findBy([], ['name' => 'ASC']);
            }

        }

        $group = $id ? 'contact_detail' : 'contact_list';
        if (!$id && isset($type)) {
            if ($type == 'client') {
                $group = 'contact_client';
            } else if ($type == 'supplier') {
                $group = 'contact_supplier';
            } else if ($type == 'agent') {
                $group = 'contact_agent_list';
            } else if ($type == 'subcontractor') {
                $group = 'contact_subcontractor_list';
            }
        }

        $results = $this->groupSerializer->serializeGroup($contact, $group);

        if ($id) {
            return new JsonResponse($this->doResponse->doResponse($results[0]));
        }
        return new JsonResponse($this->doResponse->doResponse($results));
    }

    #[Route('/contact',
        name: 'post_contact',
        methods: ['POST'])]
    public function postContact(
        Request            $request,
        ValidatorInterface $validator,
    ): JsonResponse
    {
        $data = $request->request->all();

        $contact = new Contact();

        try {
            $contact = $this->handleRelations($contact, $data);
            $contact = $this->createMethodsByInput->createMethods($contact, $data);

            $now = new \DateTimeImmutable();

            $contact->setCreatedAt($now);
            $contact->setUpdatedAt($now);

            $errors = $validator->validate($contact);

            if (count($errors) > 0) {
                $errors = $this->validatorOutputFormatter->formatOutput($errors);

                return $this->doResponse->doErrorJsonResponse($errors);
            }

            $em = $this->doctrine;
            $em->persist($contact);
            $em->flush();

            $result = $this->groupSerializer->serializeGroup($contact, 'contact_detail');

            return new JsonResponse($this->doResponse->doResponse($result));

        } catch (\Exception $e) {
            return $this->doResponse->doErrorJsonResponse($e->getMessage());
        }
    }

    #[Route('/contact/{id}',
        name: 'put_contact',
        methods: ['PUT'])]
    public function modifyContact(
        Request            $request,
        ValidatorInterface $validator,
        int                $id,
    ): JsonResponse
    {
        $data = $request->toArray();

        $contact = $this->doctrine->getRepository(Contact::class)->find($id);
        if (!$contact) {
            return $this->doResponse->doErrorJsonResponse('Contact not found', 404);
        }

        try {
            $contact = $this->handleRelations($contact, $data);
            $contact = $this->createMethodsByInput->createMethods($contact, $data);

            $em = $this->doctrine;
            $em->persist($contact);
            $em->flush();

            $result = $this->groupSerializer->serializeGroup($contact, 'contact_detail');
            return new JsonResponse($this->doResponse->doResponse($result));
        } catch (\Exception $e) {
            return $this->doResponse->doErrorJsonResponse($e->getMessage());
        }
    }

    #[Route('/contact/{id}', name: 'delete_contact', methods: ['DELETE'])]
    public function deleteContact(int $id): JsonResponse
    {
        $contact = $this->doctrine->getRepository(Contact::class)->find($id);

        if (!$contact) {
            return $this->doResponse->doErrorJsonResponse('Contact not found', 404);
        }

        $em = $this->doctrine;
        $em->remove($contact);
        $em->flush();

        return new JsonResponse($this->doResponse->doResponse('delete_successfully'));
    }

    #[Route('/contact/{id}/agent/{agentId}',
        name: 'add_contact_agent',
        requirements: ['id' => '\d+', 'agentId' => '\d+'],
        methods: ['POST'])]
    public function addAgentToContact(int $id, int $agentId): JsonResponse
    {
        $contactRepository = $this->doctrine->getRepository(Contact::class);
        $contact = $contactRepository->find($id);
        $agent = $contactRepository->find($agentId);

        if (!$contact) {
            return $this->doResponse->doErrorJsonResponse('Contact not found', 404);
        }

        if (!$agent) {
            return $this->doResponse->doErrorJsonResponse('Agent not found', 404);
        }

        if (!$agent->isAgent()) {
            return $this->doResponse->doErrorJsonResponse('The selected contact is not an agent', 400);
        }

        foreach ($contact->getContactAgents() as $existingContactAgent) {
            if ($existingContactAgent->getAgent()->getId() === $agent->getId()) {
                return $this->doResponse->doErrorJsonResponse('Agent already associated', 400);
            }
        }

        $contactAgent = new ContactAgent();
        $contactAgent->setContact($contact);
        $contactAgent->setAgent($agent);

        $this->doctrine->persist($contactAgent);
        $this->doctrine->flush();

        $result = $this->groupSerializer->serializeGroup([$contact], 'contact_detail');
        return new JsonResponse($this->doResponse->doResponse($result[0]));
    }

    #[Route('/contact/{id}/agent/{agentId}',
        name: 'remove_contact_agent',
        requirements: ['id' => '\d+', 'agentId' => '\d+'],
        methods: ['DELETE'])]
    public function removeAgentFromContact(int $id, int $agentId): JsonResponse
    {
        $contactRepository = $this->doctrine->getRepository(Contact::class);
        $contact = $contactRepository->find($id);

        if (!$contact) {
            return $this->doResponse->doErrorJsonResponse('Contact not found', 404);
        }

        $contactAgentRepository = $this->doctrine->getRepository(ContactAgent::class);
        $contactAgent = $contactAgentRepository->findOneBy([
            'contact' => $contact,
            'agent' => $agentId
        ]);

        if (!$contactAgent) {
            return $this->doResponse->doErrorJsonResponse('Association not found', 404);
        }

        $this->doctrine->remove($contactAgent);
        $this->doctrine->flush();

        return new JsonResponse($this->doResponse->doResponse('delete_successfully'));
    }

    #[Route('/contact/{id}/subcontractor/{subcontractorId}',
        name: 'add_contact_subcontractor',
        requirements: ['id' => '\d+', 'subcontractorId' => '\d+'],
        methods: ['POST'])]
    public function addSubcontractorToContact(int $id, int $subcontractorId): JsonResponse
    {
        $contactRepository = $this->doctrine->getRepository(Contact::class);
        $contact = $contactRepository->find($id);
        $subcontractor = $contactRepository->find($subcontractorId);

        if (!$contact) {
            return $this->doResponse->doErrorJsonResponse('Contact not found', 404);
        }

        if (!$subcontractor) {
            return $this->doResponse->doErrorJsonResponse('Subcontractor not found', 404);
        }

        if (!$subcontractor->isSubcontractor()) {
            return $this->doResponse->doErrorJsonResponse('The selected contact is not a subcontractor', 400);
        }

        foreach ($contact->getContactSubcontractors() as $existingContactSubcontractor) {
            if ($existingContactSubcontractor->getSubcontractor()->getId() === $subcontractor->getId()) {
                return $this->doResponse->doErrorJsonResponse('Subcontractor already associated', 400);
            }
        }

        $contactSubcontractor = new ContactSubcontractor();
        $contactSubcontractor->setContact($contact);
        $contactSubcontractor->setSubcontractor($subcontractor);

        $this->doctrine->persist($contactSubcontractor);
        $this->doctrine->flush();

        $result = $this->groupSerializer->serializeGroup([$contact], 'contact_detail');
        return new JsonResponse($this->doResponse->doResponse($result[0]));
    }

    #[Route('/contact/{id}/subcontractor/{subcontractorId}',
        name: 'remove_contact_subcontractor',
        requirements: ['id' => '\d+', 'subcontractorId' => '\d+'],
        methods: ['DELETE'])]
    public function removeSubcontractorFromContact(int $id, int $subcontractorId): JsonResponse
    {
        $contactRepository = $this->doctrine->getRepository(Contact::class);
        $contact = $contactRepository->find($id);

        if (!$contact) {
            return $this->doResponse->doErrorJsonResponse('Contact not found', 404);
        }

        $contactSubcontractorRepository = $this->doctrine->getRepository(ContactSubcontractor::class);
        $contactSubcontractor = $contactSubcontractorRepository->findOneBy([
            'contact' => $contact,
            'subcontractor' => $subcontractorId
        ]);

        if (!$contactSubcontractor) {
            return $this->doResponse->doErrorJsonResponse('Association not found', 404);
        }

        $this->doctrine->remove($contactSubcontractor);
        $this->doctrine->flush();

        return new JsonResponse($this->doResponse->doResponse('delete_successfully'));
    }

    #[Route('/contact/{id}/processing/{processingId}',
        name: 'add_contact_processing',
        requirements: ['id' => '\d+', 'processingId' => '\d+'],
        methods: ['POST'])]
    public function addProcessingToContact(int $id, int $processingId): JsonResponse
    {
        $contact = $this->doctrine->getRepository(Contact::class)->find($id);
        $processing = $this->doctrine->getRepository(Processing::class)->find($processingId);

        if (!$contact) {
            return $this->doResponse->doErrorJsonResponse('Contact not found', 404);
        }

        if (!$processing) {
            return $this->doResponse->doErrorJsonResponse('Processing not found', 404);
        }

        if ($contact->getProcessings()->contains($processing)) {
            return $this->doResponse->doErrorJsonResponse('Processing already associated', 400);
        }

        $contact->addProcessing($processing);
        $this->doctrine->flush();

        $result = $this->groupSerializer->serializeGroup([$contact], 'contact_detail');
        return new JsonResponse($this->doResponse->doResponse($result[0]));
    }

    #[Route('/contact/{id}/processing/{processingId}',
        name: 'remove_contact_processing',
        requirements: ['id' => '\d+', 'processingId' => '\d+'],
        methods: ['DELETE'])]
    public function removeProcessingFromContact(int $id, int $processingId): JsonResponse
    {
        $contact = $this->doctrine->getRepository(Contact::class)->find($id);
        $processing = $this->doctrine->getRepository(Processing::class)->find($processingId);

        if (!$contact) {
            return $this->doResponse->doErrorJsonResponse('Contact not found', 404);
        }

        if (!$processing) {
            return $this->doResponse->doErrorJsonResponse('Processing not found', 404);
        }

        if (!$contact->getProcessings()->contains($processing)) {
            return $this->doResponse->doErrorJsonResponse('Association not found', 404);
        }

        $contact->removeProcessing($processing);
        $this->doctrine->flush();

        return new JsonResponse($this->doResponse->doResponse('delete_successfully'));
    }

    private function handleRelations(Contact $contact, array &$data): Contact
    {
        if (isset($data['contact_type_id'])) {
            $contactType = $this->doctrine->getRepository(ContactType::class)->find($data['contact_type_id']);
            if ($contactType) {
                $contact->setContactType($contactType);
            }
            unset($data['contact_type_id']);
        }

        if (isset($data['contact_title_id'])) {
            $contactTitle = $this->doctrine->getRepository(ContactTitle::class)->find($data['contact_title_id']);
            if ($contactTitle) {
                $contact->setContactTitle($contactTitle);
            }
            unset($data['contact_title_id']);
        }

        if (isset($data['payment_id'])) {
            $payment = $this->doctrine->getRepository(Payment::class)->find($data['payment_id']);
            if ($payment) {
                $contact->setPayment($payment);
            }
            unset($data['payment_id']);
        }

        if (isset($data['shipment_condition_id'])) {
            $shipmentCondition = $this->doctrine->getRepository(ShipmentCondition::class)->find($data['shipment_condition_id']);
            if ($shipmentCondition) {
                $contact->setShipmentCondition($shipmentCondition);
            }
            unset($data['shipment_condition_id']);
        }

        if (isset($data['agent_id'])) {
            $agent = $this->doctrine->getRepository(Contact::class)->find($data['agent_id']);
            if ($agent) {
                $contactAgentFound = false;
                foreach ($contact->getContactAgents() as $contactAgent) {
                    if ($contactAgent->getAgent()->getId() === $agent->getId()) {
                        $contactAgentFound = true;
                        break;
                    }
                }

                if (!$contactAgentFound) {
                    $contactAgent = new ContactAgent();
                    $contactAgent->setAgent($agent);
                    $contactAgent->setContact($contact);
                    $contact->addContactAgent($contactAgent);
                }
            }
            unset($data['agent_id']);
        }

        if (isset($data['subcontractor_id'])) {
            $subcontractor = $this->doctrine->getRepository(Contact::class)->find($data['subcontractor_id']);
            if ($subcontractor) {
                $contactSubcontractorFound = false;
                foreach ($contact->getContactSubcontractors() as $contactSubcontractor) {
                    if ($contactSubcontractor->getSubcontractor()->getId() === $subcontractor->getId()) {
                        $contactSubcontractorFound = true;
                        break;
                    }
                }

                if (!$contactSubcontractorFound) {
                    $contactSubcontractor = new ContactSubcontractor();
                    $contactSubcontractor->setSubcontractor($agent);
                    $contactSubcontractor->setContact($contact);
                    $contact->addContactSubcontractor($contactSubcontractor);
                }
            }
            unset($data['subcontractor_id']);
        }

        return $contact;
    }
}

