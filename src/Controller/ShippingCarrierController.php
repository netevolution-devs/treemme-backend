<?php

namespace App\Controller;

use App\Entity\ShippingCarrier;
use App\Repository\ShippingCarrierRepository;
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

class ShippingCarrierController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface   $entityManager,
        private readonly ShippingCarrierRepository $shippingCarrierRepository,
        private readonly GroupSerializerService   $groupSerializer,
        private readonly DoResponseService        $doResponse,
        private readonly CreateMethodsByInput     $createMethodsByInput,
        private readonly ValidatorOutputFormatter $validatorOutputFormatter
    )
    {
    }

    #[Route('/shipping-carrier/{id}', name: 'app_shipping_carrier_show', methods: ['GET'], requirements: ['id' => '\d+'], defaults: ['id' => null])]
    public function show(?int $id): JsonResponse
    {
        if ($id === null) {
            $carriers = $this->shippingCarrierRepository->findBy([], ['name' => 'ASC']);
            $results = $this->groupSerializer->serializeGroup($carriers, 'shipping_carrier_list');
            return new JsonResponse($this->doResponse->doResponse($results));
        }

        $carrier = $this->shippingCarrierRepository->find($id);

        if (!$carrier) {
            return $this->doResponse->doErrorJsonResponse('Vettore non trovato', status_code: 404);
        }

        $results = $this->groupSerializer->serializeGroup($carrier, 'shipping_carrier_detail');

        return new JsonResponse($this->doResponse->doResponse($results));
    }

    #[Route('/shipping-carrier', name: 'app_shipping_carrier_create', methods: ['POST'])]
    public function create(Request $request, ValidatorInterface $validator): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? $request->request->all();

        $carrier = new ShippingCarrier();

        try {
            $this->mapDataToEntity($carrier, $data);
        } catch (\Exception $e) {
            return $this->doResponse->doErrorJsonResponse($e->getMessage());
        }

        $errors = $validator->validate($carrier);
        if (count($errors) > 0) {
            $formattedErrors = $this->validatorOutputFormatter->formatErrors($errors);
            return $this->doResponse->doErrorJsonResponse($formattedErrors);
        }

        $this->entityManager->persist($carrier);
        $this->entityManager->flush();

        $results = $this->groupSerializer->serializeGroup($carrier, 'shipping_carrier_detail');
        return new JsonResponse($this->doResponse->doResponse($results));
    }

    #[Route('/shipping-carrier/{id}', name: 'app_shipping_carrier_update', methods: ['PUT', 'PATCH'])]
    public function update(Request $request, int $id, ValidatorInterface $validator): JsonResponse
    {
        $carrier = $this->shippingCarrierRepository->find($id);

        if (!$carrier) {
            return $this->doResponse->doErrorJsonResponse('Vettore non trovato', status_code: 404);
        }

        $data = json_decode($request->getContent(), true) ?? $request->request->all();

        try {
            $this->mapDataToEntity($carrier, $data);
        } catch (\Exception $e) {
            return $this->doResponse->doErrorJsonResponse($e->getMessage());
        }

        $errors = $validator->validate($carrier);
        if (count($errors) > 0) {
            $formattedErrors = $this->validatorOutputFormatter->formatErrors($errors);
            return $this->doResponse->doErrorJsonResponse($formattedErrors);
        }

        $this->entityManager->flush();

        $results = $this->groupSerializer->serializeGroup($carrier, 'shipping_carrier_detail');
        return new JsonResponse($this->doResponse->doResponse($results));
    }

    #[Route('/shipping-carrier/{id}', name: 'app_shipping_carrier_delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $carrier = $this->shippingCarrierRepository->find($id);

        if (!$carrier) {
            return $this->doResponse->doErrorJsonResponse('Vettore non trovato', status_code: 404);
        }

        $this->entityManager->remove($carrier);
        $this->entityManager->flush();

        return new JsonResponse($this->doResponse->doResponse(null, status: 'Vettore eliminato correttamente'));
    }

    /**
     * @throws \Exception
     */
    private function mapDataToEntity(ShippingCarrier $carrier, array $data): void
    {
        // Mappatura campi semplici
        $this->createMethodsByInput->createMethods($carrier, $data);
    }
}
