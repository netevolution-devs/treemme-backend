<?php

namespace App\Controller;

use App\Entity\Contact;
use App\Entity\ContactType;
use App\Entity\ContactTitle;
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

    #[Route('/contact/{id}',
        name: 'get_contact',
        defaults: ['id' => null],
        requirements: ['id' => '\d*'],
        methods: ['GET', 'HEAD'])]
    public function getContactType(
        ?int $id,
    ): JsonResponse
    {
        $contactRepository = $this->doctrine->getRepository(Contact::class);

        if ($id) {
            $contact = [$contactRepository->find($id)];
            if (!$contact[0]) {
                return new JsonResponse($this->doResponse->doErrorResponse('Contact not found', 404));
            }
        } else {
            $contact = $contactRepository->findBy([], ['id' => 'DESC']);
        }
        $results = $this->groupSerializer->serializeGroup($contact, $id ? 'contact_detail' : 'contact_list');

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

            if (isset($data['contact_type_id'])) {
                $contactType = $this->doctrine->getRepository(ContactType::class)->find($data['contact_type_id']);
                if (!$contactType) {
                    return new JsonResponse($this->doResponse->doErrorResponse('ContactType not found', 404));
                }

                $contact->setContactType($contactType);
                unset($data['contact_type_id']);
            }
            if (isset($data['contact_title_id'])) {
                $contactTitle = $this->doctrine->getRepository(ContactTitle::class)->find($data['contact_title_id']);
                if (!$contactTitle) {
                    return new JsonResponse($this->doResponse->doErrorResponse('ContactTitle not found', 404));
                }

                $contact->setContactTitle($contactTitle);
                unset($data['contact_title_id']);
            }

            $contact = $this->createMethodsByInput->createMethods($contact, $data);

            $now = new \DateTimeImmutable();

            $contact->setCreatedAt($now);
            $contact->setUpdatedAt($now);

            $errors = $validator->validate($contact);

            if (count($errors) > 0) {
                $errors = $this->validatorOutputFormatter->formatOutput($errors);

                return new JsonResponse($this->doResponse->doErrorResponse($errors));
            }

            $em = $this->doctrine;
            $em->persist($contact);
            $em->flush();

            $result = $this->groupSerializer->serializeGroup($contact, 'contact_detail');

            return new JsonResponse($this->doResponse->doResponse($result));

        } catch (\Exception $e) {
            return new JsonResponse($this->doResponse->doErrorResponse($e->getMessage()));
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
            return new JsonResponse($this->doResponse->doErrorResponse('Contact not found', 404));
        }

        if (isset($data['contact_type_id'])) {
            $contactType = $this->doctrine->getRepository(ContactType::class)->find($data['contact_type_id']);
            if (!$contactType) {
                return new JsonResponse($this->doResponse->doErrorResponse('ContactType not found', 404));
            }

            $contact->setContactType($contactType);
            unset($data['contact_type_id']);
        }
        if (isset($data['contact_title_id'])) {
            $contactTitle = $this->doctrine->getRepository(ContactTitle::class)->find($data['contact_title_id']);
            if (!$contactTitle) {
                return new JsonResponse($this->doResponse->doErrorResponse('ContactTitle not found', 404));
            }

            $contact->setContactTitle($contactTitle);
            unset($data['contact_title_id']);
        }

        $contact = $this->createMethodsByInput->createMethods($contact, $data);

        $em = $this->doctrine;
        $em->persist($contact);
        $em->flush();

        $result = $this->groupSerializer->serializeGroup($contact, 'contact_detail');
        return new JsonResponse($this->doResponse->doResponse($result));
    }

    #[Route('/contact/{id}', name: 'delete_contact', methods: ['DELETE'])]
    public function deleteContact(int $id): JsonResponse
    {
        $contact = $this->doctrine->getRepository(Contact::class)->find($id);

        if (!$contact) {
            return new JsonResponse($this->doResponse->doErrorResponse('Contact not found', 404));
        }

        $em = $this->doctrine;
        $em->remove($contact);
        $em->flush();

        return new JsonResponse($this->doResponse->doResponse('delete_successfully'));
    }
}
