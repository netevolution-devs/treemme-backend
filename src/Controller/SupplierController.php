<?php

namespace App\Controller;

use App\Entity\Contact;
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

final class SupplierController extends AbstractController
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

    #[Route('/supplier/{id}',
        name: 'get_supplier',
        defaults: ['id' => null],
        requirements: ['id' => '\d*'],
        methods: ['GET', 'HEAD'])]
    public function getSupplier(?int $id): JsonResponse
    {
        $repository = $this->doctrine->getRepository(Supplier::class);

        if ($id) {
            $supplier = [$repository->find($id)];
            if (!$supplier[0]) {
                return new JsonResponse($this->doResponse->doErrorResponse('Supplier not found', 404));
            }
        } else {
            $supplier = $repository->findBy([], ['id' => 'DESC']);
        }
        $results = $this->groupSerializer->serializeGroup($supplier, $id ? 'supplier_detail' : 'supplier_list');

        if ($id) {
            return new JsonResponse($this->doResponse->doResponse($results[0]));
        }
        return new JsonResponse($this->doResponse->doResponse($results));
    }

    #[Route('/supplier',
        name: 'post_supplier',
        methods: ['POST'])]
    public function postSupplier(
        Request            $request,
        ValidatorInterface $validator,
    ): JsonResponse
    {
        $data = $request->request->all();
        $supplier = new Supplier();

        try {
            $supplier = $this->handleRelations($supplier, $data);
            $supplier = $this->createMethodsByInput->createMethods($supplier, $data);

            $now = new \DateTimeImmutable();
            $supplier->setCreatedAt($now);
            $supplier->setUpdatedAt($now);

            $errors = $validator->validate($supplier);
            if (count($errors) > 0) {
                $errors = $this->validatorOutputFormatter->formatOutput($errors);
                return new JsonResponse($this->doResponse->doErrorResponse($errors));
            }

            $this->doctrine->persist($supplier);
            $this->doctrine->flush();

            $result = $this->groupSerializer->serializeGroup($supplier, 'supplier_detail');
            return new JsonResponse($this->doResponse->doResponse($result));

        } catch (\Exception $e) {
            return new JsonResponse($this->doResponse->doErrorResponse($e->getMessage()));
        }
    }

    #[Route('/supplier/{id}',
        name: 'put_supplier',
        methods: ['PUT'])]
    public function modifySupplier(
        Request            $request,
        ValidatorInterface $validator,
        int                $id,
    ): JsonResponse
    {
        $data = $request->toArray();
        $supplier = $this->doctrine->getRepository(Supplier::class)->find($id);

        if (!$supplier) {
            return new JsonResponse($this->doResponse->doErrorResponse('Supplier not found', 404));
        }

        try {
            $supplier = $this->handleRelations($supplier, $data);
            $supplier = $this->createMethodsByInput->createMethods($supplier, $data);
            $supplier->setUpdatedAt(new \DateTimeImmutable());

            $errors = $validator->validate($supplier);
            if (count($errors) > 0) {
                $errors = $this->validatorOutputFormatter->formatOutput($errors);
                return new JsonResponse($this->doResponse->doErrorResponse($errors));
            }

            $this->doctrine->persist($supplier);
            $this->doctrine->flush();

            $result = $this->groupSerializer->serializeGroup($supplier, 'supplier_detail');
            return new JsonResponse($this->doResponse->doResponse($result));
        } catch (\Exception $e) {
            return new JsonResponse($this->doResponse->doErrorResponse($e->getMessage()));
        }
    }

    #[Route('/supplier/{id}',
        name: 'delete_supplier',
        methods: ['DELETE'])]
    public function deleteSupplier(int $id): JsonResponse
    {
        $supplier = $this->doctrine->getRepository(Supplier::class)->find($id);
        if (!$supplier) {
            return new JsonResponse($this->doResponse->doErrorResponse('Supplier not found', 404));
        }

        $this->doctrine->remove($supplier);
        $this->doctrine->flush();

        return new JsonResponse($this->doResponse->doResponse('delete_successfully'));
    }

    private function handleRelations(Supplier $supplier, array &$data): Supplier
    {
        if (isset($data['contact_id'])) {
            $contact = $this->doctrine->getRepository(Contact::class)->find($data['contact_id']);
            if ($contact) {
                $supplier->setContact($contact);
            }
            unset($data['contact_id']);
        }

        return $supplier;
    }
}
