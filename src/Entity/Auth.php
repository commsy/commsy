<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\Encoder\EncoderAwareInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Auth
 *
 * @ORM\Table(name="auth")
 * @ORM\Entity(repositoryClass="App\Repository\AuthRepository")"
 */
class Auth implements UserInterface, EncoderAwareInterface
{
    /**
     * @var integer
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     */
    private $contextId;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=100)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     */
    private $username;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=32)
     */
    private $passwordMd5;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $password;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=50, nullable=false)
     */
    private $firstname;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=50, nullable=false)
     */
    private $lastname;

    /**
     * @var string
     *
     * @ORM\Column(name="email", type="string", length=100, nullable=false)
     */
    private $email;

    /**
     * @var string
     *
     * @ORM\Column(name="language", type="string", length=10, nullable=false)
     */
    private $language;

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
    public function getRoles()
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
    public function getPassword()
    {
        return $this->password ?: $this->passwordMd5;
    }

    /**
     * @param string $password
     * @return Auth
     */
    public function setPassword(string $password): Auth
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
    public function getSalt()
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
     * @return Auth
     */
    public function setContextId(int $contextId): Auth
    {
        $this->contextId = $contextId;
        return $this;
    }

    /**
     * Returns the username used to authenticate the user.
     *
     * @return string The username
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * @param string $username
     * @return Auth
     */
    public function setUsername(string $username): Auth
    {
        $this->username = $username;
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
     * @return Auth
     */
    public function setPasswordMd5(?string $passwordMd5): Auth
    {
        $this->passwordMd5 = $passwordMd5;
        return $this;
    }

    /**
     * @return string
     */
    public function getFirstname(): string
    {
        return $this->firstname;
    }

    /**
     * @param string $firstname
     * @return Auth
     */
    public function setFirstname(string $firstname): Auth
    {
        $this->firstname = $firstname;
        return $this;
    }

    /**
     * @return string
     */
    public function getLastname(): string
    {
        return $this->lastname;
    }

    /**
     * @param string $lastname
     * @return Auth
     */
    public function setLastname(string $lastname): Auth
    {
        $this->lastname = $lastname;
        return $this;
    }

    /**
     * @return string
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * @param string $email
     * @return Auth
     */
    public function setEmail(string $email): Auth
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
     * @return Auth
     */
    public function setLanguage(string $language): Auth
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

    public function hasLegacyPassword()
    {
        return $this->passwordMd5 !== null;
    }

    public function getEncoderName()
    {
        if ($this->hasLegacyPassword()) {
            return 'legacy_encoder';
        }

        return null;
    }
}

