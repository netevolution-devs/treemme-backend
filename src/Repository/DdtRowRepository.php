<?php

namespace App\Repository;

use App\Entity\DdtRow;
use App\Entity\WarehouseMovement;
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
    public function findSoldLots(?int $clientId = null, ?\DateTime $startDate = null, ?\DateTime $endDate = null, ?string $batchCode = null): array
    {
        $qb = $this->createQueryBuilder('dr')
            ->join('dr.ddt', 'd')
            ->join('d.reason', 'r')
            ->leftJoin('d.batch', 'b')
            ->andWhere('r.name = :reasonName')
            ->setParameter('reasonName', 'Vendita');

        if ($batchCode) {
            $qb->andWhere('b.batch_code = :batchCode')
                ->setParameter('batchCode', $batchCode);
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
            $endDate->setTime(23, 59, 59);
            $qb->andWhere('d.ddt_date <= :endDate')
                ->setParameter('endDate', $endDate);
        }

        return $qb->getQuery()->getResult();
    }

    public function findExternalProcessingLots(?int $subcontractorId = null, ?\DateTime $startDate = null, ?\DateTime $endDate = null, ?string $batchCode = null): array
    {
        $qb = $this->createQueryBuilder('dr')
            ->join('dr.ddt', 'd')
            ->join('d.reason', 'r')
            ->join('dr.batch', 'b')
            ->andWhere('r.name = :reasonName')
            ->setParameter('reasonName', 'C/O Lavorazione');

        if ($subcontractorId) {
            $qb->andWhere('IDENTITY(d.subcontractor) = :subcontractorId')
                ->setParameter('subcontractorId', $subcontractorId);
        }

        if ($startDate) {
            $qb->andWhere('d.ddt_date >= :startDate')
                ->setParameter('startDate', $startDate);
        }

        if ($endDate) {
            $endDate->setTime(23, 59, 59);
            $qb->andWhere('d.ddt_date <= :endDate')
                ->setParameter('endDate', $endDate);
        }

        if ($batchCode) {
            $normalizedCode = str_replace('0', '', $batchCode);
            $qb->andWhere("REPLACE(b.batch_code, '0', '') LIKE :code")
                ->setParameter('code', '%' . $normalizedCode . '%');
        }

        return $qb->getQuery()->getResult();
    }

    public function findSubcontractingNotReturned(?int $subcontractorId = null, ?\DateTime $startDate = null, ?\DateTime $endDate = null, ?string $batchCode = null): array
    {
        $qb = $this->createQueryBuilder('dr')
            ->join('dr.ddt', 'd')
            ->join('d.reason', 'r')
            ->join('dr.batch', 'b')
            ->andWhere('r.name != :vendita')
            ->setParameter('vendita', 'Vendita');

        if ($subcontractorId) {
            $qb->andWhere('IDENTITY(d.subcontractor) = :subcontractorId')
                ->setParameter('subcontractorId', $subcontractorId);
        }

        if ($startDate) {
            $qb->andWhere('d.ddt_date >= :startDate')
                ->setParameter('startDate', $startDate);
        }

        if ($endDate) {
            $endDate->setTime(23, 59, 59);
            $qb->andWhere('d.ddt_date <= :endDate')
                ->setParameter('endDate', $endDate);
        }

        if ($batchCode) {
            $normalizedCode = str_replace('0', '', $batchCode);
            $qb->andWhere("REPLACE(b.batch_code, '0', '') LIKE :code")
                ->setParameter('code', '%' . $normalizedCode . '%');
        }

        return $qb->getQuery()->getResult();
    }
}
