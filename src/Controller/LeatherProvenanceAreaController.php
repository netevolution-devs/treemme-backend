<?php

namespace App\Controller;

use App\Entity\LeatherProvenanceArea;
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

final class LeatherProvenanceAreaController extends AbstractController
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

    #[Route('/leather-provenance-area/{id}',
        name: 'get_leather_provenance_area',
        defaults: ['id' => null],
        requirements: ['id' => '\d*'],
        methods: ['GET', 'HEAD'])]
    public function getLeatherProvenanceArea(?int $id): JsonResponse
    {
        $leatherProvenanceAreaRepository = $this->doctrine->getRepository(LeatherProvenanceArea::class);

        if ($id) {
            $leatherProvenanceArea = [$leatherProvenanceAreaRepository->find($id)];
            if (!$leatherProvenanceArea[0]) {
                return new JsonResponse($this->doResponse->doErrorResponse('LeatherProvenanceArea not found', 404));
            }
        } else {
            $leatherProvenanceArea = $leatherProvenanceAreaRepository->findBy([], ['id' => 'DESC']);
        }
        $results = $this->groupSerializer->serializeGroup($leatherProvenanceArea, $id ? 'leather_provenance_area_detail' : 'leather_provenance_area_list');

        if ($id) {
            return new JsonResponse($this->doResponse->doResponse($results[0]));
        }
        return new JsonResponse($this->doResponse->doResponse($results));
    }

    #[Route('/leather-provenance-area',
        name: 'post_leather_provenance_area',
        methods: ['POST'])]
    public function postLeatherProvenanceArea(
        Request            $request,
        ValidatorInterface $validator,
    ): JsonResponse
    {
        $data = $request->request->all();
        $leatherProvenanceArea = new LeatherProvenanceArea();

        try {
            $leatherProvenanceArea = $this->createMethodsByInput->createMethods($leatherProvenanceArea, $data);

            $errors = $validator->validate($leatherProvenanceArea);
            if (count($errors) > 0) {
                $errors = $this->validatorOutputFormatter->formatOutput($errors);
                return new JsonResponse($this->doResponse->doErrorResponse($errors));
            }

            $em = $this->doctrine;
            $em->persist($leatherProvenanceArea);
            $em->flush();

            $result = $this->groupSerializer->serializeGroup($leatherProvenanceArea, 'leather_provenance_area_detail');
            return new JsonResponse($this->doResponse->doResponse($result));

        } catch (\Exception $e) {
            return new JsonResponse($this->doResponse->doErrorResponse($e->getMessage()));
        }
    }

    #[Route('/leather-provenance-area/{id}',
        name: 'put_leather_provenance_area',
        methods: ['PUT'])]
    public function modifyLeatherProvenanceArea(
        Request            $request,
        ValidatorInterface $validator,
        int                $id,
    ): JsonResponse
    {
        $data = $request->toArray();
        $leatherProvenanceArea = $this->doctrine->getRepository(LeatherProvenanceArea::class)->find($id);

        if (!$leatherProvenanceArea) {
            return new JsonResponse($this->doResponse->doErrorResponse('LeatherProvenanceArea not found', 404));
        }

        try {
            $leatherProvenanceArea = $this->createMethodsByInput->createMethods($leatherProvenanceArea, $data);

            $errors = $validator->validate($leatherProvenanceArea);
            if (count($errors) > 0) {
                $errors = $this->validatorOutputFormatter->formatOutput($errors);
                return new JsonResponse($this->doResponse->doErrorResponse($errors));
            }

            $this->doctrine->persist($leatherProvenanceArea);
            $this->doctrine->flush();

            $result = $this->groupSerializer->serializeGroup($leatherProvenanceArea, 'leather_provenance_area_detail');
            return new JsonResponse($this->doResponse->doResponse($result));
        } catch (\Exception $e) {
            return new JsonResponse($this->doResponse->doErrorResponse($e->getMessage()));
        }
    }

    #[Route('/leather-provenance-area/{id}',
        name: 'delete_leather_provenance_area',
        methods: ['DELETE'])]
    public function deleteLeatherProvenanceArea(int $id): JsonResponse
    {
        $leatherProvenanceArea = $this->doctrine->getRepository(LeatherProvenanceArea::class)->find($id);
        if (!$leatherProvenanceArea) {
            return new JsonResponse($this->doResponse->doErrorResponse('LeatherProvenanceArea not found', 404));
        }

        $this->doctrine->remove($leatherProvenanceArea);
        $this->doctrine->flush();

        return new JsonResponse($this->doResponse->doResponse('delete_successfully'));
    }
}
