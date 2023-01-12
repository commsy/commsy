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
use App\Repository\RoomRepository;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use OpenApi\Annotations as OA;
use InvalidArgumentException;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * Room.
 *
 * @ApiResource(
 *     security="is_granted('ROLE_API_READ')",
 *     collectionOperations={
 *         "get",
 *     },
 *     itemOperations={
 *         "get",
 *     },
 *     normalizationContext={
 *         "groups"={"api"},
 *     },
 *     denormalizationContext={
 *         "groups"={"api"},
 *     }
 * )
 */
#[ORM\Entity(repositoryClass: RoomRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ORM\Table(name: 'room')]
#[ORM\Index(name: 'activity', columns: ['activity'])]
#[ORM\Index(name: 'context_id', columns: ['context_id'])]
#[ORM\Index(name: 'delete_idx', columns: ['deleter_id', 'deletion_date'])]
#[ORM\Index(name: 'lastlogin', columns: ['lastlogin'])]
#[ORM\Index(name: 'search_idx', columns: ['title', 'contact_persons'])]
#[ORM\Index(name: 'status', columns: ['status'])]
#[ORM\Index(name: 'title', columns: ['title'])]
#[ORM\Index(name: 'type', columns: ['type'])]
class Room
{
    public const ACTIVITY_ACTIVE = 'active';
    public const ACTIVITY_ACTIVE_NOTIFIED = 'active_notified';
    public const ACTIVITY_IDLE = 'idle';
    public const ACTIVITY_IDLE_NOTIFIED = 'idle_notified';
    public const ACTIVITY_ABANDONED = 'abandoned';

    /**
     * @var int
     *
     * @OA\Property(description="The unique identifier.")
     */
    #[ORM\Column(name: 'item_id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[Groups(['api'])]
    private $itemId;

    #[ORM\Column(name: 'context_id', type: 'integer', nullable: true)]
    private ?int $contextId = null;

    #[ORM\ManyToOne(targetEntity: 'User')]
    #[ORM\JoinColumn(name: 'creator_id', referencedColumnName: 'item_id', nullable: true)]
    private ?User $creator = null;

    #[ORM\ManyToOne(targetEntity: 'User')]
    #[ORM\JoinColumn(name: 'modifier_id', referencedColumnName: 'item_id', nullable: true)]
    private ?User $modifier = null;

    #[ORM\ManyToOne(targetEntity: 'User')]
    #[ORM\JoinColumn(name: 'deleter_id', referencedColumnName: 'item_id', nullable: true)]
    private ?User $deleter = null;

    #[ORM\Column(name: 'creation_date', type: 'datetime', nullable: false)]
    #[Groups(['api'])]
    private DateTime $creationDate;

    #[ORM\Column(name: 'modification_date', type: 'datetime', nullable: false)]
    #[Groups(['api'])]
    private DateTime $modificationDate;

    #[ORM\Column(name: 'deletion_date', type: 'datetime', nullable: true)]
    private ?DateTime $deletionDate = null;

    /**
     * @OA\Property(type="string", maxLength=255)
     */
    #[ORM\Column(name: 'title', type: 'string', length: 255)]
    #[Groups(['api'])]
    private string $title;

    #[ORM\Column(name: 'extras', type: 'mbarray', nullable: true)]
    private ?array $extras = null;

    #[ORM\Column(name: 'status', type: 'string', length: 20)]
    private string $status;

    #[ORM\Column(name: 'archived', type: 'boolean', nullable: false, options: ['default' => 0])]
    private bool $archived;

    #[ORM\Column(name: 'activity', type: 'integer', options: ['default' => 0])]
    private int $activity = 0;

    /**
     * @OA\Property(description="Either project or community")
     */
    #[ORM\Column(name: 'type', type: 'string', length: 20)]
    #[Groups(['api'])]
    private string $type = 'project';

    #[ORM\Column(name: 'public', type: 'boolean', options: ['default' => 0])]
    private bool $public = false;

    #[ORM\Column(name: 'is_open_for_guests', type: 'boolean', options: ['default' => 0])]
    private bool $openForGuests = false;

    #[ORM\Column(name: 'continuous', type: 'smallint', options: ['default' => -1])]
    private int $continuous = -1;

    #[ORM\Column(name: 'template', type: 'smallint', options: ['default' => -1])]
    private int $template = -1;

    #[ORM\Column(name: 'contact_persons', type: 'string', length: 255, nullable: true)]
    private ?string $contactPersons = null;

    /**
     * @OA\Property(type="string", nullable=true)
     */
    #[ORM\Column(name: 'room_description', type: 'string', length: 10000, nullable: true)]
    #[Groups(['api'])]
    private ?string $roomDescription = null;

    #[ORM\Column(name: 'lastlogin', type: 'datetime', nullable: true)]
    private ?DateTime $lastlogin = null;

    #[ORM\Column(name: 'activity_state', type: 'string', length: 15, options: ['default' => 'active'])]
    private string $activityState;

    #[ORM\Column(name: 'slug', type: 'string', length: 255, nullable: true)]
    private ?string $slug = null;

    #[ORM\Column(name: 'activity_state_updated', type: 'datetime', nullable: true)]
    private ?DateTime $activityStateUpdated = null;

    public function __construct()
    {
        $this->activityState = self::ACTIVITY_ACTIVE;
        $this->creationDate = new DateTime();
        $this->modificationDate = new DateTime();
    }

    public function isIndexable(): bool
    {
        return null == $this->deleter && null == $this->deletionDate && !$this->isArchived();
    }

    public function getLanguage()
    {
        $extras = $this->getExtras();

        if (isset($extras['LANGUAGE'])) {
            return $extras['LANGUAGE'];
        }

        return 'user';
    }

    public function setLanguage($language): Room
    {
        $extras = $this->getExtras();
        $extras['LANGUAGE'] = $language;
        $this->setExtras($extras);

        return $this;
    }

    public function getLogo(): string
    {
        return '';
    }

    public function getAccessCheck(): string
    {
        $extras = $this->getExtras();

        if (isset($extras['CHECKNEWMEMBERS'])) {
            $checkNewMembers = $extras['CHECKNEWMEMBERS'];

            $mapping = [-1 => 'never', 2 => 'sometimes', 3 => 'code'];

            if (isset($mapping[$checkNewMembers])) {
                return $mapping[$checkNewMembers];
            }
        }

        return 'always';
    }

    public function setAccessCheck($access): Room
    {
        $mapping = ['never' => -1, 'sometimes' => 2, 'code' => 3];

        $extras = $this->getExtras();
        $extras['CHECKNEWMEMBERS'] = $mapping[$access];
        $this->setExtras($extras);

        return $this;
    }

    public function isProjectRoom(): bool
    {
        return 'project' === $this->type;
    }

    public function isCommunityRoom(): bool
    {
        return 'community' === $this->type;
    }

    public function isMaterialOpenForGuests(): bool
    {
        $extras = $this->getExtras();
        if (isset($extras['MATERIAL_GUESTS'])) {
            $materialOpenForGuests = $extras['MATERIAL_GUESTS'];

            return 1 === $materialOpenForGuests;
        }

        return false;
    }

    public function setIsMaterialOpenForGuests($open): Room
    {
        $extras = $this->getExtras();
        $extras['MATERIAL_GUESTS'] = $open;
        $this->setExtras($extras);

        return $this;
    }

    public function isAssignmentRestricted(): bool
    {
        $extras = $this->getExtras();
        if (isset($extras['ROOMASSOCIATION'])) {
            $roomAssociation = $extras['ROOMASSOCIATION'];

            return 'onlymembers' === $roomAssociation;
        }

        return false;
    }

    public function setAssignmentRestricted($isRestricted): Room
    {
        $roomAssociation = 'forall';

        if ($isRestricted) {
            $roomAssociation = 'onlymembers';
        }

        $extras = $this->getExtras();
        $extras['ROOMASSOCIATION'] = $roomAssociation;
        $this->setExtras($extras);

        return $this;
    }

    /**
     * Get itemId.
     */
    public function getItemId(): int
    {
        return $this->itemId;
    }

    /**
     * Set contextId.
     *
     * @param int $contextId
     */
    public function setContextId($contextId): Room
    {
        $this->contextId = $contextId;

        return $this;
    }

    /**
     * Get contextId.
     *
     * @return int
     */
    public function getContextId(): ?int
    {
        return $this->contextId;
    }

    #[ORM\PrePersist]
    public function setInitialDateValues()
    {
        $this->creationDate = new DateTime('now');
        $this->modificationDate = new DateTime('now');
    }

    /**
     * Set creationDate.
     *
     * @param DateTime $creationDate
     */
    public function setCreationDate($creationDate): Room
    {
        $this->creationDate = $creationDate;

        return $this;
    }

    /**
     * Get creationDate.
     */
    public function getCreationDate(): DateTime
    {
        return $this->creationDate;
    }

    /**
     * Set modificationDate.
     *
     * @param DateTime $modificationDate
     */
    public function setModificationDate($modificationDate): Room
    {
        $this->modificationDate = $modificationDate;

        return $this;
    }

    /**
     * Get modificationDate.
     */
    public function getModificationDate(): DateTime
    {
        return $this->modificationDate;
    }

    /**
     * Set deletionDate.
     */
    public function setDeletionDate(DateTime $deletionDate): Room
    {
        $this->deletionDate = $deletionDate;

        return $this;
    }

    /**
     * Get deletionDate.
     *
     * @return DateTime
     */
    public function getDeletionDate(): ?DateTime
    {
        return $this->deletionDate;
    }

    /**
     * Set title.
     */
    public function setTitle(string $title): Room
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title.
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * Set extras.
     */
    public function setExtras(array $extras): Room
    {
        $this->extras = $extras;

        return $this;
    }

    /**
     * Get extras.
     */
    public function getExtras(): ?array
    {
        return $this->extras;
    }

    /**
     * Set status.
     *
     * @param string $status
     */
    public function setStatus($status): Room
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status.
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * Set activity.
     *
     * @param int $activity
     */
    public function setActivity($activity): Room
    {
        $this->activity = $activity;

        return $this;
    }

    /**
     * Get activity.
     */
    public function getActivity(): int
    {
        return $this->activity;
    }

    /**
     * Set type.
     *
     * @param string $type
     */
    public function setType($type): Room
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type.
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Set public.
     *
     * @param bool $public
     */
    public function setPublic($public): Room
    {
        $this->public = $public;

        return $this;
    }

    /**
     * Get public.
     */
    public function getPublic(): bool
    {
        return $this->public;
    }

    public function isArchived(): bool
    {
        return $this->archived;
    }

    public function setArchived(bool $archived): Room
    {
        $this->archived = $archived;

        return $this;
    }

    /**
     * Set openForGuests.
     *
     * @param bool $openForGuests
     */
    public function setOpenForGuests($openForGuests): Room
    {
        $this->openForGuests = $openForGuests;

        return $this;
    }

    /**
     * Get openForGuests.
     */
    public function getOpenForGuests(): bool
    {
        return $this->openForGuests;
    }

    /**
     * Set continuous.
     *
     * @param int $continuous
     */
    public function setContinuous($continuous): Room
    {
        $this->continuous = $continuous;

        return $this;
    }

    /**
     * Get continuous.
     */
    public function getContinuous(): int
    {
        return $this->continuous;
    }

    /**
     * Set template.
     */
    public function setTemplate(bool $template): Room
    {
        $this->template = $template ? 1 : -1;

        return $this;
    }

    /**
     * Get template.
     */
    public function getTemplate(): bool
    {
        return 1 == $this->template;
    }

    /**
     * Set contactPersons.
     *
     * @param string $contactPersons
     */
    public function setContactPersons($contactPersons): Room
    {
        $this->contactPersons = $contactPersons;

        return $this;
    }

    /**
     * Get contactPersons.
     *
     * @return string
     */
    public function getContactPersons(): ?string
    {
        return $this->contactPersons;
    }

    /**
     * Set roomDescription.
     *
     * @param string $roomDescription
     */
    public function setRoomDescription($roomDescription): Room
    {
        $this->roomDescription = $roomDescription;

        return $this;
    }

    /**
     * Get roomDescription.
     *
     * @return string
     */
    public function getRoomDescription(): ?string
    {
        return $this->roomDescription;
    }

    /**
     * Set lastlogin.
     *
     * @param DateTime $lastlogin
     */
    public function setLastlogin($lastlogin): Room
    {
        $this->lastlogin = $lastlogin;

        return $this;
    }

    /**
     * Get lastlogin.
     *
     * @return DateTime
     */
    public function getLastlogin(): ?DateTime
    {
        return $this->lastlogin;
    }

    /**
     * Set the room's slug (a unique textual identifier for this room).
     */
    public function setSlug(?string $slug): void
    {
        $slug = !empty($slug) ? strtolower($slug) : null;

        $this->slug = $slug;
    }

    /**
     * Get the room's slug (a unique textual identifier for this room).
     */
    public function getSlug(): ?string
    {
        return $this->slug;
    }

    /**
     * Set creator.
     */
    public function setCreator(User $creator = null): Room
    {
        $this->creator = $creator;

        return $this;
    }

    /**
     * Get creator.
     *
     * @return User
     */
    public function getCreator(): ?User
    {
        return $this->creator;
    }

    /**
     * Set modifier.
     */
    public function setModifier(User $modifier = null): Room
    {
        $this->modifier = $modifier;

        return $this;
    }

    /**
     * Get modifier.
     *
     * @return User
     */
    public function getModifier(): ?User
    {
        return $this->modifier;
    }

    /**
     * Set deleter.
     */
    public function setDeleter(User $deleter = null): Room
    {
        $this->deleter = $deleter;

        return $this;
    }

    /**
     * Get deleter.
     */
    public function getDeleter(): ?User
    {
        return $this->deleter;
    }

    public function getActivityState(): string
    {
        return $this->activityState;
    }

    public function setActivityState(string $activityState): Room
    {
        if (!in_array($activityState, [
            self::ACTIVITY_ACTIVE,
            self::ACTIVITY_ACTIVE_NOTIFIED,
            self::ACTIVITY_IDLE,
            self::ACTIVITY_IDLE_NOTIFIED,
            self::ACTIVITY_ABANDONED,
        ], true)) {
            throw new InvalidArgumentException('Invalid activity');
        }

        $this->activityState = $activityState;

        return $this;
    }

    public function getActivityStateUpdated(): ?DateTime
    {
        return $this->activityStateUpdated;
    }

    public function setActivityStateUpdated(?DateTime $activityStateUpdated): Room
    {
        $this->activityStateUpdated = $activityStateUpdated;

        return $this;
    }
}
