<?php

namespace App\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use http\Exception\InvalidArgumentException;

/**
 * ZzzRoom
 *
 * @ORM\Table(name="zzz_room", indexes={
 *     @ORM\Index(name="activity", columns={"activity"}),
 *     @ORM\Index(name="context_id", columns={"context_id"}),
 *     @ORM\Index(name="delete_idx", columns={"deleter_id", "deletion_date"}),
 *     @ORM\Index(name="lastlogin", columns={"lastlogin"}),
 *     @ORM\Index(name="search_idx", columns={"title", "contact_persons"}),
 *     @ORM\Index(name="status", columns={"status"}),
 *     @ORM\Index(name="title", columns={"title"}),
 *     @ORM\Index(name="type", columns={"type"})
 * })
 * @ORM\Entity(repositoryClass="App\Repository\ZzzRoomRepository")
 */
class ZzzRoom
{
    /**
     * @var integer
     *
     * @ORM\Column(name="item_id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $itemId;

    /**
     * @var integer|null
     *
     * @ORM\Column(name="context_id", type="integer", nullable=true)
     */
    private ?int $contextId;

    /**
     * @var ZzzUser|null
     *
     * @ORM\ManyToOne(targetEntity="ZzzUser")
     * @ORM\JoinColumn(name="creator_id", referencedColumnName="item_id", nullable=true)
     */
    private ?ZzzUser $creator;

    /**
     * @var ZzzUser|null
     *
     * @ORM\ManyToOne(targetEntity="ZzzUser")
     * @ORM\JoinColumn(name="modifier_id", referencedColumnName="item_id", nullable=true)
     */
    private ?ZzzUser $modifier;

    /**
     * @var ZzzUser|null
     *
     * @ORM\ManyToOne(targetEntity="ZzzUser")
     * @ORM\JoinColumn(name="deleter_id", referencedColumnName="item_id", nullable=true)
     */
    private ?ZzzUser $deleter;

    /**
     * @var DateTime
     *
     * @ORM\Column(name="creation_date", type="datetime", nullable=false)
     */
    private DateTime $creationDate;

    /**
     * @var DateTime
     *
     * @ORM\Column(name="modification_date", type="datetime", nullable=false)
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
     */
    private string $title;

    /**
     * @var array|null
     *
     * @ORM\Column(name="extras", type="mbarray", nullable=true)
     */
    private ?array $extras;

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
     */
    private ?string $roomDescription;

    /**
     * @var DateTime
     *
     * @ORM\Column(name="lastlogin", type="datetime", nullable=true)
     */
    private $lastlogin;

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
        $this->activityState = Room::ACTIVITY_ACTIVE;
        $this->creationDate = new DateTime();
        $this->modificationDate = new DateTime();
    }

    public function isIndexable()
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

    public function setLanguage($language): ZzzRoom
    {
        $extras = $this->getExtras();
        $extras['LANGUAGE'] = $language;
        $this->setExtras($extras);

        return $this;
    }

    public function getLogo()
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

    public function setAccessCheck($access): ZzzRoom
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

    public function setIsMaterialOpenForGuests($open): ZzzRoom
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

    public function setAssignmentRestricted($isRestricted): ZzzRoom
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
     * Set creationDate
     *
     * @param DateTime $creationDate
     *
     * @return ZzzRoom
     */
    public function setCreationDate($creationDate): ZzzRoom
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
     * @return ZzzRoom
     */
    public function setModificationDate($modificationDate): ZzzRoom
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
     * @return ZzzRoom
     */
    public function setDeletionDate(DateTime $deletionDate): ZzzRoom
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
     * @return ZzzRoom
     */
    public function setTitle(string $title): ZzzRoom
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
     * @return ZzzRoom
     */
    public function setExtras(array $extras): ZzzRoom
    {
        $this->extras = $extras;

        return $this;
    }

    /**
     * Get extras
     *
     * @return array
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
     * @return ZzzRoom
     */
    public function setStatus($status): ZzzRoom
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
     * @return ZzzRoom
     */
    public function setActivity($activity): ZzzRoom
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
     * @return ZzzRoom
     */
    public function setType($type): ZzzRoom
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
     * @return ZzzRoom
     */
    public function setPublic($public): ZzzRoom
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
     * @return ZzzRoom
     */
    public function setOpenForGuests($openForGuests): ZzzRoom
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
     * @return ZzzRoom
     */
    public function setContinuous($continuous): ZzzRoom
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
     * @param int $template
     *
     * @return ZzzRoom
     */
    public function setTemplate($template)
    {
        $this->template = $template;

        return $this;
    }

    /**
     * Get template
     *
     * @return int
     */
    public function getTemplate(): int
    {
        return $this->template;
    }

    /**
     * Set contactPersons
     *
     * @param string $contactPersons
     *
     * @return ZzzRoom
     */
    public function setContactPersons($contactPersons): ZzzRoom
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
     * @return ZzzRoom
     */
    public function setRoomDescription($roomDescription): ZzzRoom
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
     * @return ZzzRoom
     */
    public function setLastlogin($lastlogin): ZzzRoom
    {
        $this->lastlogin = $lastlogin;

        return $this;
    }

    /**
     * Get lastlogin
     *
     * @return DateTime
     */
    public function getLastlogin(): DateTime
    {
        return $this->lastlogin;
    }

    /**
     * Set creator
     *
     * @param User|null $creator
     *
     * @return ZzzRoom
     */
    public function setCreator(User $creator = null): ZzzRoom
    {
        $this->creator = $creator;

        return $this;
    }

    /**
     * Get creator
     *
     * @return ZzzUser
     */
    public function getCreator(): ?ZzzUser
    {
        return $this->creator;
    }

    /**
     * Set modifier
     *
     * @param ZzzUser $modifier
     *
     * @return ZzzRoom
     */
    public function setModifier(ZzzUser $modifier): ZzzRoom
    {
        $this->modifier = $modifier;

        return $this;
    }

    /**
     * Get modifier
     *
     * @return ZzzUser
     */
    public function getModifier(): ?ZzzUser
    {
        return $this->modifier;
    }

    /**
     * Set deleter
     *
     * @param ZzzUser $deleter
     *
     * @return ZzzRoom
     */
    public function setDeleter(ZzzUser $deleter): ZzzRoom
    {
        $this->deleter = $deleter;

        return $this;
    }

    /**
     * Get deleter
     *
     * @return ZzzUser|null
     */
    public function getDeleter(): ?ZzzUser
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
     * @return ZzzRoom
     */
    public function setActivityState(string $activityState): ZzzRoom
    {
        if (!in_array($activityState, [
            Room::ACTIVITY_ACTIVE,
            Room::ACTIVITY_ACTIVE_NOTIFIED,
            Room::ACTIVITY_IDLE,
            Room::ACTIVITY_IDLE_NOTIFIED,
            Room::ACTIVITY_ABANDONED,
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
     * @return ZzzRoom
     */
    public function setActivityStateUpdated(?DateTime $activityStateUpdated): ZzzRoom
    {
        $this->activityStateUpdated = $activityStateUpdated;
        return $this;
    }
}