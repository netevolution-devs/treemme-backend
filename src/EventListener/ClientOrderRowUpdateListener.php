<?php

namespace App\EventListener;

use App\Entity\Ddt;
use App\Entity\DdtRow;
use App\Entity\BatchOrder;
use App\Entity\DdtReason;
use App\Service\ClientOrderRowService;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Events;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Event\PostRemoveEventArgs;
use Doctrine\ORM\Event\PreRemoveEventArgs;

#[AsEntityListener(event: Events::postPersist, method: 'postPersist', entity: DdtRow::class)]
#[AsEntityListener(event: Events::postUpdate, method: 'postUpdate', entity: DdtRow::class)]
#[AsEntityListener(event: Events::postRemove, method: 'postRemove', entity: DdtRow::class)]
#[AsEntityListener(event: Events::postPersist, method: 'postPersistBatchOrder', entity: BatchOrder::class)]
#[AsEntityListener(event: Events::postUpdate, method: 'postUpdateBatchOrder', entity: BatchOrder::class)]
#[AsEntityListener(event: Events::preRemove, method: 'preRemoveBatchOrder', entity: BatchOrder::class)]
#[AsEntityListener(event: Events::postUpdate, method: 'postUpdateDdt', entity: Ddt::class)]
#[AsEntityListener(event: Events::postUpdate, method: 'postUpdateDdtReason', entity: DdtReason::class)]
class ClientOrderRowUpdateListener
{
    private array $rowsToUpdate = [];

    public function __construct(
        private readonly ClientOrderRowService $clientOrderRowService
    ) {
    }

    public function postUpdateDdt(Ddt $ddt, PostUpdateEventArgs $event): void
    {
        // Se cambia il DDT, aggiorniamo tutte le righe ordine coinvolte
        foreach ($ddt->getDdtRows() as $ddtRow) {
            $this->updateFromDdtRow($ddtRow);
        }
        $event->getObjectManager()->flush();
    }

    public function postUpdateDdtReason(DdtReason $reason, PostUpdateEventArgs $event): void
    {
        // Se cambia il flag is_shipment_reason, ricalcoliamo tutto ciò che è collegato ai DDT con questa causale
        foreach ($reason->getDdts() as $ddt) {
            foreach ($ddt->getDdtRows() as $ddtRow) {
                $this->updateFromDdtRow($ddtRow);
            }
        }
        $event->getObjectManager()->flush();
    }

    private function updateFromDdtRow(DdtRow $ddtRow): void
    {
        $batch = $ddtRow->getBatch();
        if ($batch) {
            foreach ($batch->getBatchOrders() as $batchOrder) {
                $row = $batchOrder->getOrderRow();
                if ($row) {
                    $this->clientOrderRowService->updateQuantityToShip($row);
                }
            }
        }
    }

    private function updateFromBatchOrder(BatchOrder $batchOrder): void
    {
        $row = $batchOrder->getOrderRow();
        if ($row) {
            $this->clientOrderRowService->updateQuantityToShip($row);
        }
    }

    public function postPersist(DdtRow $ddtRow, PostPersistEventArgs $event): void
    {
        $this->updateFromDdtRow($ddtRow);
        $event->getObjectManager()->flush();
    }

    public function postUpdate(DdtRow $ddtRow, PostUpdateEventArgs $event): void
    {
        $this->updateFromDdtRow($ddtRow);
        $event->getObjectManager()->flush();
    }

    public function postRemove(DdtRow $ddtRow, PostRemoveEventArgs $event): void
    {
        $this->updateFromDdtRow($ddtRow);
        $event->getObjectManager()->flush();
    }

    public function postPersistBatchOrder(BatchOrder $batchOrder, PostPersistEventArgs $event): void
    {
        $this->updateFromBatchOrder($batchOrder);
        $event->getObjectManager()->flush();
    }

    public function postUpdateBatchOrder(BatchOrder $batchOrder, PostUpdateEventArgs $event): void
    {
        $this->updateFromBatchOrder($batchOrder);
        $event->getObjectManager()->flush();
    }

    public function preRemoveBatchOrder(BatchOrder $batchOrder, PreRemoveEventArgs $event): void
    {
        // Usiamo preRemove perché postRemove l'associazione potrebbe essere già sparita
        $this->updateFromBatchOrder($batchOrder);
        $event->getObjectManager()->flush();
    }
}
