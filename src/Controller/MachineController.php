<?php

namespace App\Controller;

use App\Entity\Machine;
use App\Entity\BatchType;
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

#[OA\Tag(name: 'machine')]
final class MachineController extends AbstractController
{
    private CreateMethodsByInput $createMethodsByInput;
    private EntityManagerInterface $doctrine;
    private DoResponseService $doResponse;
    private GroupSerializerService $groupSerializer;
    private ValidatorOutputFormatter $validatorOutputFormatter;

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

    #[Route('/machine/{id}',
        name: 'get_machine',
        defaults: ['id' => null],
        requirements: ['id' => '\d*'],
        methods: ['GET', 'HEAD'])]
    public function getMachine(?int $id): JsonResponse
    {
        $machineRepository = $this->doctrine->getRepository(Machine::class);

        if ($id) {
            $machine = $machineRepository->find($id);
            if (!$machine) {
                return $this->doResponse->doErrorJsonResponse('Machine not found', 404);
            }
            $results = $this->groupSerializer->serializeGroup([$machine], 'machine_detail');
            return new JsonResponse($this->doResponse->doResponse($results[0]));
        }

        $machines = $machineRepository->findBy([], ['name' => 'ASC']);
        $results = $this->groupSerializer->serializeGroup($machines, 'machine_list');
        return new JsonResponse($this->doResponse->doResponse($results));
    }

    #[Route('/machine',
        name: 'post_machine',
        methods: ['POST'])]
    public function postMachine(
        Request            $request,
        ValidatorInterface $validator,
    ): JsonResponse
    {
        $data = $request->request->all();
        $machine = new Machine();

        try {
            $machine = $this->handleRelations($machine, $data);
            $machine = $this->createMethodsByInput->createMethods($machine, $data);

            $errors = $validator->validate($machine);
            if (count($errors) > 0) {
                return $this->doResponse->doErrorJsonResponse($this->validatorOutputFormatter->format($errors), 400);
            }

            $this->doctrine->persist($machine);
            $this->doctrine->flush();

            return new JsonResponse($this->doResponse->doResponse('created_successfully'));
        } catch (\Exception $e) {
            return $this->doResponse->doErrorJsonResponse($e->getMessage(), 400);
        }
    }

    #[Route('/machine/{id}',
        name: 'put_machine',
        methods: ['PUT'])]
    public function putMachine(
        int                $id,
        Request            $request,
        ValidatorInterface $validator,
    ): JsonResponse
    {
        $machine = $this->doctrine->getRepository(Machine::class)->find($id);
        if (!$machine) {
            return $this->doResponse->doErrorJsonResponse('Machine not found', 404);
        }

        $data = $request->request->all();

        try {
            $machine = $this->handleRelations($machine, $data);
            $machine = $this->createMethodsByInput->createMethods($machine, $data);

            $errors = $validator->validate($machine);
            if (count($errors) > 0) {
                return $this->doResponse->doErrorJsonResponse($this->validatorOutputFormatter->format($errors), 400);
            }

            $this->doctrine->flush();

            return new JsonResponse($this->doResponse->doResponse('updated_successfully'));
        } catch (\Exception $e) {
            return $this->doResponse->doErrorJsonResponse($e->getMessage(), 400);
        }
    }

    #[Route('/machine/{id}',
        name: 'delete_machine',
        methods: ['DELETE'])]
    public function deleteMachine(int $id): JsonResponse
    {
        $machine = $this->doctrine->getRepository(Machine::class)->find($id);
        if (!$machine) {
            return $this->doResponse->doErrorJsonResponse('Machine not found', 404);
        }

        $this->doctrine->remove($machine);
        $this->doctrine->flush();

        return new JsonResponse($this->doResponse->doResponse('deleted_successfully'));
    }

    private function handleRelations(Machine $machine, array &$data): Machine
    {
        if (isset($data['batch_type_id'])) {
            $batchType = $this->doctrine->getRepository(BatchType::class)->find($data['batch_type_id']);
            if ($batchType) {
                $machine->setBatchType($batchType);
            }
            unset($data['batch_type_id']);
        }

        return $machine;
    }
}

