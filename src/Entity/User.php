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

use App\Repository\UserRepository;
use App\Utils\EntityDatesTrait;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use OpenApi\Annotations as OA;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * User.
 */
#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'user')]
#[ORM\Index(name: 'creator_idx', columns: ['creator_id'])]
#[ORM\Index(name: 'deleted_idx', columns: ['deletion_date', 'deleter_id'])]
#[ORM\Index(name: 'context_idx', columns: ['context_id'])]
#[ORM\UniqueConstraint(name: 'unique_non_soft_deleted_idx', columns: ['user_id', 'auth_source', 'context_id', 'not_deleted'])]
class User
{
    use EntityDatesTrait;

    /**
     * @OA\Property(description="The unique identifier.")
     */
    #[ORM\Column(name: 'item_id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[Groups(['api_read'])]
    public int $itemId;

    #[ORM\Column(name: 'context_id', type: 'integer', nullable: true)]
    private $contextId;

    #[ORM\ManyToOne(targetEntity: 'User')]
    #[ORM\JoinColumn(name: 'creator_id', referencedColumnName: 'item_id')]
    private ?User $creator = null;

    #[ORM\ManyToOne(targetEntity: 'User')]
    #[ORM\JoinColumn(name: 'modifier_id', referencedColumnName: 'item_id')]
    private ?User $modifier = null;

    #[ORM\Column(name: 'deleter_id', type: 'integer', nullable: true)]
    private $deleterId;

    #[ORM\Column(name: 'not_deleted', type: 'boolean', insertable: false, updatable: false, generated: 'ALWAYS', columnDefinition: 'TINYINT(1) AS (IF (deleter_id IS NULL AND deletion_date IS NULL, 1, NULL)) PERSISTENT AFTER deletion_date')]
    private $isNotDeleted;

    #[ORM\Column(name: 'user_id', type: 'string', length: 100, nullable: false)]
    #[Groups(['api_read'])]
    public $userId;

    #[ORM\Column(name: 'status', type: 'smallint', nullable: false)]
    #[Groups(['api_read'])]
    private $status = '0';

    #[ORM\Column(name: 'is_contact', type: 'boolean', nullable: false)]
    private string $isContact = '0';

    #[ORM\Column(name: 'firstname', type: 'string', length: 50, nullable: false)]
    #[Groups(['api_read'])]
    private $firstname;

    #[ORM\Column(name: 'lastname', type: 'string', length: 100, nullable: false)]
    #[Groups(['api_read'])]
    private $lastname;

    #[ORM\Column(name: 'email', type: 'string', length: 100, nullable: false)]
    private $email;

    #[ORM\Column(name: 'city', type: 'string', length: 100, nullable: false)]
    private $city;

    #[ORM\Column(name: 'lastlogin', type: 'datetime', nullable: true)]
    private $lastlogin;
    #[ORM\Column(name: 'visible', type: 'boolean', nullable: false)]
    private string $visible = '1';

    #[ORM\Column(name: 'extras', type: 'text', length: 16_777_215, nullable: true)]
    private $extras;

    #[ORM\Column(name: 'auth_source', type: 'integer', nullable: true)]
    private $authSource;

    #[ORM\Column(name: 'description', type: 'text', length: 65535, nullable: true)]
    private $description;

    #[ORM\Column(name: 'expire_date', type: 'datetime', nullable: true)]
    private $expireDate;

    #[ORM\Column(name: 'use_portal_email', type: 'boolean')]
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
