<?php

namespace App\Controller;

use App\Entity\Ddt;
use App\Entity\Contact;
use App\Entity\DdtReason;
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

#[OA\Tag(name: 'ddt')]
final class DdtController extends AbstractController
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
        ValidatorOutputFormatter $validatorOutputFormatter
    ) {
        $this->createMethodsByInput = $createMethodsByInput;
        $this->doctrine = $entityManager;
        $this->doResponse = $doResponseService;
        $this->groupSerializer = $groupSerializer;
        $this->validatorOutputFormatter = $validatorOutputFormatter;
    }

    #[Route('/ddt/{id}',
        name: 'get_ddt',
        defaults: ['id' => null],
        requirements: ['id' => '\d+'],
        methods: ['GET', 'HEAD'])]
    public function getDdt(?int $id, Request $request): JsonResponse
    {
        $ddtRepository = $this->doctrine->getRepository(Ddt::class);

        if ($id) {
            $ddt = $ddtRepository->find($id);
            if (!$ddt) {
                return $this->doResponse->doErrorJsonResponse('DDT non trovato', 404);
            }
            $results = $this->groupSerializer->serializeGroup([$ddt], 'ddt_detail');
            return new JsonResponse($this->doResponse->doResponse($results[0]));
        }

        $subcontractorId = $request->query->get('subcontractor_id') ? (int)$request->query->get('subcontractor_id') : null;
        $clientId = $request->query->get('client_id') ? (int)$request->query->get('client_id') : null;
        $startDateStr = $request->query->get('start_date');
        $endDateStr = $request->query->get('end_date');

        $startDate = $startDateStr ? \DateTime::createFromFormat('Y-m-d', $startDateStr) : null;
        if ($startDate) $startDate->setTime(0, 0, 0);

        $endDate = $endDateStr ? \DateTime::createFromFormat('Y-m-d', $endDateStr) : null;
        if ($endDate) $endDate->setTime(0, 0, 0);

        if ($subcontractorId || $clientId || $startDate || $endDate) {
            $ddts = $ddtRepository->findByFilters($subcontractorId, $clientId, $startDate, $endDate);
        } else {
            $ddts = $ddtRepository->findBy([], ['ddt_number' => 'ASC']);
        }

        $results = $this->groupSerializer->serializeGroup($ddts, 'ddt_list');
        return new JsonResponse($this->doResponse->doResponse($results));
    }

    #[Route('/ddt',
        name: 'post_ddt',
        methods: ['POST'])]
    public function postDdt(Request $request, ValidatorInterface $validator): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? $request->request->all();

        $ddt = new Ddt();
        try {
            $this->handleRelations($ddt, $data);
            $ddt = $this->createMethodsByInput->createMethods($ddt, $data);
        } catch (\Exception $e) {
            return $this->doResponse->doErrorJsonResponse($e->getMessage(), 400);
        }

        $errors = $validator->validate($ddt);
        if (count($errors) > 0) {
            return $this->doResponse->doErrorJsonResponse($this->validatorOutputFormatter->formatOutput($errors), 400);
        }

        $this->doctrine->persist($ddt);
        $this->doctrine->flush();

        $results = $this->groupSerializer->serializeGroup([$ddt], 'ddt_detail');
        return new JsonResponse($this->doResponse->doResponse($results[0]));
    }

    #[Route('/ddt/{id}',
        name: 'put_ddt',
        requirements: ['id' => '\d+'],
        methods: ['PUT', 'PATCH'])]
    public function putDdt(int $id, Request $request, ValidatorInterface $validator): JsonResponse
    {
        $ddt = $this->doctrine->getRepository(Ddt::class)->find($id);
        if (!$ddt) {
            return $this->doResponse->doErrorJsonResponse('DDT non trovato', 404);
        }

        $data = json_decode($request->getContent(), true) ?? $request->request->all();

        try {
            $this->handleRelations($ddt, $data);
            $this->createMethodsByInput->createMethods($ddt, $data);
        } catch (\Exception $e) {
            return $this->doResponse->doErrorJsonResponse($e->getMessage(), 400);
        }

        $errors = $validator->validate($ddt);
        if (count($errors) > 0) {
            return $this->doResponse->doErrorJsonResponse($this->validatorOutputFormatter->formatOutput($errors), 400);
        }

        $this->doctrine->flush();

        $results = $this->groupSerializer->serializeGroup([$ddt], 'ddt_detail');
        return new JsonResponse($this->doResponse->doResponse($results[0]));
    }

    #[Route('/ddt/{id}',
        name: 'delete_ddt',
        requirements: ['id' => '\d+'],
        methods: ['DELETE'])]
    public function deleteDdt(int $id): JsonResponse
    {
        $ddt = $this->doctrine->getRepository(Ddt::class)->find($id);
        if (!$ddt) {
            return $this->doResponse->doErrorJsonResponse('DDT non trovato', 404);
        }

        if($ddt->getDdtRows()->count() != 0){
            $ddtRows = $ddt->getDdtRows();

            foreach($ddtRows as $ddtRow){
                $this->doctrine->remove($ddtRow);
                $this->doctrine->flush();
            }
        }

        $this->doctrine->remove($ddt);
        $this->doctrine->flush();

        return new JsonResponse($this->doResponse->doResponse(['message' => 'DDT eliminato con successo']));
    }

    private function handleRelations(Ddt $ddt, array &$data): void
    {
        if (isset($data['subcontractor_id'])) {
            $subcontractor = $this->doctrine->getRepository(Contact::class)->find($data['subcontractor_id']);
            if ($subcontractor) {
                $ddt->setSubcontractor($subcontractor);
            }
            unset($data['subcontractor_id']);
        }

        if (isset($data['client_id'])) {
            $client = $this->doctrine->getRepository(Contact::class)->find($data['client_id']);
            if ($client) {
                $ddt->setClient($client);
            }
            unset($data['client_id']);
        }

        if (isset($data['reason_id'])) {
            $reason = $this->doctrine->getRepository(DdtReason::class)->find($data['reason_id']);
            if ($reason) {
                $ddt->setReason($reason);
            }
            unset($data['reason_id']);
        }
    }
}

