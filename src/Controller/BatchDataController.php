<?php

namespace App\Controller;

use App\Entity\BatchData;
use App\Entity\BatchCost;
use App\Entity\BatchCostType;
use App\Entity\Currency;
use App\Entity\SeaPort;
use App\Entity\Pallet;
use App\Entity\Batch;
use App\Entity\ShipmentCondition;
use App\Entity\Contact;
use App\Repository\BatchCostRepository;
use App\Repository\BatchCostTypeRepository;
use App\Repository\CurrencyRepository;
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

final class BatchDataController extends AbstractController
{
    private $createMethodsByInput;
    private $doctrine;
    private $doResponse;
    private $groupSerializer;
    private $validatorOutputFormatter;
    private $batchCostRepository;
    private $batchCostTypeRepository;
    private $currencyRepository;

    public function __construct(
        CreateMethodsByInput     $createMethodsByInput,
        EntityManagerInterface   $entityManager,
        DoResponseService        $doResponseService,
        GroupSerializerService   $groupSerializer,
        ValidatorOutputFormatter $validatorOutputFormatter,
        BatchCostRepository      $batchCostRepository,
        BatchCostTypeRepository  $batchCostTypeRepository,
        CurrencyRepository       $currencyRepository
    )
    {
        $this->createMethodsByInput = $createMethodsByInput;
        $this->doctrine = $entityManager;
        $this->doResponse = $doResponseService;
        $this->groupSerializer = $groupSerializer;
        $this->validatorOutputFormatter = $validatorOutputFormatter;
        $this->batchCostRepository = $batchCostRepository;
        $this->batchCostTypeRepository = $batchCostTypeRepository;
        $this->currencyRepository = $currencyRepository;
    }

    #[Route('/batch-data/{id}',
        name: 'get_batch_data',
        defaults: ['id' => null],
        requirements: ['id' => '\d*'],
        methods: ['GET', 'HEAD'])]
    public function getBatchData(?int $id): JsonResponse
    {
        $batchDataRepository = $this->doctrine->getRepository(BatchData::class);

        if ($id) {
            $batchData = [$batchDataRepository->find($id)];
            if (!$batchData[0]) {
                return $this->doResponse->doErrorJsonResponse('BatchData not found', 404);
            }
        } else {
            $batchData = $batchDataRepository->findAll();
        }
        $results = $this->groupSerializer->serializeGroup($batchData, $id ? 'batch_data_detail' : 'batch_data_list');

        if ($id) {
            return new JsonResponse($this->doResponse->doResponse($results[0]));
        }
        return new JsonResponse($this->doResponse->doResponse($results));
    }

    #[Route('/batch-data',
        name: 'post_batch_data',
        methods: ['POST'])]
    public function postBatchData(
        Request            $request,
        ValidatorInterface $validator,
    ): JsonResponse
    {
        $data = $request->toArray();
        $batchData = new BatchData();

        try {
            $batchData = $this->handleRelations($batchData, $data);
            $batchData = $this->createMethodsByInput->createMethods($batchData, $data);
            $batchData = $this->calculateWeights($batchData);

            $this->handleCostCreation($batchData);

            $errors = $validator->validate($batchData);
            if (count($errors) > 0) {
                $errors = $this->validatorOutputFormatter->formatOutput($errors);
                return $this->doResponse->doErrorJsonResponse($errors);
            }

            $em = $this->doctrine;
            $em->persist($batchData);
            $em->flush();

            $result = $this->groupSerializer->serializeGroup($batchData, 'batch_data_detail');
            return new JsonResponse($this->doResponse->doResponse($result));

        } catch (\Exception $e) {
            return $this->doResponse->doErrorJsonResponse($e->getMessage());
        }
    }

    #[Route('/batch-data/{id}',
        name: 'put_batch_data',
        methods: ['PUT'])]
    public function modifyBatchData(
        Request            $request,
        ValidatorInterface $validator,
        int                $id,
    ): JsonResponse
    {
        $data = $request->toArray();
        $batchData = $this->doctrine->getRepository(BatchData::class)->find($id);

        if (!$batchData) {
            return $this->doResponse->doErrorJsonResponse('BatchData not found', 404);
        }

        try {

            $batchData = $this->handleRelations($batchData, $data);
            $batchData = $this->createMethodsByInput->createMethods($batchData, $data);
            $batchData = $this->calculateWeights($batchData);

            $this->handleCostCreation($batchData);

            $errors = $validator->validate($batchData);
            if (count($errors) > 0) {
                $errors = $this->validatorOutputFormatter->formatOutput($errors);
                return $this->doResponse->doErrorJsonResponse($errors);
            }

            $this->doctrine->persist($batchData);
            $this->doctrine->flush();

            $result = $this->groupSerializer->serializeGroup($batchData, 'batch_data_detail');
            return new JsonResponse($this->doResponse->doResponse($result));
        } catch (\Exception $e) {
            return $this->doResponse->doErrorJsonResponse($e->getMessage());
        }
    }

    #[Route('/batch-data/{id}',
        name: 'delete_batch_data',
        methods: ['DELETE'])]
    public function deleteBatchData(int $id): JsonResponse
    {
        $batchData = $this->doctrine->getRepository(BatchData::class)->find($id);
        if (!$batchData) {
            return $this->doResponse->doErrorJsonResponse('BatchData not found', 404);
        }

        $this->doctrine->remove($batchData);
        $this->doctrine->flush();

        return new JsonResponse($this->doResponse->doResponse('delete_successfully'));
    }

    private function handleRelations(BatchData $batchData, array &$data): BatchData
    {
        if (isset($data['sea_port_id'])) {
            $seaPort = $this->doctrine->getRepository(SeaPort::class)->find($data['sea_port_id']);
            if ($seaPort) {
                $batchData->setSeaPort($seaPort);
            }
            unset($data['sea_port_id']);
        }

        if (isset($data['pallet_id'])) {
            $pallet = $this->doctrine->getRepository(Pallet::class)->find($data['pallet_id']);
            if ($pallet) {
                $batchData->setPallet($pallet);
            }
            unset($data['pallet_id']);
        }

        if (isset($data['batch_id'])) {
            $batch = $this->doctrine->getRepository(Batch::class)->find($data['batch_id']);
            if ($batch) {
                $batchData->setBatch($batch);
            }
            unset($data['batch_id']);
        }

        if (isset($data['shipment_condition_id'])) {
            $shipmentCondition = $this->doctrine->getRepository(ShipmentCondition::class)->find($data['shipment_condition_id']);
            if ($shipmentCondition) {
                $batchData->setShipmentCondition($shipmentCondition);
            }
            unset($data['shipment_condition_id']);
        }

        if (isset($data['shipment_subcontractor_id'])) {
            $shipmentSubcontractor = $this->doctrine->getRepository(Contact::class)->find($data['shipment_subcontractor_id']);
            if ($shipmentSubcontractor) {
                $batchData->setShipmentSubcontractor($shipmentSubcontractor);
            }
            unset($data['shipment_subcontractor_id']);
        }

        if (isset($data['currency_id'])) {
            $currency = $this->doctrine->getRepository(Currency::class)->find($data['currency_id']);
            if ($currency) {
                $batchData->setCurrency($currency);
            }
            unset($data['currency_id']);
        }

        return $batchData;
    }

    private function handleCostCreation(BatchData $batchData): void
    {
        $batch = $batchData->getBatch();
        if (!$batch) {
            return;
        }

        $amount = $batchData->getAmount();
        if ($amount > 0) {
            $this->updateOrCreateCost($batchData, 'Acquisto', $amount);
        }

        $shippingCost = $batchData->getShippingCost();
        if ($shippingCost > 0) {
            $this->updateOrCreateCost($batchData, 'Spese Portuali', $shippingCost);
        }
    }

    private function updateOrCreateCost(BatchData $batchData, string $typeName, float $amount): void
    {
        $batch = $batchData->getBatch();
        $type = $this->batchCostTypeRepository->findOneBy(['name' => $typeName]);
        if (!$type) {
            throw new \Exception(sprintf('Tipo di costo "%s" non trovato.', $typeName));
        }

        $batchCost = $this->batchCostRepository->findOneBy([
            'batch' => $batch,
            'batch_cost_type' => $type
        ]);

        if (!$batchCost) {
            $euro = $this->currencyRepository->findOneBy(['abbreviation' => 'EUR']);
            if (!$euro) {
                $euro = $this->currencyRepository->findOneBy(['name' => 'Euro']);
            }

            $batchCost = new BatchCost();
            $batchCost->setBatch($batch);
            $batchCost->setBatchCostType($type);
            $batchCost->setCurrencyExchange(1.0);
            if ($euro) {
                $batchCost->setCurrency($euro);
            }
        }

        $batchCost->setCost($amount);
        $batchCost->setDate($batchData->getDeliveryDate() ?? new \DateTime());

        $this->doctrine->persist($batchCost);
    }

    private function calculateWeights(BatchData $batchData): BatchData
    {
        $pallet = $batchData->getPallet();
        $palletNumber = $batchData->getPalletNumber();

        if ($pallet && $palletNumber) {
            $palletWeight = $pallet->getWeight() * $palletNumber;
            $batchData->setPalletWeight($palletWeight);

            $grossFoundedWeight = $batchData->getFoundedGrossWeight();
            if ($grossFoundedWeight !== null) {
                $netFoundedWeight = $grossFoundedWeight - $palletWeight;
                $batchData->setFoundedNetWeight($netFoundedWeight);

                $batch = $batchData->getBatch();
                if ($batch && $batch->getPieces() > 0) {
                    $averageFoundedWeight = $netFoundedWeight / $batch->getPieces();
                    $batchData->setFoundedAverageWeight($averageFoundedWeight);
                }
            }

            $grossDeclaredWeight = $batchData->getDeclaredAverageWeight();
            if ($grossDeclaredWeight !== null) {
                $netDeclaredWeight = $grossDeclaredWeight - $palletWeight;
                $batchData->setDeclaredNetWeight($netDeclaredWeight);

                $batch = $batchData->getBatch();
                if ($batch && $batch->getPieces() > 0) {
                    $averageDeclaredWeight = $netDeclaredWeight / $batch->getPieces();
                    $batchData->setDeclaredAverageWeight($averageDeclaredWeight);
                }
            }
        }

        return $batchData;
    }
}

