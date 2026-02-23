<?php

namespace App\Controller;

use App\Entity\LeatherStatus;
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

final class LeatherStatusController extends AbstractController
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

    #[Route('/leather-status/{id}',
        name: 'get_leather_status',
        defaults: ['id' => null],
        requirements: ['id' => '\d*'],
        methods: ['GET', 'HEAD'])]
    public function getLeatherStatus(?int $id): JsonResponse
    {
        $leatherStatusRepository = $this->doctrine->getRepository(LeatherStatus::class);

        if ($id) {
            $leatherStatus = [$leatherStatusRepository->find($id)];
            if (!$leatherStatus[0]) {
                return new JsonResponse($this->doResponse->doErrorResponse('LeatherStatus not found', 404));
            }
        } else {
            $leatherStatus = $leatherStatusRepository->findBy([], ['id' => 'DESC']);
        }
        $results = $this->groupSerializer->serializeGroup($leatherStatus, $id ? 'leather_status_detail' : 'leather_status_list');

        if ($id) {
            return new JsonResponse($this->doResponse->doResponse($results[0]));
        }
        return new JsonResponse($this->doResponse->doResponse($results));
    }

    #[Route('/leather-status',
        name: 'post_leather_status',
        methods: ['POST'])]
    public function postLeatherStatus(
        Request            $request,
        ValidatorInterface $validator,
    ): JsonResponse
    {
        $data = $request->request->all();
        $leatherStatus = new LeatherStatus();

        try {
            $leatherStatus = $this->createMethodsByInput->createMethods($leatherStatus, $data);

            $errors = $validator->validate($leatherStatus);
            if (count($errors) > 0) {
                $errors = $this->validatorOutputFormatter->formatOutput($errors);
                return new JsonResponse($this->doResponse->doErrorResponse($errors));
            }

            $em = $this->doctrine;
            $em->persist($leatherStatus);
            $em->flush();

            $result = $this->groupSerializer->serializeGroup($leatherStatus, 'leather_status_detail');
            return new JsonResponse($this->doResponse->doResponse($result));

        } catch (\Exception $e) {
            return new JsonResponse($this->doResponse->doErrorResponse($e->getMessage()));
        }
    }

    #[Route('/leather-status/{id}',
        name: 'put_leather_status',
        methods: ['PUT'])]
    public function modifyLeatherStatus(
        Request            $request,
        ValidatorInterface $validator,
        int                $id,
    ): JsonResponse
    {
        $data = $request->toArray();
        $leatherStatus = $this->doctrine->getRepository(LeatherStatus::class)->find($id);

        if (!$leatherStatus) {
            return new JsonResponse($this->doResponse->doErrorResponse('LeatherStatus not found', 404));
        }

        try {
            $leatherStatus = $this->createMethodsByInput->createMethods($leatherStatus, $data);

            $errors = $validator->validate($leatherStatus);
            if (count($errors) > 0) {
                $errors = $this->validatorOutputFormatter->formatOutput($errors);
                return new JsonResponse($this->doResponse->doErrorResponse($errors));
            }

            $this->doctrine->persist($leatherStatus);
            $this->doctrine->flush();

            $result = $this->groupSerializer->serializeGroup($leatherStatus, 'leather_status_detail');
            return new JsonResponse($this->doResponse->doResponse($result));
        } catch (\Exception $e) {
            return new JsonResponse($this->doResponse->doErrorResponse($e->getMessage()));
        }
    }

    #[Route('/leather-status/{id}',
        name: 'delete_leather_status',
        methods: ['DELETE'])]
    public function deleteLeatherStatus(int $id): JsonResponse
    {
        $leatherStatus = $this->doctrine->getRepository(LeatherStatus::class)->find($id);
        if (!$leatherStatus) {
            return new JsonResponse($this->doResponse->doErrorResponse('LeatherStatus not found', 404));
        }

        $this->doctrine->remove($leatherStatus);
        $this->doctrine->flush();

        return new JsonResponse($this->doResponse->doResponse('delete_successfully'));
    }
}
