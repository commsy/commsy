<?php


namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity()
 */
class AuthSourceLdap extends AuthSource
{
    /**
     * @var string|null
     *
     * @ORM\Column(type="string", length=255, nullable=false)
     * @Assert\Length(max=255)
     */
    private ?string $serverUrl;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", length=50, nullable=false)
     * @Assert\Length(max=50)
     */
    private ?string $uidKey;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", length=50, nullable=false)
     * @Assert\Length(max=50)
     */
    private ?string $baseDn;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", length=50, nullable=false)
     * @Assert\Length(max=50)
     */
    private ?string $searchDn;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", length=50, nullable=false)
     * @Assert\Length(max=50)
     */
    private ?string $searchPassword;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", length=50, nullable=false)
     * @Assert\Length(max=50)
     */
    private ?string $authDn;

    public function __construct()
    {
        $this->addAccount = self::ADD_ACCOUNT_NO;
        $this->changeUsername = false;
        $this->deleteAccount = false;
        $this->changeUserdata = false;
        $this->changePassword = false;
    }

    public function getType(): string
    {
        return 'ldap';
    }

    /**
     * @return string
     */
    public function getServerUrl(): ?string
    {
        return $this->serverUrl;
    }

    /**
     * @param string $serverUrl
     * @return self
     */
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

    /**
     * @param string $uidKey
     * @return self
     */
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

    /**
     * @param string $baseDn
     * @return self
     */
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

    /**
     * @param string $searchDn
     * @return self
     */
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

    /**
     * @param string $searchPassword
     * @return self
     */
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

    /**
     * @param string $authDn
     * @return self
     */
    public function setAuthDn(string $authDn): self
    {
        $this->authDn = $authDn;
        return $this;
    }
}