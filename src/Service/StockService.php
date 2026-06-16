<?php

namespace App\Service;

use App\Entity\Batch;
use App\Entity\BatchSelection;
use App\Entity\WarehouseMovement;
use Doctrine\ORM\EntityManagerInterface;

class StockService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    /**
     * Aggiorna lo stock del lotto e della eventuale selezione associata basandosi su un movimento.
     */
    public function updateStockFromMovement(WarehouseMovement $movement): void
    {
        $batch = $movement->getBatch();
        if (!$batch) {
            return;
        }

        $quantity = $movement->getQuantity() ?? 0.0;
        $pieces = (float) ($movement->getPiece() ?? 0.0);

        // Nel caso di movimento da DDTRow, il controller lo salva come valore positivo
        // ma WarehouseMovement gestisce il segno in base alla causale.
        // Lo StockService deve semplicemente sommare il valore finale del movimento.
        $batch->setStockQuantity(round(($batch->getStockQuantity() ?? 0.0) + $quantity, 3));
        $batch->setStockItems(round(($batch->getStockItems() ?? 0.0) + $pieces, 3));

        // Se il movimento ha pezzi decimali, aggiorniamo anche lo stock delle mezze pelli
        if (floor($pieces) != $pieces) {
            $batch->setStockHalfPieces(($batch->getStockHalfPieces() ?? 0) + (int)round(($pieces - floor($pieces)) * 2));
        }
        
        $this->entityManager->persist($batch);
        $this->entityManager->flush();
    }

    /**
     * Ricalcola la media taglia del lotto basandosi sui movimenti di scarico (vendita).
     */
    public function updateBatchAverageFromMovements(?Batch $batch): void
    {
        if (!$batch || !$batch->getMeasurementUnit()) {
            return;
        }

        $totalPieces = 0.0;
        $totalQuantity = 0.0;

        foreach ($batch->getWarehouseMovements() as $movement) {
            $reason = $movement->getReason();
            if (!$reason || !$reason->getReasonType()) {
                continue;
            }

            // Consideriamo solo gli scarichi per la media taglia venduta
            if ($reason->getReasonType()->getMovementType() === '-') {
                $totalPieces += abs((float)($movement->getPiece() ?? 0.0));
                $totalQuantity += abs($movement->getQuantity() ?? 0.0);
            }
        }

        if ($totalPieces > 0 && $totalQuantity > 0) {
            $measurementUnit = $batch->getMeasurementUnit();
            // Formula invertita: Quantità / Pezzi = Taglia Media
            // Se in MQ, convertiamo prima in PQ per avere la media in piedi quadri
            $avg = $totalQuantity / $totalPieces;

            if ($measurementUnit->getPrefix() === 'MQ') {
                $coefficientUm = $measurementUnit->getMeasurementUnitCoefficients()->first();
                if ($coefficientUm) {
                    $avg = ($totalQuantity * $coefficientUm->getCoefficient()) / $totalPieces;
                }
            }
            
            $batch->setSqFtAverageFound(round($avg, 3));
        } else {
            $batch->setSqFtAverageFound(0.0);
        }

        $this->entityManager->persist($batch);
        $this->entityManager->flush();
    }

    /**
     * Sottrae stock dal lotto (usato per DDT Out)
     */
    public function removeStock(Batch $batch, float $quantity, float $pieces): void
    {
        $batch->setStockQuantity($batch->getStockQuantity() - $quantity);
        $batch->setStockItems($batch->getStockItems() - $pieces);

        if (floor($pieces) != $pieces) {
            $batch->setStockHalfPieces(($batch->getStockHalfPieces() ?? 0) - (int)round(($pieces - floor($pieces)) * 2));
        }

        $this->entityManager->persist($batch);
    }

    /**
     * Aggiunge stock al lotto (usato per ripristini o resi)
     */
    public function addStock(Batch $batch, float $quantity, float $pieces): void
    {
        // Se non ci sono movimenti, usiamo questo metodo per aggiornare i campi del lotto.
        // Se ci sono movimenti, lo stock verrà ricalcolato dal listener.
        $batch->setStockQuantity(round(($batch->getStockQuantity() ?? 0.0) + $quantity, 3));
        $batch->setStockItems(round(($batch->getStockItems() ?? 0.0) + $pieces, 3));

        if (floor($pieces) != $pieces) {
            $batch->setStockHalfPieces(($batch->getStockHalfPieces() ?? 0) + (int)round(($pieces - floor($pieces)) * 2));
        }

        $this->entityManager->persist($batch);
        $this->entityManager->flush();
    }

    /**
     * Setta lo stock iniziale di un lotto (usato alla creazione)
     */
    public function setInitialStock(Batch $batch, float $quantity, float $pieces): void
    {
        $batch->setStockQuantity($quantity);
        $batch->setStockItems($pieces);

        if (floor($pieces) != $pieces) {
            $batch->setStockHalfPieces((int)round(($pieces - floor($pieces)) * 2));
        } else {
            $batch->setStockHalfPieces(0);
        }

        $batch->setPieces($pieces);
        $this->entityManager->persist($batch);
    }

    /**
     * Metodo specifico per aggiornare stock Batch e BatchSelection (usato in composizione lotti e compensazione)
     */
    public function updateBatchAndSelectionStock(Batch $batch, ?BatchSelection $selection, float $quantityDelta, float $piecesDelta, bool $updateBatchTotals = false): void
    {
        // NOTA: Lo stock del Batch viene ora aggiornato dai movimenti di magazzino tramite listener.
        // Se questo metodo viene chiamato insieme alla creazione di un movimento, lo stock del Batch verrebbe aggiornato due volte.
        // Tuttavia, per le Selezioni (BatchSelection) non abbiamo ancora un listener automatico.
        
        if ($updateBatchTotals) {
            $batch->setStockQuantity($batch->getStockQuantity() + $quantityDelta);
            $batch->setStockItems($batch->getStockItems() + $piecesDelta);
            $batch->setQuantity($batch->getQuantity() + $quantityDelta);
            $batch->setPieces($batch->getPieces() + $piecesDelta);
            $this->entityManager->persist($batch);
        }

        if ($selection) {
            $selection->setStockQuantity(($selection->getStockQuantity() ?? 0.0) + $quantityDelta);
            $selection->setStockPieces(($selection->getStockPieces() ?? 0.0) + $piecesDelta);
            $this->entityManager->persist($selection);
        }
        
        $this->entityManager->flush();
    }

    /**
     * Ricalcola integralmente lo stock di un lotto sommando tutti i suoi movimenti.
     */
    public function recalculateBatchStock(Batch $batch): array
    {
        $movements = $batch->getWarehouseMovements();
        $totalQuantity = 0.0;
        $totalPieces = 0.0;

        foreach ($movements as $movement) {
            $totalQuantity += $movement->getQuantity() ?? 0.0;
            $totalPieces += (float)($movement->getPiece() ?? 0.0);
        }

        $oldQuantity = $batch->getStockQuantity();
        $oldItems = $batch->getStockItems();
        $oldHalfPieces = $batch->getStockHalfPieces();

        $batch->setStockQuantity(round($totalQuantity, 3));
        $batch->setStockItems(round($totalPieces, 3));

        if (floor($totalPieces) != $totalPieces) {
            $batch->setStockHalfPieces((int)round(($totalPieces - floor($totalPieces)) * 2));
        } else {
            $batch->setStockHalfPieces(0);
        }
        
        $this->entityManager->persist($batch);
        $this->entityManager->flush();

        return [
            'batch_id' => $batch->getId(),
            'old_quantity' => $oldQuantity,
            'new_quantity' => $batch->getStockQuantity(),
            'old_items' => $oldItems,
            'new_items' => $batch->getStockItems(),
            'old_half_pieces' => $oldHalfPieces,
            'new_half_pieces' => $batch->getStockHalfPieces()
        ];
    }
}
