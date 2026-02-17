<?php

namespace App\Controller;

use App\Entity\MaterialBill;
use App\Entity\Product;
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

final class MaterialBillController extends AbstractController
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

    #[Route('/material-bill/{id}',
        name: 'get_material_bill',
        defaults: ['id' => null],
        requirements: ['id' => '\d*'],
        methods: ['GET', 'HEAD'])]
    public function getMaterialBill(?int $id): JsonResponse
    {
        $repository = $this->doctrine->getRepository(MaterialBill::class);

        if ($id) {
            $bill = [$repository->find($id)];
            if (!$bill[0]) {
                return new JsonResponse($this->doResponse->doErrorResponse('MaterialBill not found', 404));
            }
        } else {
            $bill = $repository->findBy([], ['id' => 'DESC']);
        }
        $results = $this->groupSerializer->serializeGroup($bill, $id ? 'material_bill_detail' : 'material_bill_list');

        if ($id) {
            return new JsonResponse($this->doResponse->doResponse($results[0]));
        }
        return new JsonResponse($this->doResponse->doResponse($results));
    }

    #[Route('/material-bill',
        name: 'post_material_bill',
        methods: ['POST'])]
    public function postMaterialBill(
        Request            $request,
        ValidatorInterface $validator,
    ): JsonResponse
    {
        $data = $request->request->all();
        $bill = new MaterialBill();

        try {
            $bill = $this->handleRelations($bill, $data);
            $bill = $this->createMethodsByInput->createMethods($bill, $data);

            $now = new \DateTimeImmutable();
            $bill->setCreatedAt($now);
            $bill->setUpdatedAt($now);

            $errors = $validator->validate($bill);
            if (count($errors) > 0) {
                $errors = $this->validatorOutputFormatter->formatOutput($errors);
                return new JsonResponse($this->doResponse->doErrorResponse($errors));
            }

            $this->doctrine->persist($bill);
            $this->doctrine->flush();

            $result = $this->groupSerializer->serializeGroup($bill, 'material_bill_detail');
            return new JsonResponse($this->doResponse->doResponse($result));

        } catch (\Exception $e) {
            return new JsonResponse($this->doResponse->doErrorResponse($e->getMessage()));
        }
    }

    #[Route('/material-bill/{id}',
        name: 'put_material_bill',
        methods: ['PUT'])]
    public function modifyMaterialBill(
        Request            $request,
        ValidatorInterface $validator,
        int                $id,
    ): JsonResponse
    {
        $data = $request->toArray();
        $bill = $this->doctrine->getRepository(MaterialBill::class)->find($id);

        if (!$bill) {
            return new JsonResponse($this->doResponse->doErrorResponse('MaterialBill not found', 404));
        }

        try {
            $bill = $this->handleRelations($bill, $data);
            $bill = $this->createMethodsByInput->createMethods($bill, $data);
            $bill->setUpdatedAt(new \DateTimeImmutable());

            $errors = $validator->validate($bill);
            if (count($errors) > 0) {
                $errors = $this->validatorOutputFormatter->formatOutput($errors);
                return new JsonResponse($this->doResponse->doErrorResponse($errors));
            }

            $this->doctrine->persist($bill);
            $this->doctrine->flush();

            $result = $this->groupSerializer->serializeGroup($bill, 'material_bill_detail');
            return new JsonResponse($this->doResponse->doResponse($result));
        } catch (\Exception $e) {
            return new JsonResponse($this->doResponse->doErrorResponse($e->getMessage()));
        }
    }

    #[Route('/material-bill/{id}',
        name: 'delete_material_bill',
        methods: ['DELETE'])]
    public function deleteMaterialBill(int $id): JsonResponse
    {
        $bill = $this->doctrine->getRepository(MaterialBill::class)->find($id);
        if (!$bill) {
            return new JsonResponse($this->doResponse->doErrorResponse('MaterialBill not found', 404));
        }

        $this->doctrine->remove($bill);
        $this->doctrine->flush();

        return new JsonResponse($this->doResponse->doResponse('delete_successfully'));
    }

    private function handleRelations(MaterialBill $bill, array &$data): MaterialBill
    {
        if (isset($data['product_id'])) {
            $product = $this->doctrine->getRepository(Product::class)->find($data['product_id']);
            if ($product) {
                $bill->setProduct($product);
            }
            unset($data['product_id']);
        }

        return $bill;
    }
}
