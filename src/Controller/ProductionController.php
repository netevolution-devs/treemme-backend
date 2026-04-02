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
use App\Service\PdfGeneratorService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ProductionController extends AbstractController
{
    private $createMethodsByInput;
    private $doctrine;
    private $doResponse;
    private $groupSerializer;
    private $validatorOutputFormatter;
    private $pdfGenerator;

    public function __construct(
        CreateMethodsByInput     $createMethodsByInput,
        EntityManagerInterface   $entityManager,
        DoResponseService        $doResponseService,
        GroupSerializerService   $groupSerializer,
        ValidatorOutputFormatter $validatorOutputFormatter,
        PdfGeneratorService      $pdfGenerator,
    )
    {
        $this->createMethodsByInput = $createMethodsByInput;
        $this->doctrine = $entityManager;
        $this->doResponse = $doResponseService;
        $this->groupSerializer = $groupSerializer;
        $this->validatorOutputFormatter = $validatorOutputFormatter;
        $this->pdfGenerator = $pdfGenerator;
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

    #[Route('/production/daily-pdf', name: 'production_daily_pdf', methods: ['GET'])]
    public function generateDailyPdf(Request $request): Response
    {
        $dateParam = $request->query->get('date');
        if (!$dateParam) {
            return new JsonResponse($this->doResponse->doErrorResponse('Parametro "date" mancante (formato atteso: YYYY-MM-DD)', 400));
        }

        try {
            $day = \DateTimeImmutable::createFromFormat('!Y-m-d', $dateParam);
            $errors = \DateTimeImmutable::getLastErrors();

            if (
                !$day ||
                ($errors !== false && (($errors['warning_count'] ?? 0) > 0 || ($errors['error_count'] ?? 0) > 0))
            ) {
                throw new \Exception();
            }
        } catch (\Exception $e) {
            return new JsonResponse($this->doResponse->doErrorResponse('Formato data non valido per "date" (atteso: YYYY-MM-DD)'), 400);
        }

        $start = $day->setTime(0, 0, 0);
        $end = $day->modify('+1 day')->setTime(0, 0, 0);

        $repo = $this->doctrine->getRepository(Production::class);

        $productions = $repo->findBy(['scheduled_date' => $day]);

        $groupedProductions = [];
        foreach ($productions as $production) {
            $machine = $production->getMachine();
            $machineId = $machine ? $machine->getId() : 0;
            if (!isset($groupedProductions[$machineId])) {
                $groupedProductions[$machineId] = [
                    'machine' => $machine,
                    'items' => []
                ];
            }
            $groupedProductions[$machineId]['items'][] = $production;
        }

        $pdfContent = $this->pdfGenerator->generatePdf('print/production_load_pdf.html.twig', [
            'day' => $day->format('d/m/Y'),
            'grouped_productions' => $groupedProductions,
            'app_root' => $this->getParameter('kernel.project_dir')
        ], 'carico_bottali_' . $day->format('Ymd') . '.pdf');

        return new Response($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="carico_bottali_' . $day->format('Ymd') . '.pdf"'
        ]);
    }
}
