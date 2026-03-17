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
        $qb = $this->createQueryBuilder('dr');

        return $qb
            ->join('dr.ddt', 'd')
            ->join('d.reason', 'drn')
            ->join('dr.batch', 'b')
            ->join('b.warehouseMovements', 'wm')
            ->join('wm.reason', 'wmr')
            ->join('wmr.reason_type', 'rt')
            ->andWhere('drn.name = :ddtReasonName')
            ->andWhere('wmr.name = :movementReasonName')
            ->andWhere('rt.name = :movementType')
            ->setParameter('ddtReasonName', 'C/O Lavorazione')
            ->setParameter('movementReasonName', 'C/O Lavorazione')
            ->setParameter('movementType', 'Scarico')
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
