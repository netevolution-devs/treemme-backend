<?php

namespace App\Controller;

use App\Entity\ShipmentCondition;
use App\Entity\Town;
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

final class ShipmentConditionController extends AbstractController
{
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
    
    #[Route('/shipment-condition/{id}',
        name: 'get_shipment-condition',
        defaults: ['id' => null],
        requirements: ['id' => '\\d*'],
        methods: ['GET', 'HEAD'])]
    public function getTown(?int $id): JsonResponse
    {
        $repo = $this->doctrine->getRepository(Town::class);

        if ($id) {
            $items = [$repo->find($id)];
            if (!$items[0]) {
                return new JsonResponse($this->doResponse->doErrorResponse('Town not found', 404));
            }
        } else {
            $items = $repo->findBy([], ['id' => 'DESC']);
        }

        $results = $this->groupSerializer->serializeGroup($items, $id ? 'shipmentCondition_detail' : 'shipmentCondition_list');

        if ($id) {
            return new JsonResponse($this->doResponse->doResponse($results[0]));
        }
        return new JsonResponse($this->doResponse->doResponse($results));
    }

    #[Route('/shipment-condition', name: 'post_shipment-condition', methods: ['POST'])]
    public function postTown(
        Request            $request,
        ValidatorInterface $validator,
    ): JsonResponse
    {
        $data = $request->request->all();
        $shipmentCondition = new Town();

        try {
            $shipmentCondition = $this->createMethodsByInput->createMethods($shipmentCondition, $data);

            $errors = $validator->validate($shipmentCondition);
            if (count($errors) > 0) {
                $errors = $this->validatorOutputFormatter->formatOutput($errors);
                return new JsonResponse($this->doResponse->doErrorResponse($errors));
            }

            $this->doctrine->persist($shipmentCondition);
            $this->doctrine->flush();

            $result = $this->groupSerializer->serializeGroup($shipmentCondition, 'shipmentCondition_detail');
            return new JsonResponse($this->doResponse->doResponse($result));
        } catch (\Exception $e) {
            return new JsonResponse($this->doResponse->doErrorResponse($e->getMessage()));
        }
    }

    #[Route('/shipment-condition/{id}', name: 'put_shipment-condition', methods: ['PUT'])]
    public function putTown(
        Request            $request,
        ValidatorInterface $validator,
        int                $id,
    ): JsonResponse
    {
        $data = $request->toArray();
        $shipmentCondition = $this->doctrine->getRepository(Town::class)->find($id);
        if (!$shipmentCondition) {
            return new JsonResponse($this->doResponse->doErrorResponse('Town not found', 404));
        }

        try {
            $shipmentCondition = $this->createMethodsByInput->createMethods($shipmentCondition, $data);

            $errors = $validator->validate($shipmentCondition);
            if (count($errors) > 0) {
                $errors = $this->validatorOutputFormatter->formatOutput($errors);
                return new JsonResponse($this->doResponse->doErrorResponse($errors));
            }

            $this->doctrine->persist($shipmentCondition);
            $this->doctrine->flush();

            $result = $this->groupSerializer->serializeGroup($shipmentCondition, 'shipmentCondition_detail');
            return new JsonResponse($this->doResponse->doResponse($result));
        } catch (\Exception $e) {
            return new JsonResponse($this->doResponse->doErrorResponse($e->getMessage()));
        }
    }

    #[Route('/shipment-condition/{id}', name: 'delete_shipment-condition', methods: ['DELETE'])]
    public function deleteTown(int $id): JsonResponse
    {
        $shipmentCondition = $this->doctrine->getRepository(ShipmentCondition::class)->find($id);
        if (!$shipmentCondition) {
            return new JsonResponse($this->doResponse->doErrorResponse('Town not found', 404));
        }

        $this->doctrine->remove($shipmentCondition);
        $this->doctrine->flush();

        return new JsonResponse($this->doResponse->doResponse('delete_successfully'));
    }

    
}
