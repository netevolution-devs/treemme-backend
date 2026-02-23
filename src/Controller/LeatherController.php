<?php

namespace App\Controller;

use App\Entity\Leather;
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

final class LeatherController extends AbstractController
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

    #[Route('/leather/{id}',
        name: 'get_leather',
        defaults: ['id' => null],
        requirements: ['id' => '\d*'],
        methods: ['GET', 'HEAD'])]
    public function getLeather(?int $id): JsonResponse
    {
        $leatherRepository = $this->doctrine->getRepository(Leather::class);

        if ($id) {
            $leather = [$leatherRepository->find($id)];
            if (!$leather[0]) {
                return new JsonResponse($this->doResponse->doErrorResponse('Leather not found', 404));
            }
        } else {
            $leather = $leatherRepository->findBy([], ['id' => 'DESC']);
        }
        $results = $this->groupSerializer->serializeGroup($leather, $id ? 'leather_detail' : 'leather_list');

        if ($id) {
            return new JsonResponse($this->doResponse->doResponse($results[0]));
        }
        return new JsonResponse($this->doResponse->doResponse($results));
    }

    #[Route('/leather',
        name: 'post_leather',
        methods: ['POST'])]
    public function postLeather(
        Request            $request,
        ValidatorInterface $validator,
    ): JsonResponse
    {
        $data = $request->request->all();
        $leather = new Leather();

        try {
            $leather = $this->createMethodsByInput->createMethods($leather, $data);

            $errors = $validator->validate($leather);
            if (count($errors) > 0) {
                $errors = $this->validatorOutputFormatter->formatOutput($errors);
                return new JsonResponse($this->doResponse->doErrorResponse($errors));
            }

            $em = $this->doctrine;
            $em->persist($leather);
            $em->flush();

            $result = $this->groupSerializer->serializeGroup($leather, 'leather_detail');
            return new JsonResponse($this->doResponse->doResponse($result));

        } catch (\Exception $e) {
            return new JsonResponse($this->doResponse->doErrorResponse($e->getMessage()));
        }
    }

    #[Route('/leather/{id}',
        name: 'put_leather',
        methods: ['PUT'])]
    public function modifyLeather(
        Request            $request,
        ValidatorInterface $validator,
        int                $id,
    ): JsonResponse
    {
        $data = $request->toArray();
        $leather = $this->doctrine->getRepository(Leather::class)->find($id);

        if (!$leather) {
            return new JsonResponse($this->doResponse->doErrorResponse('Leather not found', 404));
        }

        try {
            $leather = $this->createMethodsByInput->createMethods($leather, $data);

            $errors = $validator->validate($leather);
            if (count($errors) > 0) {
                $errors = $this->validatorOutputFormatter->formatOutput($errors);
                return new JsonResponse($this->doResponse->doErrorResponse($errors));
            }

            $this->doctrine->persist($leather);
            $this->doctrine->flush();

            $result = $this->groupSerializer->serializeGroup($leather, 'leather_detail');
            return new JsonResponse($this->doResponse->doResponse($result));
        } catch (\Exception $e) {
            return new JsonResponse($this->doResponse->doErrorResponse($e->getMessage()));
        }
    }

    #[Route('/leather/{id}',
        name: 'delete_leather',
        methods: ['DELETE'])]
    public function deleteLeather(int $id): JsonResponse
    {
        $leather = $this->doctrine->getRepository(Leather::class)->find($id);
        if (!$leather) {
            return new JsonResponse($this->doResponse->doErrorResponse('Leather not found', 404));
        }

        $this->doctrine->remove($leather);
        $this->doctrine->flush();

        return new JsonResponse($this->doResponse->doResponse('delete_successfully'));
    }
}
