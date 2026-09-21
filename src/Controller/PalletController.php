<?php

namespace App\Controller;

use App\Entity\Pallet;
use App\Entity\MeasurementUnit;
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
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'pallet')]
final class PalletController extends AbstractController
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

    #[Route('/pallet/{id}',
        name: 'get_pallet',
        defaults: ['id' => null],
        requirements: ['id' => '\d*'],
        methods: ['GET', 'HEAD'])]
    public function getPallet(?int $id): JsonResponse
    {
        $palletRepository = $this->doctrine->getRepository(Pallet::class);

        if ($id) {
            $pallet = [$palletRepository->find($id)];
            if (!$pallet[0]) {
                return $this->doResponse->doErrorJsonResponse('Pallet not found', 404);
            }
        } else {
            $pallet = $palletRepository->findBy([], ['name' => 'ASC']);
        }
        $results = $this->groupSerializer->serializeGroup($pallet, $id ? 'pallet_detail' : 'pallet_list');

        if ($id) {
            return new JsonResponse($this->doResponse->doResponse($results[0]));
        }
        return new JsonResponse($this->doResponse->doResponse($results));
    }

    #[Route('/pallet',
        name: 'post_pallet',
        methods: ['POST'])]
    public function postPallet(
        Request            $request,
        ValidatorInterface $validator,
    ): JsonResponse
    {
        $data = $request->request->all();
        $pallet = new Pallet();

        try {
            $pallet = $this->handleRelations($pallet, $data);
            $pallet = $this->createMethodsByInput->createMethods($pallet, $data);

            $errors = $validator->validate($pallet);
            if (count($errors) > 0) {
                $errors = $this->validatorOutputFormatter->formatOutput($errors);
                return $this->doResponse->doErrorJsonResponse($errors);
            }

            $em = $this->doctrine;
            $em->persist($pallet);
            $em->flush();

            $result = $this->groupSerializer->serializeGroup($pallet, 'pallet_detail');
            return new JsonResponse($this->doResponse->doResponse($result));

        } catch (\Exception $e) {
            return $this->doResponse->doErrorJsonResponse($e->getMessage());
        }
    }

    #[Route('/pallet/{id}',
        name: 'put_pallet',
        methods: ['PUT'])]
    public function modifyPallet(
        Request            $request,
        ValidatorInterface $validator,
        int                $id,
    ): JsonResponse
    {
        $data = $request->toArray();
        $pallet = $this->doctrine->getRepository(Pallet::class)->find($id);

        if (!$pallet) {
            return $this->doResponse->doErrorJsonResponse('Pallet not found', 404);
        }

        try {
            $pallet = $this->handleRelations($pallet, $data);
            $pallet = $this->createMethodsByInput->createMethods($pallet, $data);

            $errors = $validator->validate($pallet);
            if (count($errors) > 0) {
                $errors = $this->validatorOutputFormatter->formatOutput($errors);
                return $this->doResponse->doErrorJsonResponse($errors);
            }

            $this->doctrine->persist($pallet);
            $this->doctrine->flush();

            $result = $this->groupSerializer->serializeGroup($pallet, 'pallet_detail');
            return new JsonResponse($this->doResponse->doResponse($result));
        } catch (\Exception $e) {
            return $this->doResponse->doErrorJsonResponse($e->getMessage());
        }
    }

    #[Route('/pallet/{id}',
        name: 'delete_pallet',
        methods: ['DELETE'])]
    public function deletePallet(int $id): JsonResponse
    {
        $pallet = $this->doctrine->getRepository(Pallet::class)->find($id);
        if (!$pallet) {
            return $this->doResponse->doErrorJsonResponse('Pallet not found', 404);
        }

        $this->doctrine->remove($pallet);
        $this->doctrine->flush();

        return new JsonResponse($this->doResponse->doResponse('delete_successfully'));
    }

    private function handleRelations(Pallet $pallet, array &$data): Pallet
    {
        if (isset($data['measurement_unit_id'])) {
            $measurementUnit = $this->doctrine->getRepository(MeasurementUnit::class)->find($data['measurement_unit_id']);
            if ($measurementUnit) {
                $pallet->setMeasurementUnit($measurementUnit);
            }
            unset($data['measurement_unit_id']);
        }

        return $pallet;
    }
}

