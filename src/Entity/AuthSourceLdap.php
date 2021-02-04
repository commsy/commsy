<?php


namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 */
class AuthSourceLdap extends AuthSource
{
    public function getType(): string
    {
        return 'ldap';
    }
}