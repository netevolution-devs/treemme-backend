<?php

namespace App\Controller;

use App\Entity\LeatherFlay;
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

final class LeatherFlayController extends AbstractController
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

    #[Route('/leather-flay/{id}',
        name: 'get_leather_flay',
        defaults: ['id' => null],
        requirements: ['id' => '\d*'],
        methods: ['GET', 'HEAD'])]
    public function getLeatherFlay(?int $id): JsonResponse
    {
        $leatherFlayRepository = $this->doctrine->getRepository(LeatherFlay::class);

        if ($id) {
            $leatherFlay = [$leatherFlayRepository->find($id)];
            if (!$leatherFlay[0]) {
                return new JsonResponse($this->doResponse->doErrorResponse('LeatherFlay not found', 404));
            }
        } else {
            $leatherFlay = $leatherFlayRepository->findBy([], ['id' => 'DESC']);
        }
        $results = $this->groupSerializer->serializeGroup($leatherFlay, $id ? 'leather_flay_detail' : 'leather_flay_list');

        if ($id) {
            return new JsonResponse($this->doResponse->doResponse($results[0]));
        }
        return new JsonResponse($this->doResponse->doResponse($results));
    }

    #[Route('/leather-flay',
        name: 'post_leather_flay',
        methods: ['POST'])]
    public function postLeatherFlay(
        Request            $request,
        ValidatorInterface $validator,
    ): JsonResponse
    {
        $data = $request->request->all();
        $leatherFlay = new LeatherFlay();

        try {
            $leatherFlay = $this->createMethodsByInput->createMethods($leatherFlay, $data);

            $errors = $validator->validate($leatherFlay);
            if (count($errors) > 0) {
                $errors = $this->validatorOutputFormatter->formatOutput($errors);
                return new JsonResponse($this->doResponse->doErrorResponse($errors));
            }

            $em = $this->doctrine;
            $em->persist($leatherFlay);
            $em->flush();

            $result = $this->groupSerializer->serializeGroup($leatherFlay, 'leather_flay_detail');
            return new JsonResponse($this->doResponse->doResponse($result));

        } catch (\Exception $e) {
            return new JsonResponse($this->doResponse->doErrorResponse($e->getMessage()));
        }
    }

    #[Route('/leather-flay/{id}',
        name: 'put_leather_flay',
        methods: ['PUT'])]
    public function modifyLeatherFlay(
        Request            $request,
        ValidatorInterface $validator,
        int                $id,
    ): JsonResponse
    {
        $data = $request->toArray();
        $leatherFlay = $this->doctrine->getRepository(LeatherFlay::class)->find($id);

        if (!$leatherFlay) {
            return new JsonResponse($this->doResponse->doErrorResponse('LeatherFlay not found', 404));
        }

        try {
            $leatherFlay = $this->createMethodsByInput->createMethods($leatherFlay, $data);

            $errors = $validator->validate($leatherFlay);
            if (count($errors) > 0) {
                $errors = $this->validatorOutputFormatter->formatOutput($errors);
                return new JsonResponse($this->doResponse->doErrorResponse($errors));
            }

            $this->doctrine->persist($leatherFlay);
            $this->doctrine->flush();

            $result = $this->groupSerializer->serializeGroup($leatherFlay, 'leather_flay_detail');
            return new JsonResponse($this->doResponse->doResponse($result));
        } catch (\Exception $e) {
            return new JsonResponse($this->doResponse->doErrorResponse($e->getMessage()));
        }
    }

    #[Route('/leather-flay/{id}',
        name: 'delete_leather_flay',
        methods: ['DELETE'])]
    public function deleteLeatherFlay(int $id): JsonResponse
    {
        $leatherFlay = $this->doctrine->getRepository(LeatherFlay::class)->find($id);
        if (!$leatherFlay) {
            return new JsonResponse($this->doResponse->doErrorResponse('LeatherFlay not found', 404));
        }

        $this->doctrine->remove($leatherFlay);
        $this->doctrine->flush();

        return new JsonResponse($this->doResponse->doResponse('delete_successfully'));
    }
}
