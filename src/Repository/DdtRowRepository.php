<?php

namespace App\Repository;

use App\Entity\DdtRow;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DdtRow>
 */
class DdtRowRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DdtRow::class);
    }

    /**
     * @return DdtRow[] Returns an array of DdtRow objects that are in subcontracting and not yet returned
     */
    public function findSubcontractingNotReturned(): array
    {
        return $this->createQueryBuilder('dr')
            ->join('dr.ddt', 'd')
            ->join('d.reason', 'drn')
            ->join('drn.warehouse_movement_reason', 'wmrn')
            ->join('wmrn.reason_type', 'rt')
            ->join('dr.batch', 'b')
            ->leftJoin('b.warehouseMovements', 'wm', 'WITH', 'wm.date >= d.ddt_date AND wm.reason IN (
                SELECT r.id FROM App\Entity\WarehouseMovementReason r
                JOIN r.reason_type rt2
                WHERE rt2.movement_type = \'In\'
            )')
            ->andWhere('rt.movement_type = :out_type')
            ->andWhere('drn.name = :reason_name')
            ->setParameter('out_type', 'Out')
            ->setParameter('reason_name', 'C/O Lavorazione')
            ->groupBy('dr.id', 'd.ddt_date')
            ->having('dr.quantity > SUM(COALESCE(wm.quantity, 0))')
            ->getQuery()
            ->getResult();
    }

    //    /**
    //     * @return DdtRow[] Returns an array of DdtRow objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('d')
    //            ->andWhere('d.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('d.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?DdtRow
    //    {
    //        return $this->createQueryBuilder('d')
    //            ->andWhere('d.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
