<?php

namespace App\Controller;

use App\Entity\Color;
use App\Entity\MeasurementUnit;
use App\Entity\Product;
use App\Entity\ProductType;
use App\Entity\Supplier;
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

final class ProductController extends AbstractController
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

    #[Route('/product/{id}',
        name: 'get_product',
        defaults: ['id' => null],
        requirements: ['id' => '\d*'],
        methods: ['GET', 'HEAD'])]
    public function getProduct(?int $id): JsonResponse
    {
        $repository = $this->doctrine->getRepository(Product::class);

        if ($id) {
            $product = [$repository->find($id)];
            if (!$product[0]) {
                return new JsonResponse($this->doResponse->doErrorResponse('Product not found', 404));
            }
        } else {
            $product = $repository->findBy([], ['id' => 'DESC']);
        }
        $results = $this->groupSerializer->serializeGroup($product, $id ? 'product_detail' : 'product_list');

        if ($id) {
            return new JsonResponse($this->doResponse->doResponse($results[0]));
        }
        return new JsonResponse($this->doResponse->doResponse($results));
    }

    #[Route('/product',
        name: 'post_product',
        methods: ['POST'])]
    public function postProduct(
        Request            $request,
        ValidatorInterface $validator,
    ): JsonResponse
    {
        $data = $request->request->all();
        $product = new Product();

        try {
            $product = $this->handleRelations($product, $data);
            $product = $this->createMethodsByInput->createMethods($product, $data);

            $now = new \DateTimeImmutable();
            $product->setCreatedAt($now);
            $product->setUpdatedAt($now);

            $errors = $validator->validate($product);
            if (count($errors) > 0) {
                $errors = $this->validatorOutputFormatter->formatOutput($errors);
                return new JsonResponse($this->doResponse->doErrorResponse($errors));
            }

            $this->doctrine->persist($product);
            $this->doctrine->flush();

            $result = $this->groupSerializer->serializeGroup($product, 'product_detail');
            return new JsonResponse($this->doResponse->doResponse($result));

        } catch (\Exception $e) {
            return new JsonResponse($this->doResponse->doErrorResponse($e->getMessage()));
        }
    }

    #[Route('/product/{id}',
        name: 'put_product',
        methods: ['PUT'])]
    public function modifyProduct(
        Request            $request,
        ValidatorInterface $validator,
        int                $id,
    ): JsonResponse
    {
        $data = $request->toArray();
        $product = $this->doctrine->getRepository(Product::class)->find($id);

        if (!$product) {
            return new JsonResponse($this->doResponse->doErrorResponse('Product not found', 404));
        }

        try {
            $product = $this->handleRelations($product, $data);
            $product = $this->createMethodsByInput->createMethods($product, $data);
            $product->setUpdatedAt(new \DateTimeImmutable());

            $errors = $validator->validate($product);
            if (count($errors) > 0) {
                $errors = $this->validatorOutputFormatter->formatOutput($errors);
                return new JsonResponse($this->doResponse->doErrorResponse($errors));
            }

            $this->doctrine->persist($product);
            $this->doctrine->flush();

            $result = $this->groupSerializer->serializeGroup($product, 'product_detail');
            return new JsonResponse($this->doResponse->doResponse($result));
        } catch (\Exception $e) {
            return new JsonResponse($this->doResponse->doErrorResponse($e->getMessage()));
        }
    }

    #[Route('/product/{id}',
        name: 'delete_product',
        methods: ['DELETE'])]
    public function deleteProduct(int $id): JsonResponse
    {
        $product = $this->doctrine->getRepository(Product::class)->find($id);
        if (!$product) {
            return new JsonResponse($this->doResponse->doErrorResponse('Product not found', 404));
        }

        $this->doctrine->remove($product);
        $this->doctrine->flush();

        return new JsonResponse($this->doResponse->doResponse('delete_successfully'));
    }

    private function handleRelations(Product $product, array &$data): Product
    {
        if (isset($data['product_type_id'])) {
            $type = $this->doctrine->getRepository(ProductType::class)->find($data['product_type_id']);
            if ($type) {
                $product->setProductType($type);
            }
            unset($data['product_type_id']);
        }

        if (isset($data['supplier_id'])) {
            $supplier = $this->doctrine->getRepository(Supplier::class)->find($data['supplier_id']);
            if ($supplier) {
                $product->setSupplier($supplier);
            }
            unset($data['supplier_id']);
        }

        if (isset($data['measurement_unit_id'])) {
            $unit = $this->doctrine->getRepository(MeasurementUnit::class)->find($data['measurement_unit_id']);
            if ($unit) {
                $product->setMeasurementUnit($unit);
            }
            unset($data['measurement_unit_id']);
        }

        if (isset($data['color_id'])) {
            $color = $this->doctrine->getRepository(Color::class)->find($data['color_id']);
            if ($color) {
                $product->setColor($color);
            }
            unset($data['color_id']);
        }

        return $product;
    }
}
