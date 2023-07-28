<?php

namespace App\Service;

use App\Entity\User;
use Symfony\Component\Security\Core\Security;

class UserService
{
    private Security $security;

    public function __construct(
        Security $security
    )
    {
        $this->security = $security;
    }

    public function getCurrentUser(): User
    {

        return $this->security->getUser();
    }
}