<?php

namespace App\Controller;

use App\Entity\RecipeType;
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

class RecipeTypeController extends AbstractController
{
    public function __construct(
        private CreateMethodsByInput    $createMethodsByInput,
        private EntityManagerInterface  $doctrine,
        private DoResponseService       $doResponse,
        private GroupSerializerService  $groupSerializer,
        private ValidatorOutputFormatter $validatorOutputFormatter
    ) {
    }

    #[Route('/recipe-type/{id}', name: 'get_recipe_type', methods: ['GET'], defaults: ['id' => null])]
    public function getRecipeType(?int $id): JsonResponse
    {
        $repository = $this->doctrine->getRepository(RecipeType::class);
        if ($id) {
            $recipeType = $repository->find($id);
            if (!$recipeType) {
                return new JsonResponse($this->doResponse->doErrorResponse('RecipeType not found', 404));
            }
        } else {
            $recipeType = $repository->findBy([], ['id' => 'DESC']);
        }
        $results = $this->groupSerializer->serializeGroup($recipeType, $id ? 'recipe_type_detail' : 'recipe_type_list');

        if ($id) {
            return new JsonResponse($this->doResponse->doResponse($results[0]));
        }
        return new JsonResponse($this->doResponse->doResponse($results));
    }

    #[Route('/recipe-type', name: 'post_recipe_type', methods: ['POST'])]
    public function postRecipeType(Request $request, ValidatorInterface $validator): JsonResponse
    {
        $data = $request->request->all();
        $recipeType = new RecipeType();

        try {
            $recipeType = $this->createMethodsByInput->createMethods($recipeType, $data);

            $errors = $validator->validate($recipeType);
            if (count($errors) > 0) {
                $errors = $this->validatorOutputFormatter->formatOutput($errors);
                return new JsonResponse($this->doResponse->doErrorResponse($errors));
            }

            $this->doctrine->persist($recipeType);
            $this->doctrine->flush();

            $result = $this->groupSerializer->serializeGroup($recipeType, 'recipe_type_detail');
            return new JsonResponse($this->doResponse->doResponse($result));

        } catch (\Exception $e) {
            return new JsonResponse($this->doResponse->doErrorResponse($e->getMessage()));
        }
    }

    #[Route('/recipe-type/{id}', name: 'put_recipe_type', methods: ['PUT'])]
    public function modifyRecipeType(Request $request, ValidatorInterface $validator, int $id): JsonResponse
    {
        $data = $request->toArray();
        $recipeType = $this->doctrine->getRepository(RecipeType::class)->find($id);

        if (!$recipeType) {
            return new JsonResponse($this->doResponse->doErrorResponse('RecipeType not found', 404));
        }

        try {
            $recipeType = $this->createMethodsByInput->createMethods($recipeType, $data);

            $errors = $validator->validate($recipeType);
            if (count($errors) > 0) {
                $errors = $this->validatorOutputFormatter->formatOutput($errors);
                return new JsonResponse($this->doResponse->doErrorResponse($errors));
            }

            $this->doctrine->persist($recipeType);
            $this->doctrine->flush();

            $result = $this->groupSerializer->serializeGroup($recipeType, 'recipe_type_detail');
            return new JsonResponse($this->doResponse->doResponse($result));
        } catch (\Exception $e) {
            return new JsonResponse($this->doResponse->doErrorResponse($e->getMessage()));
        }
    }

    #[Route('/recipe-type/{id}', name: 'delete_recipe_type', methods: ['DELETE'])]
    public function deleteRecipeType(int $id): JsonResponse
    {
        $recipeType = $this->doctrine->getRepository(RecipeType::class)->find($id);
        if (!$recipeType) {
            return new JsonResponse($this->doResponse->doErrorResponse('RecipeType not found', 404));
        }

        $this->doctrine->remove($recipeType);
        $this->doctrine->flush();

        return new JsonResponse($this->doResponse->doResponse('delete_successfully'));
    }
}
