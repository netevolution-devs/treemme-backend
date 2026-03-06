<?php

namespace App\Controller;

use App\Entity\MaterialBillStep;
use App\Entity\MaterialBill;
use App\Entity\Processing;
use App\Entity\Recipe;
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

class MaterialBillStepController extends AbstractController
{
    public function __construct(
        private CreateMethodsByInput    $createMethodsByInput,
        private EntityManagerInterface  $doctrine,
        private DoResponseService       $doResponse,
        private GroupSerializerService  $groupSerializer,
        private ValidatorOutputFormatter $validatorOutputFormatter
    ) {
    }

    #[Route('/material-bill-step/{id}', name: 'get_material_bill_step', methods: ['GET'], defaults: ['id' => null])]
    public function getMaterialBillStep(?int $id): JsonResponse
    {
        $repository = $this->doctrine->getRepository(MaterialBillStep::class);
        if ($id) {
            $step = $repository->find($id);
            if (!$step) {
                return new JsonResponse($this->doResponse->doErrorResponse('MaterialBillStep not found', 404));
            }
        } else {
            $step = $repository->findBy([], ['id' => 'DESC']);
        }
        $results = $this->groupSerializer->serializeGroup($step, $id ? 'material_bill_step_detail' : 'material_bill_step_list');

        if ($id) {
            return new JsonResponse($this->doResponse->doResponse($results[0]));
        }
        return new JsonResponse($this->doResponse->doResponse($results));
    }

    #[Route('/material-bill-step', name: 'post_material_bill_step', methods: ['POST'])]
    public function postMaterialBillStep(Request $request, ValidatorInterface $validator): JsonResponse
    {
        $data = $request->request->all();
        $step = new MaterialBillStep();

        try {
            $step = $this->handleRelations($step, $data);
            $step = $this->createMethodsByInput->createMethods($step, $data);

            $errors = $validator->validate($step);
            if (count($errors) > 0) {
                $errors = $this->validatorOutputFormatter->formatOutput($errors);
                return new JsonResponse($this->doResponse->doErrorResponse($errors));
            }

            $this->doctrine->persist($step);
            $this->doctrine->flush();

            $result = $this->groupSerializer->serializeGroup($step, 'material_bill_step_detail');
            return new JsonResponse($this->doResponse->doResponse($result));

        } catch (\Exception $e) {
            return new JsonResponse($this->doResponse->doErrorResponse($e->getMessage()));
        }
    }

    #[Route('/material-bill-step/{id}', name: 'put_material_bill_step', methods: ['PUT'])]
    public function modifyMaterialBillStep(Request $request, ValidatorInterface $validator, int $id): JsonResponse
    {
        $data = $request->toArray();
        $step = $this->doctrine->getRepository(MaterialBillStep::class)->find($id);

        if (!$step) {
            return new JsonResponse($this->doResponse->doErrorResponse('MaterialBillStep not found', 404));
        }

        try {
            $step = $this->handleRelations($step, $data);
            $step = $this->createMethodsByInput->createMethods($step, $data);

            $errors = $validator->validate($step);
            if (count($errors) > 0) {
                $errors = $this->validatorOutputFormatter->formatOutput($errors);
                return new JsonResponse($this->doResponse->doErrorResponse($errors));
            }

            $this->doctrine->persist($step);
            $this->doctrine->flush();

            $result = $this->groupSerializer->serializeGroup($step, 'material_bill_step_detail');
            return new JsonResponse($this->doResponse->doResponse($result));
        } catch (\Exception $e) {
            return new JsonResponse($this->doResponse->doErrorResponse($e->getMessage()));
        }
    }

    #[Route('/material-bill-step/{id}', name: 'delete_material_bill_step', methods: ['DELETE'])]
    public function deleteMaterialBillStep(int $id): JsonResponse
    {
        $step = $this->doctrine->getRepository(MaterialBillStep::class)->find($id);
        if (!$step) {
            return new JsonResponse($this->doResponse->doErrorResponse('MaterialBillStep not found', 404));
        }

        $this->doctrine->remove($step);
        $this->doctrine->flush();

        return new JsonResponse($this->doResponse->doResponse('delete_successfully'));
    }

    private function handleRelations(MaterialBillStep $step, array &$data): MaterialBillStep
    {
        if (isset($data['material_bill_id'])) {
            $bill = $this->doctrine->getRepository(MaterialBill::class)->find($data['material_bill_id']);
            if ($bill) {
                $step->setMaterialBill($bill);
            }
            unset($data['material_bill_id']);
        }

        if (isset($data['processing_id'])) {
            $processing = $this->doctrine->getRepository(Processing::class)->find($data['processing_id']);
            if ($processing) {
                $step->setProcessing($processing);
            }
            unset($data['processing_id']);
        }

        if (isset($data['recipe_id'])) {
            $recipe = $this->doctrine->getRepository(Recipe::class)->find($data['recipe_id']);
            if ($recipe) {
                $step->setRecipe($recipe);
            }
            unset($data['recipe_id']);
        }

        return $step;
    }
}
