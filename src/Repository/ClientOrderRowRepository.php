<?php

namespace App\Repository;

use App\Entity\ClientOrderRow;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ClientOrderRow>
 */
class ClientOrderRowRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ClientOrderRow::class);
    }

//    /**
//     * @return ClientOrderRow[] Returns an array of ClientOrderRow objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('c')
//            ->andWhere('c.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('c.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

    public function findMaxWeightByOrder($orderId): int
    {
        return (int) $this->createQueryBuilder('cor')
            ->select('MAX(cor.weight)')
            ->where('cor.client_order = :orderId')
            ->setParameter('orderId', $orderId)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Calcola la quantità da spedire per una riga ordine.
     * Somma la quantità dei lotti associati (BatchOrder)
     * e sottrae la quantità già presente in DDT di spedizione confermati.
     */
    public function calculateQuantityToShip(int $clientOrderRowId): float
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        // 1. Somma quantità totale dei lotti associati
        $totalBatchQuantity = (float) $qb->select('SUM(b.quantity)')
            ->from(\App\Entity\BatchOrder::class, 'bo')
            ->join('bo.batch', 'b')
            ->where('bo.order_row = :rowId')
            ->setParameter('rowId', $clientOrderRowId)
            ->getQuery()
            ->getSingleScalarResult();

        // 2. Somma quantità già spedita (DDT con is_shipment_reason = true)
        $qb2 = $this->getEntityManager()->createQueryBuilder();
        $shippedQuantity = (float) $qb2->select('SUM(dr.quantity)')
            ->from(\App\Entity\DdtRow::class, 'dr')
            ->join('dr.ddt', 'd')
            ->join('d.reason', 'r')
            ->join(\App\Entity\BatchOrder::class, 'bo', 'WITH', 'bo.batch = dr.batch')
            ->where('bo.order_row = :rowId')
            ->andWhere('r.is_shipment_reason = true')
            ->setParameter('rowId', $clientOrderRowId)
            ->getQuery()
            ->getSingleScalarResult();

        return max(0.0, $totalBatchQuantity - $shippedQuantity);
    }
    public function findNotProduced(): array
    {
        return $this->findByProductionCriteria();
    }

    public function findByProductionCriteria(?\DateTime $startDate = null, ?\DateTime $endDate = null, ?string $printedStatus = 'to_print'): array
    {
        $qb = $this->createQueryBuilder('cor')
            ->join('cor.client_order', 'co')
            ->where('cor.processed = false')
            ->andWhere('cor.cancelled = false')
            ->andWhere('co.cancelled = false');

        if ($startDate) {
            $qb->andWhere('co.order_date >= :startDate')
                ->setParameter('startDate', $startDate->format('Y-m-d'));
        }

        if ($endDate) {
            $qb->andWhere('co.order_date <= :endDate')
                ->setParameter('endDate', $endDate->format('Y-m-d'));
        }

        if ($printedStatus === 'printed') {
            $qb->andWhere('co.printed = true');
        } elseif ($printedStatus === 'to_print') {
            $qb->andWhere('(co.printed = false OR co.printed IS NULL)');
        }
        // Se 'all', non aggiungiamo filtri su co.printed

        return $qb->orderBy('co.order_date', 'ASC')
            ->addOrderBy('co.order_number', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Recupera tutte le righe ordine che hanno almeno un lotto associato (BatchOrder / TF / UF)
     * e che hanno ancora una quantità non spedita (quantity_to_ship > 0).
     */
    public function findRowsToShipForSchedule(?\DateTime $startDate = null, ?\DateTime $endDate = null, ?int $clientId = null, ?string $batchType = null): array
    {
        $qb = $this->createQueryBuilder('cor')
            ->select('cor', 'co', 'c', 'a', 'bo', 'b')
            ->innerJoin('cor.batchOrders', 'bo')
            ->innerJoin('bo.batch', 'b')
            ->leftJoin('b.batch_type', 'bt')
            ->innerJoin('cor.client_order', 'co')
            ->innerJoin('co.client', 'c')
            ->leftJoin('cor.article', 'a')
            ->leftJoin('cor.measurement_unit', 'mu')
            ->where('cor.cancelled = false')
            ->andWhere('co.cancelled = false')
            ->andWhere('cor.processed = false')
            ->andWhere('(cor.quantity_to_ship > 0 OR cor.quantity_to_ship IS NULL)');

        if ($batchType) {
            $qb->andWhere('bt.code = :batchType OR bt.name = :batchType')
               ->setParameter('batchType', $batchType);
        }

        if ($startDate) {
            $qb->andWhere('cor.delivery_date_confirmed >= :startDate OR (cor.delivery_date_confirmed IS NULL AND co.order_date >= :startDate)')
                ->setParameter('startDate', $startDate->format('Y-m-d'));
        }

        if ($endDate) {
            $qb->andWhere('cor.delivery_date_confirmed <= :endDate OR (cor.delivery_date_confirmed IS NULL AND co.order_date <= :endDate)')
                ->setParameter('endDate', $endDate->format('Y-m-d 23:59:59'));
        }

        if ($clientId) {
            $qb->andWhere('c.id = :clientId')
                ->setParameter('clientId', $clientId);
        }

        return $qb->orderBy('c.name', 'ASC')
            ->addOrderBy('co.order_date', 'ASC')
            ->addOrderBy('co.order_number', 'ASC')
            ->addOrderBy('cor.weight', 'ASC')
            ->addOrderBy('cor.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Calcola i prezzi medi ponderati per PQ e MQ e i totali per un intervallo di date
     * (es. una settimana specifica) o per data ordine / data consegna.
     * Raggruppa i risultati per tipo di pellame (LeatherType) e valuta (Currency).
     * Di default esclude i clienti interni ($internal = false).
     */
    public function getWeeklyPriceAverages(
        ?\DateTimeInterface $startDate = null,
        ?\DateTimeInterface $endDate = null,
        ?int $clientId = null,
        string $dateField = 'order_date', // 'order_date' oppure 'delivery_date_confirmed'
        ?bool $internal = false // false: escludi interni, true: solo interni, null: tutti
    ): array {
        $qb = $this->createQueryBuilder('cor')
            ->select('
                lt.id as leather_type_id,
                lt.name as leather_type_name,
                lt.code as leather_type_code,
                curr.id as currency_id,
                curr.name as currency_name,
                curr.abbreviation as currency_abbreviation,
                curr.sign as currency_sign,
                COUNT(cor.id) as rows_count,
                SUM(cor.total_price) as total_amount,
                SUM(cor.total_currency_price) as total_currency_amount,
                SUM(cor.quantity_mq) as total_quantity_mq,
                SUM(cor.quantity_pq) as total_quantity_pq
            ')
            ->join('cor.client_order', 'co')
            ->leftJoin('co.client', 'c')
            ->leftJoin('cor.currency', 'curr')
            ->leftJoin('cor.article', 'a')
            ->leftJoin('a.article_type', 'at')
            ->leftJoin('at.leather_type', 'lt')
            ->where('cor.cancelled = false')
            ->andWhere('co.cancelled = false');

        if ($internal === true) {
            $qb->andWhere('c.is_internal = true');
        } elseif ($internal === false) {
            $qb->andWhere('(c.is_internal = false OR c.is_internal IS NULL)');
        }

        if ($clientId) {
            $qb->andWhere('co.client = :clientId')
                ->setParameter('clientId', $clientId);
        }

        $dateColumn = ($dateField === 'delivery_date_confirmed') ? 'cor.delivery_date_confirmed' : 'co.order_date';

        if ($startDate) {
            $qb->andWhere("$dateColumn >= :startDate")
                ->setParameter('startDate', $startDate->format('Y-m-d 00:00:00'));
        }

        if ($endDate) {
            $qb->andWhere("$dateColumn <= :endDate")
                ->setParameter('endDate', $endDate->format('Y-m-d 23:59:59'));
        }

        $qb->groupBy('lt.id, lt.name, lt.code, curr.id, curr.name, curr.abbreviation, curr.sign')
           ->orderBy('lt.name', 'ASC')
           ->addOrderBy('curr.name', 'ASC');

        $results = $qb->getQuery()->getResult();

        $rows = [];
        $grandTotalAmount = 0.0;
        $grandTotalQuantityMq = 0.0;
        $grandTotalQuantityPq = 0.0;
        $grandTotalRowsCount = 0;

        foreach ($results as $res) {
            $totalAmount = (float)($res['total_amount'] ?? 0.0);
            $totalCurrencyAmount = (float)($res['total_currency_amount'] ?? 0.0);
            $totalQuantityMq = (float)($res['total_quantity_mq'] ?? 0.0);
            $totalQuantityPq = (float)($res['total_quantity_pq'] ?? 0.0);
            $rowsCount = (int)($res['rows_count'] ?? 0);

            $avgPriceMqBase = $totalQuantityMq > 0 ? round($totalAmount / $totalQuantityMq, 4) : 0.0;
            $avgPricePqBase = $totalQuantityPq > 0 ? round($totalAmount / $totalQuantityPq, 4) : 0.0;
            $avgPriceMqCurrency = $totalQuantityMq > 0 ? round($totalCurrencyAmount / $totalQuantityMq, 4) : 0.0;
            $avgPricePqCurrency = $totalQuantityPq > 0 ? round($totalCurrencyAmount / $totalQuantityPq, 4) : 0.0;

            $rows[] = [
                'leather_type' => $res['leather_type_id'] ? [
                    'id' => (int)$res['leather_type_id'],
                    'name' => $res['leather_type_name'],
                    'code' => $res['leather_type_code'],
                ] : null,
                'currency' => $res['currency_id'] ? [
                    'id' => (int)$res['currency_id'],
                    'name' => $res['currency_name'],
                    'abbreviation' => $res['currency_abbreviation'],
                    'sign' => $res['currency_sign'],
                ] : null,
                'rows_count' => $rowsCount,
                'total_quantity_mq' => round($totalQuantityMq, 4),
                'total_quantity_pq' => round($totalQuantityPq, 4),
                'avg_price_pq_currency' => $avgPricePqCurrency,
                'avg_price_pq_lire' => $avgPricePqBase,
                'avg_price_mq_currency' => $avgPriceMqCurrency,
                'avg_price_mq_lire' => $avgPriceMqBase,
                'avg_price_mq' => $avgPriceMqBase,
                'avg_price_pq' => $avgPricePqBase,
            ];

            $grandTotalAmount += $totalAmount;
            $grandTotalQuantityMq += $totalQuantityMq;
            $grandTotalQuantityPq += $totalQuantityPq;
            $grandTotalRowsCount += $rowsCount;
        }

        $overallAvgPriceMq = $grandTotalQuantityMq > 0 ? round($grandTotalAmount / $grandTotalQuantityMq, 4) : 0.0;
        $overallAvgPricePq = $grandTotalQuantityPq > 0 ? round($grandTotalAmount / $grandTotalQuantityPq, 4) : 0.0;

        return [
            'items' => $rows,
            'summary' => [
                'rows_count' => $grandTotalRowsCount,
                'total_amount' => round($grandTotalAmount, 2),
                'total_quantity_mq' => round($grandTotalQuantityMq, 4),
                'total_quantity_pq' => round($grandTotalQuantityPq, 4),
                'avg_price_pq_lire' => $overallAvgPricePq,
                'avg_price_mq_lire' => $overallAvgPriceMq,
                'avg_price_mq' => $overallAvgPriceMq,
                'avg_price_pq' => $overallAvgPricePq,
            ],
            'start_date' => $startDate ? $startDate->format('Y-m-d') : null,
            'end_date' => $endDate ? $endDate->format('Y-m-d') : null,
            'internal' => $internal,
        ];
    }
}
