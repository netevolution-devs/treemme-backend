<?php

namespace App\Controller;

use App\Entity\LeatherProvenance;
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

final class LeatherProvenanceController extends AbstractController
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

    #[Route('/leather-provenance/{id}',
        name: 'get_leather_provenance',
        defaults: ['id' => null],
        requirements: ['id' => '\d*'],
        methods: ['GET', 'HEAD'])]
    public function getLeatherProvenance(?int $id): JsonResponse
    {
        $leatherProvenanceRepository = $this->doctrine->getRepository(LeatherProvenance::class);

        if ($id) {
            $leatherProvenance = [$leatherProvenanceRepository->find($id)];
            if (!$leatherProvenance[0]) {
                return new JsonResponse($this->doResponse->doErrorResponse('LeatherProvenance not found', 404));
            }
        } else {
            $leatherProvenance = $leatherProvenanceRepository->findBy([], ['id' => 'DESC']);
        }
        $results = $this->groupSerializer->serializeGroup($leatherProvenance, $id ? 'leather_provenance_detail' : 'leather_provenance_list');

        if ($id) {
            return new JsonResponse($this->doResponse->doResponse($results[0]));
        }
        return new JsonResponse($this->doResponse->doResponse($results));
    }

    #[Route('/leather-provenance',
        name: 'post_leather_provenance',
        methods: ['POST'])]
    public function postLeatherProvenance(
        Request            $request,
        ValidatorInterface $validator,
    ): JsonResponse
    {
        $data = $request->request->all();
        $leatherProvenance = new LeatherProvenance();

        try {
            $leatherProvenance = $this->createMethodsByInput->createMethods($leatherProvenance, $data);

            $errors = $validator->validate($leatherProvenance);
            if (count($errors) > 0) {
                $errors = $this->validatorOutputFormatter->formatOutput($errors);
                return new JsonResponse($this->doResponse->doErrorResponse($errors));
            }

            $em = $this->doctrine;
            $em->persist($leatherProvenance);
            $em->flush();

            $result = $this->groupSerializer->serializeGroup($leatherProvenance, 'leather_provenance_detail');
            return new JsonResponse($this->doResponse->doResponse($result));

        } catch (\Exception $e) {
            return new JsonResponse($this->doResponse->doErrorResponse($e->getMessage()));
        }
    }

    #[Route('/leather-provenance/{id}',
        name: 'put_leather_provenance',
        methods: ['PUT'])]
    public function modifyLeatherProvenance(
        Request            $request,
        ValidatorInterface $validator,
        int                $id,
    ): JsonResponse
    {
        $data = $request->toArray();
        $leatherProvenance = $this->doctrine->getRepository(LeatherProvenance::class)->find($id);

        if (!$leatherProvenance) {
            return new JsonResponse($this->doResponse->doErrorResponse('LeatherProvenance not found', 404));
        }

        try {
            $leatherProvenance = $this->createMethodsByInput->createMethods($leatherProvenance, $data);

            $errors = $validator->validate($leatherProvenance);
            if (count($errors) > 0) {
                $errors = $this->validatorOutputFormatter->formatOutput($errors);
                return new JsonResponse($this->doResponse->doErrorResponse($errors));
            }

            $this->doctrine->persist($leatherProvenance);
            $this->doctrine->flush();

            $result = $this->groupSerializer->serializeGroup($leatherProvenance, 'leather_provenance_detail');
            return new JsonResponse($this->doResponse->doResponse($result));
        } catch (\Exception $e) {
            return new JsonResponse($this->doResponse->doErrorResponse($e->getMessage()));
        }
    }

    #[Route('/leather-provenance/{id}',
        name: 'delete_leather_provenance',
        methods: ['DELETE'])]
    public function deleteLeatherProvenance(int $id): JsonResponse
    {
        $leatherProvenance = $this->doctrine->getRepository(LeatherProvenance::class)->find($id);
        if (!$leatherProvenance) {
            return new JsonResponse($this->doResponse->doErrorResponse('LeatherProvenance not found', 404));
        }

        $this->doctrine->remove($leatherProvenance);
        $this->doctrine->flush();

        return new JsonResponse($this->doResponse->doResponse('delete_successfully'));
    }
}
