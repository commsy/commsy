<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use App\Controller\Api\GetAccountsCheckLocalLogin;
use App\Dto\LocalLoginInput;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\Encoder\EncoderAwareInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Annotation\Groups;
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
 *     },
 *     normalizationContext={
 *         "groups"={"api"}
 *     },
 *     denormalizationContext={
 *         "groups"={"api"}
 *     }
 * )
 */
class Account implements UserInterface, EncoderAwareInterface, \Serializable
{
    /**
     * @var integer
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     *
     * @Assert\NotBlank(groups={"checkLocalLoginValidation"})
     *
     * @Groups({"api_check_local_login"})
     */
    private $contextId;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=100)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     *
     * @Assert\NotBlank(groups={"Default", "checkLocalLoginValidation"})
     * @Assert\Regex(pattern="/^(root|guest)$/i", match=false, message="{{ value }} is a reserved name")
     *
     * @Groups({"api", "api_check_local_login"})
     */
    private $username;

    /**
     * @Assert\NotBlank()
     * @Assert\NotCompromisedPassword()
     * @Assert\Length(max=4096, min=8, allowEmptyString=false, minMessage="Your password must be at least {{ limit }} characters long.")
     * @Assert\Regex(pattern="/(*UTF8)[\p{Ll}\p{Lm}\p{Lo}]/", message="Your password must contain at least one lowercase character.")
     * @Assert\Regex(pattern="/(*UTF8)[\p{Lu}\p{Lt}]/", message="Your password must contain at least one uppercase character.")
     * @Assert\Regex(pattern="/[[:punct:]]/", message="Your password must contain at least one special character.")
     * @Assert\Regex(pattern="/\p{Nd}/", message="Your password must contain at least one numeric character.")
     */
    private $plainPassword;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=32)
     */
    private $passwordMd5;

    /**
     * @ORM\Column(type="string", length=255)
     *
     * @Assert\NotBlank(groups={"checkLocalLoginValidation"})
     *
     * @Groups({"api_check_local_login"})
     */
    private $password;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=50, nullable=false)
     *
     * @Assert\NotBlank()
     *
     * @Groups({"api"})
     */
    private $firstname;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=50, nullable=false)
     *
     * @Assert\NotBlank()
     *
     * @Groups({"api"})
     */
    private $lastname;

    /**
     * @var string
     *
     * @ORM\Column(name="email", type="string", length=100, nullable=false)
     *
     * @Assert\Email()
     *
     * @Groups({"api"})
     */
    private $email;

    /**
     * @var string
     *
     * @ORM\Column(name="language", type="string", length=10, nullable=false)
     */
    private $language;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\AuthSource")
     * @ORM\JoinColumn(nullable=false)
     */
    private $authSource;

    /**
     * @var bool
     *
     * @ORM\Column(name="locked", type="boolean")
     *
     * @Groups({"api"})
     */
    private $locked = false;

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
    public function getSalt()
    {
        return null;
    }

    /**
     * @return int
     */
    public function getContextId(): ?int
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
    public function getUsername()
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

    public function getAuthSource(): ?AuthSource
    {
        return $this->authSource;
    }

    public function setAuthSource(?AuthSource $authSource): self
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
    public function setLocked(bool $locked): self
    {
        $this->locked = $locked;
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

