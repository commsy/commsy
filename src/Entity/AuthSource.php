<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use App\Controller\Api\GetAuthSourceDirectLoginUrl;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;
use OpenApi\Annotations as OA;
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
 * @ApiResource(
 *     security="is_granted('ROLE_API_READ')",
 *     collectionOperations={
 *         "get"
 *     },
 *     itemOperations={
 *         "get",
 *         "get_direct_login_url"={
 *             "method"="GET",
 *             "path"="auth_sources/{id}/login_url",
 *             "controller"=GetAuthSourceDirectLoginUrl::class,
 *             "openapi_context"={
 *                 "summary"="Get a single auth source login url",
 *                 "responses"={
 *                     "200"={
 *                         "description"="A direct login url",
 *                         "content"={
 *                             "application/json"={
 *                                 "schema"={
 *                                     "type"="object",
 *                                     "properties"={
 *                                         "url"={
 *                                             "type"="string",
 *                                         },
 *                                     },
 *                                 },
 *                             },
 *                         },
 *                     },
 *                 },
 *             },
 *         },
 *     },
 *     normalizationContext={
 *         "groups"={"api"}
 *     },
 *     denormalizationContext={
 *         "groups"={"api"}
 *     }
 * )
 */
abstract class AuthSource
{
    public const ADD_ACCOUNT_YES = 'yes';
    public const ADD_ACCOUNT_NO = 'no';
    public const ADD_ACCOUNT_INVITE = 'invitation';

    /**
     * @var integer
     *
     * @ORM\Column(type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     *
     * @Groups({"api"})
     * @OA\Property(description="The unique identifier.")
     */
    private int $id;

    /**
     * @var ?string
     *
     * @ORM\Column(type="string", length=255, nullable=false)
     *
     * @Groups({"api"})
     * @OA\Property(type="string", maxLength=255)
     */
    private ?string $title = null;

    /**
     * @var ?string
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     *
     * @Groups({"api"})
     * @OA\Property(type="string", maxLength=255)
     */
    private ?string $description = null;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Portal", inversedBy="authSources")
     * @ORM\JoinColumn(name="portal_id", referencedColumnName="id")
     */
    private ?Portal $portal;

    /**
     * @var boolean
     *
     * @ORM\Column(type="boolean")
     *
     * @Groups({"api"})
     * @OA\Property(type="boolean")
     */
    private ?bool $enabled = null;

    /**
     * @var boolean
     *
     * @ORM\Column(name="`default`", type="boolean")
     */
    private ?bool $default = null;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=10, nullable=false, columnDefinition="ENUM('yes', 'no', 'invitation')")
     */
    protected string $addAccount;

    /**
     * @var boolean
     *
     * @ORM\Column(type="boolean")
     */
    protected bool $changeUsername;

    /**
     * @var boolean
     *
     * @ORM\Column(type="boolean")
     */
    protected bool $deleteAccount;

    /**
     * @var boolean
     *
     * @ORM\Column(type="boolean")
     */
    protected bool $changeUserdata;

    /**
     * @var boolean
     *
     * @ORM\Column(type="boolean")
     */
    protected bool $changePassword;

    /**
     * @ORM\Column(type="boolean")
     */
    protected bool $createRoom = true;

    /**
     * @var string
     *
     * @Groups({"api"})
     * @OA\Property(type="string")
     */
    protected string $type = '';

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
     * @param ?string $description
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
     * @return string
     */
    public function getAddAccount(): string
    {
        return $this->addAccount;
    }

    /**
     * @param string $addAccount
     * @return self
     */
    public function setAddAccount(string $addAccount): self
    {
        if (!in_array($addAccount, [self::ADD_ACCOUNT_YES, self::ADD_ACCOUNT_NO, self::ADD_ACCOUNT_INVITE])) {
            throw new InvalidArgumentException('invalid value for add_account');
        }

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

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }
}
