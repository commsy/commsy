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
class AuthSourceLdap extends AuthSource
{
    #[ORM\Column(type: Types::STRING, length: 255)]
    #[Assert\Length(max: 255)]
    private ?string $serverUrl = null;

    #[ORM\Column(type: Types::STRING, length: 50)]
    #[Assert\Length(max: 50)]
    private ?string $uidKey = null;

    #[ORM\Column(type: Types::STRING, length: 50)]
    #[Assert\Length(max: 50)]
    private ?string $baseDn = null;

    #[ORM\Column(type: Types::STRING, length: 50)]
    #[Assert\Length(max: 50)]
    private ?string $searchDn = null;

    #[ORM\Column(type: Types::STRING, length: 50)]
    #[Assert\Length(max: 50)]
    private ?string $searchPassword = null;

    #[ORM\Column(type: Types::STRING, length: 50)]
    #[Assert\Length(max: 50)]
    private ?string $authDn = null;

    #[ORM\Column(type: Types::STRING, length: 50)]
    #[Assert\Length(max: 50)]
    private ?string $authQuery = null;

    protected string $type = 'ldap';

    public function __construct()
    {
        $this->addAccount = self::ADD_ACCOUNT_NO;
        $this->changeUsername = false;
        $this->deleteAccount = false;
        $this->changeUserdata = false;
        $this->changePassword = false;
    }

    /**
     * @return string
     */
    public function getServerUrl(): ?string
    {
        return $this->serverUrl;
    }

    public function setServerUrl(string $serverUrl): self
    {
        $this->serverUrl = $serverUrl;

        return $this;
    }

    /**
     * @return string
     */
    public function getUidKey(): ?string
    {
        return $this->uidKey;
    }

    public function setUidKey(string $uidKey): self
    {
        $this->uidKey = $uidKey;

        return $this;
    }

    /**
     * @return string
     */
    public function getBaseDn(): ?string
    {
        return $this->baseDn;
    }

    public function setBaseDn(string $baseDn): self
    {
        $this->baseDn = $baseDn;

        return $this;
    }

    /**
     * @return string
     */
    public function getSearchDn(): ?string
    {
        return $this->searchDn;
    }

    public function setSearchDn(string $searchDn): self
    {
        $this->searchDn = $searchDn;

        return $this;
    }

    /**
     * @return string
     */
    public function getSearchPassword(): ?string
    {
        return $this->searchPassword;
    }

    public function setSearchPassword(string $searchPassword): self
    {
        $this->searchPassword = $searchPassword;

        return $this;
    }

    /**
     * @return string
     */
    public function getAuthDn(): ?string
    {
        return $this->authDn;
    }

    public function setAuthDn(string $authDn): self
    {
        $this->authDn = $authDn;

        return $this;
    }

    /**
     * @return string
     */
    public function getAuthQuery(): ?string
    {
        return $this->authQuery;
    }

    public function setAuthQuery(string $authQuery): self
    {
        $this->authQuery = $authQuery;

        return $this;
    }
}
