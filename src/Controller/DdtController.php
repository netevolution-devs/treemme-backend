<?php

namespace App\Controller;

use App\Entity\Ddt;
use App\Entity\Contact;
use App\Entity\DdtPurpose;
use App\Service\DoResponseService;
use App\Service\GroupSerializerService;
use App\Service\ValidatorOutputFormatter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class DdtController extends AbstractController
{
    private $doctrine;
    private $doResponse;
    private $groupSerializer;
    private $validatorOutputFormatter;

    public function __construct(
        EntityManagerInterface   $entityManager,
        DoResponseService        $doResponseService,
        GroupSerializerService   $groupSerializer,
        ValidatorOutputFormatter $validatorOutputFormatter
    ) {
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
    public function getDdt(?int $id): JsonResponse
    {
        $ddtRepository = $this->doctrine->getRepository(Ddt::class);

        if ($id) {
            $ddt = $ddtRepository->find($id);
            if (!$ddt) {
                return new JsonResponse($this->doResponse->doErrorResponse('DDT non trovato', 404));
            }
            $results = $this->groupSerializer->serializeGroup([$ddt], 'ddt_detail');
            return new JsonResponse($this->doResponse->doResponse($results[0]));
        }

        $ddts = $ddtRepository->findBy([], ['id' => 'DESC']);
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
            $this->handleData($ddt, $data);
        } catch (\Exception $e) {
            return new JsonResponse($this->doResponse->doErrorResponse($e->getMessage(), 400));
        }

        $errors = $validator->validate($ddt);
        if (count($errors) > 0) {
            return new JsonResponse($this->doResponse->doErrorResponse($this->validatorOutputFormatter->formatErrors($errors), 400));
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
            return new JsonResponse($this->doResponse->doErrorResponse('DDT non trovato', 404));
        }

        $data = json_decode($request->getContent(), true) ?? $request->request->all();

        try {
            $this->handleData($ddt, $data);
        } catch (\Exception $e) {
            return new JsonResponse($this->doResponse->doErrorResponse($e->getMessage(), 400));
        }

        $errors = $validator->validate($ddt);
        if (count($errors) > 0) {
            return new JsonResponse($this->doResponse->doErrorResponse($this->validatorOutputFormatter->formatErrors($errors), 400));
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
            return new JsonResponse($this->doResponse->doErrorResponse('DDT non trovato', 404));
        }

        $this->doctrine->remove($ddt);
        $this->doctrine->flush();

        return new JsonResponse($this->doResponse->doResponse(['message' => 'DDT eliminato con successo']));
    }

    private function handleData(Ddt $ddt, array $data): void
    {
        if (isset($data['ddt_number'])) {
            $ddt->setDdtNumber($data['ddt_number']);
        }
        if (isset($data['ddt_date'])) {
            $ddt->setDdtDate(new \DateTime($data['ddt_date']));
        }
        if (isset($data['ddt_start_date'])) {
            $ddt->setDdtStartDate($data['ddt_start_date'] ? new \DateTime($data['ddt_start_date']) : null);
        }
        if (isset($data['subcontractor_id'])) {
            $subcontractor = $this->doctrine->getRepository(Contact::class)->find($data['subcontractor_id']);
            if (!$subcontractor) {
                throw new \Exception('Terzista non trovato');
            }
            $ddt->setSubcontractor($subcontractor);
        }
        if (isset($data['ddt_purpose_id'])) {
            $purpose = $this->doctrine->getRepository(DdtPurpose::class)->find($data['ddt_purpose_id']);
            if (!$purpose) {
                throw new \Exception('Causale DDT non trovata');
            }
            $ddt->setDdtPurpose($purpose);
        }
    }
}
