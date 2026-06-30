<?php

namespace App\Service;

use App\Entity\RefreshToken;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

class UserService
{
    private Security $security;
    private EntityManagerInterface $doctrine;

    public function __construct(
        Security               $security,
        EntityManagerInterface $entityManager,
    )
    {
        $this->security = $security;
        $this->doctrine = $entityManager;
    }

        public function getCurrentUser(): ?User
    {
        $user = $this->security->getUser();

        if (!$user instanceof User) {
            return null;
        }

        return $user;
    }

    public function getUserByRefreshToken($refreshToken): ?User
    {
        $refreshTokenObj = $this->doctrine
            ->getRepository(RefreshToken::class)
            ->findOneBy(['refreshToken' => $refreshToken]);

        if ($refreshTokenObj) {
            $user = $this->doctrine
                ->getRepository(User::class)
                ->findOneBy(['email' => $refreshTokenObj->getUsername()]);

            if ($user) {
                return $user;
            }
        }

        return null;
    }
}
