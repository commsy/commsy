<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Swagger\Annotations as SWG;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * AuthSource
 *
 * @ORM\Table(name="auth_source", indexes={
 *     @ORM\Index(name="context_id", columns={"context_id"})
 * })
 * @ORM\Entity(repositoryClass="App\Repository\AuthSourceRepository")
 */
class AuthSource
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
     * @ORM\ManyToOne(targetEntity="App\Entity\Portal", inversedBy="authSources")
     * @ORM\JoinColumn(name="portal_id", referencedColumnName="id")
     *
     * @Groups({"api"})
     * @SWG\Property(description="The portal.")
     */
    private $portal;

    /**
     * @var string
     *
     * @ORM\Column(type="string", columnDefinition="ENUM('local')")
     */
    private $type;

    /**
     * @var string
     *
     * @ORM\Column(name="extras", type="object", nullable=true)
     */
    private $extras;

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
    private $addAccount;

    /**
     * @var boolean
     *
     * @ORM\Column(type="boolean")
     */
    private $changeUsername;

    /**
     * @var boolean
     *
     * @ORM\Column(type="boolean")
     */
    private $deleteAccount;

    /**
     * @var boolean
     *
     * @ORM\Column(type="boolean")
     */
    private $changeUserdata;

    /**
     * @var boolean
     *
     * @ORM\Column(type="boolean")
     */
    private $changePassword;

    /**
     * @ORM\Column(type="boolean")
     */
    private $createRoom;

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return AuthSource
     */
    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @param string $title
     * @return AuthSource
     */
    public function setTitle(string $title): self
    {
        $this->title = $title;
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
     * @return string
     */
    public function getExtras(): string
    {
        return $this->extras;
    }

    /**
     * @param string $extras
     * @return AuthSource
     */
    public function setExtras(string $extras): self
    {
        $this->extras = $extras;
        return $this;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param string $type
     * @return AuthSource
     */
    public function setType(string $type): AuthSource
    {
        $this->type = $type;
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
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * @param bool $enabled
     * @return AuthSource
     */
    public function setEnabled(bool $enabled): AuthSource
    {
        $this->enabled = $enabled;
        return $this;
    }

    /**
     * @return bool
     */
    public function isDefault(): bool
    {
        return $this->default;
    }

    /**
     * @param bool $default
     * @return AuthSource
     */
    public function setDefault(bool $default): AuthSource
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
     * @return AuthSource
     */
    public function setAddAccount(bool $addAccount): AuthSource
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
     * @return AuthSource
     */
    public function setChangeUsername(bool $changeUsername): AuthSource
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
     * @return AuthSource
     */
    public function setDeleteAccount(bool $deleteAccount): AuthSource
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
     * @return AuthSource
     */
    public function setChangeUserdata(bool $changeUserdata): AuthSource
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
     * @return AuthSource
     */
    public function setChangePassword(bool $changePassword): AuthSource
    {
        $this->changePassword = $changePassword;
        return $this;
    }
}
