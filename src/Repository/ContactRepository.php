<?php

namespace App\Repository;

use App\Entity\Contact;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Contact>
 */
class ContactRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Contact::class);
    }

    //    /**
    //     * @return Contact[] Returns an array of Contact objects
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

    //    public function findOneBySomeField($value): ?Contact
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
    public function searchContacts(?string $name, ?string $detailName): array
    {
        $qb = $this->createQueryBuilder('c');

        if ($name) {
            $qb->andWhere('c.name LIKE :name')
                ->setParameter('name', '%' . $name . '%');
        }

        if ($detailName) {
            $qb->leftJoin('c.contactDetails', 'cd')
                ->andWhere('cd.name LIKE :detailName')
                ->setParameter('detailName', '%' . $detailName . '%');
        }

        return $qb->orderBy('c.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function generateNextCode(): string
    {
        $lastContact = $this->createQueryBuilder('c')
            ->where('c.code LIKE :prefix')
            ->setParameter('prefix', 'C%')
            ->orderBy('c.code', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if (!$lastContact || !$lastContact->getCode()) {
            return 'C000001';
        }

        $lastCode = $lastContact->getCode();
        $lastNumber = (int)substr($lastCode, 1);
        $nextNumber = $lastNumber + 1;

        return 'C' . str_pad((string)$nextNumber, 6, '0', STR_PAD_LEFT);
    }
}
