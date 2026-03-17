<?php

namespace App\Controller;

use App\Entity\Selection;
use App\Service\CreateMethodsByInput;
use App\Service\DoResponseService;
use App\Service\GroupSerializerService;
use App\Service\ValidatorOutputFormatter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class SelectionController extends AbstractController
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

    #[Route('/selection/{id}',
        name: 'get_selection',
        defaults: ['id' => null],
        requirements: ['id' => '\\d*'],
        methods: ['GET', 'HEAD'])]
    public function getSelection(?int $id): JsonResponse
    {
        $repo = $this->doctrine->getRepository(Selection::class);

        if ($id) {
            $items = [$repo->find($id)];
            if (!$items[0]) {
                return new JsonResponse($this->doResponse->doErrorResponse('Selection not found', 404));
            }
        } else {
            $items = $repo->findBy([], ['id' => 'DESC']);
        }

        $results = $this->groupSerializer->serializeGroup($items, $id ? 'selection_detail' : 'selection_list');

        if ($id) {
            return new JsonResponse($this->doResponse->doResponse($results[0]));
        }
        return new JsonResponse($this->doResponse->doResponse($results));
    }

    #[Route('/selection', name: 'post_selection', methods: ['POST'])]
    public function postSelection(
        Request            $request,
        ValidatorInterface $validator,
    ): JsonResponse
    {
        $data = $request->request->all();
        $selection = new Selection();

        try {
            $selection = $this->createMethodsByInput->createMethods($selection, $data);

            $errors = $validator->validate($selection);
            if (count($errors) > 0) {
                $errors = $this->validatorOutputFormatter->formatOutput($errors);
                return new JsonResponse($this->doResponse->doErrorResponse($errors));
            }

            $this->doctrine->persist($selection);
            $this->doctrine->flush();

            $result = $this->groupSerializer->serializeGroup($selection, 'selection_detail');
            return new JsonResponse($this->doResponse->doResponse($result));
        } catch (\Exception $e) {
            return new JsonResponse($this->doResponse->doErrorResponse($e->getMessage()));
        }
    }

    #[Route('/selection/{id}', name: 'put_selection', methods: ['PUT'])]
    public function putSelection(
        Request            $request,
        ValidatorInterface $validator,
        int                $id,
    ): JsonResponse
    {
        $data = $request->toArray();
        $selection = $this->doctrine->getRepository(Selection::class)->find($id);
        if (!$selection) {
            return new JsonResponse($this->doResponse->doErrorResponse('Selection not found', 404));
        }

        try {
            $selection = $this->createMethodsByInput->createMethods($selection, $data);

            $errors = $validator->validate($selection);
            if (count($errors) > 0) {
                $errors = $this->validatorOutputFormatter->formatOutput($errors);
                return new JsonResponse($this->doResponse->doErrorResponse($errors));
            }

            $this->doctrine->persist($selection);
            $this->doctrine->flush();

            $result = $this->groupSerializer->serializeGroup($selection, 'selection_detail');
            return new JsonResponse($this->doResponse->doResponse($result));
        } catch (\Exception $e) {
            return new JsonResponse($this->doResponse->doErrorResponse($e->getMessage()));
        }
    }

    #[Route('/selection/{id}', name: 'delete_selection', methods: ['DELETE'])]
    public function deleteSelection(int $id): JsonResponse
    {
        $selection = $this->doctrine->getRepository(Selection::class)->find($id);
        if (!$selection) {
            return new JsonResponse($this->doResponse->doErrorResponse('Selection not found', 404));
        }

        $this->doctrine->remove($selection);
        $this->doctrine->flush();

        return new JsonResponse($this->doResponse->doResponse('delete_successfully'));
    }
}
