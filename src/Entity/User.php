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
use DateTimeInterface;
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
    private bool $isContact = false;

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
    private ?DateTimeInterface $lastlogin = null;

    #[ORM\Column(name: 'visible', type: Types::BOOLEAN, nullable: false)]
    private bool $visible = true;

    #[ORM\Column(name: 'extras', type: Types::ARRAY, nullable: true)]
    private ?array $extras = null;

    #[ORM\Column(name: 'auth_source', type: Types::INTEGER, nullable: true)]
    private ?int $authSource = null;

    #[ORM\Column(name: 'description', type: Types::TEXT, length: 65535, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(name: 'expire_date', type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?DateTimeInterface $expireDate = null;

    #[ORM\Column(name: 'use_portal_email', type: Types::BOOLEAN)]
    private bool $usePortalEmail = false;

    /**
     * Set contextId.
     *
     * @param int $contextId
     */
    public function setContextId($contextId): static
    {
        $this->contextId = $contextId;

        return $this;
    }

    /**
     * Get contextId.
     */
    public function getContextId(): ?int
    {
        return $this->contextId;
    }

    /**
     * Set creator.
     *
     * @param User|null $creator
     */
    public function setCreator(User $creator = null): static
    {
        $this->creator = $creator;

        return $this;
    }

    public function getCreator(): ?User
    {
        return $this->creator;
    }

    public function setModifier(User $modifier = null): static
    {
        $this->modifier = $modifier;

        return $this;
    }

    public function getModifier(): ?User
    {
        return $this->modifier;
    }

    /**
     * Set deleterId.
     *
     * @param int $deleterId
     */
    public function setDeleterId($deleterId): static
    {
        $this->deleterId = $deleterId;

        return $this;
    }

    public function getDeleterId(): ?int
    {
        return $this->deleterId;
    }

    public function isDeleted(): bool
    {
        return null !== $this->deleterId && null !== $this->deletionDate;
    }

    /**
     * Set userId.
     *
     * @param string $userId
     */
    public function setUserId($userId): static
    {
        $this->userId = $userId;

        return $this;
    }

    public function getUserId(): string
    {
        return $this->userId;
    }

    public function setStatus(int $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    /**
     * Set isContact.
     *
     * @param bool $isContact
     */
    public function setIsContact($isContact): static
    {
        $this->isContact = $isContact;

        return $this;
    }

    public function getIsContact(): bool
    {
        return $this->isContact;
    }

    /**
     * Set firstname.
     *
     * @param string $firstname
     */
    public function setFirstname($firstname): static
    {
        $this->firstname = $firstname;

        return $this;
    }

    /**
     * Get firstname.
     *
     * @return string
     */
    public function getFirstname(): string
    {
        return $this->firstname;
    }

    /**
     * Set lastname.
     *
     * @param string $lastname
     */
    public function setLastname($lastname): static
    {
        $this->lastname = $lastname;

        return $this;
    }

    /**
     * Get lastname.
     *
     * @return string
     */
    public function getLastname(): string
    {
        return $this->lastname;
    }

    /**
     * Set email.
     *
     * @param string $email
     */
    public function setEmail($email): static
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Get email.
     *
     * @return string
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * Set city.
     *
     * @param string $city
     */
    public function setCity($city): static
    {
        $this->city = $city;

        return $this;
    }

    /**
     * Get city.
     *
     * @return string
     */
    public function getCity(): string
    {
        return $this->city;
    }

    /**
     * Set lastlogin.
     *
     * @param DateTime $lastlogin
     */
    public function setLastlogin($lastlogin): static
    {
        $this->lastlogin = $lastlogin;

        return $this;
    }

    /**
     * Get lastlogin.
     */
    public function getLastlogin(): ?DateTime
    {
        return $this->lastlogin;
    }

    /**
     * Set visible.
     *
     * @param bool $visible
     */
    public function setVisible($visible): static
    {
        $this->visible = $visible;

        return $this;
    }

    /**
     * Get visible.
     */
    public function getVisible(): bool
    {
        return $this->visible;
    }

    public function setExtras(array $extras): static
    {
        $this->extras = $extras;

        return $this;
    }

    public function getExtras(): ?array
    {
        return $this->extras;
    }

    /**
     * Set authSource.
     *
     * @param int $authSource
     */
    public function setAuthSource($authSource): static
    {
        $this->authSource = $authSource;

        return $this;
    }

    /**
     * Get authSource.
     */
    public function getAuthSource(): ?int
    {
        return $this->authSource;
    }

    /**
     * Set description.
     *
     * @param string $description
     */
    public function setDescription($description): static
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description.
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * Set expireDate.
     *
     * @param DateTime $expireDate
     */
    public function setExpireDate($expireDate): static
    {
        $this->expireDate = $expireDate;

        return $this;
    }

    /**
     * Get expireDate.
     */
    public function getExpireDate(): ?DateTime
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
