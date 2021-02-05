<?php


namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity()
 */
class AuthSourceShibboleth extends AuthSource
{
    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255, nullable=false)
     * @Assert\Length(max=255)
     */
    private $loginUrl;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255, nullable=false)
     * @Assert\Length(max=255)
     */
    private $logoutUrl;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255, nullable=false)
     * @Assert\Length(max=255)
     */
    private $passwordResetUrl;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=50, nullable=false)
     * @Assert\Length(max=50)
     */
    private $mappingUsername;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=50, nullable=false)
     * @Assert\Length(max=50)
     */
    private $mappingFirstname;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=50, nullable=false)
     * @Assert\Length(max=50)
     */
    private $mappingLastname;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=50, nullable=false)
     * @Assert\Length(max=50)
     */
    private $mappingEmail;

    public function __construct()
    {
        $this->addAccount = false;
        $this->changeUsername = false;
        $this->deleteAccount = false;
        $this->changeUserdata = false;
        $this->changePassword = false;
    }

    public function getType(): string
    {
        return 'shib';
    }

    /**
     * @return string
     */
    public function getLoginUrl(): ?string
    {
        return $this->loginUrl;
    }

    /**
     * @param string $loginUrl
     * @return self
     */
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

    /**
     * @param string $logoutUrl
     * @return self
     */
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

    /**
     * @param string $passwordResetUrl
     * @return self
     */
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

    /**
     * @param string $mappingUsername
     * @return self
     */
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

    /**
     * @param string $mappingFirstname
     * @return self
     */
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

    /**
     * @param string $mappingLastname
     * @return self
     */
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

    /**
     * @param string $mappingEmail
     * @return self
     */
    public function setMappingEmail(string $mappingEmail): self
    {
        $this->mappingEmail = $mappingEmail;
        return $this;
    }
}