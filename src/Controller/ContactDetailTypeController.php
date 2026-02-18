<?php

namespace App\Controller;

use App\Entity\ContactDetailType;
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

final class ContactDetailTypeController extends AbstractController
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

    #[Route('/contact-detail-type/{id}',
        name: 'get_contact_detail_type',
        defaults: ['id' => null],
        requirements: ['id' => '\\d*'],
        methods: ['GET', 'HEAD'])]
    public function getContactDetailType(?int $id): JsonResponse
    {
        $repo = $this->doctrine->getRepository(ContactDetailType::class);

        if ($id) {
            $items = [$repo->find($id)];
            if (!$items[0]) {
                return new JsonResponse($this->doResponse->doErrorResponse('ContactDetailType not found', 404));
            }
        } else {
            $items = $repo->findBy([], ['id' => 'DESC']);
        }

        $results = $this->groupSerializer->serializeGroup($items, $id ? 'contact_detail_type_detail' : 'contact_detail_type_list');
        return new JsonResponse($this->doResponse->doResponse($id ? $results[0] : $results));
    }

    #[Route('/contact-detail-type', name: 'post_contact_detail_type', methods: ['POST'])]
    public function postContactDetailType(Request $request, ValidatorInterface $validator): JsonResponse
    {
        $data = $request->request->all();
        $entity = new ContactDetailType();

        try {
            $entity = $this->createMethodsByInput->createMethods($entity, $data);

            $errors = $validator->validate($entity);
            if (count($errors) > 0) {
                $errors = $this->validatorOutputFormatter->formatOutput($errors);
                return new JsonResponse($this->doResponse->doErrorResponse($errors));
            }

            $this->doctrine->persist($entity);
            $this->doctrine->flush();

            $result = $this->groupSerializer->serializeGroup($entity, 'contact_detail_type_detail');
            return new JsonResponse($this->doResponse->doResponse($result));
        } catch (\Exception $e) {
            return new JsonResponse($this->doResponse->doErrorResponse($e->getMessage()));
        }
    }

    #[Route('/contact-detail-type/{id}', name: 'put_contact_detail_type', methods: ['PUT'])]
    public function modifyContactDetailType(Request $request, ValidatorInterface $validator, int $id): JsonResponse
    {
        $data = $request->toArray();
        $entity = $this->doctrine->getRepository(ContactDetailType::class)->find($id);
        if (!$entity) {
            return new JsonResponse($this->doResponse->doErrorResponse('ContactDetailType not found', 404));
        }

        $entity = $this->createMethodsByInput->createMethods($entity, $data);

        $errors = $validator->validate($entity);
        if (count($errors) > 0) {
            $errors = $this->validatorOutputFormatter->formatOutput($errors);
            return new JsonResponse($this->doResponse->doErrorResponse($errors));
        }
        $this->doctrine->persist($entity);
        $this->doctrine->flush();

        $result = $this->groupSerializer->serializeGroup($entity, 'contact_detail_type_detail');
        return new JsonResponse($this->doResponse->doResponse($result));
    }

    #[Route('/contact-detail-type/{id}', name: 'delete_contact_detail_type', methods: ['DELETE'])]
    public function deleteContactDetailType(int $id): JsonResponse
    {
        $entity = $this->doctrine->getRepository(ContactDetailType::class)->find($id);
        if (!$entity) {
            return new JsonResponse($this->doResponse->doErrorResponse('ContactDetailType not found', 404));
        }

        $this->doctrine->remove($entity);
        $this->doctrine->flush();

        return new JsonResponse($this->doResponse->doResponse('delete_successfully'));
    }
}
