<?php

/*
 * This file is part of CommSy.
 *
 * (c) Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
 * Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
 * Edouard Simon, Monique Strauss, Jose Mauel Gonzalez Vazquez, Johannes Schultze
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
class AuthSourceOIDC extends AuthSource
{
    #[ORM\Column(type: Types::STRING, length: 255)]
    #[Assert\Length(max: 255)]
    #[Assert\Url]
    private ?string $issuer = null;

    #[ORM\Column(type: Types::STRING, length: 255)]
    #[Assert\Length(max: 255)]
    private ?string $clientIdentifier = null;

    #[ORM\Column(type: Types::STRING, length: 255)]
    #[Assert\Length(max: 255)]
    private ?string $clientSecret = null;

    protected string $type = 'oidc';

    public function __construct()
    {
        $this->addAccount = self::ADD_ACCOUNT_NO;
        $this->changeUsername = false;
        $this->deleteAccount = false;
        $this->changeUserdata = false;
        $this->changePassword = false;
    }

    public function getIssuer(): ?string
    {
        return $this->issuer;
    }

    public function setIssuer(?string $issuer): AuthSourceOIDC
    {
        $this->issuer = $issuer;
        return $this;
    }

    public function getClientSecret(): ?string
    {
        return $this->clientSecret;
    }

    public function setClientSecret(?string $clientSecret): AuthSourceOIDC
    {
        $this->clientSecret = $clientSecret;
        return $this;
    }

    public function getClientIdentifier(): ?string
    {
        return $this->clientIdentifier;
    }

    public function setClientIdentifier(?string $clientIdentifier): AuthSourceOIDC
    {
        $this->clientIdentifier = $clientIdentifier;
        return $this;
    }
}
