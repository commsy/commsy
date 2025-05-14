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

use ApiPlatform\Metadata\ApiProperty;
use App\Enum\AddAccountSetting;
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

    #[ORM\Column(type: Types::STRING, length: 255)]
    #[Assert\Length(max: 255)]
    #[Assert\Url]
    private ?string $userInfoUrl = null;

    #[ORM\Column(type: Types::STRING, length: 100)]
    #[Assert\Length(max: 100)]
    private ?string $usernameMapping = null;

    #[ORM\Column(type: Types::STRING, length: 100)]
    #[Assert\Length(max: 100)]
    private ?string $displaynameMapping = null;

    #[ORM\Column(type: Types::STRING, length: 100)]
    #[Assert\Length(max: 100)]
    private ?string $emailMapping = null;

    #[ORM\Column(type: Types::STRING, length: 100)]
    #[Assert\Length(max: 100)]
    private ?string $firstnameMapping = null;

    #[ORM\Column(type: Types::STRING, length: 100)]
    #[Assert\Length(max: 100)]
    private ?string $lastnameMapping = null;

    #[ApiProperty(openapiContext: ['type' => 'boolean'])]
    #[ORM\Column(type: Types::BOOLEAN)]
    private ?bool $useEmailAsIdentifier = false;

    protected string $type = 'oidc';

    public function __construct()
    {
        $this->addAccount = AddAccountSetting::NO;
        $this->changeUsername = false;
        $this->deleteAccount = false;
        $this->changeUserdata = false;
        $this->changePassword = false;
    }

    public function getIssuer(): ?string
    {
        return $this->issuer;
    }

    public function setIssuer(?string $issuer): static
    {
        $this->issuer = $issuer;
        return $this;
    }

    public function getClientSecret(): ?string
    {
        return $this->clientSecret;
    }

    public function setClientSecret(?string $clientSecret): static
    {
        $this->clientSecret = $clientSecret;
        return $this;
    }

    public function getClientIdentifier(): ?string
    {
        return $this->clientIdentifier;
    }

    public function setClientIdentifier(?string $clientIdentifier): static
    {
        $this->clientIdentifier = $clientIdentifier;
        return $this;
    }

    public function getUserInfoUrl(): ?string
    {
        return $this->userInfoUrl;
    }

    public function setUserInfoUrl(?string $userInfoUrl): static
    {
        $this->userInfoUrl = $userInfoUrl;
        return $this;
    }

    public function getUsernameMapping(): ?string
    {
        return $this->usernameMapping;
    }

    public function setUsernameMapping(?string $usernameMapping): static
    {
        $this->usernameMapping = $usernameMapping;
        return $this;
    }

    public function getDisplaynameMapping(): ?string
    {
        return $this->displaynameMapping;
    }

    public function setDisplaynameMapping(?string $displaynameMapping): static
    {
        $this->displaynameMapping = $displaynameMapping;
        return $this;
    }

    public function getEmailMapping(): ?string
    {
        return $this->emailMapping;
    }

    public function setEmailMapping(?string $emailMapping): static
    {
        $this->emailMapping = $emailMapping;
        return $this;
    }

    public function getFirstnameMapping(): ?string
    {
        return $this->firstnameMapping;
    }

    public function setFirstnameMapping(?string $firstnameMapping): static
    {
        $this->firstnameMapping = $firstnameMapping;
        return $this;
    }

    public function getLastnameMapping(): ?string
    {
        return $this->lastnameMapping;
    }

    public function setLastnameMapping(?string $lastnameMapping): static
    {
        $this->lastnameMapping = $lastnameMapping;
        return $this;
    }

    public function getUseEmailAsIdentifier(): ?bool
    {
        return $this->useEmailAsIdentifier;
    }

    public function setUseEmailAsIdentifier(?bool $useEmailAsIdentifier): static
    {
        $this->useEmailAsIdentifier = $useEmailAsIdentifier;
        return $this;
    }
}
