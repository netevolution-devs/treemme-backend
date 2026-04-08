<?php

namespace App\Controller;

use App\Entity\ProductCategory;
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

final class ProductCategoryController extends AbstractController
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

    #[Route('/product-category/{id}',
        name: 'get_product_category',
        defaults: ['id' => null],
        requirements: ['id' => '\d*'],
        methods: ['GET', 'HEAD'])]
    public function getProductCategory(?int $id): JsonResponse
    {
        $repository = $this->doctrine->getRepository(ProductCategory::class);

        if ($id) {
            $productCategory = [$repository->find($id)];
            if (!$productCategory[0]) {
                return new JsonResponse($this->doResponse->doErrorResponse('ProductCategory not found', 404));
            }
        } else {
            $productCategory = $repository->findBy([], ['name' => 'ASC']);
        }
        $results = $this->groupSerializer->serializeGroup($productCategory, $id ? 'product_category_detail' : 'product_category_list');

        if ($id) {
            return new JsonResponse($this->doResponse->doResponse($results[0]));
        }
        return new JsonResponse($this->doResponse->doResponse($results));
    }

    #[Route('/product-category',
        name: 'post_product_category',
        methods: ['POST'])]
    public function postProductCategory(
        Request            $request,
        ValidatorInterface $validator,
    ): JsonResponse
    {
        $data = $request->request->all();
        $productCategory = new ProductCategory();

        try {
            $productCategory = $this->createMethodsByInput->createMethods($productCategory, $data);

            $errors = $validator->validate($productCategory);
            if (count($errors) > 0) {
                $errors = $this->validatorOutputFormatter->formatOutput($errors);
                return new JsonResponse($this->doResponse->doErrorResponse($errors));
            }

            $this->doctrine->persist($productCategory);
            $this->doctrine->flush();

            $result = $this->groupSerializer->serializeGroup($productCategory, 'product_category_detail');
            return new JsonResponse($this->doResponse->doResponse($result));

        } catch (\Exception $e) {
            return new JsonResponse($this->doResponse->doErrorResponse($e->getMessage()));
        }
    }

    #[Route('/product/{id}',
        name: 'put_product',
        methods: ['PUT'])]
    public function modifyProductCategory(
        Request            $request,
        ValidatorInterface $validator,
        int                $id,
    ): JsonResponse
    {
        $data = $request->toArray();
        $productCategory = $this->doctrine->getRepository(ProductCategory::class)->find($id);

        if (!$productCategory) {
            return new JsonResponse($this->doResponse->doErrorResponse('ProductCategory not found', 404));
        }

        try {
            $productCategory = $this->createMethodsByInput->createMethods($productCategory, $data);

            $errors = $validator->validate($productCategory);
            if (count($errors) > 0) {
                $errors = $this->validatorOutputFormatter->formatOutput($errors);
                return new JsonResponse($this->doResponse->doErrorResponse($errors));
            }

            $this->doctrine->persist($productCategory);
            $this->doctrine->flush();

            $result = $this->groupSerializer->serializeGroup($productCategory, 'product_category_detail');
            return new JsonResponse($this->doResponse->doResponse($result));
        } catch (\Exception $e) {
            return new JsonResponse($this->doResponse->doErrorResponse($e->getMessage()));
        }
    }

    #[Route('/product/{id}',
        name: 'delete_product',
        methods: ['DELETE'])]
    public function deleteProductCategory(int $id): JsonResponse
    {
        $productCategory = $this->doctrine->getRepository(ProductCategory::class)->find($id);
        if (!$productCategory) {
            return new JsonResponse($this->doResponse->doErrorResponse('ProductCategory not found', 404));
        }

        $this->doctrine->remove($productCategory);
        $this->doctrine->flush();

        return new JsonResponse($this->doResponse->doResponse('delete_successfully'));
    }
}
