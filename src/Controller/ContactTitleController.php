<?php

namespace App\Controller;

use App\Entity\ContactTitle;
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

final class ContactTitleController extends AbstractController
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

    #[Route('/contact-title/{id}',
        name: 'get_contact_title',
        defaults: ['id' => null],
        requirements: ['id' => '\\d*'],
        methods: ['GET', 'HEAD'])]
    public function getContactTitle(?int $id): JsonResponse
    {
        $repo = $this->doctrine->getRepository(ContactTitle::class);

        if ($id) {
            $items = [$repo->find($id)];
            if (!$items[0]) {
                return new JsonResponse($this->doResponse->doErrorResponse('ContactTitle not found', 404));
            }
        } else {
            $items = $repo->findBy([], ['id' => 'DESC']);
        }

        $results = $this->groupSerializer->serializeGroup($items, $id ? 'contact_title_detail' : 'contact_title_list');
        return new JsonResponse($this->doResponse->doResponse($id ? $results[0] : $results));
    }

    #[Route('/contact-title', name: 'post_contact_title', methods: ['POST'])]
    public function postContactTitle(Request $request, ValidatorInterface $validator): JsonResponse
    {
        $data = $request->request->all();
        $entity = new ContactTitle();

        try {
            $entity = $this->createMethodsByInput->createMethods($entity, $data);

            $errors = $validator->validate($entity);
            if (count($errors) > 0) {
                $errors = $this->validatorOutputFormatter->formatOutput($errors);
                return new JsonResponse($this->doResponse->doErrorResponse($errors));
            }

            $this->doctrine->persist($entity);
            $this->doctrine->flush();

            $result = $this->groupSerializer->serializeGroup($entity, 'contact_title_detail');
            return new JsonResponse($this->doResponse->doResponse($result));
        } catch (\Exception $e) {
            return new JsonResponse($this->doResponse->doErrorResponse($e->getMessage()));
        }
    }

    #[Route('/contact-title/{id}', name: 'put_contact_title', methods: ['PUT'])]
    public function modifyContactTitle(Request $request, ValidatorInterface $validator, int $id): JsonResponse
    {
        $data = $request->toArray();
        $entity = $this->doctrine->getRepository(ContactTitle::class)->find($id);
        if (!$entity) {
            return new JsonResponse($this->doResponse->doErrorResponse('ContactTitle not found', 404));
        }

        $entity = $this->createMethodsByInput->createMethods($entity, $data);

        $errors = $validator->validate($entity);
        if (count($errors) > 0) {
            $errors = $this->validatorOutputFormatter->formatOutput($errors);
            return new JsonResponse($this->doResponse->doErrorResponse($errors));
        }
        $this->doctrine->persist($entity);
        $this->doctrine->flush();

        $result = $this->groupSerializer->serializeGroup($entity, 'contact_title_detail');
        return new JsonResponse($this->doResponse->doResponse($result));
    }

    #[Route('/contact-title/{id}', name: 'delete_contact_title', methods: ['DELETE'])]
    public function deleteContactTitle(int $id): JsonResponse
    {
        $entity = $this->doctrine->getRepository(ContactTitle::class)->find($id);
        if (!$entity) {
            return new JsonResponse($this->doResponse->doErrorResponse('ContactTitle not found', 404));
        }

        $this->doctrine->remove($entity);
        $this->doctrine->flush();

        return new JsonResponse($this->doResponse->doResponse('delete_successfully'));
    }
}
