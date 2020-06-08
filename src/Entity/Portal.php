<?php

namespace App\Entity;

use App\Services\LegacyEnvironment;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Swagger\Annotations as SWG;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * Portal
 *
 * @ORM\Table(name="portal", indexes={
 *     @ORM\Index(name="context_id", columns={"context_id"}),
 *     @ORM\Index(name="creator_id", columns={"creator_id"})
 * })
 * @ORM\Entity(repositoryClass="App\Repository\PortalRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class Portal
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
     * @var string
     *
     * @ORM\Column(name="type", type="string", length=10, nullable=false)
     */
    private $type = 'portal';

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_open_for_guests", type="boolean", nullable=false)
     */
    private $isOpenForGuests = '1';

    /**
     * @var string
     *
     * @ORM\Column(name="url", type="string", length=255, nullable=true)
     */
    private $url;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\AuthSource", mappedBy="portal")
     */
    private $authSources;

    /**
     * @var string
     */
    private $logoFilename;

    /**
     * array - containing the data of this item, including lists of linked items
     */
    var $_data = array();
    private $_environment;
    var $_room_list_continuous = NULL;

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
    public function getExtras():? array
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
     * Set type
     *
     * @param string $type
     *
     * @return Portal
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set isOpenForGuests
     *
     * @param boolean $isOpenForGuests
     *
     * @return Portal
     */
    public function setIsOpenForGuests($isOpenForGuests)
    {
        $this->isOpenForGuests = $isOpenForGuests;

        return $this;
    }

    /**
     * Get isOpenForGuests
     *
     * @return boolean
     */
    public function getIsOpenForGuests()
    {
        return $this->isOpenForGuests;
    }

    /**
     * Set url
     *
     * @param string $url
     *
     * @return Portal
     */
    public function setUrl($url)
    {
        $this->url = $url;

        return $this;
    }

    /**
     * Get url
     *
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @return Collection
     */
    public function getAuthSources(): Collection
    {
        return $this->authSources;
    }

    public function addAuthSource(AuthSource $authSource): self
    {
        if (!$this->authSources->contains($authSource)) {
            $this->authSources[] = $authSource;
            $authSource->setPortal($this);
        }

        return $this;
    }

    public function removeAuthSource(AuthSource $authSource): self
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
     * @return string
     */
    public function getLogoFilename():? string
    {
        return $this->logoFilename;
    }

    /**
     * @param string $logoFilename
     * @return Portal
     */
    public function setLogoFilename(string $logoFilename): self
    {
        $this->logoFilename = $logoFilename;
        return $this;
    }

    public function getSupportPageLink():? string
    {
        return $this->extras['SUPPORTPAGELINK'] ?? '';
    }

    public function setSupportPageLink(?string $link): self
    {
        $this->extras['SUPPORTPAGELINK'] = $link;
        return $this;
    }

    public function getSupportPageLinkTooltip():? string
    {
        return $this->extras['SUPPORTPAGELINKTOOLTIP'] ?? '';
    }

    public function setSupportPageLinkTooltip(?string $tooltip): self
    {
        $this->extras['SUPPORTPAGELINKTOOLTIP'] = $tooltip;
        return $this;
    }

    public function getShowTime():? int
    {
        return $this->extras['SHOW_TIME'] ?? 0;
    }

    public function setShowTime(?int $showTime): self
    {
        $this->extras['SHOW_TIME'] = $showTime;
        return $this;
    }

    public function getFutureTimeCycles():? int
    {
        return $this->extras['FUTURE_TIME_CYCLES'] ?? 1;
    }

    public function setFutureTimeCycles(?int $futureCycles): self
    {
        $this->extras['FUTURE_TIME_CYCLES'] = $futureCycles;
        return $this;
    }

    public function getConfigurationSelection():? int
    {
        return $this->extras['CONFIGURATION_SELECTION'] ?? 0;
    }

    public function setConfigurationSelection(?int $configurationSelection): self
    {
        $this->extras['CONFIGURATION_SELECTION'] = $configurationSelection;
        return $this;
    }

    //CONFIGURATION_ROOM_LIST_TEMPLATES

    public function hasConfigurationRoomListTemplates(): bool
    {
        return $this->extras['CONFIGURATION_ROOM_LIST_TEMPLATES'] ?? true;
    }

    public function setConfigurationRoomListTemplates(?bool $configurationRoomListTemplates): self
    {
        $this->extras['CONFIGURATION_ROOM_LIST_TEMPLATES'] = $configurationRoomListTemplates;
        return $this;
    }

    public function getAnnouncementText():? string
    {
        return $this->extras['ANNOUNCEMENT_TEXT'] ?? '';
    }

    public function setAnnouncementText(?string $text): self
    {
        $this->extras['ANNOUNCEMENT_TEXT'] = $text;
        return $this;
    }

    public function getAnnouncementLink():? string
    {
        return $this->extras['ANNOUNCEMENT_LINK'] ?? '';
    }

    public function setTimeCycleNameGerman(?string $text): self
    {
        $this->extras['TIME_CYCLE_NAME_GERMAN'] = $text;
        return $this;
    }

    public function getTimeCycleNameGerman():? string
    {
        return $this->extras['TIME_CYCLE_NAME_GERMAN'] ?? '';
    }

    public function setTimeCycleNameEnglish(?string $text): self
    {
        $this->extras['TIME_CYCLE_NAME_ENGLISH'] = $text;
        return $this;
    }

    public function getTimeCycleNameEnglish():? string
    {
        return $this->extras['TIME_CYCLE_NAME_ENGLISH'] ?? '';
    }

    public function setAnnouncementLink(?string $link): self
    {
        $this->extras['ANNOUNCEMENT_LINK'] = $link;
        return $this;
    }

    public function getAnnouncementTitle():? string
    {
        return $this->extras['ANNOUNCEMENT_TITLE'] ?? '';
    }

    public function setAnnouncementTitle(string $title): self
    {
        $this->extras['ANNOUNCEMENT_TITLE'] = $title;
        return $this;
    }

    public function getAnnouncementSeverity():? string
    {
        return $this->extras['ANNOUNCEMENT_SEVERITY'] ?? '';
    }

    public function setAnnouncementSeverity(string $severity): self
    {
        $this->extras['ANNOUNCEMENT_SEVERITY'] = $severity;
        return $this;
    }

    public function hasAnnouncementEnabled(): bool
    {
        return $this->extras['ANNOUNCEMENT_ENABLED'] ?? false;
    }

    public function setAnnouncementEnabled(bool $enabled): self
    {
        $this->extras['ANNOUNCEMENT_ENABLED'] = $enabled;
        return $this;
    }

    public function hasServerAnnouncementEnabled(): bool
    {
        return $this->extras['ANNOUNCEMENT_SERVER_ENABLED'] ?? false;
    }

    public function setServerAnnouncementEnabled(bool $enabled): self
    {
        $this->extras['ANNOUNCEMENT_SERVER_ENABLED'] = $enabled;
        return $this;
    }

    /** is room a normal open ?
     * this method returns a boolean explaining if a room is open
     *
     * @return boolean true, if a room is open
     *                 false, if a room is not open
     */
    function isOpen () {
        $retour = false;
        if ( !empty($this->_data['status'])
            and $this->_data['status'] == CS_ROOM_OPEN
        ) {
            $retour = true;
        }
        return $retour;
    }

    /** open the room for usage
     * this method sets the status of the room to open
     */
    function open () {
        $this->_data['status'] = CS_ROOM_OPEN;
    }

    /** close a room
     * this method sets the status of the room to closed
     */
    function close () {
        $this->_data['status'] = CS_ROOM_CLOSED;
    }

    public function setNotShowTime ()
    {
        $this->getExtras()['SHOW_TIME'] = 0;
    }

    function getTimeNameArray () : array
    {
        $retour = array();
        if ($this->getExtras()['TIME_NAME_ARRAY']) {
            $retour = $this->getExtras()['TIME_NAME_ARRAY'];
        }
        return $retour;
    }

    function setTimeNameArray ($value) {
        $this->getExtras()['TIME_NAME_ARRAY'] = $value;

        $value2 = array();
        $value2['NAME'] = CS_TIME_TYPE;

        foreach ($value as $lang => $name) {
            $value2[mb_strtoupper($lang, 'UTF-8')]['NOMPL'] = $name;
        }
        $this->setRubricArray(CS_TIME_TYPE, $value2);
    }

    /** set RubricArray
     * this method sets the Rubric Name
     *
     * @param array value name cases
     */
    function setRubricArray ($rubric, $array) {

        $rubricTranslationArray = array();
        try{
            $rubricTranslationArray = $this->getExtras()['RUBRIC_TRANSLATION_ARRAY'];
        }catch(\ErrorException $e){
        }

        if(empty($rubricTranslationArray) or sizeof($rubricTranslationArray) > 1){
            $rubricTranslationArray = array();
        }
        $extras = $this->getExtras();
        $rubricTranslationArray[cs_strtoupper($rubric)] = $array;
        $extras['RUBRIC_TRANSLATION_ARRAY'] = $rubricTranslationArray;
        $this->setExtras($extras);
    }

    function getTimeInFuture () {
        return ($this->getExtras()['TIME_IN_FUTURE']) ?? 0;
    }

    function setTimeInFuture ($value) {
        $this->getExtras()['TIME_IN_FUTURE'] = $value;
    }

    function setTimeTextArray ($value) {
        $this->getExtras()['TIME_TEXT_ARRAY'] = $value;
    }

    function getIndexViewAction () {
        return ($this->getExtras()['INDEX_VIEW_ACTION']) ?? 0;
    }

    function setIndexViewAction ($value) {
        $this->getExtras()['INDEX_VIEW_ACTION'] = $value;
    }

    function getUserIndexFilterChoice () {
        return ($this->getExtras()['INDEX_FILTER_CHOICE']) ?? 0;
    }

    function setUserIndexFilterChoice ($value) {
        $this->getExtras()['INDEX_FILTER_CHOICE'] = $value;
    }

    function getAccountIndexSearchString () {
        return ($this->getExtras()['ACCOUNT_INDEX_SEARCH_STRING']) ?? "";
    }

    function setAccountIndexSearchString ($value) {
        $this->getExtras()['ACCOUNT_INDEX_SEARCH_STRING'] = $value;
    }


    function getContinuousRoomList (LegacyEnvironment $environment) {
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

    function saveWithoutChangingModificationInformation (LegacyEnvironment $environment) {
        $manager = $environment->getEnvironment()->getPortalManager();
        $manager->saveWithoutChangingModificationInformation();
        $this->_save($manager);
        $this->_changes = array();
    }
}
