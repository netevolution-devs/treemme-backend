<?php

namespace App\Controller;

use App\Entity\ContactType;
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

final class ContactTypeController extends AbstractController
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

    #[Route('/contact-type/{id}',
        name: 'get_contact_type',
        defaults: ['id' => null],
        requirements: ['id' => '\d*'],
        methods: ['GET', 'HEAD'])]
    public function getContactType(
        ?int            $id,
    ): JsonResponse
    {
        $contactTypeRepository = $this->doctrine->getRepository(ContactType::class);

        if($id) {
            $contactType = [$contactTypeRepository->find($id)];
            if (!$contactType[0]) {
                return new JsonResponse($this->doResponse->doErrorResponse('ContactType not found', 404));
            }
        } else {
            $contactType = $contactTypeRepository->findBy([], ['id' => 'DESC']);
        }
        $results = $this->groupSerializer->serializeGroup($contactType, $id ? 'contact_type_detail' : 'contact_type_list');

        if ($id) {
            return new JsonResponse($this->doResponse->doResponse($results[0]));
        }
        return new JsonResponse($this->doResponse->doResponse($results));
    }

    #[Route('/contact-type',
        name: 'post_contact_type',
        methods: ['POST'])]
    public function postContactType(
        Request            $request,
        ValidatorInterface $validator,
    ): JsonResponse
    {
        $data = $request->request->all();

        $contactType = new ContactType();

        try {

            $contactType = $this->createMethodsByInput->createMethods($contactType, $data);

            $now = new \DateTimeImmutable();

            $contactType->setCreatedAt($now);
            $contactType->setUpdatedAt($now);

            $errors = $validator->validate($contactType);

            if (count($errors) > 0) {
                $errors = $this->validatorOutputFormatter->formatOutput($errors);

                return new JsonResponse($this->doResponse->doErrorResponse($errors));
            }

            $em = $this->doctrine;
            $em->persist($contactType);
            $em->flush();

            $result = $this->groupSerializer->serializeGroup($contactType, 'contact_type_detail');

            return new JsonResponse($this->doResponse->doResponse($result));

        } catch (\Exception $e) {
            return new JsonResponse($this->doResponse->doErrorResponse($e->getMessage()));
        }
    }
    #[Route('/contact-type/{id}',
        name: 'put_contact_type',
        methods: ['PUT'])]
    public function modifyContactType(
        Request            $request,
        ValidatorInterface $validator,
        int                $id
    ): JsonResponse
    {
        $data = $request->toArray();

        $contactType = $this->doctrine->getRepository(ContactType::class)->find($id);
        if (!$contactType) {
            return new JsonResponse($this->doResponse->doErrorResponse('ContactType not found', 404));
        }

        $contactType = $this->createMethodsByInput->createMethods($contactType, $data);

        $now = new \DateTimeImmutable();

        $contactType->setUpdatedAt($now);

        $errors = $validator->validate($contactType);

        if (count($errors) > 0) {
            $errors = $this->validatorOutputFormatter->formatOutput($errors);

            return new JsonResponse($this->doResponse->doErrorResponse($errors));
        }
        $this->doctrine->persist($contactType);
        $this->doctrine->flush();

        $result = $this->groupSerializer->serializeGroup($contactType, 'contact_type_detail');

        return new JsonResponse($this->doResponse->doResponse($result));
    }

    #[Route('/contact-type/{id}',
        name: 'delete_contact_type',
        methods: ['DELETE'])]
    public function deleteContactType(int $id): Response
    {
        $contactType = $this->doctrine->getRepository(ContactType::class)->find($id);
        if (!$contactType) {
            return new JsonResponse($this->doResponse->doErrorResponse('ContactType not found', 404));
        }

        $this->doctrine->remove($contactType);

        return new JsonResponse($this->doResponse->doResponse('delete_successfully'));
    }
}
