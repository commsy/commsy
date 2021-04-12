<?php


namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 */
class AuthSourceOIDC extends AuthSource
{
    public function getType(): string
    {
        return 'oidc';
    }
}