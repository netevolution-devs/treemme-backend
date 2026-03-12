<?php

namespace App\Controller;

use App\Entity\Batch;
use App\Entity\Machine;
use App\Entity\Production;
use App\Repository\ProductionRepository;
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

class ProductionController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface   $entityManager,
        private readonly ProductionRepository      $productionRepository,
        private readonly GroupSerializerService   $groupSerializer,
        private readonly DoResponseService        $doResponse,
        private readonly CreateMethodsByInput     $createMethodsByInput,
        private readonly ValidatorOutputFormatter $validatorOutputFormatter
    )
    {
    }

    #[Route('/production/{id}', name: 'app_production_index', methods: ['GET'], defaults: ['id' => null], requirements: ['id' => '\d+'])]
    public function index(Request $request, ?int $id = null): JsonResponse
    {
        if ($id) {
            $production = $this->productionRepository->find($id);

            if (!$production) {
                return new JsonResponse($this->doResponse->doErrorResponse('Produzione non trovata', status_code: 404));
            }

            $results = $this->groupSerializer->serializeGroup($production, 'production_detail');

            return new JsonResponse($this->doResponse->doResponse($results));
        }

        $productions = $this->productionRepository->findAll();
        $results = $this->groupSerializer->serializeGroup($productions, 'production_list');

        return new JsonResponse($this->doResponse->doResponse($results));
    }

    #[Route('/production', name: 'app_production_create', methods: ['POST'])]
    public function create(Request $request, ValidatorInterface $validator): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? $request->request->all();

        $production = new Production();

        try {
            $this->mapDataToEntity($production, $data);
        } catch (\Exception $e) {
            return new JsonResponse($this->doResponse->doErrorResponse($e->getMessage()));
        }

        $errors = $validator->validate($production);
        if (count($errors) > 0) {
            $formattedErrors = $this->validatorOutputFormatter->formatOutput($errors);
            return new JsonResponse($this->doResponse->doErrorResponse($formattedErrors));
        }

        $this->entityManager->persist($production);
        $this->entityManager->flush();

        $results = $this->groupSerializer->serializeGroup($production, 'production_detail');
        return new JsonResponse($this->doResponse->doResponse($results));
    }

    #[Route('/production/{id}', name: 'app_production_update', methods: ['PUT', 'PATCH'])]
    public function update(Request $request, int $id, ValidatorInterface $validator): JsonResponse
    {
        $production = $this->productionRepository->find($id);

        if (!$production) {
            return new JsonResponse($this->doResponse->doErrorResponse('Produzione non trovata', status_code: 404));
        }

        $data = json_decode($request->getContent(), true) ?? $request->request->all();

        try {
            $this->mapDataToEntity($production, $data);
        } catch (\Exception $e) {
            return new JsonResponse($this->doResponse->doErrorResponse($e->getMessage()));
        }

        $errors = $validator->validate($production);
        if (count($errors) > 0) {
            $formattedErrors = $this->validatorOutputFormatter->formatOutput($errors);
            return new JsonResponse($this->doResponse->doErrorResponse($formattedErrors));
        }

        $this->entityManager->flush();

        $results = $this->groupSerializer->serializeGroup($production, 'production_detail');
        return new JsonResponse($this->doResponse->doResponse($results));
    }

    #[Route('/production/{id}', name: 'app_production_delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $production = $this->productionRepository->find($id);

        if (!$production) {
            return new JsonResponse($this->doResponse->doErrorResponse('Produzione non trovata', status_code: 404));
        }

        $this->entityManager->remove($production);
        $this->entityManager->flush();

        return new JsonResponse($this->doResponse->doResponse(null, status: 'Produzione eliminata correttamente'));
    }

    /**
     * @throws \Exception
     */
    private function mapDataToEntity(Production $production, array $data): void
    {
        // Relazioni
        if (isset($data['batch_id'])) {
            $batch = $this->entityManager->getRepository(Batch::class)->find($data['batch_id']);
            if (!$batch) throw new \Exception("Lotto con ID {$data['batch_id']} non trovato");
            $production->setBatch($batch);
            unset($data['batch_id']);
        }

        if (isset($data['machine_id'])) {
            $machine = $this->entityManager->getRepository(Machine::class)->find($data['machine_id']);
            if (!$machine) throw new \Exception("Macchinario con ID {$data['machine_id']} non trovato");
            $production->setMachine($machine);
            unset($data['machine_id']);
        }

        if (isset($data['scheduled_date'])) {
            try {
                $production->setScheduledDate(new \DateTime($data['scheduled_date']));
            } catch (\Exception $e) {
                throw new \Exception("Formato data non valido per scheduled_date: {$data['scheduled_date']}");
            }
            unset($data['scheduled_date']);
        }

        // Mappatura campi semplici
        $this->createMethodsByInput->createMethods($production, $data);
    }
}
