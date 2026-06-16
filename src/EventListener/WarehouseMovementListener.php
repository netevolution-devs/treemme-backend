<?php

namespace App\EventListener;

use App\Entity\WarehouseMovement;
use App\Service\StockService;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostRemoveEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Events;

#[AsEntityListener(event: Events::prePersist, method: 'prePersist', entity: WarehouseMovement::class)]
#[AsEntityListener(event: Events::postPersist, method: 'postPersist', entity: WarehouseMovement::class)]
#[AsEntityListener(event: Events::postUpdate, method: 'postUpdate', entity: WarehouseMovement::class)]
#[AsEntityListener(event: Events::postRemove, method: 'postRemove', entity: WarehouseMovement::class)]
class WarehouseMovementListener
{
    public function __construct(
        private readonly StockService $stockService
    ) {
    }

    public function prePersist(WarehouseMovement $movement, PrePersistEventArgs $event): void
    {
        // Questo metodo è richiesto da Doctrine ma per ora non fa nulla.
        // La logica di aggiornamento stock è in postPersist.
    }

    public function postPersist(WarehouseMovement $movement, PostPersistEventArgs $event): void
    {
        $this->stockService->updateStockFromMovement($movement);
        $this->stockService->updateBatchAverageFromMovements($movement->getBatch());
    }

    public function postUpdate(WarehouseMovement $movement, PostUpdateEventArgs $event): void
    {
        // Per semplicità e sicurezza, ricalcoliamo lo stock del lotto coinvolto.
        // Se il lotto è cambiato, ricalcoliamo entrambi.
        $unitOfWork = $event->getObjectManager()->getUnitOfWork();
        $changeSet = $unitOfWork->getEntityChangeSet($movement);

        if (isset($changeSet['batch'])) {
            $oldBatch = $changeSet['batch'][0];
            if ($oldBatch) {
                $this->stockService->recalculateBatchStock($oldBatch);
                $this->stockService->updateBatchAverageFromMovements($oldBatch);
            }
        }

        $this->stockService->recalculateBatchStock($movement->getBatch());
        $this->stockService->updateBatchAverageFromMovements($movement->getBatch());
    }

    public function postRemove(WarehouseMovement $movement, PostRemoveEventArgs $event): void
    {
        $batch = $movement->getBatch();
        if ($batch) {
            $this->stockService->recalculateBatchStock($batch);
            $this->stockService->updateBatchAverageFromMovements($batch);
        }
    }
}
