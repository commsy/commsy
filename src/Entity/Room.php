<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;
use OpenApi\Annotations as OA;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * Room
 *
 * @ORM\Table(name="room", indexes={
 *     @ORM\Index(name="activity", columns={"activity"}),
 *     @ORM\Index(name="context_id", columns={"context_id"}),
 *     @ORM\Index(name="delete_idx", columns={"deleter_id", "deletion_date"}),
 *     @ORM\Index(name="lastlogin", columns={"lastlogin"}),
 *     @ORM\Index(name="search_idx", columns={"title", "contact_persons"}),
 *     @ORM\Index(name="status", columns={"status"}),
 *     @ORM\Index(name="title", columns={"title"}),
 *     @ORM\Index(name="type", columns={"type"})
 * })
 * @ORM\Entity(repositoryClass="App\Repository\RoomRepository")
 * @ORM\HasLifecycleCallbacks
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
class Room
{
    public const ACTIVITY_ACTIVE = 'active';
    public const ACTIVITY_ACTIVE_NOTIFIED = 'active_notified';
    public const ACTIVITY_IDLE = 'idle';
    public const ACTIVITY_IDLE_NOTIFIED = 'idle_notified';
    public const ACTIVITY_ABANDONED = 'abandoned';

    /**
     * @var integer
     *
     * @ORM\Column(name="item_id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     *
     * @Groups({"api"})
     * @OA\Property(description="The unique identifier.")
     */
    private $itemId;

    /**
     * @var integer|null
     *
     * @ORM\Column(name="context_id", type="integer", nullable=true)
     */
    private ?int $contextId;

    /**
     * @var User|null
     *
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(name="creator_id", referencedColumnName="item_id", nullable=true)
     */
    private ?User $creator;

    /**
     * @var User|null
     *
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(name="modifier_id", referencedColumnName="item_id", nullable=true)
     */
    private ?User $modifier;

    /**
     * @var User|null
     *
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(name="deleter_id", referencedColumnName="item_id", nullable=true)
     */
    private ?User $deleter;

    /**
     * @var DateTime
     *
     * @ORM\Column(name="creation_date", type="datetime", nullable=false)
     *
     * @Groups({"api"})
     */
    private DateTime $creationDate;

    /**
     * @var DateTime
     *
     * @ORM\Column(name="modification_date", type="datetime", nullable=false)
     *
     * @Groups({"api"})
     */
    private DateTime $modificationDate;

    /**
     * @var DateTime|null
     *
     * @ORM\Column(name="deletion_date", type="datetime", nullable=true)
     */
    private ?DateTime $deletionDate;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string", length=255)
     *
     * @Groups({"api"})
     * @OA\Property(type="string", maxLength=255)
     */
    private string $title;

    /**
     * @var array|null
     *
     * @ORM\Column(name="extras", type="mbarray", nullable=true)
     */
    private ?array $extras = null;

    /**
     * @var string
     *
     * @ORM\Column(name="status", type="string", length=20)
     */
    private string $status;

    /**
     * @var integer
     *
     * @ORM\Column(name="activity", type="integer", options={"default":0})
     */
    private int $activity = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="type", type="string", length=20)
     *
     * @Groups({"api"})
     * @OA\Property(description="Either project or community")
     */
    private string $type = 'project';

    /**
     * @var boolean
     *
     * @ORM\Column(name="public", type="boolean", options={"default":0})
     */
    private bool $public = false;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_open_for_guests", type="boolean", options={"default":0})
     */
    private bool $openForGuests = false;

    /**
     * @var int
     *
     * @ORM\Column(name="continuous", type="smallint", options={"default":-1})
     */
    private int $continuous = -1;

    /**
     * @var int
     *
     * @ORM\Column(name="template", type="smallint", options={"default":-1})
     */
    private int $template = -1;

    /**
     * @var string|null
     *
     * @ORM\Column(name="contact_persons", type="string", length=255, nullable=true)
     */
    private ?string $contactPersons;

    /**
     * @var string|null
     *
     * @ORM\Column(name="room_description", type="string", length=10000, nullable=true)
     *
     * @Groups({"api"})
     * @OA\Property(type="string", nullable=true)
     */
    private ?string $roomDescription = null;

    /**
     * @var DateTime|null
     *
     * @ORM\Column(name="lastlogin", type="datetime", nullable=true)
     */
    private ?DateTime $lastlogin;

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
        $this->activityState = self::ACTIVITY_ACTIVE;
        $this->creationDate = new DateTime();
        $this->modificationDate = new DateTime();
    }

    public function isIndexable(): bool
    {
        return ($this->deleter == null && $this->deletionDate == null);
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

            $mapping = array(
                -1 => 'never',
                2 => 'sometimes',
                3 => 'code',
            );

            if (isset($mapping[$checkNewMembers])) {
                return $mapping[$checkNewMembers];
            }
        }

        return 'always';
    }

    public function setAccessCheck($access): Room
    {
        $mapping = array(
            'never' => -1,
            'sometimes' => 2,
            'code' => 3,
        );

        $extras = $this->getExtras();
        $extras['CHECKNEWMEMBERS'] = $mapping[$access];
        $this->setExtras($extras);

        return $this;
    }

    public function isProjectRoom(): bool
    {
        return $this->type === 'project';
    }

    public function isCommunityRoom(): bool
    {
        return $this->type === 'community';
    }

    public function isMaterialOpenForGuests(): bool
    {
        $extras = $this->getExtras();
        if (isset($extras['MATERIAL_GUESTS'])) {
            $materialOpenForGuests = $extras['MATERIAL_GUESTS'];

            return $materialOpenForGuests === 1;
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

            return $roomAssociation === 'onlymembers';
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
     * Get itemId
     *
     * @return integer
     */
    public function getItemId(): int
    {
        return $this->itemId;
    }

    /**
     * Set contextId
     *
     * @param integer $contextId
     *
     * @return Room
     */
    public function setContextId($contextId): Room
    {
        $this->contextId = $contextId;

        return $this;
    }

    /**
     * Get contextId
     *
     * @return integer
     */
    public function getContextId(): ?int
    {
        return $this->contextId;
    }

    /**
     * @ORM\PrePersist()
     */
    public function setInitialDateValues()
    {
        $this->creationDate = new \DateTime("now");
        $this->modificationDate = new \DateTime("now");
    }

    /**
     * Set creationDate
     *
     * @param DateTime $creationDate
     *
     * @return Room
     */
    public function setCreationDate($creationDate): Room
    {
        $this->creationDate = $creationDate;

        return $this;
    }

    /**
     * Get creationDate
     *
     * @return DateTime
     */
    public function getCreationDate(): DateTime
    {
        return $this->creationDate;
    }

    /**
     * Set modificationDate
     *
     * @param DateTime $modificationDate
     *
     * @return Room
     */
    public function setModificationDate($modificationDate): Room
    {
        $this->modificationDate = $modificationDate;

        return $this;
    }

    /**
     * Get modificationDate
     *
     * @return DateTime
     */
    public function getModificationDate(): DateTime
    {
        return $this->modificationDate;
    }

    /**
     * Set deletionDate
     *
     * @param DateTime $deletionDate
     *
     * @return Room
     */
    public function setDeletionDate(DateTime $deletionDate): Room
    {
        $this->deletionDate = $deletionDate;

        return $this;
    }

    /**
     * Get deletionDate
     *
     * @return DateTime
     */
    public function getDeletionDate(): ?DateTime
    {
        return $this->deletionDate;
    }

    /**
     * Set title
     *
     * @param string $title
     *
     * @return Room
     */
    public function setTitle(string $title): Room
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title
     *
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * Set extras
     *
     * @param array $extras
     *
     * @return Room
     */
    public function setExtras(array $extras): Room
    {
        $this->extras = $extras;

        return $this;
    }

    /**
     * Get extras
     *
     * @return array|null
     */
    public function getExtras(): ?array
    {
        return $this->extras;
    }

    /**
     * Set status
     *
     * @param string $status
     *
     * @return Room
     */
    public function setStatus($status): Room
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * Set activity
     *
     * @param integer $activity
     *
     * @return Room
     */
    public function setActivity($activity): Room
    {
        $this->activity = $activity;

        return $this;
    }

    /**
     * Get activity
     *
     * @return integer
     */
    public function getActivity(): int
    {
        return $this->activity;
    }

    /**
     * Set type
     *
     * @param string $type
     *
     * @return Room
     */
    public function setType($type): Room
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type
     *
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Set public
     *
     * @param boolean $public
     *
     * @return Room
     */
    public function setPublic($public): Room
    {
        $this->public = $public;

        return $this;
    }

    /**
     * Get public
     *
     * @return boolean
     */
    public function getPublic(): bool
    {
        return $this->public;
    }

    /**
     * Set openForGuests
     *
     * @param boolean $openForGuests
     *
     * @return Room
     */
    public function setOpenForGuests($openForGuests): Room
    {
        $this->openForGuests = $openForGuests;

        return $this;
    }

    /**
     * Get openForGuests
     *
     * @return boolean
     */
    public function getOpenForGuests(): bool
    {
        return $this->openForGuests;
    }

    /**
     * Set continuous
     *
     * @param int $continuous
     *
     * @return Room
     */
    public function setContinuous($continuous): Room
    {
        $this->continuous = $continuous;

        return $this;
    }

    /**
     * Get continuous
     *
     * @return int
     */
    public function getContinuous(): int
    {
        return $this->continuous;
    }

    /**
     * Set template
     *
     * @param bool $template
     *
     * @return Room
     */
    public function setTemplate(bool $template): Room
    {
        $this->template = $template ? 1 : -1;

        return $this;
    }

    /**
     * Get template
     *
     * @return bool
     */
    public function getTemplate(): bool
    {
        return $this->template == 1;
    }

    /**
     * Set contactPersons
     *
     * @param string $contactPersons
     *
     * @return Room
     */
    public function setContactPersons($contactPersons): Room
    {
        $this->contactPersons = $contactPersons;

        return $this;
    }

    /**
     * Get contactPersons
     *
     * @return string
     */
    public function getContactPersons(): ?string
    {
        return $this->contactPersons;
    }

    /**
     * Set roomDescription
     *
     * @param string $roomDescription
     *
     * @return Room
     */
    public function setRoomDescription($roomDescription): Room
    {
        $this->roomDescription = $roomDescription;

        return $this;
    }

    /**
     * Get roomDescription
     *
     * @return string
     */
    public function getRoomDescription(): ?string
    {
        return $this->roomDescription;
    }

    /**
     * Set lastlogin
     *
     * @param DateTime $lastlogin
     *
     * @return Room
     */
    public function setLastlogin($lastlogin): Room
    {
        $this->lastlogin = $lastlogin;

        return $this;
    }

    /**
     * Get lastlogin
     *
     * @return DateTime
     */
    public function getLastlogin(): ?DateTime
    {
        return $this->lastlogin;
    }

    /**
     * Set creator
     *
     * @param User|null $creator
     *
     * @return Room
     */
    public function setCreator(User $creator = null): Room
    {
        $this->creator = $creator;

        return $this;
    }

    /**
     * Get creator
     *
     * @return User
     */
    public function getCreator(): ?User
    {
        return $this->creator;
    }

    /**
     * Set modifier
     *
     * @param User|null $modifier
     *
     * @return Room
     */
    public function setModifier(User $modifier = null): Room
    {
        $this->modifier = $modifier;

        return $this;
    }

    /**
     * Get modifier
     *
     * @return User
     */
    public function getModifier(): ?User
    {
        return $this->modifier;
    }

    /**
     * Set deleter
     *
     * @param User|null $deleter
     *
     * @return Room
     */
    public function setDeleter(User $deleter = null): Room
    {
        $this->deleter = $deleter;

        return $this;
    }

    /**
     * Get deleter
     *
     * @return User|null
     */
    public function getDeleter(): ?User
    {
        return $this->deleter;
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
     * @return Room
     */
    public function setActivityState(string $activityState): Room
    {
        if (!in_array($activityState, [
            self::ACTIVITY_ACTIVE,
            self::ACTIVITY_ACTIVE_NOTIFIED,
            self::ACTIVITY_IDLE,
            self::ACTIVITY_IDLE_NOTIFIED,
            self::ACTIVITY_ABANDONED,
        ], true)) {
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
    public function setActivityStateUpdated(?DateTime $activityStateUpdated): Room
    {
        $this->activityStateUpdated = $activityStateUpdated;
        return $this;
    }
}
