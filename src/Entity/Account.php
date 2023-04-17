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

use ApiPlatform\Core\Annotation\ApiResource;
use App\Controller\Api\GetAccountsCheckLocalLogin;
use App\Controller\Api\GetAccountsWorkspaces;
use App\Dto\LocalLoginInput;
use App\Repository\AccountsRepository;
use App\Validator\Constraints\EmailRegex;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;
use Serializable;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherAwareInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Account.
 *
 * @ApiResource(
 *     security="is_granted('ROLE_API_READ')",
 *     collectionOperations={
 *     },
 *     itemOperations={
 *         "get",
 *         "check_local_login"={
 *             "method"="POST",
 *             "path"="accounts/checkLocalLogin",
 *             "controller"=GetAccountsCheckLocalLogin::class,
 *             "status"=200,
 *             "read"=false,
 *             "write"=false,
 *             "input"=LocalLoginInput::class,
 *             "validation_groups"={"checkLocalLoginValidation"},
 *             "normalization_context"={
 *                 "groups"={"api"},
 *             },
 *             "denormalization_context"={
 *                 "groups"={"api_check_local_login"},
 *              },
 *             "openapi_context"={
 *                 "summary"="Checks plain user credentials and returns account information",
 *                 "parameters"={
 *                 },
 *                 "requestBody"={
 *                     "required"=true,
 *                     "description"="Local login data",
 *                     "content"={
 *                         "application/json"={
 *                             "schema"={
 *                                 "type"="object",
 *                                 "properties"={
 *                                     "contextId"={
 *                                         "type"="int",
 *                                     },
 *                                     "username"={
 *                                         "type"="string",
 *                                     },
 *                                     "password"={
 *                                         "type"="string",
 *                                     }
 *                                 },
 *                             },
 *                         },
 *                     },
 *                 },
 *             },
 *         },
 *         "get_workspaces"={
 *             "method"="GET",
 *             "path"="accounts/{id}/workspaces",
 *             "controller"=GetAccountsWorkspaces::class,
 *         }
 *     },
 *     normalizationContext={
 *         "groups"={"api"}
 *     },
 *     denormalizationContext={
 *         "groups"={"api"}
 *     }
 * )
 */
#[ORM\Entity(repositoryClass: AccountsRepository::class)]
#[UniqueEntity(fields: ['contextId', 'username', 'authSource'], repositoryMethod: 'findOneByCredentialsArray', errorPath: 'username')]
#[ORM\Table(name: 'accounts')]
#[ORM\UniqueConstraint(name: 'accounts_idx', columns: ['context_id', 'username', 'auth_source_id'])]
#[EmailRegex]
class Account implements
    UserInterface,
    PasswordAuthenticatedUserInterface,
    PasswordHasherAwareInterface,
    Serializable
{
    public const ACTIVITY_ACTIVE = 'active';
    public const ACTIVITY_ACTIVE_NOTIFIED = 'active_notified';
    public const ACTIVITY_IDLE = 'idle';
    public const ACTIVITY_IDLE_NOTIFIED = 'idle_notified';
    public const ACTIVITY_ABANDONED = 'abandoned';

    #[ORM\Column(type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[Groups(['api', 'api_check_local_login'])]
    private ?int $id = null;

    #[ORM\Column(type: 'integer')]
    #[Assert\NotBlank(groups: ['checkLocalLoginValidation'])]
    #[Groups(['api_check_local_login'])]
    private int $contextId;

    #[ORM\Column(type: 'string', length: 100)]
    #[Assert\NotBlank(groups: ['Default', 'checkLocalLoginValidation'])]
    #[Assert\Regex(pattern: '/^(root|guest)$/i', match: false, message: '{{ value }} is a reserved name')]
    #[Groups(['api', 'api_check_local_login'])]
    private string $username;

    #[Assert\NotBlank]
    #[Assert\NotCompromisedPassword]
    #[Assert\Length(max: 4096, min: 8, minMessage: 'Your password must be at least {{ limit }} characters long.')]
    #[Assert\Regex(pattern: '/(*UTF8)[\p{Ll}\p{Lm}\p{Lo}]/', message: 'Your password must contain at least one lowercase character.')]
    #[Assert\Regex(pattern: '/(*UTF8)[\p{Lu}\p{Lt}]/', message: 'Your password must contain at least one uppercase character.')]
    #[Assert\Regex(pattern: '/[[:punct:]]/', message: 'Your password must contain at least one special character.')]
    #[Assert\Regex(pattern: '/\p{Nd}/', message: 'Your password must contain at least one numeric character.')]
    private ?string $plainPassword;

    #[ORM\Column(type: 'string', length: 32, nullable: true)]
    private ?string $passwordMd5;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Assert\NotBlank(groups: ['checkLocalLoginValidation'])]
    #[Groups(['api_check_local_login'])]
    private ?string $password;

    #[ORM\Column(type: 'string', length: 50)]
    #[Assert\NotBlank]
    #[Groups(['api'])]
    private string $firstname;

    #[ORM\Column(type: 'string', length: 50)]
    #[Assert\NotBlank]
    #[Groups(['api'])]
    private string $lastname;

    #[ORM\Column(name: 'email', type: 'string', length: 100)]
    #[Assert\Email]
    #[Groups(['api'])]
    private string $email;

    #[ORM\Column(name: 'language', type: 'string', length: 10)]
    private string $language;

    #[ORM\ManyToOne(targetEntity: AuthSource::class)]
    #[ORM\JoinColumn]
    private AuthSource $authSource;

    #[ORM\Column(name: 'locked', type: 'boolean')]
    #[Groups(['api'])]
    private bool $locked = false;

    #[ORM\Column(name: 'last_login', type: 'datetime', nullable: true)]
    private ?DateTime $lastLogin;

    #[ORM\Column(name: 'activity_state', type: 'string', length: 15, options: ['default' => 'active'])]
    private string $activityState;

    #[ORM\Column(name: 'activity_state_updated', type: 'datetime', nullable: true)]
    private ?DateTime $activityStateUpdated = null;

    public function __construct()
    {
        $this->lastLogin = null;
        $this->password = null;
        $this->passwordMd5 = null;
        $this->activityState = self::ACTIVITY_ACTIVE;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): Account
    {
        $this->id = $id;
        return $this;
    }

    /**
     * Returns the roles granted to the user.
     *
     *     public function getRoles()
     *     {
     *         return ['ROLE_USER'];
     *     }
     *
     * Alternatively, the roles might be stored on a ``roles`` property,
     * and populated in any number of different ways when the user object
     * is created.
     */
    public function getRoles(): array
    {
        return ['ROLE_USER'];
    }

    /**
     * Returns the password used to authenticate the user.
     *
     * This should be the encoded password. On authentication, a plain-text
     * password will be salted, encoded, and then compared to this value.
     */
    public function getPassword(): ?string
    {
        return $this->password ?: $this->passwordMd5;
    }

    public function setPassword(string $password): Account
    {
        $this->password = $password;
        return $this;
    }

    /**
     * Returns the salt that was originally used to encode the password.
     *
     * This can return null if the password was not encoded using a salt.
     */
    public function getSalt(): ?string
    {
        return null;
    }

    public function getContextId(): ?int
    {
        return $this->contextId;
    }

    public function setContextId(int $contextId): Account
    {
        $this->contextId = $contextId;
        return $this;
    }

    /**
     * Returns the username used to authenticate the user.
     */
    public function getUsername(): string
    {
        return $this->username;
    }

    public function setUsername(string $username): Account
    {
        $this->username = $username;
        return $this;
    }

    public function getUserIdentifier(): string
    {
        return $this->getUsername();
    }

    public function getPlainPassword(): ?string
    {
        return $this->plainPassword;
    }

    public function setPlainPassword(mixed $plainPassword): Account
    {
        $this->plainPassword = $plainPassword;
        return $this;
    }

    public function getPasswordMd5(): string
    {
        return $this->passwordMd5;
    }

    public function setPasswordMd5(?string $passwordMd5): Account
    {
        $this->passwordMd5 = $passwordMd5;
        return $this;
    }

    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    public function setFirstname(string $firstname): Account
    {
        $this->firstname = $firstname;
        return $this;
    }

    public function getLastname(): ?string
    {
        return $this->lastname;
    }

    public function setLastname(string $lastname): Account
    {
        $this->lastname = $lastname;
        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): Account
    {
        $this->email = $email;
        return $this;
    }

    public function getLanguage(): string
    {
        return $this->language;
    }

    public function setLanguage(string $language): Account
    {
        $this->language = $language;
        return $this;
    }

    /**
     * Removes sensitive data from the user.
     *
     * This is important if, at any given point, sensitive information like
     * the plain-text password is stored on this object.
     */
    public function eraseCredentials()
    {
    }

    public function hasLegacyPassword(): bool
    {
        return null !== $this->passwordMd5;
    }

    public function getPasswordHasherName(): ?string
    {
        if ($this->hasLegacyPassword()) {
            return 'legacy_encoder';
        }
        return null;
    }

    public function getAuthSource(): ?AuthSource
    {
        return $this->authSource;
    }

    public function setAuthSource(?AuthSource $authSource): Account
    {
        $this->authSource = $authSource;
        return $this;
    }

    public function isLocked(): bool
    {
        return $this->locked;
    }

    public function setLocked(bool $locked): Account
    {
        $this->locked = $locked;
        return $this;
    }

    public function getLastLogin(): ?DateTime
    {
        return $this->lastLogin;
    }

    public function setLastLogin(?DateTime $lastLogin): Account
    {
        $this->lastLogin = $lastLogin;
        return $this;
    }

    public function getActivityState(): string
    {
        return $this->activityState;
    }

    public function setActivityState(string $activityState): Account
    {
        if (!in_array($activityState, [
            self::ACTIVITY_ACTIVE,
            self::ACTIVITY_ACTIVE_NOTIFIED,
            self::ACTIVITY_IDLE,
            self::ACTIVITY_IDLE_NOTIFIED,
            self::ACTIVITY_ABANDONED,
        ])) {
            throw new InvalidArgumentException('Invalid activity');
        }

        $this->activityState = $activityState;
        return $this;
    }

    public function getActivityStateUpdated(): ?DateTime
    {
        return $this->activityStateUpdated;
    }

    public function setActivityStateUpdated(?DateTime $activityStateUpdated): Account
    {
        $this->activityStateUpdated = $activityStateUpdated;
        return $this;
    }

    // Serializable
    public function serialize()
    {
        $serializableData = get_object_vars($this);

        // exclude from serialization
        unset($serializableData['authSource']);
        return serialize($serializableData);
    }

    public function unserialize($serialized)
    {
        $unserializedData = unserialize($serialized);
        foreach ($unserializedData as $key => $value) {
            $this->{$key} = $value;
        }
    }
}
