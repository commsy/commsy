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
     * @var array
     *
     * @ORM\Column(name="extras", type="object", nullable=true)
     */
    protected $extras;

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
    private $createRoom;

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

    /**
     * @return string|null
     */
    public function getSourceOriginName():? string
    {
        if(!is_null($this->getExtras())){
            return $this->getExtras()['SOURCE_ORIGIN_NAME'] ?? '';
        }
        return '';
    }

    /**
     * @param string|null $identityProviderUpdates
     */
    public function setSourceOriginName(?string $identityProviderUpdates)
    {
        $extras = $this->getExtras();
        $extras['SOURCE_ORIGIN_NAME'] = $identityProviderUpdates;
        $this->setExtras($extras);
    }

    /**
     * @return bool|null
     */
    public function getAvailable():? bool
    {
        return $this->getExtras()['AVAILABLE'] ?? false;
    }

    /**
     * @param bool|null $available
     */
    public function setAvailable(?bool $available)
    {
        $extras = $this->getExtras();
        $extras['AVAILABLE'] = $available;
        $this->setExtras($extras);
    }

    /**
     * @return bool|null
     */
    public function getDirectLogin():? bool
    {
        return $this->getExtras()['DIRECT_LOGIN'] ?? false;
    }

    /**
     * @param bool|null $directLogin
     */
    public function setDirectLogin(?bool $directLogin)
    {
        $extras = $this->getExtras();
        $extras['DIRECT_LOGIN'] = $directLogin;
        $this->setExtras($extras);
    }

    /**
     * @return string|null
     */
    public function getSessionInitiatorURL():? string
    {
        return $this->getExtras()['SESSION_INITIATOR_URL'] ?? '';
    }

    /**
     * @param string|null $sessionInitiatorURL
     */
    public function setSessionInitiatorURL(?string $sessionInitiatorURL)
    {
        $extras = $this->getExtras();
        $extras['SESSION_INITIATOR_URL'] = $sessionInitiatorURL;
        $this->setExtras($extras);
    }

    /**
     * @return string|null
     */
    public function getSessionLogoutURL():? string
    {
        return $this->getExtras()['SESSION_LOGOUT_URL'] ?? '';
    }

    /**
     * @param string|null $sessionLogoutURL
     */
    public function setSessionLogoutURL(?string $sessionLogoutURL)
    {
        $extras = $this->getExtras();
        $extras['SESSION_LOGOUT_URL'] = $sessionLogoutURL;
        $this->setExtras($extras);
    }

    /**
     * @return string|null
     */
    public function getChangePasswordURL():? string
    {
        return $this->getExtras()['CHANGE_PASSWORD_URL'] ?? '';
    }

    /**
     * @param string|null $changePasswordURL
     */
    public function setChangePasswordURL(?string $changePasswordURL)
    {
        $extras = $this->getExtras();
        $extras['CHANGE_PASSWORD_URL'] = $changePasswordURL;
        $this->setExtras($extras);
    }

    /**
     * @return string|null
     */
    public function getUsername():? string
    {
        return $this->getExtras()['USERNAME'] ?? '';
    }

    /**
     * @param string|null $username
     */
    public function setUsername(?string $username)
    {
        $extras = $this->getExtras();
        $extras['USERNAME'] = $username;
        $this->setExtras($extras);
    }

    /**
     * @return string|null
     */
    public function getFirstName():? string
    {
        return $this->getExtras()['FIRSTNAME'] ?? '';
    }

    /**
     * @param string|null $firstName
     */
    public function setFirstName(?string $firstName)
    {
        $extras = $this->getExtras();
        $extras['FIRSTNAME'] = $firstName;
        $this->setExtras($extras);
    }

    /**
     * @return string|null
     */
    public function getLastName():? string
    {
        return $this->getExtras()['LASTNAME'] ?? '';
    }

    /**
     * @param string|null $lastName
     */
    public function setLastName(?string $lastName)
    {
        $extras = $this->getExtras();
        $extras['LASTNAME'] = $lastName;
        $this->setExtras($extras);
    }

    /**
     * @return string|null
     */
    public function getMail():? string
    {
        return $this->getExtras()['MAIL'] ?? '';
    }

    /**
     * @param string|null $mail
     */
    public function setMail(?string $mail)
    {
        $extras = $this->getExtras();
        $extras['MAIL'] = $mail;
        $this->setExtras($extras);
    }

    /**
     * @return bool|null
     */
    public function isIdentityProviderUpdates():? bool
    {
        return $this->getExtras()['IDENTITY_PROVIDER_UPDATES'] ?? false;
    }

    /**
     * @param bool|null $identityProviderUpdates
     */
    public function setIdentityProviderUpdates(?bool $identityProviderUpdates)
    {
        $extras = $this->getExtras();
        $extras['IDENTITY_PROVIDER_UPDATES'] = $identityProviderUpdates;
        $this->setExtras($extras);
    }

    /**
     * @return string|null
     */
    public function getUserIdLdapField():? string
    {
        return $this->getExtras()['USER_ID_LDAP_FIELD'] ?? '';
    }

    /**
     * @param string|null $userIdLdapField
     */
    public function setUserIdLdapField(?string $userIdLdapField)
    {
        $extras = $this->getExtras();
        $extras['USER_ID_LDAP_FIELD'] = $userIdLdapField;
        $this->setExtras($extras);
    }

    /**
     * @return string|null
     */
    public function getPath():? string
    {
        return $this->getExtras()['PATH'] ?? '';
    }

    /**
     * @param string|null $path
     */
    public function setPath(?string $path)
    {
        $extras = $this->getExtras();
        $extras['PATH'] = $path;
        $this->setExtras($extras);
    }

    /**
     * @return string|null
     */
    public function getPassword():? string
    {
        return $this->getExtras()['PASSWORD'] ?? '';
    }

    /**
     * @param string|null $password
     */
    public function setPassword(?string $password)
    {
        $extras = $this->getExtras();
        $extras['PASSWORD'] = $password;
        $this->setExtras($extras);
    }

    /**
     * @return string|null
     */
    public function getEncryption():? string
    {
        return $this->getExtras()['ENCRYPTION'] ?? '';
    }

    /**
     * @param string|null $encryption
     */
    public function setEncryption(?string $encryption)
    {
        $extras = $this->getExtras();
        $extras['ENCRYPTION'] = $encryption;
        $this->setExtras($extras);
    }

    /**
     * @return string|null
     */
    public function getServerAddress():? string
    {
        return $this->getExtras()['SERVER_ADDRESS'] ?? '';
    }

    /**
     * @param string|null $serverAddress
     */
    public function setServerAddress(?string $serverAddress)
    {
        $extras = $this->getExtras();
        $extras['SERVER_ADDRESS'] = $serverAddress;
        $this->setExtras($extras);
    }

    /**
     * @return bool|null
     */
    public function isChangeUserID():? bool
    {
        return $this->getExtras()['CHANGE_USER_ID'] ?? false;
    }

    /**
     * @param bool|null $changeUserID
     */
    public function setChangeUserID(?bool $changeUserID)
    {
        $extras = $this->getExtras();
        $extras['CHANGE_USER_ID'] = $changeUserID;
        $this->setExtras($extras);
    }

    /**
     * @return bool|null
     */
    public function isChangeIdentification():? bool
    {
        return $this->getExtras()['CHANGE_IDENTIFICATION'] ?? false;
    }

    /**
     * @param bool|null $changeIdentification
     */
    public function setChangeIdentification(?bool $changeIdentification)
    {
        $extras = $this->getExtras();
        $extras['CHANGE_IDENTIFICATION'] = $changeIdentification;
        $this->setExtras($extras);
    }

    /**
     * @return string|null
     */
    public function getCreateIdentification():? string
    {
        return $this->getExtras()['CREATE_IDENTIFICATION'] ?? '';
    }

    /**
     * @param string|null $createIdentification
     */
    public function setCreateIdentification(?string $createIdentification)
    {
        $extras = $this->getExtras();
        $extras['CREATE_IDENTIFICATION'] = $createIdentification;
        $this->setExtras($extras);
    }

    /**
     * @return string|null
     */
    public function getCreateUserIDs():? string
    {
        return $this->getExtras()['CREATE_USER_IDS'] ?? '';
    }

    /**
     * @param string|null $createUserIDs
     */
    public function setCreateUserIDs(?string $createUserIDs)
    {
        $extras = $this->getExtras();
        $extras['CREATE_USER_IDS'] = $createUserIDs;
        $this->setExtras($extras);
    }

    /**
     * @return string|null
     */
    public function getMailRegEx():? string
    {
        return $this->getExtras()['MAIL_REG_EX'] ?? '';
    }

    /**
     * @param string|null $mailRegEx
     */
    public function setMailRegEx(?string $mailRegEx)
    {
        $extras = $this->getExtras();
        $extras['MAIL_REG_EX'] = $mailRegEx;
        $this->setExtras($extras);
    }

    /**
     * @return string|null
     */
    public function getContactTelephone():? string
    {
        return $this->getExtras()['CONTACT_TELEPHONE'] ?? '';
    }

    /**
     * @param string|null $contactTelephone
     */
    public function setContactTelephone(?string $contactTelephone)
    {
        $extras = $this->getExtras();
        $extras['CONTACT_TELEPHONE'] = $contactTelephone;
        $this->setExtras($extras);
    }

    /**
     * @return string|null
     */
    public function getContactMail():? string
    {
        return $this->getExtras()['CONTACT_MAIL'] ?? '';
    }

    /**
     * @param string|null $contactMail
     */
    public function setContactMail(?string $contactMail)
    {
        $extras = $this->getExtras();
        $extras['CONTACT_MAIL'] = $contactMail;
        $this->setExtras($extras);
    }
}
