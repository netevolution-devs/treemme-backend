<?php

namespace App\Repository;

use App\Entity\ClientOrder;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ClientOrder>
 */
class ClientOrderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ClientOrder::class);
    }

//    /**
//     * @return ClientOrder[] Returns an array of ClientOrder objects
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

    //    public function findOneBySomeField($value): ?ClientOrder
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }

    public function generateNextOrderNumber(): string
    {
        $yearPrefix = 'C' . date('y');

        $lastOrder = $this->createQueryBuilder('o')
            ->where('o.order_number LIKE :prefix')
            ->setParameter('prefix', $yearPrefix . '%')
            ->orderBy('o.order_number', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if (!$lastOrder || !$lastOrder->getOrderNumber()) {
            return $yearPrefix . '000001';
        }

        $lastNumberStr = substr($lastOrder->getOrderNumber(), strlen($yearPrefix));

        if (!is_numeric($lastNumberStr)) {
            return $yearPrefix . '000001';
        }

        $lastNumber = (int) $lastNumberStr;
        $nextNumber = $lastNumber + 1;

        return $yearPrefix . str_pad((string) $nextNumber, 6, '0', STR_PAD_LEFT);
    }
}
