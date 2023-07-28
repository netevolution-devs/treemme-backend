<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Table;
use Gesdinet\JWTRefreshTokenBundle\Entity\RefreshToken as BaseRefreshToken;


#[ORM\Entity]
#[Table("refresh_tokens")]
class RefreshToken extends BaseRefreshToken
{
}
