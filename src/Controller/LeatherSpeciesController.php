<?php

namespace App\Controller;

use App\Entity\LeatherSpecies;
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

final class LeatherSpeciesController extends AbstractController
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

    #[Route('/leather-species/{id}',
        name: 'get_leather_species',
        defaults: ['id' => null],
        requirements: ['id' => '\d*'],
        methods: ['GET', 'HEAD'])]
    public function getLeatherSpecies(?int $id): JsonResponse
    {
        $leatherSpeciesRepository = $this->doctrine->getRepository(LeatherSpecies::class);

        if ($id) {
            $leatherSpecies = [$leatherSpeciesRepository->find($id)];
            if (!$leatherSpecies[0]) {
                return new JsonResponse($this->doResponse->doErrorResponse('LeatherSpecies not found', 404));
            }
        } else {
            $leatherSpecies = $leatherSpeciesRepository->findBy([], ['id' => 'DESC']);
        }
        $results = $this->groupSerializer->serializeGroup($leatherSpecies, $id ? 'leather_species_detail' : 'leather_species_list');

        if ($id) {
            return new JsonResponse($this->doResponse->doResponse($results[0]));
        }
        return new JsonResponse($this->doResponse->doResponse($results));
    }

    #[Route('/leather-species',
        name: 'post_leather_species',
        methods: ['POST'])]
    public function postLeatherSpecies(
        Request            $request,
        ValidatorInterface $validator,
    ): JsonResponse
    {
        $data = $request->request->all();
        $leatherSpecies = new LeatherSpecies();

        try {
            $leatherSpecies = $this->createMethodsByInput->createMethods($leatherSpecies, $data);

            $errors = $validator->validate($leatherSpecies);
            if (count($errors) > 0) {
                $errors = $this->validatorOutputFormatter->formatOutput($errors);
                return new JsonResponse($this->doResponse->doErrorResponse($errors));
            }

            $em = $this->doctrine;
            $em->persist($leatherSpecies);
            $em->flush();

            $result = $this->groupSerializer->serializeGroup($leatherSpecies, 'leather_species_detail');
            return new JsonResponse($this->doResponse->doResponse($result));

        } catch (\Exception $e) {
            return new JsonResponse($this->doResponse->doErrorResponse($e->getMessage()));
        }
    }

    #[Route('/leather-species/{id}',
        name: 'put_leather_species',
        methods: ['PUT'])]
    public function modifyLeatherSpecies(
        Request            $request,
        ValidatorInterface $validator,
        int                $id,
    ): JsonResponse
    {
        $data = $request->toArray();
        $leatherSpecies = $this->doctrine->getRepository(LeatherSpecies::class)->find($id);

        if (!$leatherSpecies) {
            return new JsonResponse($this->doResponse->doErrorResponse('LeatherSpecies not found', 404));
        }

        try {
            $leatherSpecies = $this->createMethodsByInput->createMethods($leatherSpecies, $data);

            $errors = $validator->validate($leatherSpecies);
            if (count($errors) > 0) {
                $errors = $this->validatorOutputFormatter->formatOutput($errors);
                return new JsonResponse($this->doResponse->doErrorResponse($errors));
            }

            $this->doctrine->persist($leatherSpecies);
            $this->doctrine->flush();

            $result = $this->groupSerializer->serializeGroup($leatherSpecies, 'leather_species_detail');
            return new JsonResponse($this->doResponse->doResponse($result));
        } catch (\Exception $e) {
            return new JsonResponse($this->doResponse->doErrorResponse($e->getMessage()));
        }
    }

    #[Route('/leather-species/{id}',
        name: 'delete_leather_species',
        methods: ['DELETE'])]
    public function deleteLeatherSpecies(int $id): JsonResponse
    {
        $leatherSpecies = $this->doctrine->getRepository(LeatherSpecies::class)->find($id);
        if (!$leatherSpecies) {
            return new JsonResponse($this->doResponse->doErrorResponse('LeatherSpecies not found', 404));
        }

        $this->doctrine->remove($leatherSpecies);
        $this->doctrine->flush();

        return new JsonResponse($this->doResponse->doResponse('delete_successfully'));
    }
}
