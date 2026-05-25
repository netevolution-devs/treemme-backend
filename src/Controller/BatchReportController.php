<?php

namespace App\Controller;

use App\Entity\Batch;
use App\Entity\BatchComposition;
use App\Entity\BatchCost;
use App\Entity\DdtRow;
use App\Entity\MeasurementUnit;
use App\Service\DoResponseService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class BatchReportController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private DoResponseService $doResponse;

    public function __construct(EntityManagerInterface $entityManager, DoResponseService $doResponse)
    {
        $this->entityManager = $entityManager;
        $this->doResponse = $doResponse;
    }

    #[Route('/batch/{id}/report', name: 'get_batch_report', methods: ['GET'])]
    public function getBatchReport(int $id): JsonResponse
    {
        $batch = $this->entityManager->getRepository(Batch::class)->find($id);

        if (!$batch) {
            return $this->doResponse->doErrorJsonResponse('Batch non trovato', 404);
        }

        $reportData = $this->getBatchRecursiveData($batch);

        return new JsonResponse($this->doResponse->doResponse($reportData));
    }

    private function getBatchRecursiveData(Batch $batch): array
    {
        $data = $this->collectBatchData($batch);

        foreach ($batch->getSonBatches() as $composition) {
            $sonBatch = $composition->getBatch();
            if ($sonBatch) {
                $childData = $this->getBatchRecursiveData($sonBatch);
                $this->aggregateChildData($data, $childData);
            }
        }

        // Calcolo medie finali dopo aggregazione
        if ($data['sold_pieces'] > 0) {
            $data['average_revenue_per_leather'] = $data['total_revenue'] / $data['sold_pieces'];
        }

        if ($data['sold_pieces'] > 0 && $data['sold_quantity_ftsq'] > 0) {
             // L'utente chiede la media dei ftsq per pelle (ftsq totali / pezzi totali)
             $data['average_ftsq_per_leather'] = $data['sold_quantity_ftsq'] / $data['sold_pieces'];
        } elseif ($data['total_pieces'] > 0 && $data['total_quantity_ftsq'] > 0) {
             // Se non venduto, usiamo i dati totali del lotto per la media ftsq
             $data['average_ftsq_per_leather'] = $data['total_quantity_ftsq'] / $data['total_pieces'];
        }

        return $data;
    }

    private function collectBatchData(Batch $batch): array
    {
        $totalPieces = $batch->getPieces() ?? 0;
        $totalQuantity = $batch->getQuantity() ?? 0.0;
        $um = $batch->getMeasurementUnit();
        
        $totalQuantityFtsq = $this->convertToFtsq($totalQuantity, $um);

        $soldPieces = 0;
        $soldQuantity = 0.0;
        $totalRevenue = 0.0;
        $sales = [];

        foreach ($batch->getDdtRows() as $row) {
            $soldPieces += ($row->getPieces() ?? 0);
            $soldQuantity += ($row->getQuantity() ?? 0.0);
            $totalRevenue += ($row->getTotalValue() ?? 0.0);

            $ddt = $row->getDdt();
            $sales[] = [
                'ddt_number' => $ddt?->getDdtNumber(),
                'ddt_date' => $ddt?->getDdtDate()?->format('Y-m-d'),
                'ddt_reason' => $ddt?->getReason()?->getName(),
                'client' => $ddt?->getClient()?->getName() ?? $ddt?->getSubcontractor()?->getName(),
                'pieces' => $row->getPieces(),
                'quantity' => $row->getQuantity(),
                'total_value' => $row->getTotalValue(),
                'note' => $row->getRowNote(),
            ];
        }

        $soldQuantityFtsq = $this->convertToFtsq($soldQuantity, $um);

        // Calcolo giacenza reale basata sui movimenti di magazzino
        $actualStockPieces = 0;
        $actualStockQuantity = 0.0;
        foreach ($batch->getWarehouseMovements() as $mov) {
            $reason = $mov->getReason();
            $multiplier = 1;
            if ($reason && $reason->getReasonType()) {
                if ($reason->getReasonType()->getMovementType() === 'S') {
                    $multiplier = -1;
                }
            }
            $actualStockPieces += (($mov->getPiece() ?? 0) * $multiplier);
            $actualStockQuantity += (($mov->getQuantity() ?? 0.0) * $multiplier);
        }

        $actualStockQuantityFtsq = $this->convertToFtsq($actualStockQuantity, $um);

        $salePricePerLeather = 0.0;
        if ($soldPieces > 0) {
            $salePricePerLeather = $totalRevenue / $soldPieces;
        }

        $costs = [];
        foreach ($batch->getBatchCosts() as $cost) {
            $costs[] = [
                'date' => $cost->getDate()?->format('Y-m-d'),
                'type' => $cost->getBatchCostType()?->getName(),
                'amount' => $cost->getCost(),
                'currency' => $cost->getCurrency()?->getAbbreviation(),
                'note' => $cost->getCostNote(),
            ];
        }
            
        return [
            'id' => $batch->getId(),
            'code' => $batch->getBatchCode(),
            'total_pieces' => $totalPieces,
            'total_quantity' => $totalQuantity,
            'total_quantity_ftsq' => $totalQuantityFtsq,
            'sold_pieces' => $soldPieces,
            'sold_quantity' => $soldQuantity,
            'sold_quantity_ftsq' => $soldQuantityFtsq,
            'available_pieces' => $actualStockPieces,
            'available_quantity' => $actualStockQuantity,
            'available_quantity_ftsq' => $actualStockQuantityFtsq,
            'sale_price_per_leather' => $salePricePerLeather,
            'total_sale_price' => $totalRevenue,
            'total_revenue' => $totalRevenue,
            'average_revenue_per_leather' => 0.0, // Calcolato in recursive dopo aggregazione
            'average_ftsq_per_leather' => 0.0, // Calcolato in recursive dopo aggregazione
            'costs' => $costs,
            'sales' => $sales,
        ];
    }

    private function aggregateChildData(array &$parent, array $child): void
    {
        $parent['total_pieces'] += $child['total_pieces'];
        $parent['total_quantity'] += $child['total_quantity'];
        $parent['total_quantity_ftsq'] += $child['total_quantity_ftsq'];
        $parent['sold_pieces'] += $child['sold_pieces'];
        $parent['sold_quantity'] += $child['sold_quantity'];
        $parent['sold_quantity_ftsq'] += $child['sold_quantity_ftsq'];
        $parent['available_pieces'] += $child['available_pieces'];
        $parent['available_quantity'] += $child['available_quantity'];
        $parent['available_quantity_ftsq'] += $child['available_quantity_ftsq'];
        $parent['total_revenue'] += $child['total_revenue'];
        $parent['total_sale_price'] += $child['total_sale_price'];
        
        $parent['costs'] = array_merge($parent['costs'], $child['costs']);
        $parent['sales'] = array_merge($parent['sales'], $child['sales']);
    }

    private function convertToFtsq(float $quantity, ?MeasurementUnit $um): float
    {
        if (!$um) {
            return $quantity;
        }

        $prefix = $um->getPrefix();
        if ($prefix === 'MQ') {
            $coefficientUm = $um->getMeasurementUnitCoefficients()->first();
            if ($coefficientUm && $coefficientUm->getCoefficient() > 0) {
                // Se MQ, convertiamo in ftsq. 
                // Dalla logica in BatchController: sqFtAverageFound = pieces / (coeff * quantity)
                // Quindi coeff * quantity sembra essere la quantità in ftsq? 
                // Di solito 1 MQ = 10.764 Piedi Quadri. Se il coefficiente è ~0.0929, allora quantity / 0.0929 = ftsq.
                // O se coeff è 10.764, allora quantity * 10.764 = ftsq.
                // Basandoci su BatchController: $batch->getPieces() / ($coefficientUm->getCoefficient() * $batch->getQuantity())
                // Questo suggerisce che (coeff * quantity) è la superficie totale in PQ (ftsq).
                return $quantity * $coefficientUm->getCoefficient();
            }
        } elseif ($prefix === 'PQ') {
            return $quantity;
        }

        return $quantity;
    }


}
