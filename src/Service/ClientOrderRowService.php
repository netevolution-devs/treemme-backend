<?php

namespace App\Service;

use App\Entity\ClientOrderRow;
use App\Entity\MeasurementUnit;
use App\Entity\MeasurementUnitCoefficient;
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

    /**
     * Calcola prezzi (EUR / Valuta estera), totali e normalizzazione prezzi/quantità per PQ e MQ.
     */
    public function calculatePricesAndUnits(ClientOrderRow $clientOrderRow): void
    {
        $quantity = (float)($clientOrderRow->getQuantity() ?: 0.0);
        $currencyPrice = $clientOrderRow->getCurrencyPrice();
        $currencyExchange = (float)($clientOrderRow->getCurrencyExchange() ?: 1.0);

        if ($currencyPrice !== null) {
            $price = $currencyExchange != 0 ? round($currencyPrice / $currencyExchange, 4) : 0.0;
            $clientOrderRow->setPrice(round($price, 2));
            $clientOrderRow->setCurrencyExchange($currencyExchange);
            $clientOrderRow->setCurrencyPrice(round($currencyPrice, 4));
        } else {
            $price = (float)($clientOrderRow->getPrice() ?: 0.0);
            $currencyPrice = round($price * $currencyExchange, 4);
            $clientOrderRow->setCurrencyPrice($currencyPrice);
        }

        // Totali
        $totalPrice = round($quantity * $price, 4);
        $totalCurrencyPrice = round($quantity * $currencyPrice, 4);

        $clientOrderRow->setTotalPrice($totalPrice);
        $clientOrderRow->setTotalCurrencyPrice($totalCurrencyPrice);

        // Calcolo quantità normalizzate MQ e PQ
        $unit = $clientOrderRow->getMeasurementUnit();
        $quantityMq = $this->convertToMq($quantity, $unit);
        $quantityPq = $this->convertToPq($quantity, $unit);

        $clientOrderRow->setQuantityMq($quantityMq);
        $clientOrderRow->setQuantityPq($quantityPq);

        // Calcolo prezzi unitari per MQ e PQ (in EUR base)
        // price_mq = total_price / quantity_mq
        // price_pq = total_price / quantity_pq
        if ($quantityMq > 0) {
            $clientOrderRow->setPriceMq(round($totalPrice / $quantityMq, 4));
        } elseif ($quantity == 0 && $price > 0 && $unit) {
            $unitMq = $this->convertToMq(1.0, $unit);
            $clientOrderRow->setPriceMq($unitMq > 0 ? round($price / $unitMq, 4) : null);
        } else {
            $clientOrderRow->setPriceMq(null);
        }

        if ($quantityPq > 0) {
            $clientOrderRow->setPricePq(round($totalPrice / $quantityPq, 4));
        } elseif ($quantity == 0 && $price > 0 && $unit) {
            $unitPq = $this->convertToPq(1.0, $unit);
            $clientOrderRow->setPricePq($unitPq > 0 ? round($price / $unitPq, 4) : null);
        } else {
            $clientOrderRow->setPricePq(null);
        }
    }

    /**
     * Converte una quantità in Metri Quadri (MQ).
     */
    public function convertToMq(float $quantity, ?MeasurementUnit $unit): float
    {
        if (!$unit) {
            return $quantity;
        }

        $prefix = strtoupper((string)$unit->getPrefix());
        if ($prefix === 'MQ') {
            return $quantity;
        }

        // Cerca coefficiente diretto verso unità con prefisso MQ
        foreach ($unit->getMeasurementUnitCoefficients() as $coeff) {
            $end = $coeff->getEndUm();
            if ($end && strtoupper((string)$end->getPrefix()) === 'MQ' && $coeff->getCoefficient() > 0) {
                return round($quantity * (float)$coeff->getCoefficient(), 4);
            }
        }

        // Fallback standard: PQ -> MQ
        if ($prefix === 'PQ') {
            return round($quantity / 10.764, 4);
        }

        return round($quantity, 4);
    }

    /**
     * Converte una quantità in Piedi Quadri (PQ).
     */
    public function convertToPq(float $quantity, ?MeasurementUnit $unit): float
    {
        if (!$unit) {
            return $quantity;
        }

        $prefix = strtoupper((string)$unit->getPrefix());
        if ($prefix === 'PQ') {
            return $quantity;
        }

        // Cerca coefficiente diretto verso unità con prefisso PQ
        foreach ($unit->getMeasurementUnitCoefficients() as $coeff) {
            $end = $coeff->getEndUm();
            if ($end && strtoupper((string)$end->getPrefix()) === 'PQ' && $coeff->getCoefficient() > 0) {
                return round($quantity * (float)$coeff->getCoefficient(), 4);
            }
        }

        // Fallback standard: MQ -> PQ
        if ($prefix === 'MQ') {
            return round($quantity * 10.764, 4);
        }

        return round($quantity, 4);
    }
}
