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
        $batch->setStockQuantity($batch->getStockQuantity() + $quantity);
        $batch->setStockItems($batch->getStockItems() + $pieces);

        // Se il movimento ha pezzi decimali, aggiorniamo anche lo stock delle mezze pelli
        if (floor($pieces) != $pieces) {
            $batch->setStockHalfPieces(($batch->getStockHalfPieces() ?? 0) + (int)round(($pieces - floor($pieces)) * 2));
        }
        
        $this->entityManager->persist($batch);
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
        $batch->setStockQuantity($batch->getStockQuantity() + $quantity);
        $batch->setStockItems($batch->getStockItems() + $pieces);

        if (floor($pieces) != $pieces) {
            $batch->setStockHalfPieces(($batch->getStockHalfPieces() ?? 0) + (int)round(($pieces - floor($pieces)) * 2));
        }

        $this->entityManager->persist($batch);
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
     * Metodo specifico per aggiornare stock Batch e BatchSelection (usato in composizione lotti)
     */
    public function updateBatchAndSelectionStock(Batch $batch, ?BatchSelection $selection, float $quantityDelta, float $piecesDelta, bool $updateBatchTotals = false): void
    {
        $batch->setStockQuantity($batch->getStockQuantity() + $quantityDelta);
        $batch->setStockItems($batch->getStockItems() + $piecesDelta);

        if (floor($piecesDelta) != $piecesDelta) {
            $batch->setStockHalfPieces(($batch->getStockHalfPieces() ?? 0) + (int)round(($piecesDelta - floor($piecesDelta)) * 2));
        }
        
        if ($updateBatchTotals) {
            $batch->setQuantity($batch->getQuantity() + $quantityDelta);
            $batch->setPieces($batch->getPieces() + $piecesDelta);
        }

        $this->entityManager->persist($batch);

        if ($selection) {
            $selection->setStockQuantity($selection->getStockQuantity() + $quantityDelta);
            $selection->setStockPieces($selection->getStockPieces() + $piecesDelta);
            $this->entityManager->persist($selection);
        }
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
