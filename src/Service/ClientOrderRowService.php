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
     * La quantità da spedire è la somma delle quantità di tutti i lotti associati
     * che NON sono stati venduti e NON sono fuori sede in Conto Lavorazione.
     */
    public function updateQuantityToShip(ClientOrderRow $row): void
    {
        $batchOrders = $row->getBatchOrders();
        $totalQuantityToShip = 0.0;

        foreach ($batchOrders as $batchOrder) {
            $batch = $batchOrder->getBatch();
            if (!$batch) {
                continue;
            }

            // Un lotto è considerato "non disponibile per la spedizione" se è presente in un DDT
            // con causale "Vendita" o "Conto Lavorazione".
            $isShippedOrOut = false;
            foreach ($batch->getDdtRows() as $ddtRow) {
                $ddt = $ddtRow->getDdt();
                if (!$ddt) {
                    continue;
                }

                $reasonName = $ddt->getReason()?->getName();
                if ($reasonName === 'Vendita' || $reasonName === 'Conto Lavorazione') {
                    $isShippedOrOut = true;
                    break;
                }
            }

            if (!$isShippedOrOut) {
                $totalQuantityToShip += $batch->getQuantity();
            }
        }

        $row->setQuantityToShip($totalQuantityToShip);
        $this->entityManager->persist($row);
    }
}
