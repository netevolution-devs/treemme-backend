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

    #[Route('/production/{id}', name: 'app_production_index', methods: ['GET'], defaults: ['id' => null], requirements: ['id' => '\d+'])]
    public function index(Request $request, ?int $id = null): JsonResponse
    {
        if ($id) {
            $production = $this->doctrine->getRepository(Production::class)->find($id);

            if (!$production) {
                return new JsonResponse($this->doResponse->doErrorResponse('Produzione non trovata'), 404);
            }

            $results = $this->groupSerializer->serializeGroup($production, 'production_detail');

            return new JsonResponse($this->doResponse->doResponse($results));
        }

        $productions = [];
        $scheduledDate = $request->query->get('scheduled_date');

        if ($scheduledDate) {
            try {
                $date = new \DateTimeImmutable($scheduledDate);
            } catch (\Exception $e) {
                return new JsonResponse($this->doResponse->doErrorResponse('Formato data non valido per scheduled_date'), 404);
            }

            $productions = $this->doctrine->getRepository(Production::class)->findBy(['scheduled_date' => $date]);
        } else {
            $productions = $this->doctrine->getRepository(Production::class)->findAll();
        }

        $results = $this->groupSerializer->serializeGroup($productions, 'production_list');

        return new JsonResponse($this->doResponse->doResponse($results));
    }

    #[Route('/batch/{batch_id}/production', name: 'app_production_by_batch',
        methods: ['GET'],
        defaults: ['batch_id' => null],
        requirements: ['batch_id' => '\d+'])]
    public function getProductionByBatch(Request $request, ?int $batch_id = null): JsonResponse
    {
        if (!$batch_id) {
            return new JsonResponse($this->doResponse->doErrorResponse('ID lotto non specificato', status_code: 400));
        }

        $batch = $this->doctrine->getRepository(Batch::class)->find($batch_id);

        if (!$batch) {
            return new JsonResponse($this->doResponse->doErrorResponse('Lotto non trovato', status_code: 404));
        }

        $productions = $this->doctrine->getRepository(Production::class)->findBy(['batch' => $batch]);
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

        $this->doctrine->persist($production);
        $this->doctrine->flush();

        $results = $this->groupSerializer->serializeGroup($production, 'production_detail');
        return new JsonResponse($this->doResponse->doResponse($results));
    }

    #[Route('/production/{id}', name: 'app_production_update', methods: ['PUT', 'PATCH'])]
    public function update(Request $request, int $id, ValidatorInterface $validator): JsonResponse
    {
        $production = $this->doctrine->getRepository(Production::class)->find($id);

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

        $this->doctrine->flush();

        $results = $this->groupSerializer->serializeGroup($production, 'production_detail');
        return new JsonResponse($this->doResponse->doResponse($results));
    }

    #[Route('/production/{id}', name: 'app_production_delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $production = $this->doctrine->getRepository(Production::class)->find($id);

        if (!$production) {
            return new JsonResponse($this->doResponse->doErrorResponse('Produzione non trovata', status_code: 404));
        }

        $this->doctrine->remove($production);
        $this->doctrine->flush();

        return new JsonResponse($this->doResponse->doResponse(null, status: 'Produzione eliminata correttamente'));
    }

    /**
     * @throws \Exception
     */
    private function mapDataToEntity(Production $production, array $data): void
    {
        // Relazioni
        if (isset($data['batch_id'])) {
            $batch = $this->doctrine->getRepository(Batch::class)->find($data['batch_id']);
            if (!$batch) throw new \Exception("Lotto con ID {$data['batch_id']} non trovato");
            $production->setBatch($batch);
            unset($data['batch_id']);
        }

        if (isset($data['machine_id'])) {
            $machine = $this->doctrine->getRepository(Machine::class)->find($data['machine_id']);
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
