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

        $quantityFt = $this->convertToFtsq($batch->getQuantity(), $batch->getMeasurementUnit());

        if ($batch->getPieces() > 0 && $quantityFt > 0) {
            $data['report']['average_ftsq_per_leather'] = $quantityFt / $batch->getPieces();
        } elseif ($data['report']['sold_pieces'] > 0 && $data['report']['sold_quantity_ftsq'] > 0) {
            $data['report']['average_ftsq_per_leather'] = $data['report']['sold_quantity_ftsq'] / $data['report']['sold_pieces'];
        }

        // Calcolo costo fiore: (costo totale / pezzi totali) - (ricavo della crosta / pezzi totali)
        if ($batch->getPieces() > 0) {
            $totalCosts = $data['report']['total_costs'] ?? 0.0;
            $scRevenue = $data['report']['sc_sale_revenue_euro_mq'] ?? 0.0;
            $totalPieces = $batch->getPieces();

            $flowerCostEuro = ($totalCosts / $totalPieces) - ($scRevenue / $totalPieces);
            $data['report']['flower_cost_euro_mq'] = $flowerCostEuro;
            $data['report']['flower_cost_lire_pq'] = $flowerCostEuro * 1936.27;
        }

        // Resa totale vendita fiore SF->TF->Vendita
        // Se il lotto corrente è SF e ha figli (che dovrebbero essere TF), aggreghiamo le loro vendite
        if (str_starts_with($batch->getBatchCode() ?? '', 'SF')) {
            $data['report']['flower_total_revenue'] = $data['report']['total_revenue'];
            $data['report']['flower_total_revenue_lire'] = $data['report']['flower_total_revenue'] * 1936.27;
        }

        return $data;
    }

    private function collectBatchData(Batch $batch): array
    {
        $totalPieces = $batch->getPieces() ?? 0;
        $totalQuantity = $batch->getQuantity() ?? 0.0;
        $um = $batch->getMeasurementUnit();
        $isMq = ($um?->getPrefix() === 'MQ');
        
        $totalQuantityFtsq = $this->convertToFtsq($totalQuantity, $um);

        $soldPieces = 0;
        $soldQuantity = 0.0;
        $totalRevenue = 0.0;
        $sales = [];

        foreach ($batch->getDdtRows() as $row) {
            $ddt = $row->getDdt();
            if ($ddt?->getReason()?->getName() !== 'Vendita') {
                continue;
            }

            $soldPieces += ($row->getPieces() ?? 0);
            $soldQuantity += ($row->getQuantity() ?? 0.0);
            $totalRevenue += ($row->getTotalValue() ?? 0.0);

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
            $pieces = (float)($mov->getPiece() ?? 0.0);
            $quantity = (float)($mov->getQuantity() ?? 0.0);

            if ($mov->isOutgoing()) {
                $actualStockPieces -= abs($pieces);
                $actualStockQuantity -= abs($quantity);
            } else {
                $actualStockPieces += abs($pieces);
                $actualStockQuantity += abs($quantity);
            }
        }

        $actualStockQuantityFtsq = $this->convertToFtsq($actualStockQuantity, $um);

        $salePricePerLeather = 0.0;
        if ($soldPieces > 0) {
            $salePricePerLeather = $totalRevenue / $soldPieces;
        }

        $totalCosts = 0.0;
        $costs = [];
        foreach ($batch->getBatchCosts() as $cost) {
            $costAmount = $cost->getCost() ?? 0.0;
            $totalCosts += $costAmount;
            $costs[] = [
                'date' => $cost->getDate()?->format('Y-m-d'),
                'type' => $cost->getBatchCostType()?->getName(),
                'amount' => $costAmount,
                'currency' => $cost->getCurrency()?->getAbbreviation(),
                'note' => $cost->getCostNote(),
            ];
        }

        $sqFtExpected = $batch->getSqFtAverageExpected() ?? 0.0;
        $sqFtFound = $batch->getSqFtAverageFound() ?? 0.0;
        $sqFtDiff = $sqFtFound - $sqFtExpected;

        $costPerPieceEuroMq = 0.0;
        $costPerPieceLirePq = 0.0;
        if ($totalPieces > 0) {
            $costPerPiece = $totalCosts / $totalPieces;
            $costPerPieceEuroMq = $costPerPiece; // Valore in Euro
            $costPerPieceLirePq = $costPerPiece * 1936.27; // Valore in Lire
        }

        // Resa al pezzo in € e lire
        $revenuePerPieceEuroMq = 0.0;
        $revenuePerPieceLirePq = 0.0;
        if ($soldPieces > 0) {
            $revenuePerPiece = $totalRevenue / $soldPieces;
            $revenuePerPieceEuroMq = $revenuePerPiece;
            $revenuePerPieceLirePq = $revenuePerPiece * 1936.27;
        }

        // Resa della vendita del lotto SC
        $scSaleRevenueEuroMq = 0.0;
        if ($batch->getBatchCode() && str_starts_with($batch->getBatchCode(), 'SC')) {
            $scSaleRevenueEuroMq = $totalRevenue;
        }
            
        return [
            'id' => $batch->getId(),
            'code' => $batch->getBatchCode(),
            'report' => [
                'sold_pieces' => $soldPieces,
                'sold_quantity' => $soldQuantity,
                'sold_quantity_ftsq' => $soldQuantityFtsq,
                'available_pieces' => $actualStockPieces,
                'available_quantity' => $actualStockQuantity,
                'available_quantity_ftsq' => $actualStockQuantityFtsq,
                'sale_price_per_leather' => $salePricePerLeather,
                'total_sale_price' => $totalRevenue,
                'total_revenue' => $totalRevenue,
                'total_costs' => $totalCosts,
                'average_revenue_per_leather' => 0.0, // Calcolato in recursive dopo aggregazione
                'average_ftsq_per_leather' => 0.0, // Calcolato in recursive dopo aggregazione
                
                'sq_ft_average_expected' => $sqFtExpected,
                'sq_ft_average_found' => $sqFtFound,
                'sq_ft_average_diff' => $sqFtDiff,
                
                'cost_per_piece_euro_mq' => $costPerPieceEuroMq,
                'cost_per_piece_lire_pq' => $costPerPieceLirePq,
                
                'revenue_per_piece_euro_mq' => $revenuePerPieceEuroMq,
                'revenue_per_piece_lire_pq' => $revenuePerPieceLirePq,
                
                'sc_sale_revenue_euro_mq' => $scSaleRevenueEuroMq,
                'sc_sale_revenue_lire_pq' => $scSaleRevenueEuroMq * 1936.27,
                
                'flower_cost_euro_mq' => 0.0, // Calcolato in recursive dopo aggregazione
                'flower_cost_lire_pq' => 0.0, // Calcolato in recursive dopo aggregazione

                'compensation_waste' => $batch->getCompensationWaste() ?? 0.0,
                
                'flower_total_revenue' => 0.0, // Aggregato ricorsivamente
                'flower_total_revenue_lire' => 0.0,
            ],
            'costs' => $costs,
            'sales' => $sales,
        ];
    }

    private function aggregateChildData(array &$parent, array $child): void
    {
        $parent['report']['sold_pieces'] += $child['report']['sold_pieces'];
        $parent['report']['sold_quantity'] += $child['report']['sold_quantity'];
        $parent['report']['sold_quantity_ftsq'] += $child['report']['sold_quantity_ftsq'];
        $parent['report']['available_pieces'] += $child['report']['available_pieces'];
        $parent['report']['available_quantity'] += $child['report']['available_quantity'];
        $parent['report']['available_quantity_ftsq'] += $child['report']['available_quantity_ftsq'];
        $parent['report']['total_revenue'] += $child['report']['total_revenue'];
        $parent['report']['total_sale_price'] += $child['report']['total_sale_price'];
        $parent['report']['total_costs'] += ($child['report']['total_costs'] ?? 0.0);
        $parent['report']['compensation_waste'] += ($child['report']['compensation_waste'] ?? 0.0);

        // Aggregazione specifica per il fiore (SF->TF->Vendita)
        // Se il padre è un SF, accumuliamo i ricavi dei figli
        if (str_starts_with($parent['code'] ?? '', 'SF')) {
            $parent['report']['flower_total_revenue'] += $child['report']['total_revenue'];
            $parent['report']['flower_total_revenue_lire'] = $parent['report']['flower_total_revenue'] * 1936.27;
        }

        // Aggregazione specifica per SC (SC->Vendita)
        if (str_starts_with($parent['code'] ?? '', 'SC')) {
            $parent['report']['sc_sale_revenue_euro_mq'] += $child['report']['total_revenue'];
            $parent['report']['sc_sale_revenue_lire_pq'] = $parent['report']['sc_sale_revenue_euro_mq'] * 1936.27;
        }
        
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
            foreach ($um->getMeasurementUnitCoefficients() as $coeff) {
                if ($coeff->getEndUm() && $coeff->getEndUm()->getPrefix() === 'PQ') {
                    return $quantity * $coeff->getCoefficient();
                }
            }
            // Fallback: cerca il primo coefficiente utile se non ne trova uno specifico per PQ
            $coefficientUm = $um->getMeasurementUnitCoefficients()->first();
            if ($coefficientUm && $coefficientUm->getCoefficient() > 0) {
                return $quantity * $coefficientUm->getCoefficient();
            }
            // Default conversion factor if no coefficient is found: 1 MQ = 10.764 PQ
            return $quantity * 10.764;
        } elseif ($prefix === 'PQ') {
            return $quantity;
        }

        return $quantity;
    }


}
