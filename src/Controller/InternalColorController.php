<?php

namespace App\Controller;

use App\Entity\InternalColor;
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

final class InternalColorController extends AbstractController
{
    public function __construct(
        private CreateMethodsByInput    $createMethodsByInput,
        private EntityManagerInterface  $doctrine,
        private DoResponseService       $doResponse,
        private GroupSerializerService  $groupSerializer,
        private ValidatorOutputFormatter $validatorOutputFormatter
    ) {
    }

    #[Route('/internal-color/{id}', 
        name: 'get_internal_color', 
        methods: ['GET'], 
        defaults: ['id' => null], 
        requirements: ['id' => '\d*'])]
    public function getInternalColor(?int $id): JsonResponse
    {
        $repository = $this->doctrine->getRepository(InternalColor::class);

        if ($id !== null) {
            $internalColor = $repository->find($id);

            if (!$internalColor) {
                return $this->doResponse->doErrorJsonResponse('InternalColor not found', 404);
            }
        } else {
            $internalColor = $repository->findBy([], ['name' => 'ASC']);
        }

        $results = $this->groupSerializer->serializeGroup($internalColor, $id !== null ? 'internal_color_detail' : 'internal_color_list');

        return new JsonResponse($this->doResponse->doResponse($results));
    }

    #[Route('/internal-color', name: 'post_internal_color', methods: ['POST'])]
    public function postInternalColor(Request $request, ValidatorInterface $validator): JsonResponse
    {
        $data = $request->request->all();
        $internalColor = new InternalColor();

        try {
            $internalColor = $this->createMethodsByInput->createMethods($internalColor, $data);

            $errors = $validator->validate($internalColor);
            if (count($errors) > 0) {
                $errors = $this->validatorOutputFormatter->formatOutput($errors);
                return $this->doResponse->doErrorJsonResponse($errors);
            }

            $this->doctrine->persist($internalColor);
            $this->doctrine->flush();

            $result = $this->groupSerializer->serializeGroup($internalColor, 'internal_color_detail');
            return new JsonResponse($this->doResponse->doResponse($result));

        } catch (\Exception $e) {
            return $this->doResponse->doErrorJsonResponse($e->getMessage());
        }
    }

    #[Route('/internal-color/{id}', name: 'put_internal_color', methods: ['PUT'])]
    public function modifyInternalColor(Request $request, ValidatorInterface $validator, int $id): JsonResponse
    {
        $data = $request->toArray();
        $internalColor = $this->doctrine->getRepository(InternalColor::class)->find($id);

        if (!$internalColor) {
            return $this->doResponse->doErrorJsonResponse('InternalColor not found', 404);
        }

        try {
            $internalColor = $this->createMethodsByInput->createMethods($internalColor, $data);

            $errors = $validator->validate($internalColor);
            if (count($errors) > 0) {
                $errors = $this->validatorOutputFormatter->formatOutput($errors);
                return $this->doResponse->doErrorJsonResponse($errors);
            }

            $this->doctrine->persist($internalColor);
            $this->doctrine->flush();

            $result = $this->groupSerializer->serializeGroup($internalColor, 'internal_color_detail');
            return new JsonResponse($this->doResponse->doResponse($result));
        } catch (\Exception $e) {
            return $this->doResponse->doErrorJsonResponse($e->getMessage());
        }
    }

    #[Route('/internal-color/{id}', name: 'delete_internal_color', methods: ['DELETE'])]
    public function deleteInternalColor(int $id): JsonResponse
    {
        $internalColor = $this->doctrine->getRepository(InternalColor::class)->find($id);
        if (!$internalColor) {
            return $this->doResponse->doErrorJsonResponse('InternalColor not found', 404);
        }

        $this->doctrine->remove($internalColor);
        $this->doctrine->flush();

        return new JsonResponse($this->doResponse->doResponse('delete_successfully'));
    }
}

