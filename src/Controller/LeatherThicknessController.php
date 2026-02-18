<?php

namespace App\Controller;

use App\Entity\LeatherThickness;
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

final class LeatherThicknessController extends AbstractController
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

    #[Route('/leather-thickness/{id}',
        name: 'get_leather_thickness',
        defaults: ['id' => null],
        requirements: ['id' => '\d*'],
        methods: ['GET', 'HEAD'])]
    public function getLeatherThickness(?int $id): JsonResponse
    {
        $leatherThicknessRepository = $this->doctrine->getRepository(LeatherThickness::class);

        if ($id) {
            $leatherThickness = [$leatherThicknessRepository->find($id)];
            if (!$leatherThickness[0]) {
                return new JsonResponse($this->doResponse->doErrorResponse('LeatherThickness not found', 404));
            }
        } else {
            $leatherThickness = $leatherThicknessRepository->findBy([], ['id' => 'DESC']);
        }
        $results = $this->groupSerializer->serializeGroup($leatherThickness, $id ? 'leather_thickness_detail' : 'leather_thickness_list');

        if ($id) {
            return new JsonResponse($this->doResponse->doResponse($results[0]));
        }
        return new JsonResponse($this->doResponse->doResponse($results));
    }

    #[Route('/leather-thickness',
        name: 'post_leather_thickness',
        methods: ['POST'])]
    public function postLeatherThickness(
        Request            $request,
        ValidatorInterface $validator,
    ): JsonResponse
    {
        $data = $request->request->all();
        $leatherThickness = new LeatherThickness();

        try {
            $leatherThickness = $this->createMethodsByInput->createMethods($leatherThickness, $data);

            $errors = $validator->validate($leatherThickness);
            if (count($errors) > 0) {
                $errors = $this->validatorOutputFormatter->formatOutput($errors);
                return new JsonResponse($this->doResponse->doErrorResponse($errors));
            }

            $em = $this->doctrine;
            $em->persist($leatherThickness);
            $em->flush();

            $result = $this->groupSerializer->serializeGroup($leatherThickness, 'leather_thickness_detail');
            return new JsonResponse($this->doResponse->doResponse($result));

        } catch (\Exception $e) {
            return new JsonResponse($this->doResponse->doErrorResponse($e->getMessage()));
        }
    }

    #[Route('/leather-thickness/{id}',
        name: 'put_leather_thickness',
        methods: ['PUT'])]
    public function modifyLeatherThickness(
        Request            $request,
        ValidatorInterface $validator,
        int                $id,
    ): JsonResponse
    {
        $data = $request->toArray();
        $leatherThickness = $this->doctrine->getRepository(LeatherThickness::class)->find($id);

        if (!$leatherThickness) {
            return new JsonResponse($this->doResponse->doErrorResponse('LeatherThickness not found', 404));
        }

        try {
            $leatherThickness = $this->createMethodsByInput->createMethods($leatherThickness, $data);

            $errors = $validator->validate($leatherThickness);
            if (count($errors) > 0) {
                $errors = $this->validatorOutputFormatter->formatOutput($errors);
                return new JsonResponse($this->doResponse->doErrorResponse($errors));
            }

            $this->doctrine->persist($leatherThickness);
            $this->doctrine->flush();

            $result = $this->groupSerializer->serializeGroup($leatherThickness, 'leather_thickness_detail');
            return new JsonResponse($this->doResponse->doResponse($result));
        } catch (\Exception $e) {
            return new JsonResponse($this->doResponse->doErrorResponse($e->getMessage()));
        }
    }

    #[Route('/leather-thickness/{id}',
        name: 'delete_leather_thickness',
        methods: ['DELETE'])]
    public function deleteLeatherThickness(int $id): JsonResponse
    {
        $leatherThickness = $this->doctrine->getRepository(LeatherThickness::class)->find($id);
        if (!$leatherThickness) {
            return new JsonResponse($this->doResponse->doErrorResponse('LeatherThickness not found', 404));
        }

        $this->doctrine->remove($leatherThickness);
        $this->doctrine->flush();

        return new JsonResponse($this->doResponse->doResponse('delete_successfully'));
    }
}
