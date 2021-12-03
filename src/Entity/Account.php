<?php

namespace App\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\Encoder\EncoderAwareInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Account
 *
 * @ORM\Table(name="accounts")
 * @ORM\Entity(repositoryClass="App\Repository\AccountsRepository")
 * @UniqueEntity(
 *     fields={"contextId", "username", "authSource"},
 *     errorPath="username",
 *     repositoryMethod="findOnByCredentials"
 * )
 */
class Account implements UserInterface, EncoderAwareInterface, \Serializable
{
    public const ACTIVITY_ACTIVE = 'active';
    public const ACTIVITY_ACTIVE_NOTIFIED = 'active_notified';
    public const ACTIVITY_IDLE = 'idle';
    public const ACTIVITY_IDLE_NOTIFIED = 'idle_notified';
    public const ACTIVITY_ABANDONED = 'abandoned';

    /**
     * @var integer
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     */
    private int $contextId;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=100)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     *
     * @Assert\NotBlank()
     * @Assert\Regex(pattern="/^(root|guest)$/i", match=false, message="{{ value }} is a reserved name")
     */
    private string $username;

    /**
     * @Assert\NotBlank()
     * @Assert\Length(max=4096)
     * @Assert\NotCompromisedPassword()
     */
    private $plainPassword;

    /**
     * @ORM\Column(type="string", length=32, nullable=true)
     */
    private ?string $passwordMd5;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $password;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=50)
     *
     * @Assert\NotBlank()
     */
    private string $firstname;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=50)
     *
     * @Assert\NotBlank()
     */
    private string $lastname;

    /**
     * @var string
     *
     * @ORM\Column(name="email", type="string", length=100)
     *
     * @Assert\Email()
     */
    private string $email;

    /**
     * @var string
     *
     * @ORM\Column(name="language", type="string", length=10)
     */
    private string $language;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\AuthSource")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     * @ORM\JoinColumn()
     */
    private AuthSource $authSource;

    /**
     * @var bool
     *
     * @ORM\Column(name="locked", type="boolean")
     */
    private bool $locked = false;

    /**
     * @var DateTime|null
     *
     * @ORM\Column(name="last_login", type="datetime", nullable=true)
     */
    private ?DateTime $lastLogin;

    /**
     * @var string
     *
     * @ORM\Column(name="activity_state", type="string", length=15, options={"default"="active"})
     */
    private string $activityState;

    /**
     * @var DateTime|null
     *
     * @ORM\Column(name="activity_state_updated", type="datetime", nullable=true)
     */
    private ?DateTime $activityStateUpdated;

    public function __construct()
    {
        $this->lastLogin = null;
        $this->password = null;
        $this->passwordMd5 = null;
        $this->activityState = self::ACTIVITY_ACTIVE;
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
     *
     * @return (Role|string)[] The user roles
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
     *
     * @return string The password
     */
    public function getPassword(): string
    {
        return $this->password ?: $this->passwordMd5;
    }

    /**
     * @param string $password
     * @return Account
     */
    public function setPassword(string $password): Account
    {
        $this->password = $password;
        return $this;
    }

    /**
     * Returns the salt that was originally used to encode the password.
     *
     * This can return null if the password was not encoded using a salt.
     *
     * @return string|null The salt
     */
    public function getSalt(): ?string
    {
        return null;
    }

    /**
     * @return int
     */
    public function getContextId(): int
    {
        return $this->contextId;
    }

    /**
     * @param int $contextId
     * @return Account
     */
    public function setContextId(int $contextId): Account
    {
        $this->contextId = $contextId;
        return $this;
    }

    /**
     * Returns the username used to authenticate the user.
     *
     * @return string The username
     */
    public function getUsername(): string
    {
        return $this->username;
    }

    /**
     * @param string $username
     * @return Account
     */
    public function setUsername(string $username): Account
    {
        $this->username = $username;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getPlainPassword()
    {
        return $this->plainPassword;
    }

    /**
     * @param mixed $plainPassword
     * @return Account
     */
    public function setPlainPassword($plainPassword)
    {
        $this->plainPassword = $plainPassword;
        return $this;
    }

    /**
     * @return string
     */
    public function getPasswordMd5(): string
    {
        return $this->passwordMd5;
    }

    /**
     * @param string|null $passwordMd5
     * @return Account
     */
    public function setPasswordMd5(?string $passwordMd5): Account
    {
        $this->passwordMd5 = $passwordMd5;
        return $this;
    }

    /**
     * @return string
     */
    public function getFirstname():? string
    {
        return $this->firstname;
    }

    /**
     * @param string $firstname
     * @return Account
     */
    public function setFirstname(string $firstname): Account
    {
        $this->firstname = $firstname;
        return $this;
    }

    /**
     * @return string
     */
    public function getLastname():? string
    {
        return $this->lastname;
    }

    /**
     * @param string $lastname
     * @return Account
     */
    public function setLastname(string $lastname): Account
    {
        $this->lastname = $lastname;
        return $this;
    }

    /**
     * @return string
     */
    public function getEmail():? string
    {
        return $this->email;
    }

    /**
     * @param string $email
     * @return Account
     */
    public function setEmail(string $email): Account
    {
        $this->email = $email;
        return $this;
    }

    /**
     * @return string
     */
    public function getLanguage(): string
    {
        return $this->language;
    }

    /**
     * @param string $language
     * @return Account
     */
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
        return $this->passwordMd5 !== null;
    }

    public function getEncoderName(): ?string
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

    /**
     * @return bool
     */
    public function isLocked(): bool
    {
        return $this->locked;
    }

    /**
     * @param bool $locked
     * @return Account
     */
    public function setLocked(bool $locked): Account
    {
        $this->locked = $locked;
        return $this;
    }

    /**
     * @return DateTime|null
     */
    public function getLastLogin(): ?DateTime
    {
        return $this->lastLogin;
    }

    /**
     * @param DateTime|null $lastLogin
     * @return Account
     */
    public function setLastLogin(?DateTime $lastLogin): Account
    {
        $this->lastLogin = $lastLogin;
        return $this;
    }

    /**
     * @return string
     */
    public function getActivityState(): string
    {
        return $this->activityState;
    }

    /**
     * @param string $activityState
     * @return Account
     */
    public function setActivityState(string $activityState): Account
    {
        if (!in_array($activityState, [
            self::ACTIVITY_ACTIVE,
            self::ACTIVITY_ACTIVE_NOTIFIED,
            self::ACTIVITY_IDLE,
            self::ACTIVITY_IDLE_NOTIFIED,
            self::ACTIVITY_ABANDONED,
        ])) {
            throw new InvalidArgumentException("Invalid activity");
        }

        $this->activityState = $activityState;
        return $this;
    }

    /**
     * @return DateTime|null
     */
    public function getActivityStateUpdated(): ?DateTime
    {
        return $this->activityStateUpdated;
    }

    /**
     * @param DateTime|null $activityStateUpdated
     * @return Room
     */
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
            $this->$key = $value;
        }
    }
}

