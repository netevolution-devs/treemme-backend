<?php

namespace App\Controller;

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

final class MeasurementUnitController extends AbstractController
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

    #[Route('/measurement-unit/{id}',
        name: 'get_measurement_unit',
        defaults: ['id' => null],
        requirements: ['id' => '\d*'],
        methods: ['GET', 'HEAD'])]
    public function getMeasurementUnit(?int $id): JsonResponse
    {
        $repository = $this->doctrine->getRepository(MeasurementUnit::class);

        if ($id) {
            $unit = [$repository->find($id)];
            if (!$unit[0]) {
                return new JsonResponse($this->doResponse->doErrorResponse('MeasurementUnit not found', 404));
            }
        } else {
            $unit = $repository->findBy([], ['id' => 'DESC']);
        }
        $results = $this->groupSerializer->serializeGroup($unit, $id ? 'measurement_unit_detail' : 'measurement_unit_list');

        if ($id) {
            return new JsonResponse($this->doResponse->doResponse($results[0]));
        }
        return new JsonResponse($this->doResponse->doResponse($results));
    }

    #[Route('/measurement-unit',
        name: 'post_measurement_unit',
        methods: ['POST'])]
    public function postMeasurementUnit(
        Request            $request,
        ValidatorInterface $validator,
    ): JsonResponse
    {
        $data = $request->request->all();
        $unit = new MeasurementUnit();

        try {
            $unit = $this->createMethodsByInput->createMethods($unit, $data);

            $now = new \DateTimeImmutable();
            $unit->setCreatedAt($now);
            $unit->setUpdatedAt($now);

            $errors = $validator->validate($unit);
            if (count($errors) > 0) {
                $errors = $this->validatorOutputFormatter->formatOutput($errors);
                return new JsonResponse($this->doResponse->doErrorResponse($errors));
            }

            $this->doctrine->persist($unit);
            $this->doctrine->flush();

            $result = $this->groupSerializer->serializeGroup($unit, 'measurement_unit_detail');
            return new JsonResponse($this->doResponse->doResponse($result));

        } catch (\Exception $e) {
            return new JsonResponse($this->doResponse->doErrorResponse($e->getMessage()));
        }
    }

    #[Route('/measurement-unit/{id}',
        name: 'put_measurement_unit',
        methods: ['PUT'])]
    public function modifyMeasurementUnit(
        Request            $request,
        ValidatorInterface $validator,
        int                $id,
    ): JsonResponse
    {
        $data = $request->toArray();
        $unit = $this->doctrine->getRepository(MeasurementUnit::class)->find($id);

        if (!$unit) {
            return new JsonResponse($this->doResponse->doErrorResponse('MeasurementUnit not found', 404));
        }

        try {
            $unit = $this->createMethodsByInput->createMethods($unit, $data);
            $unit->setUpdatedAt(new \DateTimeImmutable());

            $errors = $validator->validate($unit);
            if (count($errors) > 0) {
                $errors = $this->validatorOutputFormatter->formatOutput($errors);
                return new JsonResponse($this->doResponse->doErrorResponse($errors));
            }

            $this->doctrine->persist($unit);
            $this->doctrine->flush();

            $result = $this->groupSerializer->serializeGroup($unit, 'measurement_unit_detail');
            return new JsonResponse($this->doResponse->doResponse($result));
        } catch (\Exception $e) {
            return new JsonResponse($this->doResponse->doErrorResponse($e->getMessage()));
        }
    }

    #[Route('/measurement-unit/{id}',
        name: 'delete_measurement_unit',
        methods: ['DELETE'])]
    public function deleteMeasurementUnit(int $id): JsonResponse
    {
        $unit = $this->doctrine->getRepository(MeasurementUnit::class)->find($id);
        if (!$unit) {
            return new JsonResponse($this->doResponse->doErrorResponse('MeasurementUnit not found', 404));
        }

        $this->doctrine->remove($unit);
        $this->doctrine->flush();

        return new JsonResponse($this->doResponse->doResponse('delete_successfully'));
    }
}
