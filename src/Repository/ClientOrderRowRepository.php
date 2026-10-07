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
}
