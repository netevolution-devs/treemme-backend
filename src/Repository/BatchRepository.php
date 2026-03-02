<?php

namespace App\Repository;

use App\Entity\Batch;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Batch>
 */
class BatchRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Batch::class);
    }

    //    /**
    //     * @return Batch[] Returns an array of Batch objects
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

    public function findAvailableStock(): array
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.stock_items > 0')
            ->orderBy('b.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findLatestBatchByPrefix(string $prefix): ?Batch
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.batch_code LIKE :prefix')
            ->setParameter('prefix', $prefix . '%')
            ->orderBy('b.batch_code', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
