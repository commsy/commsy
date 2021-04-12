<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Swagger\Annotations as SWG;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * AuthSource
 *
 * @ORM\Table(name="auth_source", indexes={
 *     @ORM\Index(name="portal_id", columns={"portal_id"})
 * })
 * @ORM\Entity(repositoryClass="App\Repository\AuthSourceRepository")
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="type", type="string")
 * @ORM\DiscriminatorMap({"local" = "AuthSourceLocal", "oidc" = "AuthSourceOIDC", "ldap" = "AuthSourceLdap", "shib" = "AuthSourceShibboleth", "guest" = "AuthSourceGuest"})
 */
abstract class AuthSource
{
    /**
     * @var integer
     *
     * @ORM\Column(type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     *
     * @Groups({"api"})
     * @SWG\Property(description="The unique identifier.")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255, nullable=false)
     *
     * @Groups({"api"})
     * @SWG\Property(type="string", maxLength=255)
     */
    private $title;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     *
     * @Groups({"api"})
     * @SWG\Property(type="string", maxLength=255)
     */
    private $description;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Portal", inversedBy="authSources")
     * @ORM\JoinColumn(name="portal_id", referencedColumnName="id")
     *
     * @Groups({"api"})
     * @SWG\Property(description="The portal.")
     */
    private $portal;

    /**
     * @var boolean
     *
     * @ORM\Column(type="boolean")
     */
    private $enabled;

    /**
     * @var boolean
     *
     * @ORM\Column(name="`default`", type="boolean")
     */
    private $default;

    /**
     * @var boolean
     *
     * @ORM\Column(type="boolean")
     */
    protected $addAccount;

    /**
     * @var boolean
     *
     * @ORM\Column(type="boolean")
     */
    protected $changeUsername;

    /**
     * @var boolean
     *
     * @ORM\Column(type="boolean")
     */
    protected $deleteAccount;

    /**
     * @var boolean
     *
     * @ORM\Column(type="boolean")
     */
    protected $changeUserdata;

    /**
     * @var boolean
     *
     * @ORM\Column(type="boolean")
     */
    protected $changePassword;

    /**
     * @ORM\Column(type="boolean")
     */
    protected $createRoom;

    abstract public function getType(): string;

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return self
     */
    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return string
     */
    public function getTitle(): ?string
    {
        return $this->title;
    }

    /**
     * @param string $title
     * @return self
     */
    public function setTitle(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    /**
     * @return string
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @param string $description
     * @return self
     */
    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getPortal(): ?Portal
    {
        return $this->portal;
    }

    public function setPortal(?Portal $portal): self
    {
        $this->portal = $portal;

        return $this;
    }


    /**
     * @return array
     */
    public function getExtras()
    {
        return $this->extras;
    }

    /**
     * @param array $extras
     * @return self
     */
    public function setExtras(array $extras): self
    {
        $this->extras = $extras;
        return $this;
    }

    public function getCreateRoom(): ?bool
    {
        return $this->createRoom;
    }

    public function setCreateRoom(bool $createRoom): self
    {
        $this->createRoom = $createRoom;

        return $this;
    }

    /**
     * @return bool
     */
    public function isEnabled(): ?bool
    {
        return $this->enabled;
    }

    /**
     * @param bool $enabled
     * @return self
     */
    public function setEnabled(bool $enabled): self
    {
        $this->enabled = $enabled;
        return $this;
    }

    /**
     * @return bool
     */
    public function isDefault(): ?bool
    {
        return $this->default;
    }

    /**
     * @param bool $default
     * @return self
     */
    public function setDefault(bool $default): self
    {
        $this->default = $default;
        return $this;
    }

    /**
     * @return bool
     */
    public function isAddAccount(): bool
    {
        return $this->addAccount;
    }

    /**
     * @param bool $addAccount
     * @return self
     */
    public function setAddAccount(bool $addAccount): self
    {
        $this->addAccount = $addAccount;
        return $this;
    }

    /**
     * @return bool
     */
    public function isChangeUsername(): bool
    {
        return $this->changeUsername;
    }

    /**
     * @param bool $changeUsername
     * @return self
     */
    public function setChangeUsername(bool $changeUsername): self
    {
        $this->changeUsername = $changeUsername;
        return $this;
    }

    /**
     * @return bool
     */
    public function isDeleteAccount(): bool
    {
        return $this->deleteAccount;
    }

    /**
     * @param bool $deleteAccount
     * @return self
     */
    public function setDeleteAccount(bool $deleteAccount): self
    {
        $this->deleteAccount = $deleteAccount;
        return $this;
    }

    /**
     * @return bool
     */
    public function isChangeUserdata(): bool
    {
        return $this->changeUserdata;
    }

    /**
     * @param bool $changeUserdata
     * @return self
     */
    public function setChangeUserdata(bool $changeUserdata): self
    {
        $this->changeUserdata = $changeUserdata;
        return $this;
    }

    /**
     * @return bool
     */
    public function isChangePassword(): bool
    {
        return $this->changePassword;
    }

    /**
     * @param bool $changePassword
     * @return self
     */
    public function setChangePassword(bool $changePassword): self
    {
        $this->changePassword = $changePassword;
        return $this;
    }
}
