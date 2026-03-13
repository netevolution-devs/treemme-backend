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
            $ddt = $this->createMethodsByInput->createMethods($ddt, $data);
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
            $this->createMethodsByInput->createMethods($ddt, $data);
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
        if (isset($data['subcontractor_id'])) {
            $subcontractor = $this->doctrine->getRepository(Contact::class)->find($data['subcontractor_id']);
            if (!$subcontractor) {
                throw new \Exception('Terzista non trovato');
            }
            $ddt->setSubcontractor($subcontractor);
        }

        if (isset($data['reason_id'])) {
            $reason = $this->doctrine->getRepository(DdtReason::class)->find($data['reason_id']);
            if (!$reason) {
                throw new \Exception('Motivo non trovato');
            }
            $ddt->setReason($reason);
        }
    }
}
