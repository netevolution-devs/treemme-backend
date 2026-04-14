<?php

namespace App\Repository;

use App\Entity\Batch;
use App\Entity\BatchCost;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<BatchCost>
 */
class BatchCostRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BatchCost::class);
    }

    //    /**
    //     * @return BatchCost[] Returns an array of BatchCost objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('b')
    //            ->andWhere('b.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('b.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    public function hasCostWithType(Batch $batch, string $typeName): bool
    {
        return $this->createQueryBuilder('bc')
            ->select('COUNT(bc.id)')
            ->join('bc.batch_cost_type', 'bct')
            ->where('bc.batch = :batch')
            ->andWhere('bct.name = :typeName')
            ->setParameter('batch', $batch)
            ->setParameter('typeName', $typeName)
            ->getQuery()
            ->getSingleScalarResult() > 0;
    }
}
