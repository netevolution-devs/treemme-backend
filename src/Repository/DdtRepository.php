<?php

namespace App\Repository;

use App\Entity\Ddt;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Ddt>
 */
class DdtRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Ddt::class);
    }

//    /**
//     * @return Ddt[] Returns an array of Ddt objects
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

    public function findByFilters(?int $subcontractorId = null, ?int $clientId = null, ?\DateTime $startDate = null, ?\DateTime $endDate = null): array
    {
        $qb = $this->createQueryBuilder('d');

        if ($subcontractorId) {
            $qb->andWhere('IDENTITY(d.subcontractor) = :subcontractorId')
                ->setParameter('subcontractorId', $subcontractorId);
        }

        if ($clientId) {
            $qb->andWhere('IDENTITY(d.client) = :clientId')
                ->setParameter('clientId', $clientId);
        }

        if ($startDate) {
            $qb->andWhere('d.ddt_date >= :startDate')
                ->setParameter('startDate', $startDate);
        }

        if ($endDate) {
            // Se endDate non ha l'orario, consideriamo fino a fine giornata per includere i DDT del giorno indicato
            $endDate->setTime(23, 59, 59);
            $qb->andWhere('d.ddt_date <= :endDate')
                ->setParameter('endDate', $endDate);
        }

        return $qb->orderBy('d.ddt_date', 'DESC')
            ->addOrderBy('d.id', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
