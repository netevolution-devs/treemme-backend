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
        if ($data['report']['sold_pieces'] > 0) {
            $data['report']['average_revenue_per_leather'] = $data['report']['total_revenue'] / $data['report']['sold_pieces'];
        }

        if ($data['report']['sold_pieces'] > 0 && $data['report']['sold_quantity_ftsq'] > 0) {
             // L'utente chiede la media dei ftsq per pelle (ftsq totali / pezzi totali)
             $data['report']['average_ftsq_per_leather'] = $data['report']['sold_quantity_ftsq'] / $data['report']['sold_pieces'];
        } elseif ($data['report']['total_pieces'] > 0 && $data['report']['total_quantity_ftsq'] > 0) {
             // Se non venduto, usiamo i dati totali del lotto per la media ftsq
             $data['report']['average_ftsq_per_leather'] = $data['report']['total_quantity_ftsq'] / $data['report']['total_pieces'];
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
            'report' => [
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
            ],
            'costs' => $costs,
            'sales' => $sales,
        ];
    }

    private function aggregateChildData(array &$parent, array $child): void
    {
        $parent['report']['total_pieces'] += $child['report']['total_pieces'];
        $parent['report']['total_quantity'] += $child['report']['total_quantity'];
        $parent['report']['total_quantity_ftsq'] += $child['report']['total_quantity_ftsq'];
        $parent['report']['sold_pieces'] += $child['report']['sold_pieces'];
        $parent['report']['sold_quantity'] += $child['report']['sold_quantity'];
        $parent['report']['sold_quantity_ftsq'] += $child['report']['sold_quantity_ftsq'];
        $parent['report']['available_pieces'] += $child['report']['available_pieces'];
        $parent['report']['available_quantity'] += $child['report']['available_quantity'];
        $parent['report']['available_quantity_ftsq'] += $child['report']['available_quantity_ftsq'];
        $parent['report']['total_revenue'] += $child['report']['total_revenue'];
        $parent['report']['total_sale_price'] += $child['report']['total_sale_price'];
        
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
