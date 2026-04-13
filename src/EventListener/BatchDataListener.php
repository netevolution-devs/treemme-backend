<?php

namespace App\EventListener;

use App\Entity\BatchCost;
use App\Entity\BatchCostType;
use App\Entity\BatchData;
use App\Entity\Currency;
use App\Repository\BatchCostRepository;
use App\Repository\BatchCostTypeRepository;
use App\Repository\CurrencyRepository;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;

#[AsEntityListener(event: Events::prePersist, method: 'prePersist', entity: BatchData::class)]
#[AsEntityListener(event: Events::preUpdate, method: 'preUpdate', entity: BatchData::class)]
class BatchDataListener
{
    public function __construct(
        private BatchCostRepository $batchCostRepository,
        private BatchCostTypeRepository $batchCostTypeRepository,
        private CurrencyRepository $currencyRepository
    ) {
    }

    public function prePersist(BatchData $batchData, PrePersistEventArgs $args): void
    {
        $this->handleCostCreation($batchData, $args->getObjectManager());
    }

    public function preUpdate(BatchData $batchData, PreUpdateEventArgs $args): void
    {
        if ($args->hasChangedField('amount') || $args->hasChangedField('shipping_cost')) {
            $this->handleCostCreation($batchData, $args->getObjectManager(), $args);
        }
    }

    private function handleCostCreation(BatchData $batchData, $entityManager, ?PreUpdateEventArgs $args = null): void
    {
        $batch = $batchData->getBatch();
        if (!$batch) {
            return;
        }

        // Gestione Amount -> Acquisto
        $amount = $batchData->getAmount();
        $isAmountNew = $args === null || ($args->hasChangedField('amount') && $args->getOldValue('amount') <= 0);
        
        if ($amount > 0 && $isAmountNew) {
            $this->createCostIfNotExists($batch, 'Acquisto', $amount, $entityManager, $batchData);
        }

        // Gestione Shipping Cost -> Spese Portuali
        $shippingCost = $batchData->getShippingCost();
        $isShippingNew = $args === null || ($args->hasChangedField('shipping_cost') && $args->getOldValue('shipping_cost') <= 0);

        if ($shippingCost > 0 && $isShippingNew) {
            $this->createCostIfNotExists($batch, 'Spese Portuali', $shippingCost, $entityManager, $batchData);
        }
    }

    private function createCostIfNotExists($batch, string $typeName, float $amount, $entityManager, BatchData $batchData): void
    {
        if ($this->batchCostRepository->hasCostWithType($batch, $typeName)) {
            throw new \Exception(sprintf('Il lotto ha già una voce di costo di tipo "%s".', $typeName));
        }

        $type = $this->batchCostTypeRepository->findOneBy(['name' => $typeName]);
        if (!$type) {
            throw new \Exception(sprintf('Tipo di costo "%s" non trovato.', $typeName));
        }

        $euro = $this->currencyRepository->findOneBy(['abbreviation' => 'EUR']);
        if (!$euro) {
            $euro = $this->currencyRepository->findOneBy(['name' => 'Euro']);
        }

        $batchCost = new BatchCost();
        $batchCost->setBatch($batch);
        $batchCost->setBatchCostType($type);
        $batchCost->setCost($amount);
        // Usa la data di consegna se presente, altrimenti oggi
        $batchCost->setDate($batchData->getDeliveryDate() ?? new \DateTime());
        
        if ($euro) {
            $batchCost->setCurrency($euro);
            $batchCost->setCurrencyExchange(1.0);
        }

        $entityManager->persist($batchCost);
    }
}
