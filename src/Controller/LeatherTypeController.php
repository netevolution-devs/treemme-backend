<?php

namespace App\Controller;

use App\Entity\LeatherType;
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

final class LeatherTypeController extends AbstractController
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

    #[Route('/leather-type/{id}',
        name: 'get_leather_type',
        defaults: ['id' => null],
        requirements: ['id' => '\d*'],
        methods: ['GET', 'HEAD'])]
    public function getLeatherType(?int $id): JsonResponse
    {
        $leatherTypeRepository = $this->doctrine->getRepository(LeatherType::class);

        if ($id) {
            $leatherType = [$leatherTypeRepository->find($id)];
            if (!$leatherType[0]) {
                return new JsonResponse($this->doResponse->doErrorResponse('LeatherType not found', 404));
            }
        } else {
            $leatherType = $leatherTypeRepository->findBy([], ['id' => 'DESC']);
        }
        $results = $this->groupSerializer->serializeGroup($leatherType, $id ? 'leather_type_detail' : 'leather_type_list');

        if ($id) {
            return new JsonResponse($this->doResponse->doResponse($results[0]));
        }
        return new JsonResponse($this->doResponse->doResponse($results));
    }

    #[Route('/leather-type',
        name: 'post_leather_type',
        methods: ['POST'])]
    public function postLeatherType(
        Request            $request,
        ValidatorInterface $validator,
    ): JsonResponse
    {
        $data = $request->request->all();
        $leatherType = new LeatherType();

        try {
            $leatherType = $this->createMethodsByInput->createMethods($leatherType, $data);

            $errors = $validator->validate($leatherType);
            if (count($errors) > 0) {
                $errors = $this->validatorOutputFormatter->formatOutput($errors);
                return new JsonResponse($this->doResponse->doErrorResponse($errors));
            }

            $em = $this->doctrine;
            $em->persist($leatherType);
            $em->flush();

            $result = $this->groupSerializer->serializeGroup($leatherType, 'leather_type_detail');
            return new JsonResponse($this->doResponse->doResponse($result));

        } catch (\Exception $e) {
            return new JsonResponse($this->doResponse->doErrorResponse($e->getMessage()));
        }
    }

    #[Route('/leather-type/{id}',
        name: 'put_leather_type',
        methods: ['PUT'])]
    public function modifyLeatherType(
        Request            $request,
        ValidatorInterface $validator,
        int                $id,
    ): JsonResponse
    {
        $data = $request->toArray();
        $leatherType = $this->doctrine->getRepository(LeatherType::class)->find($id);

        if (!$leatherType) {
            return new JsonResponse($this->doResponse->doErrorResponse('LeatherType not found', 404));
        }

        try {
            $leatherType = $this->createMethodsByInput->createMethods($leatherType, $data);

            $errors = $validator->validate($leatherType);
            if (count($errors) > 0) {
                $errors = $this->validatorOutputFormatter->formatOutput($errors);
                return new JsonResponse($this->doResponse->doErrorResponse($errors));
            }

            $this->doctrine->persist($leatherType);
            $this->doctrine->flush();

            $result = $this->groupSerializer->serializeGroup($leatherType, 'leather_type_detail');
            return new JsonResponse($this->doResponse->doResponse($result));
        } catch (\Exception $e) {
            return new JsonResponse($this->doResponse->doErrorResponse($e->getMessage()));
        }
    }

    #[Route('/leather-type/{id}',
        name: 'delete_leather_type',
        methods: ['DELETE'])]
    public function deleteLeatherType(int $id): JsonResponse
    {
        $leatherType = $this->doctrine->getRepository(LeatherType::class)->find($id);
        if (!$leatherType) {
            return new JsonResponse($this->doResponse->doErrorResponse('LeatherType not found', 404));
        }

        $this->doctrine->remove($leatherType);
        $this->doctrine->flush();

        return new JsonResponse($this->doResponse->doResponse('delete_successfully'));
    }
}
