<?php

namespace App\Controller;

use App\Entity\ProductType;
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

final class ProductTypeController extends AbstractController
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

    #[Route('/product-type/{id}',
        name: 'get_product_type',
        defaults: ['id' => null],
        requirements: ['id' => '\d*'],
        methods: ['GET', 'HEAD'])]
    public function getProductType(?int $id): JsonResponse
    {
        $repository = $this->doctrine->getRepository(ProductType::class);

        if ($id) {
            $type = [$repository->find($id)];
            if (!$type[0]) {
                return new JsonResponse($this->doResponse->doErrorResponse('ProductType not found', 404));
            }
        } else {
            $type = $repository->findBy([], ['id' => 'DESC']);
        }
        $results = $this->groupSerializer->serializeGroup($type, $id ? 'product_type_detail' : 'product_type_list');

        if ($id) {
            return new JsonResponse($this->doResponse->doResponse($results[0]));
        }
        return new JsonResponse($this->doResponse->doResponse($results));
    }

    #[Route('/product-type',
        name: 'post_product_type',
        methods: ['POST'])]
    public function postProductType(
        Request            $request,
        ValidatorInterface $validator,
    ): JsonResponse
    {
        $data = $request->request->all();
        $type = new ProductType();

        try {
            $type = $this->createMethodsByInput->createMethods($type, $data);

            $now = new \DateTimeImmutable();
            $type->setCreatedAt($now);
            $type->setUpdatedAt($now);

            $errors = $validator->validate($type);
            if (count($errors) > 0) {
                $errors = $this->validatorOutputFormatter->formatOutput($errors);
                return new JsonResponse($this->doResponse->doErrorResponse($errors));
            }

            $this->doctrine->persist($type);
            $this->doctrine->flush();

            $result = $this->groupSerializer->serializeGroup($type, 'product_type_detail');
            return new JsonResponse($this->doResponse->doResponse($result));

        } catch (\Exception $e) {
            return new JsonResponse($this->doResponse->doErrorResponse($e->getMessage()));
        }
    }

    #[Route('/product-type/{id}',
        name: 'put_product_type',
        methods: ['PUT'])]
    public function modifyProductType(
        Request            $request,
        ValidatorInterface $validator,
        int                $id,
    ): JsonResponse
    {
        $data = $request->toArray();
        $type = $this->doctrine->getRepository(ProductType::class)->find($id);

        if (!$type) {
            return new JsonResponse($this->doResponse->doErrorResponse('ProductType not found', 404));
        }

        try {
            $type = $this->createMethodsByInput->createMethods($type, $data);
            $type->setUpdatedAt(new \DateTimeImmutable());

            $errors = $validator->validate($type);
            if (count($errors) > 0) {
                $errors = $this->validatorOutputFormatter->formatOutput($errors);
                return new JsonResponse($this->doResponse->doErrorResponse($errors));
            }

            $this->doctrine->persist($type);
            $this->doctrine->flush();

            $result = $this->groupSerializer->serializeGroup($type, 'product_type_detail');
            return new JsonResponse($this->doResponse->doResponse($result));
        } catch (\Exception $e) {
            return new JsonResponse($this->doResponse->doErrorResponse($e->getMessage()));
        }
    }

    #[Route('/product-type/{id}',
        name: 'delete_product_type',
        methods: ['DELETE'])]
    public function deleteProductType(int $id): JsonResponse
    {
        $type = $this->doctrine->getRepository(ProductType::class)->find($id);
        if (!$type) {
            return new JsonResponse($this->doResponse->doErrorResponse('ProductType not found', 404));
        }

        $this->doctrine->remove($type);
        $this->doctrine->flush();

        return new JsonResponse($this->doResponse->doResponse('delete_successfully'));
    }
}
