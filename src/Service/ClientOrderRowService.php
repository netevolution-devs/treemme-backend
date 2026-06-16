<?php

namespace App\Service;

use App\Entity\ClientOrderRow;
use Doctrine\ORM\EntityManagerInterface;

class ClientOrderRowService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    /**
     * Calcola e aggiorna la quantità da spedire per una riga ordine cliente.
     * Gestisce inoltre la chiusura della riga (processed) e dell'ordine.
     */
    public function updateQuantityToShip(ClientOrderRow $row): void
    {
        /** @var \App\Repository\ClientOrderRowRepository $repo */
        $repo = $this->entityManager->getRepository(ClientOrderRow::class);
        $totalQuantityToShip = $repo->calculateQuantityToShip($row->getId());

        $row->setQuantityToShip($totalQuantityToShip);

        // Calcolo della quantità totale spedita (già fuori sede)
        $shippedQuantity = $this->calculateTotalShippedQuantity($row);

        // Se la quantità spedita copre l'ordine, la riga è processata
        if ($shippedQuantity >= $row->getQuantity()) {
            $row->setProcessed(true);
        }

        $this->entityManager->persist($row);

        // Aggiorna lo stato dell'intero ordine
        $this->updateOrderStatus($row->getClientOrder());
    }

    /**
     * Calcola la quantità totale già spedita per la riga (DDT con causale di spedizione).
     */
    private function calculateTotalShippedQuantity(ClientOrderRow $row): float
    {
        $shippedQuantity = 0.0;
        foreach ($row->getBatchOrders() as $batchOrder) {
            $batch = $batchOrder->getBatch();
            if (!$batch) continue;

            foreach ($batch->getDdtRows() as $ddtRow) {
                if ($ddtRow->getDdt()?->getReason()?->isIsShipmentReason()) {
                    $shippedQuantity += $ddtRow->getQuantity();
                }
            }
        }
        return $shippedQuantity;
    }

    /**
     * Chiude manualmente una riga d'ordine e ricalcola lo stato dell'ordine.
     */
    public function manualCloseRow(ClientOrderRow $row): void
    {
        $row->setProcessed(true);
        $this->entityManager->persist($row);
        
        $this->updateOrderStatus($row->getClientOrder());
        $this->entityManager->flush();
    }

    /**
     * Chiude manualmente l'intero ordine e tutte le sue righe.
     */
    public function manualCloseOrder(\App\Entity\ClientOrder $order): void
    {
        foreach ($order->getClientOrderRows() as $row) {
            $row->setProcessed(true);
            $this->entityManager->persist($row);
        }
        
        $order->setProcessed(true);
        $this->entityManager->persist($order);
        $this->entityManager->flush();
    }

    /**
     * Chiude l'ordine se tutte le sue righe sono processate.
     */
    private function updateOrderStatus(?\App\Entity\ClientOrder $order): void
    {
        if (!$order) return;

        $allProcessed = true;
        foreach ($order->getClientOrderRows() as $orderRow) {
            if (!$orderRow->isProcessed()) {
                $allProcessed = false;
                break;
            }
        }

        if ($allProcessed !== $order->isProcessed()) {
            $order->setProcessed($allProcessed);
            $this->entityManager->persist($order);
        }
    }
}
