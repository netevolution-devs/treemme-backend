<?php

namespace App\Controller;

use App\Entity\LeatherWeight;
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

final class LeatherWeightController extends AbstractController
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

    #[Route('/leather-weight/{id}',
        name: 'get_leather_weight',
        defaults: ['id' => null],
        requirements: ['id' => '\d*'],
        methods: ['GET', 'HEAD'])]
    public function getLeatherWeight(?int $id): JsonResponse
    {
        $leatherWeightRepository = $this->doctrine->getRepository(LeatherWeight::class);

        if ($id) {
            $leatherWeight = [$leatherWeightRepository->find($id)];
            if (!$leatherWeight[0]) {
                return new JsonResponse($this->doResponse->doErrorResponse('LeatherWeight not found', 404));
            }
        } else {
            $leatherWeight = $leatherWeightRepository->findBy([], ['id' => 'DESC']);
        }
        $results = $this->groupSerializer->serializeGroup($leatherWeight, $id ? 'leather_weight_detail' : 'leather_weight_list');

        if ($id) {
            return new JsonResponse($this->doResponse->doResponse($results[0]));
        }
        return new JsonResponse($this->doResponse->doResponse($results));
    }

    #[Route('/leather-weight',
        name: 'post_leather_weight',
        methods: ['POST'])]
    public function postLeatherWeight(
        Request            $request,
        ValidatorInterface $validator,
    ): JsonResponse
    {
        $data = $request->request->all();
        $leatherWeight = new LeatherWeight();

        try {
            $leatherWeight = $this->createMethodsByInput->createMethods($leatherWeight, $data);

            $errors = $validator->validate($leatherWeight);
            if (count($errors) > 0) {
                $errors = $this->validatorOutputFormatter->formatOutput($errors);
                return new JsonResponse($this->doResponse->doErrorResponse($errors));
            }

            $em = $this->doctrine;
            $em->persist($leatherWeight);
            $em->flush();

            $result = $this->groupSerializer->serializeGroup($leatherWeight, 'leather_weight_detail');
            return new JsonResponse($this->doResponse->doResponse($result));

        } catch (\Exception $e) {
            return new JsonResponse($this->doResponse->doErrorResponse($e->getMessage()));
        }
    }

    #[Route('/leather-weight/{id}',
        name: 'put_leather_weight',
        methods: ['PUT'])]
    public function modifyLeatherWeight(
        Request            $request,
        ValidatorInterface $validator,
        int                $id,
    ): JsonResponse
    {
        $data = $request->toArray();
        $leatherWeight = $this->doctrine->getRepository(LeatherWeight::class)->find($id);

        if (!$leatherWeight) {
            return new JsonResponse($this->doResponse->doErrorResponse('LeatherWeight not found', 404));
        }

        try {
            $leatherWeight = $this->createMethodsByInput->createMethods($leatherWeight, $data);

            $errors = $validator->validate($leatherWeight);
            if (count($errors) > 0) {
                $errors = $this->validatorOutputFormatter->formatOutput($errors);
                return new JsonResponse($this->doResponse->doErrorResponse($errors));
            }

            $this->doctrine->persist($leatherWeight);
            $this->doctrine->flush();

            $result = $this->groupSerializer->serializeGroup($leatherWeight, 'leather_weight_detail');
            return new JsonResponse($this->doResponse->doResponse($result));
        } catch (\Exception $e) {
            return new JsonResponse($this->doResponse->doErrorResponse($e->getMessage()));
        }
    }

    #[Route('/leather-weight/{id}',
        name: 'delete_leather_weight',
        methods: ['DELETE'])]
    public function deleteLeatherWeight(int $id): JsonResponse
    {
        $leatherWeight = $this->doctrine->getRepository(LeatherWeight::class)->find($id);
        if (!$leatherWeight) {
            return new JsonResponse($this->doResponse->doErrorResponse('LeatherWeight not found', 404));
        }

        $this->doctrine->remove($leatherWeight);
        $this->doctrine->flush();

        return new JsonResponse($this->doResponse->doResponse('delete_successfully'));
    }
}
