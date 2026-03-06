<?php

namespace App\Controller;

use App\Entity\Recipe;
use App\Entity\RecipeType;
use App\Entity\Product;
use App\Entity\Processing;
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

class RecipeController extends AbstractController
{
    public function __construct(
        private CreateMethodsByInput    $createMethodsByInput,
        private EntityManagerInterface  $doctrine,
        private DoResponseService       $doResponse,
        private GroupSerializerService  $groupSerializer,
        private ValidatorOutputFormatter $validatorOutputFormatter
    ) {
    }

    #[Route('/recipe/{id}', name: 'get_recipe', methods: ['GET'], defaults: ['id' => null])]
    public function getRecipe(?int $id): JsonResponse
    {
        $repository = $this->doctrine->getRepository(Recipe::class);
        if ($id) {
            $recipe = $repository->find($id);
            if (!$recipe) {
                return new JsonResponse($this->doResponse->doErrorResponse('Recipe not found', 404));
            }
        } else {
            $recipe = $repository->findBy([], ['id' => 'DESC']);
        }
        $results = $this->groupSerializer->serializeGroup($recipe, $id ? 'recipe_detail' : 'recipe_list');

        if ($id) {
            return new JsonResponse($this->doResponse->doResponse($results[0]));
        }
        return new JsonResponse($this->doResponse->doResponse($results));
    }

    #[Route('/recipe', name: 'post_recipe', methods: ['POST'])]
    public function postRecipe(Request $request, ValidatorInterface $validator): JsonResponse
    {
        $data = $request->request->all();
        $recipe = new Recipe();

        try {
            $recipe = $this->handleRelations($recipe, $data);
            $recipe = $this->createMethodsByInput->createMethods($recipe, $data);

            $errors = $validator->validate($recipe);
            if (count($errors) > 0) {
                $errors = $this->validatorOutputFormatter->formatOutput($errors);
                return new JsonResponse($this->doResponse->doErrorResponse($errors));
            }

            $this->doctrine->persist($recipe);
            $this->doctrine->flush();

            $result = $this->groupSerializer->serializeGroup($recipe, 'recipe_detail');
            return new JsonResponse($this->doResponse->doResponse($result));

        } catch (\Exception $e) {
            return new JsonResponse($this->doResponse->doErrorResponse($e->getMessage()));
        }
    }

    #[Route('/recipe/{id}', name: 'put_recipe', methods: ['PUT'])]
    public function modifyRecipe(Request $request, ValidatorInterface $validator, int $id): JsonResponse
    {
        $data = $request->toArray();
        $recipe = $this->doctrine->getRepository(Recipe::class)->find($id);

        if (!$recipe) {
            return new JsonResponse($this->doResponse->doErrorResponse('Recipe not found', 404));
        }

        try {
            $recipe = $this->handleRelations($recipe, $data);
            $recipe = $this->createMethodsByInput->createMethods($recipe, $data);

            $errors = $validator->validate($recipe);
            if (count($errors) > 0) {
                $errors = $this->validatorOutputFormatter->formatOutput($errors);
                return new JsonResponse($this->doResponse->doErrorResponse($errors));
            }

            $this->doctrine->persist($recipe);
            $this->doctrine->flush();

            $result = $this->groupSerializer->serializeGroup($recipe, 'recipe_detail');
            return new JsonResponse($this->doResponse->doResponse($result));
        } catch (\Exception $e) {
            return new JsonResponse($this->doResponse->doErrorResponse($e->getMessage()));
        }
    }

    #[Route('/recipe/{id}', name: 'delete_recipe', methods: ['DELETE'])]
    public function deleteRecipe(int $id): JsonResponse
    {
        $recipe = $this->doctrine->getRepository(Recipe::class)->find($id);
        if (!$recipe) {
            return new JsonResponse($this->doResponse->doErrorResponse('Recipe not found', 404));
        }

        $this->doctrine->remove($recipe);
        $this->doctrine->flush();

        return new JsonResponse($this->doResponse->doResponse('delete_successfully'));
    }

    private function handleRelations(Recipe $recipe, array &$data): Recipe
    {
        if (isset($data['recipe_type_id'])) {
            $type = $this->doctrine->getRepository(RecipeType::class)->find($data['recipe_type_id']);
            if ($type) {
                $recipe->setRecipeType($type);
            }
            unset($data['recipe_type_id']);
        }

        if (isset($data['product_id'])) {
            $product = $this->doctrine->getRepository(Product::class)->find($data['product_id']);
            if ($product) {
                $recipe->setProduct($product);
            }
            unset($data['product_id']);
        }

        if (isset($data['processing_id'])) {
            $processing = $this->doctrine->getRepository(Processing::class)->find($data['processing_id']);
            if ($processing) {
                $recipe->setProcessing($processing);
            }
            unset($data['processing_id']);
        }

        return $recipe;
    }
}
