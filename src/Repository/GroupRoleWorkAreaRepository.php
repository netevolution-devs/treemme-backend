<?php

namespace App\Repository;

use App\Entity\GroupRoleWorkArea;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<GroupRoleWorkArea>
 */
class GroupRoleWorkAreaRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GroupRoleWorkArea::class);
    }
}
