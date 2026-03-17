<?php

namespace App\Repository;

use App\Entity\Leather;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Leather>
 */
class LeatherRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Leather::class);
    }

    //    /**
    //     * @return Leather[] Returns an array of Leather objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('l')
    //            ->andWhere('l.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('l.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    public function findWithFilters(array $filters): array
    {
        $qb = $this->createQueryBuilder('l');

        foreach ($filters as $field => $value) {
            if ($value !== null && $value !== '') {
                if ($field === 'provenance_area') {
                    $qb->join('l.provenance', 'p')
                        ->andWhere('p.area = :provenance_area')
                        ->setParameter('provenance_area', $value);
                } else {
                    $qb->andWhere(sprintf('l.%s = :%s', $field, $field))
                        ->setParameter($field, $value);
                }
            }
        }

        return $qb->orderBy('l.id', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
