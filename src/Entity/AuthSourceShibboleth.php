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

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
class AuthSourceShibboleth extends AuthSource
{
    #[ORM\Column(type: Types::STRING, length: 255)]
    #[Assert\Length(max: 255)]
    private ?string $loginUrl = null;

    #[ORM\Column(type: Types::STRING, length: 255)]
    #[Assert\Length(max: 255)]
    private ?string $logoutUrl = null;

    #[ORM\Column(type: Types::STRING, length: 255)]
    #[Assert\Length(max: 255)]
    private ?string $passwordResetUrl = null;

    #[ORM\Column(type: Types::STRING, length: 50)]
    #[Assert\Length(max: 50)]
    private ?string $mappingUsername = null;

    #[ORM\Column(type: Types::STRING, length: 50)]
    #[Assert\Length(max: 50)]
    private ?string $mappingFirstname = null;

    #[ORM\Column(type: Types::STRING, length: 50)]
    #[Assert\Length(max: 50)]
    private ?string $mappingLastname = null;

    #[ORM\Column(type: Types::STRING, length: 50)]
    #[Assert\Length(max: 50)]
    private ?string $mappingEmail = null;

    #[ORM\Column(type: Types::ARRAY, name: 'identity_provider')]
    private ?Collection $identityProviders;

    protected string $type = 'shib';

    public function __construct()
    {
        $this->addAccount = self::ADD_ACCOUNT_NO;
        $this->changeUsername = false;
        $this->deleteAccount = false;
        $this->changeUserdata = false;
        $this->changePassword = false;
        $this->identityProviders = new ArrayCollection();
    }

    /**
     * @return string
     */
    public function getLoginUrl(): ?string
    {
        return $this->loginUrl;
    }

    public function setLoginUrl(string $loginUrl): self
    {
        $this->loginUrl = $loginUrl;

        return $this;
    }

    /**
     * @return string
     */
    public function getLogoutUrl(): ?string
    {
        return $this->logoutUrl;
    }

    public function setLogoutUrl(?string $logoutUrl): self
    {
        $this->logoutUrl = $logoutUrl;

        return $this;
    }

    /**
     * @return string
     */
    public function getPasswordResetUrl(): ?string
    {
        return $this->passwordResetUrl;
    }

    public function setPasswordResetUrl(?string $passwordResetUrl): self
    {
        $this->passwordResetUrl = $passwordResetUrl;

        return $this;
    }

    /**
     * @return string
     */
    public function getMappingUsername(): ?string
    {
        return $this->mappingUsername;
    }

    public function setMappingUsername(string $mappingUsername): self
    {
        $this->mappingUsername = $mappingUsername;

        return $this;
    }

    /**
     * @return string
     */
    public function getMappingFirstname(): ?string
    {
        return $this->mappingFirstname;
    }

    public function setMappingFirstname(string $mappingFirstname): self
    {
        $this->mappingFirstname = $mappingFirstname;

        return $this;
    }

    /**
     * @return string
     */
    public function getMappingLastname(): ?string
    {
        return $this->mappingLastname;
    }

    public function setMappingLastname(string $mappingLastname): self
    {
        $this->mappingLastname = $mappingLastname;

        return $this;
    }

    /**
     * @return string
     */
    public function getMappingEmail(): ?string
    {
        return $this->mappingEmail;
    }

    public function setMappingEmail(string $mappingEmail): self
    {
        $this->mappingEmail = $mappingEmail;

        return $this;
    }

    /**
     * @return Collection|ShibbolethIdentityProvider[]|null
     */
    public function getIdentityProviders(): ?Collection
    {
        return $this->identityProviders;
    }

    public function setIdentityProviders(Collection $identityProviders): self
    {
        $this->identityProviders = new ArrayCollection();

        foreach ($identityProviders as $provider) {
            $this->identityProviders->add($provider);
        }

        return $this;
    }
}
