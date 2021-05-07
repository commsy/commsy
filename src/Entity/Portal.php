<?php

namespace App\Entity;

use App\Services\LegacyEnvironment;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Swagger\Annotations as SWG;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Serializer\Annotation\Groups;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

/**
 * Portal
 *
 * @ORM\Table(name="portal", indexes={
 *     @ORM\Index(name="context_id", columns={"context_id"}),
 *     @ORM\Index(name="creator_id", columns={"creator_id"})
 * })
 * @ORM\Entity(repositoryClass="App\Repository\PortalRepository")
 * @ORM\HasLifecycleCallbacks()
 * @Vich\Uploadable
 */
class Portal implements \Serializable
{
    /**
     * @ORM\Id
     * @ORM\Column(name="id", type="integer")
     * @ORM\GeneratedValue
     *
     * @Groups({"api"})
     * @SWG\Property(description="The unique identifier.")
     */
    private $id;

    /**
     * @var integer
     *
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(name="deleter_id", referencedColumnName="item_id", nullable=true)
     */
    private $deleter;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="creation_date", type="datetime", nullable=false)
     *
     * @Groups({"api"})
     */
    private $creationDate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="modification_date", type="datetime", nullable=false)
     *
     * @Groups({"api"})
     */
    private $modificationDate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="deletion_date", type="datetime", nullable=true)
     */
    private $deletionDate;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string", length=255, nullable=false)
     *
     * @Groups({"api"})
     * @SWG\Property(type="string", maxLength=255)
     */
    private $title;

    /**
     * @var string
     *
     * @ORM\Column(name="description_de", type="text")
     *
     * @Groups({"api"})
     * @SWG\Property(type="string")
     */
    private $descriptionGerman;

    /**
     * @var string
     *
     * @ORM\Column(name="description_en", type="text")
     *
     * @Groups({"api"})
     * @SWG\Property(type="string")
     */
    private $descriptionEnglish;

    /**
     * @var string
     *
     * @ORM\Column(name="terms_de", type="text")
     */
    private $termsGerman;

    /**
     * @var string
     *
     * @ORM\Column(name="terms_en", type="text")
     */
    private $termsEnglish;

    /**
     * @var array
     *
     * @ORM\Column(name="extras", type="array", nullable=true)
     */
    private $extras;

    /**
     * @var string
     *
     * @ORM\Column(name="status", type="string", length=20, nullable=false)
     */
    private $status;

    /**
     * @var integer
     *
     * @ORM\Column(name="activity", type="integer", nullable=false)
     */
    private $activity = '0';

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\AuthSource", mappedBy="portal")
     */
    private $authSources;

    /**
     * NOTE: This is not a mapped field of entity metadata, just a simple property used by VichUploaderBundle.
     *
     * @Vich\UploadableField(mapping="portal_logo", fileNameProperty="logoFilename")
     *
     * @var File|null
     */
    private $logoFile;

    /**
     * @ORM\Column(name="logo_filename", type="string", length=255, nullable=true)
     *
     * @var string|null
     */
    private $logoFilename;

    /**
     * array - containing the data of this item, including lists of linked items
     */
    var $_data = array();
    private $_environment;
    var $_room_list_continuous = null;

    public function __construct()
    {
        $this->authSources = new ArrayCollection();
        $this->_environment = $this;
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set deleter
     *
     * @param \App\Entity\User $deleter
     *
     * @return Portal
     */
    public function setDeleter(\App\Entity\User $deleter = null)
    {
        $this->deleter = $deleter;

        return $this;
    }

    /**
     * Get deleter
     *
     * @return \App\Entity\User
     */
    public function getDeleter()
    {
        return $this->deleter;
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
     * @param \DateTime $creationDate
     *
     * @return Portal
     */
    public function setCreationDate($creationDate)
    {
        $this->creationDate = $creationDate;

        return $this;
    }

    /**
     * Get creationDate
     *
     * @return \DateTime
     */
    public function getCreationDate()
    {
        return $this->creationDate;
    }

    /**
     * @ORM\PreUpdate()
     */
    public function setModificationDateValue()
    {
        $this->modificationDate = new \DateTime("now");
    }

    /**
     * Set modificationDate
     *
     * @param \DateTime $modificationDate
     *
     * @return Portal
     */
    public function setModificationDate($modificationDate)
    {
        $this->modificationDate = $modificationDate;

        return $this;
    }

    /**
     * Get modificationDate
     *
     * @return \DateTime
     */
    public function getModificationDate()
    {
        return $this->modificationDate;
    }

    /**
     * Set deletionDate
     *
     * @param \DateTime $deletionDate
     *
     * @return Portal
     */
    public function setDeletionDate($deletionDate)
    {
        $this->deletionDate = $deletionDate;

        return $this;
    }

    /**
     * Get deletionDate
     *
     * @return \DateTime
     */
    public function getDeletionDate()
    {
        return $this->deletionDate;
    }

    /**
     * Set title
     *
     * @param string $title
     *
     * @return Portal
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set extras
     *
     * @param array $extras
     *
     * @return Portal
     */
    public function setExtras(array $extras): Portal
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
     * @return Portal
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set activity
     *
     * @param integer $activity
     *
     * @return Portal
     */
    public function setActivity($activity)
    {
        $this->activity = $activity;

        return $this;
    }

    /**
     * Get activity
     *
     * @return integer
     */
    public function getActivity()
    {
        return $this->activity;
    }

    /**
     * @return Collection
     */
    public function getAuthSources(): Collection
    {
        return $this->authSources;
    }

    public function addAuthSource(AuthSource $authSource): Portal
    {
        if (!$this->authSources->contains($authSource)) {
            $this->authSources[] = $authSource;
            $authSource->setPortal($this);
        }

        return $this;
    }

    public function removeAuthSource(AuthSource $authSource): Portal
    {
        if ($this->authSources->contains($authSource)) {
            $this->authSources->removeElement($authSource);
            // set the owning side to null (unless already changed)
            if ($authSource->getPortal() === $this) {
                $authSource->setPortal(null);
            }
        }

        return $this;
    }

    /**
     * @return File|null
     */
    public function getLogoFile(): ?File
    {
        return $this->logoFile;
    }

    /**
     * @param File|UploadedFile|null $logoFile
     * @return Portal
     */
    public function setLogoFile(?File $logoFile = null): Portal
    {
        $this->logoFile = $logoFile;
        if ($logoFile !== null) {
            // VichUploaderBundle NOTE: it is required that at least one field changes if you are
            // using Doctrine otherwise the event listeners won't be called and the file is lost
            $this->modificationDate = new DateTimeImmutable();
        }
        return $this;
    }

    /**
     * @return string|null
     */
    public function getLogoFilename(): ?string
    {
        return $this->logoFilename;
    }

    /**
     * @param string|null $logoFilename
     * @return Portal
     */
    public function setLogoFilename(?string $logoFilename): Portal
    {
        $this->logoFilename = $logoFilename;
        return $this;
    }

    public function getSupportPageLink(): ?string
    {
        return $this->extras['SUPPORTPAGELINK'] ?? '';
    }

    public function setSupportPageLink(?string $link): Portal
    {
        $this->extras['SUPPORTPAGELINK'] = $link;
        return $this;
    }

    public function getSupportPageLinkTooltip(): ?string
    {
        return $this->extras['SUPPORTPAGELINKTOOLTIP'] ?? '';
    }

    public function setSupportPageLinkTooltip(?string $tooltip): Portal
    {
        $this->extras['SUPPORTPAGELINKTOOLTIP'] = $tooltip;
        return $this;
    }

    public function hasSupportRequestsEnabled(): bool
    {
        return $this->extras['SERVICELINK'] ?? false;
    }

    public function setSupportRequestsEnabled(bool $enabled): Portal
    {
        $this->extras['SERVICELINK'] = $enabled;
        return $this;
    }

    public function getSupportEmail(): ?string
    {
        return $this->extras['SERVICEEMAIL'] ?? '';
    }

    public function setSupportEmail(?string $email): Portal
    {
        $this->extras['SERVICEEMAIL'] = $email;
        return $this;
    }

    public function getSupportFormLink(): ?string
    {
        return $this->extras['SERVICELINKEXTERNAL'] ?? '';
    }

    public function setSupportFormLink(?string $externalLink): Portal
    {
        $this->extras['SERVICELINKEXTERNAL'] = $externalLink;
        return $this;
    }

    public function getAnnouncementText(): ?string
    {
        return $this->extras['ANNOUNCEMENT_TEXT'] ?? '';
    }

    public function setAnnouncementText(?string $text): Portal
    {
        $this->extras['ANNOUNCEMENT_TEXT'] = $text;
        return $this;
    }

    public function getAnnouncementTitle(): ?string
    {
        return $this->extras['ANNOUNCEMENT_TITLE'] ?? '';
    }

    public function setAnnouncementTitle(string $title): Portal
    {
        $this->extras['ANNOUNCEMENT_TITLE'] = $title;
        return $this;
    }

    public function getAnnouncementSeverity(): ?string
    {
        return $this->extras['ANNOUNCEMENT_SEVERITY'] ?? '';
    }

    public function setAnnouncementSeverity(string $severity): Portal
    {
        $this->extras['ANNOUNCEMENT_SEVERITY'] = $severity;
        return $this;
    }

    public function hasAnnouncementEnabled(): bool
    {
        return $this->extras['ANNOUNCEMENT_ENABLED'] ?? false;
    }

    public function setAnnouncementEnabled(bool $enabled): Portal
    {
        $this->extras['ANNOUNCEMENT_ENABLED'] = $enabled;
        return $this;
    }

    public function hasServerAnnouncementEnabled(): bool
    {
        return $this->extras['ANNOUNCEMENT_SERVER_ENABLED'] ?? false;
    }

    public function setServerAnnouncementEnabled(bool $enabled): Portal
    {
        $this->extras['ANNOUNCEMENT_SERVER_ENABLED'] = $enabled;
        return $this;
    }

    /**
     * @return DateTimeImmutable|null
     */
    public function getAGBChangeDate(): ?DateTimeImmutable
    {
        $agbChangeDateString = $this->extras['AGB_CHANGE_DATE'] ?? '';
        return !empty($agbChangeDateString) ?
            DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $agbChangeDateString) :
            null;
    }

    /**
     * @param DateTimeImmutable|null $agbChangeDate
     * @return $this
     */
    public function setAGBChangeDate(?DateTimeImmutable $agbChangeDate): Portal
    {
        $agbChangeDateString = $agbChangeDate ? $agbChangeDate->format('Y-m-d H:i:s') : '';
        $this->extras['AGB_CHANGE_DATE'] = $agbChangeDateString;
        return $this;
    }

    public function hasAGBEnabled(): bool
    {
        /**
         * agb status 1 = yes, 2 = no (default)
         * @var integer
         */
        $agbStatus = $this->extras['AGBSTATUS'] ?? 2;

        return $agbStatus === 1;
    }

    public function setAGBEnabled(bool $enabled): Portal
    {
        $this->extras['AGBSTATUS'] = $enabled ? 1 : 2;
        return $this;
    }

    public function getShowRoomsOnHome(): string
    {
        return $this->extras['SHOWROOMSONHOME'] ?? 'normal';
    }

    public function setShowRoomsOnHome(?string $text): Portal
    {
        if ($text !== 'onlycommunityrooms' && $text !== 'onlyprojectrooms') {
            $text = 'normal';
        }
        $this->extras['SHOWROOMSONHOME'] = $text;
        return $this;
    }

    public function getShowTemplatesInRoomList(): bool
    {
        /**
         * show templates: 1 = yes (default), -1 = no
         * @var integer
         */
        $showTemplates = $this->extras['SHOW_TEMPLATE_IN_ROOM_LIST'] ?? 1;

        return $showTemplates === 1 ? true : false;
    }

    public function setShowTemplatesInRoomList(?bool $showTemplates): Portal
    {
        $this->extras['SHOW_TEMPLATE_IN_ROOM_LIST'] = $showTemplates ? 1 : -1;
        return $this;
    }

    /** Returns the community room creation status.
     *
     * @return string room creation status ("all" = all users (default), "moderator" = only portal moderators)
     */
    public function getCommunityRoomCreationStatus(): string
    {
        return $this->extras['COMMUNITYROOMCREATIONSTATUS'] ?? 'all';
    }

    public function setCommunityRoomCreationStatus(?string $status): Portal
    {
        if ($status !== 'moderator' && $status !== 'all') {
            $status = 'all';
        }
        $this->extras['COMMUNITYROOMCREATIONSTATUS'] = $status;
        return $this;
    }

    /** Returns the project room creation status.
     *
     * @return string room creation status ("portal" = in community rooms & portal (default), "communityroom" = only in community rooms)
     */
    public function getProjectRoomCreationStatus(): string
    {
        return $this->extras['PROJECTCREATIONSTATUS'] ?? 'portal';
    }

    public function setProjectRoomCreationStatus(?string $status): Portal
    {
        if ($status !== 'communityroom' && $status !== 'portal') {
            $status = 'portal';
        }
        $this->extras['PROJECTCREATIONSTATUS'] = $status;
        return $this;
    }

    /** Returns the project room link status.
     *
     * @return string room link status ("optional" = a project room can be created without assigning it to a community room (default),
     * "mandatory" = upon room creation, a project room must be assigned to a community room)
     */
    public function getProjectRoomLinkStatus(): string
    {
        return $this->extras['PROJECTROOMLINKSTATUS'] ?? 'optional';
    }

    public function setProjectRoomLinkStatus(?string $status): Portal
    {
        if ($status !== 'mandatory' && $status !== 'optional') {
            $status = 'optional';
        }
        $this->extras['PROJECTROOMLINKSTATUS'] = $status;
        return $this;
    }

    /** Returns the ID of the room that should be suggested as a room template when creating a new project room.
     *
     * @return int ID of the default room template, or -1 if no default room template has been defined
     */
    public function getDefaultProjectTemplateID(): int
    {
        $roomTemplateID = $this->extras['DEFAULTPROJECTTEMPLATEID'] ?? '-1';
        return intval($roomTemplateID);
    }

    public function setDefaultProjectTemplateID(?int $id): Portal
    {
        $this->extras['DEFAULTPROJECTTEMPLATEID'] = !empty($id) ? strval($id) : '-1';
        return $this;
    }

    /** Returns the ID of the room that should be suggested as a room template when creating a new community room.
     *
     * @return int ID of the default room template, or -1 if no default room template has been defined
     */
    public function getDefaultCommunityTemplateID(): int
    {
        $roomTemplateID = $this->extras['DEFAULTCOMMUNITYTEMPLATEID'] ?? '-1';
        return intval($roomTemplateID);
    }

    public function setDefaultCommunityTemplateID(?int $id): Portal
    {
        $this->extras['DEFAULTCOMMUNITYTEMPLATEID'] = !empty($id) ? strval($id) : '-1';
        return $this;
    }

    /**
     * Are room categories mandatory?
     * If room categories are mandatory, at least one room category must be assigned when creating a new room.
     *
     * @return boolean whether room categories are mandatory (true) or not (false)
     */
    public function isTagMandatory(): bool
    {
        return $this->extras['TAGMANDATORY'] ?? false;
    }

    public function setTagMandatory(bool $isTagMandatory): Portal
    {
        $this->extras['TAGMANDATORY'] = $isTagMandatory;
        return $this;
    }

    public function getHideAccountname(): bool
    {
        /**
         * hide account name: 1 = yes, 2 = no (default)
         * @var integer
         */
        $hideAccountName = $this->extras['HIDE_ACCOUNTNAME'] ?? null;
        if (!isset($hideAccountName) && isset($this->extras['EXTRA_CONFIG'])) {
            // NOTE: in CommSy9 and earlier, HIDE_ACCOUNTNAME was part of an EXTRA_CONFIG array
            $hideAccountName = $this->extras['EXTRA_CONFIG']['HIDE_ACCOUNTNAME'] ?? 2;
        }

        return $hideAccountName === 1 ? true : false;
    }

    public function setHideAccountname(?bool $hideAccountName): Portal
    {
        // NOTE: for consistency reasons, we keep the behavior of CommSy9 and earlier where `2` was used to indicate `no`
        $this->extras['HIDE_ACCOUNTNAME'] = $hideAccountName ? 1 : 2;
        return $this;
    }

    public function getHideEmailAddressByDefault(): bool
    {
        /**
         * hide mail by default: 1 = yes, 0 = no (default)
         * @var integer
         */
        $hideEmailAddress = $this->extras['HIDE_MAIL_BY_DEFAULT'] ?? 0;

        return $hideEmailAddress === 1 ? true : false;
    }

    public function setHideEmailAddressByDefault(?bool $hideEmailAddress): Portal
    {
        $this->extras['HIDE_MAIL_BY_DEFAULT'] = $hideEmailAddress ? 1 : 0;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getInactivityLockDays(): ?int
    {
        return $this->extras['INACTIVITY_LOCK'] ?? null;
    }

    /**
     * @param int|null $days
     * @return self
     */
    public function setInactivityLockDays(?int $days): self
    {
        $this->extras['INACTIVITY_LOCK'] = $days;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getInactivitySendMailBeforeLockDays(): ?int
    {
        return $this->extras['INACTIVITY_MAIL_BEFORE_LOCK'] ?? null;
    }

    /**
     * @param int|null $days
     * @return self
     */
    public function setInactivitySendMailBeforeLockDays(?int $days): self
    {
        $this->extras['INACTIVITY_MAIL_BEFORE_LOCK'] = $days;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getInactivityDeleteDays(): ?int
    {
        return $this->extras['INACTIVITY_DELETE'] ?? null;
    }

    /**
     * @param int|null $days
     * @return self
     */
    public function setInactivityDeleteDays(?int $days): self
    {
        $this->extras['INACTIVITY_DELETE'] = $days;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getInactivitySendMailBeforeDeleteDays(): ?int
    {
        return $this->extras['INACTIVITY_MAIL_DELETE'] ?? null;
    }

    /**
     * @param int|null $days
     * @return self
     */
    public function setInactivitySendMailBeforeDeleteDays(?int $days): self
    {
        $this->extras['INACTIVITY_MAIL_DELETE'] = $days;
        return $this;
    }

    /**
     * @return string
     */
    public function getDescriptionGerman(): ?string
    {
        return $this->descriptionGerman;
    }

    /**
     * @param string $descriptionGerman
     * @return Portal
     */
    public function setDescriptionGerman(string $descriptionGerman): Portal
    {
        $this->descriptionGerman = $descriptionGerman;
        return $this;
    }

    /**
     * @return string
     */
    public function getDescriptionEnglish(): ?string
    {
        return $this->descriptionEnglish;
    }

    /**
     * @param string $descriptionEnglish
     * @return Portal
     */
    public function setDescriptionEnglish(string $descriptionEnglish): Portal
    {
        $this->descriptionEnglish = $descriptionEnglish;
        return $this;
    }

    public function getTermsGerman(): ?string
    {
        return $this->termsGerman;
    }

    public function setTermsGerman(string $termsGerman): Portal
    {
        $this->termsGerman = $termsGerman;
        return $this;
    }

    public function getTermsEnglish(): ?string
    {
        return $this->termsEnglish;
    }

    public function setTermsEnglish(string $termsEnglish): Portal
    {
        $this->termsEnglish = $termsEnglish;
        return $this;
    }


    public function getShowTimePulses(): bool
    {
        /**
         * show time pulses: 1 = yes, -1 = no (default)
         * @var integer
         */
        $showTimePulses = $this->extras['TIME_SHOW'] ?? -1;

        return $showTimePulses === 1 ? true : false;
    }

    public function setShowTimePulses(?bool $showTimePulses): Portal
    {
        $this->extras['TIME_SHOW'] = $showTimePulses ? 1 : -1;
        return $this;
    }

    public function getTimePulseNameGerman(): string
    {
        return $this->getTimeNameArray()['DE'] ?? '';
    }

    public function setTimePulseNameGerman(?string $timePulseName): Portal
    {
        $timePulseName = $timePulseName ?? '';
        if ($this->getTimePulseNameGerman() !== $timePulseName) {
            $timePulseNamesByLanguage = $this->getTimeNameArray();
            $timePulseNamesByLanguage['DE'] = $timePulseName;
            $this->setTimeNameArray($timePulseNamesByLanguage);
        }
        return $this;
    }

    public function getTimePulseNameEnglish(): string
    {
        return $this->getTimeNameArray()['EN'] ?? '';
    }

    public function setTimePulseNameEnglish(?string $timePulseName): Portal
    {
        $timePulseName = $timePulseName ?? '';
        if ($this->getTimePulseNameEnglish() !== $timePulseName) {
            $timePulseNamesByLanguage = $this->getTimeNameArray();
            $timePulseNamesByLanguage['EN'] = $timePulseName;
            $this->setTimeNameArray($timePulseNamesByLanguage);
        }
        return $this;
    }

    public function getTimeNameArray(): array
    {
        return $this->extras['TIME_NAME_ARRAY'] ?? [];
    }

    public function setTimeNameArray(array $timePulseNamesByLanguage): Portal
    {
        $this->extras['TIME_NAME_ARRAY'] = $timePulseNamesByLanguage;
        return $this;
    }

    public function getNumberOfFutureTimePulses(): int
    {
        return $this->extras['TIME_IN_FUTURE'] ?? 0;
    }

    public function setNumberOfFutureTimePulses(?int $count): Portal
    {
        $this->extras['TIME_IN_FUTURE'] = $count ?? 0;
        return $this;
    }

    public function getTimeTextArray(): array
    {
        return $this->extras['TIME_TEXT_ARRAY'] ?? [];
    }

    public function setTimeTextArray(array $timePulseTemplates): Portal
    {
        $this->extras['TIME_TEXT_ARRAY'] = $timePulseTemplates;
        return $this;
    }

    public function getIndexViewAction()
    {
        return ($this->getExtras()['INDEX_VIEW_ACTION']) ?? 0;
    }

    public function setIndexViewAction($value)
    {
        $this->getExtras()['INDEX_VIEW_ACTION'] = $value;
    }

    public function getUserIndexFilterChoice()
    {
        return ($this->getExtras()['INDEX_FILTER_CHOICE']) ?? 0;
    }

    public function setUserIndexFilterChoice($value)
    {
        $this->getExtras()['INDEX_FILTER_CHOICE'] = $value;
    }

    public function getAccountIndexSearchString()
    {
        return ($this->getExtras()['ACCOUNT_INDEX_SEARCH_STRING']) ?? "";
    }

    public function setAccountIndexSearchString($value)
    {
        $this->getExtras()['ACCOUNT_INDEX_SEARCH_STRING'] = $value;
    }


    public function getContinuousRoomList(LegacyEnvironment $environment)
    {
        if (!isset($this->_room_list_continuous)) {
            $manager = $environment->getEnvironment()->getRoomManager();
            $manager->setContextLimit($this->getId());
            $manager->setContinuousLimit();
            $manager->select();
            $this->_room_list_continuous = $manager->get();
            unset($manager);
        }
        return $this->_room_list_continuous;
    }


    // Serializable

    public function serialize()
    {
        $serializableData = get_object_vars($this);

        // exclude from serialization
        unset($serializableData['logoFile']);

        return serialize($serializableData);
    }

    public function unserialize($serialized)
    {
        $unserializedData = unserialize($serialized);

        foreach ($unserializedData as $key => $value) {
            $this->$key = $value;
        }
    }

    ###################################################
    # email text translation methods
    ###################################################

    function getEmailTextArray()
    {
        $retour = array();
        if ($this->_issetExtra('MAIL_TEXT_ARRAY')) {
            $retour = $this->getExtras()['MAIL_TEXT_ARRAY'];
        }
        return $retour;
    }

    function setEmailText($message_tag, $array)
    {
        $mail_text_array = array();
        if ($this->_issetExtra('MAIL_TEXT_ARRAY')) {
            $mail_text_array = $this->getExtras()['MAIL_TEXT_ARRAY'];
        }
        if (!empty($array)) {
            $mail_text_array[$message_tag] = $array;
        } elseif (!empty($mail_text_array[$message_tag])) {
            unset($mail_text_array[$message_tag]);
        }
        $this->_addExtra('MAIL_TEXT_ARRAY', $mail_text_array);
    }

    function setEmailTextArray($array)
    {
        if (!empty($array)) {
            $this->_addExtra('MAIL_TEXT_ARRAY', $array);
        }
    }

    /** exists the extra information with the name $key ?
     * this method returns a boolean, if the value exists or not
     *
     * @param string key   the key (name) of the value
     *
     * @return boolean true, if value exists
     *                 false, if not
     */
    function _issetExtra($key)
    {
        $result = false;
        $extras = $this->getExtras();
        if (isset($extras) and is_array($extras) and array_key_exists($key, $extras) and isset($extras[$key])) {
            $result = true;
        }
        return $result;
    }

    function _addExtra($key, $value)
    {
        $extras = $this->getExtras();
        $extras[$key] = $value;
        $this->setExtras($extras);
    }

    /** save context
     * this method save the context
     */
    function save()
    {
        $manager = $this->_environment->getManager($this->_type);
        $this->_save($manager);
        $this->_changes = array();
    }


    ###################################################
    # archiving and deleting rooms
    ###################################################

    function setStatusArchivingUnusedRooms(bool $statusArchivingUnusedRooms)
    {
        $this->extras['ARCHIVING_ROOMS_STATUS'] = $statusArchivingUnusedRooms ? 1 : -1;
        return $this;
    }

    public function getStatusArchivingUnusedRooms(): bool
    {
        $statusArchivingUnusedRooms = $this->extras['ARCHIVING_ROOMS_STATUS'] ?? -1;
        return $statusArchivingUnusedRooms === 1;
    }


    public function turnOnArchivingUnusedRooms()
    {
        $this->extras['ARCHIVING_ROOMS_STATUS'] = 1;
    }

    public function turnOffArchivingUnusedRooms()
    {
        $this->extras['ARCHIVING_ROOMS_STATUS'] = -1;
    }

    public function getDaysUnusedBeforeArchivingRooms(): int
    {
        $retour = 365; //default
        if ($this->_issetExtra('ARCHIVING_ROOMS_DAYS_UNUSED_BEFORE_ARCHIVE')) {
            $retour = $this->extras['ARCHIVING_ROOMS_DAYS_UNUSED_BEFORE_ARCHIVE'];
        }
        return $retour;
    }

    public function setDaysUnusedBeforeArchivingRooms(int $value)
    {
        $this->extras['ARCHIVING_ROOMS_DAYS_UNUSED_BEFORE_ARCHIVE'] = $value;
    }

    public function isActivatedArchivingUnusedRooms(): bool
    {
        $status = $this->getStatusArchivingUnusedRooms();
        return $status === 1;
    }

    /** get days send an email before archiving an unused room
     *
     * @return int days send email before archiving an unused room
     */
    public function getDaysSendMailBeforeArchivingRooms(): int
    {
        $retour = 0;
        if ($this->_issetExtra('ARCHIVING_ROOMS_DAYS_SEND_MAIL_BEFORE_ARCHIVE')) {
            $retour = $this->extras['ARCHIVING_ROOMS_DAYS_SEND_MAIL_BEFORE_ARCHIVE'];
        }
        return $retour;
    }

    /** set days sed mail before archiving an unused room
     *
     * @param int days send mail before archiving an unused room
     */
    public function setDaysSendMailBeforeArchivingRooms(int $value)
    {
        $this->extras['ARCHIVING_ROOMS_DAYS_SEND_MAIL_BEFORE_ARCHIVE'] = $value;
    }

    public function turnOnDeletingUnusedRooms()
    {
        $this->setStatusDeletingUnusedRooms(1);
    }

    public function turnOffDeletingUnusedRooms()
    {
        $this->setStatusDeletingUnusedRooms(-1);
    }

    public function getStatusDeletingUnusedRooms(): bool
    {
        return $this->extras['DELETING_ROOMS_STATUS'] ?? 0;
    }

    public function setStatusDeletingUnusedRooms(bool $value)
    {
        $this->extras['DELETING_ROOMS_STATUS'] = $value;
    }

    /** get days before deleting an unused archived room
     *
     * @return int days before deleting an unused archived room
     */
    public function getDaysUnusedBeforeDeletingRooms(): int
    {
        $retour = 365; //default
        if ($this->_issetExtra('ARCHIVING_ROOMS_DAYS_UNUSED_BEFORE_DELETE')) {
            $retour = $this->extras['ARCHIVING_ROOMS_DAYS_UNUSED_BEFORE_DELETE'];
        }
        return $retour;
    }

    /** set days before deleting an unused archived room
     *
     * @param int days before deleting an unused archived room
     */
    public function setDaysUnusedBeforeDeletingRooms(int $value)
    {
        $this->extras['ARCHIVING_ROOMS_DAYS_UNUSED_BEFORE_DELETE'] = $value;
    }

    /** get days send an email before deleting an unused archived room
     *
     * @return int days send email before deleting an unused archived room
     */
    public function getDaysSendMailBeforeDeletingRooms(): int
    {
        $retour = 0;
        if ($this->_issetExtra('ARCHIVING_ROOMS_DAYS_SEND_MAIL_BEFORE_DELETE')) {
            $retour = $this->extras['ARCHIVING_ROOMS_DAYS_SEND_MAIL_BEFORE_DELETE'];
        }
        return $retour;
    }

    /** set days sed mail before deleting an unused archived room
     *
     * @param int days send mail before deleting an unused archived room
     */
    public function setDaysSendMailBeforeDeletingRooms(int $value)
    {
        $this->extras['ARCHIVING_ROOMS_DAYS_SEND_MAIL_BEFORE_DELETE'] = $value;
    }
}
