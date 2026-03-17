<?php

namespace App\Controller;

use App\Entity\Nation;
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

final class NationController extends AbstractController
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

    #[Route('/nation/{id}',
        name: 'get_nation',
        defaults: ['id' => null],
        requirements: ['id' => '\d*'],
        methods: ['GET', 'HEAD'])]
    public function getNation(?int $id): JsonResponse
    {
        $nationRepository = $this->doctrine->getRepository(Nation::class);

        if ($id) {
            $nation = [$nationRepository->find($id)];
            if (!$nation[0]) {
                return new JsonResponse($this->doResponse->doErrorResponse('Nation not found', 404));
            }
        } else {
            $nation = $nationRepository->findBy([], ['id' => 'DESC']);
        }
        $results = $this->groupSerializer->serializeGroup($nation, $id ? 'nation_detail' : 'nation_list');

        if ($id) {
            return new JsonResponse($this->doResponse->doResponse($results[0]));
        }
        return new JsonResponse($this->doResponse->doResponse($results));
    }

    #[Route('/nation',
        name: 'post_nation',
        methods: ['POST'])]
    public function postNation(
        Request            $request,
        ValidatorInterface $validator,
    ): JsonResponse
    {
        $data = $request->request->all();
        $nation = new Nation();

        try {
            $nation = $this->createMethodsByInput->createMethods($nation, $data);

            $errors = $validator->validate($nation);
            if (count($errors) > 0) {
                $errors = $this->validatorOutputFormatter->formatOutput($errors);
                return new JsonResponse($this->doResponse->doErrorResponse($errors));
            }

            $em = $this->doctrine;
            $em->persist($nation);
            $em->flush();

            $result = $this->groupSerializer->serializeGroup($nation, 'nation_detail');
            return new JsonResponse($this->doResponse->doResponse($result));

        } catch (\Exception $e) {
            return new JsonResponse($this->doResponse->doErrorResponse($e->getMessage()));
        }
    }

    #[Route('/nation/{id}',
        name: 'put_nation',
        methods: ['PUT'])]
    public function modifyNation(
        Request            $request,
        ValidatorInterface $validator,
        int                $id,
    ): JsonResponse
    {
        $data = $request->toArray();
        $nation = $this->doctrine->getRepository(Nation::class)->find($id);

        if (!$nation) {
            return new JsonResponse($this->doResponse->doErrorResponse('Nation not found', 404));
        }

        try {
            $nation = $this->createMethodsByInput->createMethods($nation, $data);

            $errors = $validator->validate($nation);
            if (count($errors) > 0) {
                $errors = $this->validatorOutputFormatter->formatOutput($errors);
                return new JsonResponse($this->doResponse->doErrorResponse($errors));
            }

            $this->doctrine->persist($nation);
            $this->doctrine->flush();

            $result = $this->groupSerializer->serializeGroup($nation, 'nation_detail');
            return new JsonResponse($this->doResponse->doResponse($result));
        } catch (\Exception $e) {
            return new JsonResponse($this->doResponse->doErrorResponse($e->getMessage()));
        }
    }

    #[Route('/nation/{id}',
        name: 'delete_nation',
        methods: ['DELETE'])]
    public function deleteNation(int $id): JsonResponse
    {
        $nation = $this->doctrine->getRepository(Nation::class)->find($id);
        if (!$nation) {
            return new JsonResponse($this->doResponse->doErrorResponse('Nation not found', 404));
        }

        $this->doctrine->remove($nation);
        $this->doctrine->flush();

        return new JsonResponse($this->doResponse->doResponse('delete_successfully'));
    }
}
