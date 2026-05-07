<?php

namespace App\Repository;

use App\Entity\WarehouseMovement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<WarehouseMovement>
 */
class WarehouseMovementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WarehouseMovement::class);
    }

//    /**
//     * @return WarehouseMovement[] Returns an array of WarehouseMovement objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('w')
//            ->andWhere('w.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('w.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?WarehouseMovement
//    {
//        return $this->createQueryBuilder('w')
//            ->andWhere('w.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
    public function findExternalProcessingMovements(?int $subcontractorId = null, ?\DateTime $startDate = null, ?\DateTime $endDate = null): array
    {
        $qb = $this->createQueryBuilder('wm')
            ->join('wm.reason', 'r')
            ->join('r.reason_type', 'rt')
            ->andWhere('r.name IN (:reasons)')
            ->setParameter('reasons', ['C/O Lavorazione', 'Reso C/O Lavorazione']);

        if ($subcontractorId) {
            $qb->andWhere('IDENTITY(wm.contact) = :subcontractorId')
                ->setParameter('subcontractorId', $subcontractorId);
        }

        if ($startDate) {
            $qb->andWhere('wm.date >= :startDate')
                ->setParameter('startDate', $startDate);
        }

        if ($endDate) {
            $endDate->setTime(23, 59, 59);
            $qb->andWhere('wm.date <= :endDate')
                ->setParameter('endDate', $endDate);
        }

        $qb->orderBy('wm.date', 'ASC')
            ->addOrderBy('wm.id', 'ASC');

        return $qb->getQuery()->getResult();
    }
}
