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
        return $this->createQueryBuilder('cor')
            ->join('cor.client_order', 'co')
            ->where('cor.processed = false')
            ->andWhere('cor.cancelled = false')
            ->andWhere('co.cancelled = false')
            ->andWhere('co.printed = false OR co.printed IS NULL')
            ->orderBy('co.order_date', 'ASC')
            ->addOrderBy('co.order_number', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
