<?php

namespace App\Controller;

use App\Entity\Batch;
use App\Entity\BatchComposition;
use App\Entity\BatchCost;
use App\Entity\DdtRow;
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
        $data['children'] = [];

        foreach ($batch->getSonBatches() as $composition) {
            $sonBatch = $composition->getBatch();
            if ($sonBatch) {
                $data['children'][] = $this->getBatchRecursiveData($sonBatch);
            }
        }

        // Dopo aver raccolto i dati dei figli, calcoliamo i totali per questo nodo (inclusi i figli)
        $this->aggregateCalculatedData($data);

        return $data;
    }

    private function collectBatchData(Batch $batch): array
    {
        $batchData = [
            'id' => $batch->getId(),
            'code' => $batch->getBatchCode(),
            'date' => $batch->getBatchDate()?->format('Y-m-d'),
            'type' => $batch->getBatchType()?->getName(),
            'pieces' => $batch->getPieces(),
            'quantity' => $batch->getQuantity(),
            'um' => $batch->getMeasurementUnit()?->getName(),
            'leather' => $batch->getLeather()?->getName(),
            'article' => $batch->getArticle()?->getName(),
            'movements' => [],
            'costs' => [],
            'sales' => [],
            'orders' => [],
            'productions' => [],
            'selections' => [],
            'calculated' => [
                'total_revenue' => 0.0,
                'total_cost' => 0.0,
                'current_stock_pieces' => 0,
                'current_stock_quantity' => 0.0,
            ]
        ];

        // Movimenti
        foreach ($batch->getWarehouseMovements() as $mov) {
            $batchData['movements'][] = [
                'date' => $mov->getDate()?->format('Y-m-d'),
                'reason' => $mov->getReason()?->getName(),
                'contact' => $mov->getContact()?->getName(),
                'pieces' => $mov->getPiece(),
                'quantity' => $mov->getQuantity(),
                'note' => $mov->getMovementNote(),
            ];
            $batchData['calculated']['current_stock_pieces'] += ($mov->getPiece() ?? 0);
            $batchData['calculated']['current_stock_quantity'] += ($mov->getQuantity() ?? 0.0);
        }

        // Costi
        foreach ($batch->getBatchCosts() as $cost) {
            $batchData['costs'][] = [
                'date' => $cost->getDate()?->format('Y-m-d'),
                'type' => $cost->getBatchCostType()?->getName(),
                'amount' => $cost->getCost(),
                'currency' => $cost->getCurrency()?->getAbbreviation(),
                'note' => $cost->getCostNote(),
            ];
            $batchData['calculated']['total_cost'] += ($cost->getCost() ?? 0.0);
        }

        // Vendite (DdtRow)
        foreach ($batch->getDdtRows() as $row) {
            $ddt = $row->getDdt();
            $batchData['sales'][] = [
                'ddt_number' => $ddt?->getDdtNumber(),
                'ddt_date' => $ddt?->getDdtDate()?->format('Y-m-d'),
                'client' => $ddt?->getClient()?->getName() ?? $ddt?->getSubcontractor()?->getName(),
                'pieces' => $row->getPieces(),
                'quantity' => $row->getQuantity(),
                'total_value' => $row->getTotalValue(),
                'note' => $row->getRowNote(),
            ];
            $batchData['calculated']['total_revenue'] += ($row->getTotalValue() ?? 0.0);
        }

        // Ordini (BatchOrder)
        foreach ($batch->getBatchOrders() as $batchOrder) {
            $orderRow = $batchOrder->getOrderRow();
            if ($orderRow) {
                $clientOrder = $orderRow->getClientOrder();
                $batchData['orders'][] = [
                    'order_number' => $clientOrder?->getOrderNumber(),
                    'client' => $clientOrder?->getClient()?->getName(),
                    'pieces' => $orderRow->getQuantity(), // ClientOrderRow uses quantity for pieces often, or getQuantity
                    'price' => $orderRow->getPrice(),
                    'total_price' => $orderRow->getTotalPrice(),
                ];
            }
        }

        // Produzioni (Conto lavori)
        foreach ($batch->getProductions() as $prod) {
            $batchData['productions'][] = [
                'date' => $prod->getScheduledDate()?->format('Y-m-d'),
                'machine' => $prod->getMachine()?->getName(),
                'note' => $prod->getProductionNote(),
            ];
        }

        // Selezioni
        foreach ($batch->getBatchSelections() as $sel) {
            $batchData['selections'][] = [
                'selection' => $sel->getSelection()?->getName(),
                'pieces' => $sel->getPieces(),
                'quantity' => $sel->getQuantity(),
                'thickness' => $sel->getThickness()?->getName(),
                'note' => $sel->getNote(),
            ];
        }

        return $batchData;
    }

    private function aggregateCalculatedData(array &$data): void
    {
        // I dati 'calculated' del nodo corrente sono già stati inizializzati in collectBatchData
        // Ora aggiungiamo i dati aggregati dei figli se necessario.
        // Se il requisito "totale" si riferisce all'intero albero partendo dal nodo corrente:
        
        foreach ($data['children'] as $child) {
            $data['calculated']['total_revenue'] += $child['calculated']['total_revenue'];
            $data['calculated']['total_cost'] += $child['calculated']['total_cost'];
            // La giacenza solitamente è specifica del lotto, ma se serve il totale del ramo:
            // $data['calculated']['current_stock_pieces'] += $child['calculated']['current_stock_pieces'];
            // $data['calculated']['current_stock_quantity'] += $child['calculated']['current_stock_quantity'];
        }
    }


}
