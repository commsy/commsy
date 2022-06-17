<?php


namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 */
class AuthSourceOIDC extends AuthSource
{
    protected string $type = 'oidc';
}