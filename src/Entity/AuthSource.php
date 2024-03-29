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
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use App\Controller\Api\GetAuthSourceDirectLoginUrl;
use App\Repository\AuthSourceRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: AuthSourceRepository::class)]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'type', type: 'string')]
#[ORM\DiscriminatorMap(['local' => 'AuthSourceLocal', 'oidc' => 'AuthSourceOIDC', 'ldap' => 'AuthSourceLdap', 'shib' => 'AuthSourceShibboleth', 'guest' => 'AuthSourceGuest'])]
#[ORM\Table(name: 'auth_source')]
#[ORM\Index(columns: ['portal_id'], name: 'portal_id')]
#[ApiResource(
    operations: [
        new Get(),
        new Get(
            uriTemplate: 'auth_sources/{id}/login_url',
            controller: GetAuthSourceDirectLoginUrl::class,
            openapiContext: [
                'summary' => 'Get a single auth source login url',
                'responses' => [[
                    'description' => 'A direct login url',
                    'content' => [
                        'application/json' => [
                            'schema' => [
                                'type' => 'object',
                                'properties' => [
                                    'url' => ['type' => 'string'],
                                ],
                            ],
                        ],
                    ],
                ]],
            ],
        ),
        new GetCollection(),
    ],
    normalizationContext: ['groups' => ['api']],
    denormalizationContext: ['groups' => ['api']],
    security: "is_granted('ROLE_API_READ')"
)]
#[ApiResource(
    uriTemplate: '/portals/{id}/auth_sources.{_format}',
    operations: [new GetCollection()],
    uriVariables: [
        'id' => new Link(
            fromProperty: 'authSources',
            fromClass: Portal::class
        )
    ]
)]
abstract class AuthSource
{
    public final const ADD_ACCOUNT_YES = 'yes';
    public final const ADD_ACCOUNT_NO = 'no';
    public final const ADD_ACCOUNT_INVITE = 'invitation';
    #[ApiProperty(description: 'The unique identifier.')]
    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[Groups(['api'])]
    private int $id;

    #[ApiProperty(openapiContext: ['type' => 'string', 'maxLength' => 255])]
    #[ORM\Column(type: Types::STRING, length: 255)]
    #[Groups(['api'])]
    private ?string $title = null;

    #[ApiProperty(openapiContext: ['type' => 'string', 'maxLength' => 255])]
    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    #[Groups(['api'])]
    private ?string $description = null;

    #[ORM\ManyToOne(targetEntity: Portal::class, inversedBy: 'authSources')]
    #[ORM\JoinColumn(name: 'portal_id')]
    private ?Portal $portal = null;

    #[ApiProperty(openapiContext: ['type' => 'boolean'])]
    #[ORM\Column(type: Types::BOOLEAN)]
    #[Groups(['api'])]
    private ?bool $enabled = null;

    #[ORM\Column(name: '`default`', type: Types::BOOLEAN)]
    private ?bool $default = null;

    #[ORM\Column(type: Types::STRING, length: 10, columnDefinition: "ENUM('yes', 'no', 'invitation')")]
    protected string $addAccount;

    #[ORM\Column(type: Types::BOOLEAN)]
    protected bool $changeUsername;

    #[ORM\Column(type: Types::BOOLEAN)]
    protected bool $deleteAccount;

    #[ORM\Column(type: Types::BOOLEAN)]
    protected bool $changeUserdata;

    #[ORM\Column(type: Types::BOOLEAN)]
    protected bool $changePassword;

    #[ORM\Column(type: Types::BOOLEAN)]
    protected bool $createRoom = true;

    #[ApiProperty(openapiContext: ['type' => 'string'])]
    #[Groups(['api'])]
    protected string $type = '';

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

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

    public function isEnabled(): ?bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): self
    {
        $this->enabled = $enabled;
        return $this;
    }

    public function isDefault(): ?bool
    {
        return $this->default;
    }

    public function setDefault(bool $default): self
    {
        $this->default = $default;
        return $this;
    }

    public function getAddAccount(): string
    {
        return $this->addAccount;
    }

    public function setAddAccount(string $addAccount): self
    {
        if (!in_array($addAccount, [self::ADD_ACCOUNT_YES, self::ADD_ACCOUNT_NO, self::ADD_ACCOUNT_INVITE])) {
            throw new InvalidArgumentException('invalid value for add_account');
        }
        $this->addAccount = $addAccount;
        return $this;
    }

    public function isChangeUsername(): bool
    {
        return $this->changeUsername;
    }

    public function setChangeUsername(bool $changeUsername): self
    {
        $this->changeUsername = $changeUsername;
        return $this;
    }

    public function isDeleteAccount(): bool
    {
        return $this->deleteAccount;
    }

    public function setDeleteAccount(bool $deleteAccount): self
    {
        $this->deleteAccount = $deleteAccount;
        return $this;
    }

    public function isChangeUserdata(): bool
    {
        return $this->changeUserdata;
    }

    public function setChangeUserdata(bool $changeUserdata): self
    {
        $this->changeUserdata = $changeUserdata;
        return $this;
    }

    public function isChangePassword(): bool
    {
        return $this->changePassword;
    }

    public function setChangePassword(bool $changePassword): self
    {
        $this->changePassword = $changePassword;
        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }
}
