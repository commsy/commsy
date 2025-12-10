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
use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'user')]
#[ORM\Index(name: 'creator_idx', columns: ['creator_id'])]
#[ORM\Index(name: 'deleted_idx', columns: ['deletion_date', 'deleter_id'])]
#[ORM\Index(name: 'context_idx', columns: ['context_id'])]
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

    #[ORM\ManyToOne(targetEntity: Room::class)]
    #[ORM\JoinColumn(name: 'context_id', referencedColumnName: 'item_id', nullable: true)]
    private ?Room $room = null;

    #[ORM\ManyToOne(targetEntity: Portal::class)]
    #[ORM\JoinColumn(name: 'portal_id', referencedColumnName: 'id', nullable: true)]
    private ?Portal $portal = null;

    #[ORM\OneToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'creator_id', referencedColumnName: 'item_id')]
    private ?User $creator = null;

    #[ORM\OneToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'modifier_id', referencedColumnName: 'item_id')]
    private ?User $modifier = null;

    #[ORM\Column(name: 'deleter_id', type: Types::INTEGER, nullable: true)]
    private ?int $deleterId = null;

    #[ORM\Column(name: 'not_deleted', type: Types::BOOLEAN, insertable: false, updatable: false, columnDefinition: 'TINYINT(1) AS (IF (deleter_id IS NULL AND deletion_date IS NULL, 1, NULL)) PERSISTENT AFTER deletion_date', generated: 'ALWAYS')]
    private ?bool $isNotDeleted = null;

    /*
     * Currently, the account is still allowed to be null. When deleting an account it is removed from the accounts table
     * (no soft-deletion), but the user entries will still remain in the user table. Right now they are not removed at
     * all.
     */
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?Account $account = null;

    #[ORM\Column(name: 'user_id', type: Types::STRING, length: 100, nullable: false)]
    #[Groups(['api_read'])]
    public string $userId;

    #[ORM\Column(name: 'status', type: Types::SMALLINT, nullable: false)]
    #[Groups(['api_read'])]
    private int $status = 0;

    #[ORM\Column(name: 'is_contact', type: Types::BOOLEAN, nullable: false, options: ['default' => false])]
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

    #[ORM\Column(name: 'use_portal_email', type: Types::BOOLEAN, options: ['default' => false])]
    private bool $usePortalEmail = false;

    public function getRoom(): ?Room
    {
        return $this->room;
    }

    public function setRoom(?Room $room): static
    {
        $this->room = $room;
        return $this;
    }

    public function getContextId(): ?int
    {
        return $this->getRoom()?->getItemId();
    }

    public function setPortal(?Portal $portal = null): static
    {
        $this->portal = $portal;
        return $this;
    }

    public function getPortal(): ?Portal
    {
        return $this->portal;
    }

    public function setCreator(?User $creator = null): static
    {
        $this->creator = $creator;

        return $this;
    }

    public function getCreator(): ?User
    {
        return $this->creator;
    }

    public function setModifier(?User $modifier = null): static
    {
        $this->modifier = $modifier;

        return $this;
    }

    public function getModifier(): ?User
    {
        return $this->modifier;
    }

    public function setDeleterId(?int $deleterId): static
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

    public function getAccount(): ?Account
    {
        return $this->account;
    }

    public function setAccount(?Account $account): static
    {
        $this->account = $account;

        return $this;
    }

    public function setUserId(string $userId): static
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

    public function isRequested(): bool
    {
        return $this->status === 1;
    }

    public function isModerator(): bool
    {
        return $this->status === 3;
    }

    public function setIsContact(bool $isContact): static
    {
        $this->isContact = $isContact;

        return $this;
    }

    public function getIsContact(): bool
    {
        return $this->isContact;
    }

    public function setFirstname(string $firstname): static
    {
        $this->firstname = $firstname;

        return $this;
    }

    public function getFirstname(): string
    {
        return $this->firstname;
    }

    public function setLastname($lastname): static
    {
        $this->lastname = $lastname;

        return $this;
    }

    public function getLastname(): string
    {
        return $this->lastname;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setCity(string $city): static
    {
        $this->city = $city;

        return $this;
    }

    public function getCity(): string
    {
        return $this->city;
    }

    public function setLastlogin(?DateTimeInterface $lastlogin): static
    {
        $this->lastlogin = $lastlogin;

        return $this;
    }

    public function getLastlogin(): ?DateTimeInterface
    {
        return $this->lastlogin;
    }

    public function setVisible(bool $visible): static
    {
        $this->visible = $visible;

        return $this;
    }

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

    public function getUserComment(): string
    {
        return $this->extras['USERCOMMENT'] ?? '';
    }

    public function getTitle(): string
    {
        return $this->extras['USERTITLE'] ?? '';
    }

    public function getTelephone(): string
    {
        return $this->extras['USERTELEPHONE'] ?? '';
    }

    public function getCellularphone(): string
    {
        return $this->extras['USERCELLULARPHONE'] ?? '';
    }

    public function getBirthday(): string
    {
        return $this->extras['USERBIRTHDAY'] ?? '';
    }

    public function getStreet(): string
    {
        return $this->extras['USERSTREET'] ?? '';
    }

    public function getZipcode(): string
    {
        return $this->extras['USERZIPCODE'] ?? '';
    }

    public function getOffice(): string
    {
        return $this->extras['OFFICE'] ?? '';
    }

    public function getOrganisation(): string
    {
        return $this->extras['USERORGANISATION'] ?? '';
    }

    public function getPosition(): string
    {
        return $this->extras['USERPOSITION'] ?? '';
    }

    public function getHomepage(): string
    {
        return $this->extras['USERHOMEPAGE'] ?? '';
    }

    public function getMSN(): string
    {
        return $this->extras['MSN'] ?? '';
    }

    public function getSkype(): string
    {
        return $this->extras['SKYPE'] ?? '';
    }

    public function getICQ(): string
    {
        return $this->extras['ICQ'] ?? '';
    }

    public function getYahoo(): string
    {
        return $this->extras['YAHOO'] ?? '';
    }

    public function getLanguage(): string
    {
        return $this->extras['LANGUAGE'] ?? 'de';
    }

    public function isEmailVisible(): bool
    {
        $visible = $this->extras['EMAIL_VISIBILITY'] ?? '';
        return $visible != '-1';
    }

    public function setAuthSource(?int $authSource): static
    {
        $this->authSource = $authSource;

        return $this;
    }

    public function getAuthSource(): ?int
    {
        return $this->authSource;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getItemId(): int
    {
        return $this->itemId;
    }

    public function isIndexable(): bool
    {
        return null == $this->deleterId && null == $this->deletionDate;
    }

    public function getFullname(): string
    {
        return trim("{$this->getFirstname()} {$this->getLastname()}");
    }
}
