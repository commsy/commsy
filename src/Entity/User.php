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
use App\Repository\UserRepository;
use App\Utils\EntityDatesTrait;
use DateTime;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * User.
 */
#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'user')]
#[ORM\Index(columns: ['creator_id'], name: 'creator_idx')]
#[ORM\Index(columns: ['deletion_date', 'deleter_id'], name: 'deleted_idx')]
#[ORM\Index(columns: ['context_id'], name: 'context_idx')]
#[ORM\UniqueConstraint(name: 'unique_non_soft_deleted_idx', columns: ['user_id', 'auth_source', 'context_id', 'not_deleted'])]
class User
{
    use EntityDatesTrait;

    #[ApiProperty(description: 'The unique identifier.')]
    #[ORM\Column(name: 'item_id', type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[Groups(['api_read'])]
    public int $itemId;

    #[ORM\Column(name: 'context_id', type: Types::INTEGER, nullable: true)]
    private ?int $contextId = null;

    #[ORM\ManyToOne(targetEntity: 'User')]
    #[ORM\JoinColumn(name: 'creator_id', referencedColumnName: 'item_id')]
    private ?User $creator = null;

    #[ORM\ManyToOne(targetEntity: 'User')]
    #[ORM\JoinColumn(name: 'modifier_id', referencedColumnName: 'item_id')]
    private ?User $modifier = null;

    #[ORM\Column(name: 'deleter_id', type: Types::INTEGER, nullable: true)]
    private ?int $deleterId = null;

    #[ORM\Column(name: 'not_deleted', type: Types::BOOLEAN, insertable: false, updatable: false, generated: 'ALWAYS', columnDefinition: 'TINYINT(1) AS (IF (deleter_id IS NULL AND deletion_date IS NULL, 1, NULL)) PERSISTENT AFTER deletion_date')]
    private ?bool $isNotDeleted = null;

    #[ORM\Column(name: 'user_id', type: Types::STRING, length: 100, nullable: false)]
    #[Groups(['api_read'])]
    public string $userId;

    #[ORM\Column(name: 'status', type: Types::SMALLINT, nullable: false)]
    #[Groups(['api_read'])]
    private int $status = 0;

    #[ORM\Column(name: 'is_contact', type: Types::BOOLEAN, nullable: false)]
    private string $isContact = '0';

    #[ORM\Column(name: 'firstname', type: Types::STRING, length: 50, nullable: false)]
    #[Groups(['api_read'])]
    private string $firstname;

    #[ORM\Column(name: 'lastname', type: Types::STRING, length: 100, nullable: false)]
    #[Groups(['api_read'])]
    private string $lastname;

    #[ORM\Column(name: 'email', type: Types::STRING, length: 100, nullable: false)]
    private string $email;

    #[ORM\Column(name: 'city', type: Types::STRING, length: 100, nullable: false)]
    private string $city;

    #[ORM\Column(name: 'lastlogin', type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $lastlogin = null;
    #[ORM\Column(name: 'visible', type: Types::BOOLEAN, nullable: false)]
    private string $visible = '1';

    #[ORM\Column(name: 'extras', type: Types::TEXT, length: 16_777_215, nullable: true)]
    private ?string $extras = null;

    #[ORM\Column(name: 'auth_source', type: Types::INTEGER, nullable: true)]
    private ?int $authSource = null;

    #[ORM\Column(name: 'description', type: Types::TEXT, length: 65535, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(name: 'expire_date', type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $expireDate = null;

    #[ORM\Column(name: 'use_portal_email', type: Types::BOOLEAN)]
    private false $usePortalEmail = false;

    /**
     * Set contextId.
     *
     * @param int $contextId
     *
     * @return User
     */
    public function setContextId($contextId)
    {
        $this->contextId = $contextId;

        return $this;
    }

    /**
     * Get contextId.
     *
     * @return int
     */
    public function getContextId()
    {
        return $this->contextId;
    }

    /**
     * Set creator.
     *
     * @param User $modifier
     *
     * @return User
     */
    public function setCreator(User $creator = null)
    {
        $this->creator = $creator;

        return $this;
    }

    /**
     * Get creator.
     *
     * @return User
     */
    public function getCreator()
    {
        return $this->creator;
    }

    /**
     * Set modifier.
     *
     * @return User
     */
    public function setModifier(User $modifier = null)
    {
        $this->modifier = $modifier;

        return $this;
    }

    /**
     * Get modifier.
     *
     * @return User
     */
    public function getModifier()
    {
        return $this->modifier;
    }

    /**
     * Set deleterId.
     *
     * @param int $deleterId
     *
     * @return User
     */
    public function setDeleterId($deleterId)
    {
        $this->deleterId = $deleterId;

        return $this;
    }

    /**
     * Get deleterId.
     *
     * @return int
     */
    public function getDeleterId()
    {
        return $this->deleterId;
    }

    /**
     * isDeleted.
     */
    public function isDeleted(): bool
    {
        return null !== $this->deleterId && null !== $this->deletionDate;
    }

    /**
     * Set userId.
     *
     * @param string $userId
     *
     * @return User
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;

        return $this;
    }

    /**
     * Get userId.
     *
     * @return string
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * Set status.
     */
    public function setStatus(int $status): self
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status.
     */
    public function getStatus(): int
    {
        return $this->status;
    }

    /**
     * Set isContact.
     *
     * @param bool $isContact
     *
     * @return User
     */
    public function setIsContact($isContact)
    {
        $this->isContact = $isContact;

        return $this;
    }

    /**
     * Get isContact.
     *
     * @return bool
     */
    public function getIsContact()
    {
        return $this->isContact;
    }

    /**
     * Set firstname.
     *
     * @param string $firstname
     *
     * @return User
     */
    public function setFirstname($firstname)
    {
        $this->firstname = $firstname;

        return $this;
    }

    /**
     * Get firstname.
     *
     * @return string
     */
    public function getFirstname()
    {
        return $this->firstname;
    }

    /**
     * Set lastname.
     *
     * @param string $lastname
     *
     * @return User
     */
    public function setLastname($lastname)
    {
        $this->lastname = $lastname;

        return $this;
    }

    /**
     * Get lastname.
     *
     * @return string
     */
    public function getLastname()
    {
        return $this->lastname;
    }

    /**
     * Set email.
     *
     * @param string $email
     *
     * @return User
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Get email.
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Set city.
     *
     * @param string $city
     *
     * @return User
     */
    public function setCity($city)
    {
        $this->city = $city;

        return $this;
    }

    /**
     * Get city.
     *
     * @return string
     */
    public function getCity()
    {
        return $this->city;
    }

    /**
     * Set lastlogin.
     *
     * @param DateTime $lastlogin
     *
     * @return User
     */
    public function setLastlogin($lastlogin)
    {
        $this->lastlogin = $lastlogin;

        return $this;
    }

    /**
     * Get lastlogin.
     *
     * @return DateTime
     */
    public function getLastlogin()
    {
        return $this->lastlogin;
    }

    /**
     * Set visible.
     *
     * @param bool $visible
     *
     * @return User
     */
    public function setVisible($visible)
    {
        $this->visible = $visible;

        return $this;
    }

    /**
     * Get visible.
     *
     * @return bool
     */
    public function getVisible()
    {
        return $this->visible;
    }

    /**
     * Set extras.
     *
     * @param string $extras
     *
     * @return User
     */
    public function setExtras($extras)
    {
        $this->extras = $extras;

        return $this;
    }

    /**
     * Get extras.
     *
     * @return string
     */
    public function getExtras()
    {
        return $this->extras;
    }

    /**
     * Set authSource.
     *
     * @param int $authSource
     *
     * @return User
     */
    public function setAuthSource($authSource)
    {
        $this->authSource = $authSource;

        return $this;
    }

    /**
     * Get authSource.
     *
     * @return int
     */
    public function getAuthSource()
    {
        return $this->authSource;
    }

    /**
     * Set description.
     *
     * @param string $description
     *
     * @return User
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description.
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set expireDate.
     *
     * @param DateTime $expireDate
     *
     * @return User
     */
    public function setExpireDate($expireDate)
    {
        $this->expireDate = $expireDate;

        return $this;
    }

    /**
     * Get expireDate.
     *
     * @return DateTime
     */
    public function getExpireDate()
    {
        return $this->expireDate;
    }

    public function getItemId(): int
    {
        return $this->itemId;
    }

    public function isIndexable()
    {
        return null == $this->deleterId && null == $this->deletionDate;
    }

    public function getFullname()
    {
        return trim($this->getFirstname().' '.$this->getLastname());
    }
}
