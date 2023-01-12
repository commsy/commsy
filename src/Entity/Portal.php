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
use ApiPlatform\Core\Annotation\ApiSubresource;
use App\Controller\Api\GetPortalAnnouncement;
use App\Controller\Api\GetPortalTou;
use App\Repository\PortalRepository;
use App\Services\LegacyEnvironment;
use cs_environment;
use cs_list;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use OpenApi\Annotations as OA;
use Serializable;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

/**
 * Portal.
 *
 * @Vich\Uploadable
 * @ApiResource(
 *     security="is_granted('ROLE_API_READ')",
 *     collectionOperations={
 *         "get",
 *     },
 *     itemOperations={
 *         "get",
 *         "get_announcement"={
 *             "method"="GET",
 *             "path"="portals/{id}/announcement",
 *             "controller"=GetPortalAnnouncement::class,
 *             "openapi_context"={
 *                 "summary"="Get portal announcement",
 *                 "responses"={
 *                     "200"={
 *                         "description"="Portal announcement",
 *                         "content"={
 *                             "application/json"={
 *                                 "schema"={
 *                                     "type"="object",
 *                                     "properties"={
 *                                         "enabled"={"type"="boolean"},
 *                                         "title"={"type"="string"},
 *                                         "severity"={"type"="string"},
 *                                         "text"={"type"="string"},
 *                                     },
 *                                 },
 *                             },
 *                         },
 *                     },
 *                 },
 *             },
 *         },
 *         "get_tou"={
 *             "method"="GET",
 *             "path"="portals/{id}/tou",
 *             "controller"=GetPortalTou::class,
 *             "openapi_context"={
 *                 "summary"="Get portal terms of use",
 *                 "responses"={
 *                     "200"={
 *                         "description"="Portal terms of use",
 *                         "content"={
 *                             "application/json"={
 *                                 "schema"={
 *                                     "type"="object",
 *                                     "properties"={
 *                                         "de"={"type"="string","nullable"=true},
 *                                         "en"={"type"="string","nullable"=true},
 *                                     },
 *                                 },
 *                             },
 *                         },
 *                     },
 *                 },
 *             },
 *         },
 *     },
 *     normalizationContext={
 *         "groups"={"api"},
 *     },
 *     denormalizationContext={
 *         "groups"={"api"},
 *     }
 * )
 */
#[ORM\Entity(repositoryClass: PortalRepository::class)]
#[ORM\Table(name: 'portal')]
#[ORM\HasLifecycleCallbacks]
class Portal implements Serializable
{
    /**
     * @OA\Property(description="The unique identifier.")
     */
    #[ORM\Id]
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\GeneratedValue]
    #[Groups(['api'])]
    private $id;

    #[ORM\ManyToOne(targetEntity: 'User')]
    #[ORM\JoinColumn(name: 'deleter_id', referencedColumnName: 'item_id', nullable: true)]
    private ?User $deleter = null;

    #[ORM\Column(name: 'creation_date', type: 'datetime', nullable: false)]
    #[Groups(['api'])]
    private ?DateTime $creationDate = null;

    #[ORM\Column(name: 'modification_date', type: 'datetime', nullable: false)]
    #[Groups(['api'])]
    private DateTime|DateTimeImmutable|null $modificationDate = null;

    /**
     * @var DateTimeInterface
     */
    #[ORM\Column(name: 'deletion_date', type: 'datetime', nullable: true)]
    private $deletionDate;

    /**
     * @var string
     *
     * @OA\Property(type="string", maxLength=255)
     */
    #[ORM\Column(name: 'title', type: 'string', length: 255, nullable: false)]
    #[Groups(['api'])]
    private $title;

    /**
     * @var string
     *
     * @OA\Property(type="string")
     */
    #[ORM\Column(name: 'description_de', type: 'text')]
    #[Groups(['api'])]
    private ?string $descriptionGerman = null;

    /**
     * @var string
     *
     * @OA\Property(type="string")
     */
    #[ORM\Column(name: 'description_en', type: 'text')]
    #[Groups(['api'])]
    private ?string $descriptionEnglish = null;

    #[ORM\Column(name: 'terms_de', type: 'text')]
    private ?string $termsGerman = null;

    #[ORM\Column(name: 'terms_en', type: 'text')]
    private ?string $termsEnglish = null;

    /**
     * @var mixed[]|int[]|string[]|null[]|bool[]|string[]|mixed[][]|null
     */
    #[ORM\Column(name: 'extras', type: 'array', nullable: true)]
    private ?array $extras = null;

    /**
     * @var string
     */
    #[ORM\Column(name: 'status', type: 'string', length: 20, nullable: false)]
    private $status;

    /**
     * @var int
     */
    #[ORM\Column(name: 'activity', type: 'integer', nullable: false)]
    private $activity = '0';

    /**
     * @ApiSubresource()
     */
    #[ORM\OneToMany(targetEntity: AuthSource::class, mappedBy: 'portal')]
    private Collection $authSources;

    /**
     * NOTE: This is not a mapped field of entity metadata, just a simple property used by VichUploaderBundle.
     *
     * @Vich\UploadableField(mapping="portal_logo", fileNameProperty="logoFilename")
     */
    private ?File $logoFile = null;

    #[ORM\Column(name: 'logo_filename', type: 'string', length: 255, nullable: true)]
    private ?string $logoFilename = null;

    #[ORM\Column(type: 'boolean', options: ['default' => 0])]
    private bool $defaultFilterHideTemplates = false;

    #[ORM\Column(type: 'boolean', options: ['default' => 0])]
    private bool $defaultFilterHideArchived = false;

    #[ORM\Column(type: 'boolean', options: ['default' => 0])]
    private bool $authMembershipEnabled = false;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    #[Assert\NotBlank(message: 'The request identifier must not be empty.', groups: ['authMembershipValidation'])]
    #[Assert\Length(max: 100, maxMessage: 'The request identifier must not exceed {{ limit }} characters.', groups: ['Default', 'authMembershipValidation'])]
    #[Assert\Regex(pattern: '/^[[:alnum:]~._-]+$/', message: 'The request identifier may only contain lowercase English letters, digits or any of these special characters: -._~', groups: ['authMembershipValidation'])]
    private ?string $authMembershipIdentifier = null;

    #[ORM\Column(type: 'boolean', options: ['default' => 0])]
    private bool $clearInactiveAccountsFeatureEnabled = false;

    #[ORM\Column(type: 'smallint', options: ['default' => 180])]
    #[Assert\Positive(message: 'This value should be positive.')]
    private int $clearInactiveAccountsNotifyLockDays = 180;

    #[ORM\Column(type: 'smallint', options: ['default' => 30])]
    #[Assert\Positive(message: 'This value should be positive.')]
    private int $clearInactiveAccountsLockDays = 30;

    #[ORM\Column(type: 'smallint', options: ['default' => 180])]
    #[Assert\Positive(message: 'This value should be positive.')]
    private int $clearInactiveAccountsNotifyDeleteDays = 180;

    #[ORM\Column(type: 'smallint', options: ['default' => 30])]
    #[Assert\Positive(message: 'This value should be positive.')]
    private int $clearInactiveAccountsDeleteDays = 30;

    #[ORM\Column(type: 'boolean', options: ['default' => 0])]
    private bool $clearInactiveRoomsFeatureEnabled = false;

    #[ORM\Column(type: 'smallint', options: ['default' => 180])]
    #[Assert\Positive(message: 'This value should be positive.')]
    private int $clearInactiveRoomsNotifyLockDays = 180;

    #[ORM\Column(type: 'smallint', options: ['default' => 30])]
    #[Assert\Positive(message: 'This value should be positive.')]
    private int $clearInactiveRoomsLockDays = 30;

    #[ORM\Column(type: 'smallint', options: ['default' => 180])]
    #[Assert\Positive(message: 'This value should be positive.')]
    private int $clearInactiveRoomsNotifyDeleteDays = 180;

    #[ORM\Column(type: 'smallint', options: ['default' => 30])]
    #[Assert\Positive(message: 'This value should be positive.')]
    private int $clearInactiveRoomsDeleteDays = 30;

    #[ORM\Column(type: 'boolean', options: ['default' => 1])]
    private bool $projectShowDeactivatedEntriesTitle = true;

    #[ORM\Column(type: 'boolean', options: ['default' => 1])]
    private bool $communityShowDeactivatedEntriesTitle = true;

    public function __construct()
    {
        $this->authSources = new ArrayCollection();
    }

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set deleter.
     *
     * @return Portal
     */
    public function setDeleter(User $deleter = null)
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
     *
     * @return Portal
     */
    public function setCreationDate($creationDate)
    {
        $this->creationDate = $creationDate;

        return $this;
    }

    /**
     * Get creationDate.
     *
     * @return DateTime
     */
    public function getCreationDate()
    {
        return $this->creationDate;
    }

    #[ORM\PreUpdate]
    public function setModificationDateValue()
    {
        $this->modificationDate = new DateTime('now');
    }

    /**
     * Set modificationDate.
     *
     * @param DateTime $modificationDate
     *
     * @return Portal
     */
    public function setModificationDate($modificationDate)
    {
        $this->modificationDate = $modificationDate;

        return $this;
    }

    /**
     * Get modificationDate.
     *
     * @return DateTime
     */
    public function getModificationDate()
    {
        return $this->modificationDate;
    }

    /**
     * Set deletionDate.
     *
     * @param DateTime $deletionDate
     *
     * @return Portal
     */
    public function setDeletionDate($deletionDate)
    {
        $this->deletionDate = $deletionDate;

        return $this;
    }

    /**
     * Get deletionDate.
     *
     * @return DateTime
     */
    public function getDeletionDate()
    {
        return $this->deletionDate;
    }

    /**
     * Set title.
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
     * Get title.
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set extras.
     */
    public function setExtras(array $extras): Portal
    {
        $this->extras = $extras;

        return $this;
    }

    /**
     * Get extras.
     *
     * @return array
     */
    public function getExtras(): ?array
    {
        return $this->extras;
    }

    /**
     * Set status.
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
     * Get status.
     *
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set activity.
     *
     * @param int $activity
     *
     * @return Portal
     */
    public function setActivity($activity)
    {
        $this->activity = $activity;

        return $this;
    }

    /**
     * Get activity.
     *
     * @return int
     */
    public function getActivity()
    {
        return $this->activity;
    }

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

    public function getLogoFile(): ?File
    {
        return $this->logoFile;
    }

    public function setLogoFile(?File $logoFile = null): Portal
    {
        $this->logoFile = $logoFile;
        if (null !== $logoFile) {
            // VichUploaderBundle NOTE: it is required that at least one field changes if you are
            // using Doctrine otherwise the event listeners won't be called and the file is lost
            $this->modificationDate = new DateTimeImmutable();
        }

        return $this;
    }

    public function getLogoFilename(): ?string
    {
        return $this->logoFilename;
    }

    public function setLogoFilename(?string $logoFilename): Portal
    {
        $this->logoFilename = $logoFilename;

        return $this;
    }

    public function getMaxRoomActivityPoints(): int
    {
        return $this->extras['MAX_ROOM_ACTIVITY'] ?? 0;
    }

    public function setMaxRoomActivityPoints(int $points): Portal
    {
        $this->extras['MAX_ROOM_ACTIVITY'] = $points;

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

    public function getAGBChangeDate(): ?DateTimeImmutable
    {
        $agbChangeDateString = $this->extras['AGB_CHANGE_DATE'] ?? '';

        return !empty($agbChangeDateString) ?
            DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $agbChangeDateString) :
            null;
    }

    public function setAGBChangeDate(?DateTimeImmutable $agbChangeDate): Portal
    {
        $agbChangeDateString = $agbChangeDate ? $agbChangeDate->format('Y-m-d H:i:s') : '';
        $this->extras['AGB_CHANGE_DATE'] = $agbChangeDateString;

        return $this;
    }

    /**
     * @OA\Property(type="boolean")
     */
    #[Groups(['api'])]
    public function hasAGBEnabled(): bool
    {
        /**
         * agb status 1 = yes, 2 = no (default).
         *
         * @var int
         */
        $agbStatus = $this->extras['AGBSTATUS'] ?? 2;

        return 1 === $agbStatus;
    }

    public function setAGBEnabled(bool $enabled): Portal
    {
        $this->extras['AGBSTATUS'] = $enabled ? 1 : 2;

        return $this;
    }

    public function getTimePulseName(string $language): string
    {
        if ('EN' === strtoupper($language)) {
            return $this->getTimePulseNameEnglish();
        }

        return $this->getTimePulseNameGerman();
    }

    public function getShowRoomsOnHome(): string
    {
        return $this->extras['SHOWROOMSONHOME'] ?? 'normal';
    }

    public function setShowRoomsOnHome(?string $text): Portal
    {
        if ('onlycommunityrooms' !== $text && 'onlyprojectrooms' !== $text) {
            $text = 'normal';
        }
        $this->extras['SHOWROOMSONHOME'] = $text;

        return $this;
    }

    public function getShowTemplatesInRoomList(): bool
    {
        /**
         * show templates: 1 = yes (default), -1 = no.
         *
         * @var int
         */
        $showTemplates = $this->extras['SHOW_TEMPLATE_IN_ROOM_LIST'] ?? 1;

        return 1 === $showTemplates ? true : false;
    }

    public function setShowTemplatesInRoomList(?bool $showTemplates): Portal
    {
        $this->extras['SHOW_TEMPLATE_IN_ROOM_LIST'] = $showTemplates ? 1 : -1;

        return $this;
    }

    public function getSortRoomsBy(): string
    {
        return $this->extras['SORTROOMSONHOME'] ?? 'activity';
    }

    public function setSortRoomsBy(?string $text): Portal
    {
        if ('activity' !== $text && 'title' !== $text) {
            $text = 'activity';
        }
        $this->extras['SORTROOMSONHOME'] = $text;

        return $this;
    }

    public function getDefaultFilterHideTemplates(): bool
    {
        return $this->defaultFilterHideTemplates;
    }

    public function setDefaultFilterHideTemplates(bool $enabled): Portal
    {
        $this->defaultFilterHideTemplates = $enabled;

        return $this;
    }

    public function getDefaultFilterHideArchived(): bool
    {
        return $this->defaultFilterHideArchived;
    }

    public function setDefaultFilterHideArchived(bool $enabled): Portal
    {
        $this->defaultFilterHideArchived = $enabled;

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
        if ('moderator' !== $status && 'all' !== $status) {
            $status = 'all';
        }
        $this->extras['COMMUNITYROOMCREATIONSTATUS'] = $status;

        return $this;
    }

    public function isProjectShowDeactivatedEntriesTitle(): bool
    {
        return $this->projectShowDeactivatedEntriesTitle;
    }

    public function setProjectShowDeactivatedEntriesTitle(bool $projectShowDeactivatedEntriesTitle): Portal
    {
        $this->projectShowDeactivatedEntriesTitle = $projectShowDeactivatedEntriesTitle;

        return $this;
    }

    public function isCommunityShowDeactivatedEntriesTitle(): bool
    {
        return $this->communityShowDeactivatedEntriesTitle;
    }

    public function setCommunityShowDeactivatedEntriesTitle(bool $communityShowDeactivatedEntriesTitle): Portal
    {
        $this->communityShowDeactivatedEntriesTitle = $communityShowDeactivatedEntriesTitle;

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
        if ('communityroom' !== $status && 'portal' !== $status) {
            $status = 'portal';
        }
        $this->extras['PROJECTCREATIONSTATUS'] = $status;

        return $this;
    }

    /** Returns the project room link status.
     *
     * @return string room link status ("optional" = a project room can be created without assigning it to a community room (default),
     *                "mandatory" = upon room creation, a project room must be assigned to a community room)
     */
    public function getProjectRoomLinkStatus(): string
    {
        return $this->extras['PROJECTROOMLINKSTATUS'] ?? 'optional';
    }

    public function setProjectRoomLinkStatus(?string $status): Portal
    {
        if ('mandatory' !== $status && 'optional' !== $status) {
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
     * @return bool whether room categories are mandatory (true) or not (false)
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
         * hide account name: 1 = yes, 2 = no (default).
         *
         * @var int
         */
        $hideAccountName = $this->extras['HIDE_ACCOUNTNAME'] ?? null;
        if (!isset($hideAccountName) && isset($this->extras['EXTRA_CONFIG'])) {
            // NOTE: in CommSy9 and earlier, HIDE_ACCOUNTNAME was part of an EXTRA_CONFIG array
            $hideAccountName = $this->extras['EXTRA_CONFIG']['HIDE_ACCOUNTNAME'] ?? 2;
        }

        return 1 === $hideAccountName ? true : false;
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
         * hide mail by default: 1 = yes, 0 = no (default).
         *
         * @var int
         */
        $hideEmailAddress = $this->extras['HIDE_MAIL_BY_DEFAULT'] ?? 0;

        return 1 === $hideEmailAddress ? true : false;
    }

    public function setHideEmailAddressByDefault(?bool $hideEmailAddress): Portal
    {
        $this->extras['HIDE_MAIL_BY_DEFAULT'] = $hideEmailAddress ? 1 : 0;

        return $this;
    }

    /**
     * @return string
     */
    public function getDescriptionGerman(): ?string
    {
        return $this->descriptionGerman;
    }

    public function setDescriptionGerman(?string $descriptionGerman): Portal
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

    public function setDescriptionEnglish(?string $descriptionEnglish): Portal
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
         * show time pulses: 1 = yes, -1 = no (default).
         *
         * @var int
         */
        $showTimePulses = $this->extras['TIME_SHOW'] ?? -1;

        return 1 === $showTimePulses ? true : false;
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
        $timePulseName ??= '';
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
        $timePulseName ??= '';
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
        return $this->getExtras()['INDEX_VIEW_ACTION'] ?? 0;
    }

    public function setIndexViewAction($value)
    {
        $this->getExtras()['INDEX_VIEW_ACTION'] = $value;
    }

    public function getUserIndexFilterChoice()
    {
        return $this->getExtras()['INDEX_FILTER_CHOICE'] ?? 0;
    }

    public function setUserIndexFilterChoice($value)
    {
        $this->getExtras()['INDEX_FILTER_CHOICE'] = $value;
    }

    public function getAccountIndexSearchString()
    {
        return $this->getExtras()['ACCOUNT_INDEX_SEARCH_STRING'] ?? '';
    }

    public function setAccountIndexSearchString($value)
    {
        $this->getExtras()['ACCOUNT_INDEX_SEARCH_STRING'] = $value;
    }

    public function getContinuousRoomList(LegacyEnvironment $environment)
    {
        $manager = $environment->getEnvironment()->getRoomManager();
        $manager->setContextLimit($this->getId());
        $manager->setContinuousLimit();
        $manager->select();

        return $manager->get();
    }

    /**
     * @param cs_environment $environment
     *
     * @return cs_list
     */
    public function getContactModeratorList($environment)
    {
        $user_manager = $environment->getUserManager();
        $user_manager->setContextLimit($this->getId());
        $user_manager->setContactModeratorLimit();
        $user_manager->select();
        $contactModeratorList = $user_manager->get();

        if ($contactModeratorList->isEmpty()) {
            $contactModeratorList = $this->getModeratorList($environment);
        }

        return $contactModeratorList;
    }

    /**
     * @param cs_environment $environment
     *
     * @return cs_list
     */
    public function getModeratorList($environment)
    {
        $userManager = $environment->getUserManager();
        $userManager->resetLimits();
        $userManager->setContextLimit($this->getId());
        $userManager->setModeratorLimit();
        $userManager->select();

        return $userManager->get();
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

    // ##################################################
    // email text translation methods
    // ##################################################

    public function getEmailTextArray()
    {
        $retour = [];
        if ($this->_issetExtra('MAIL_TEXT_ARRAY')) {
            $retour = $this->getExtras()['MAIL_TEXT_ARRAY'];
        }

        return $retour;
    }

    public function setEmailText($message_tag, $array)
    {
        $mail_text_array = [];
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

    public function setEmailTextArray($array)
    {
        if (!empty($array)) {
            $this->_addExtra('MAIL_TEXT_ARRAY', $array);
        }
    }

    /** exists the extra information with the name $key ?
     * this method returns a boolean, if the value exists or not.
     *
     * @param string key   the key (name) of the value
     *
     * @return bool true, if value exists
     *              false, if not
     */
    public function _issetExtra($key)
    {
        $result = false;
        $extras = $this->getExtras();
        if (isset($extras) and is_array($extras) and array_key_exists($key, $extras) and isset($extras[$key])) {
            $result = true;
        }

        return $result;
    }

    public function _addExtra($key, $value)
    {
        $extras = $this->getExtras();
        $extras[$key] = $value;
        $this->setExtras($extras);
    }

    public function isClearInactiveAccountsFeatureEnabled(): bool
    {
        return $this->clearInactiveAccountsFeatureEnabled;
    }

    public function setClearInactiveAccountsFeatureEnabled(bool $clearInactiveAccountsFeatureEnabled): Portal
    {
        $this->clearInactiveAccountsFeatureEnabled = $clearInactiveAccountsFeatureEnabled;

        return $this;
    }

    public function getClearInactiveAccountsNotifyLockDays(): int
    {
        return $this->clearInactiveAccountsNotifyLockDays;
    }

    public function setClearInactiveAccountsNotifyLockDays(int $clearInactiveAccountsNotifyLockDays): Portal
    {
        $this->clearInactiveAccountsNotifyLockDays = $clearInactiveAccountsNotifyLockDays;

        return $this;
    }

    public function getClearInactiveAccountsLockDays(): int
    {
        return $this->clearInactiveAccountsLockDays;
    }

    public function setClearInactiveAccountsLockDays(int $clearInactiveAccountsLockDays): Portal
    {
        $this->clearInactiveAccountsLockDays = $clearInactiveAccountsLockDays;

        return $this;
    }

    public function getClearInactiveAccountsNotifyDeleteDays(): int
    {
        return $this->clearInactiveAccountsNotifyDeleteDays;
    }

    public function setClearInactiveAccountsNotifyDeleteDays(int $clearInactiveAccountsNotifyDeleteDays): Portal
    {
        $this->clearInactiveAccountsNotifyDeleteDays = $clearInactiveAccountsNotifyDeleteDays;

        return $this;
    }

    public function getClearInactiveAccountsDeleteDays(): int
    {
        return $this->clearInactiveAccountsDeleteDays;
    }

    public function setClearInactiveAccountsDeleteDays(int $clearInactiveAccountsDeleteDays): Portal
    {
        $this->clearInactiveAccountsDeleteDays = $clearInactiveAccountsDeleteDays;

        return $this;
    }

    public function isClearInactiveRoomsFeatureEnabled(): bool
    {
        return $this->clearInactiveRoomsFeatureEnabled;
    }

    public function setClearInactiveRoomsFeatureEnabled(bool $clearInactiveRoomsFeatureEnabled): Portal
    {
        $this->clearInactiveRoomsFeatureEnabled = $clearInactiveRoomsFeatureEnabled;

        return $this;
    }

    public function getClearInactiveRoomsNotifyLockDays(): int
    {
        return $this->clearInactiveRoomsNotifyLockDays;
    }

    public function setClearInactiveRoomsNotifyLockDays(int $clearInactiveRoomsNotifyLockDays): Portal
    {
        $this->clearInactiveRoomsNotifyLockDays = $clearInactiveRoomsNotifyLockDays;

        return $this;
    }

    public function getClearInactiveRoomsLockDays(): int
    {
        return $this->clearInactiveRoomsLockDays;
    }

    public function setClearInactiveRoomsLockDays(int $clearInactiveRoomsLockDays): Portal
    {
        $this->clearInactiveRoomsLockDays = $clearInactiveRoomsLockDays;

        return $this;
    }

    public function getClearInactiveRoomsNotifyDeleteDays(): int
    {
        return $this->clearInactiveRoomsNotifyDeleteDays;
    }

    public function setClearInactiveRoomsNotifyDeleteDays(int $clearInactiveRoomsNotifyDeleteDays): Portal
    {
        $this->clearInactiveRoomsNotifyDeleteDays = $clearInactiveRoomsNotifyDeleteDays;

        return $this;
    }

    public function getClearInactiveRoomsDeleteDays(): int
    {
        return $this->clearInactiveRoomsDeleteDays;
    }

    public function setClearInactiveRoomsDeleteDays(int $clearInactiveRoomsDeleteDays): Portal
    {
        $this->clearInactiveRoomsDeleteDays = $clearInactiveRoomsDeleteDays;

        return $this;
    }

    public function getAuthMembershipEnabled(): ?bool
    {
        return $this->authMembershipEnabled;
    }

    public function setAuthMembershipEnabled(bool $authMembershipEnabled): self
    {
        $this->authMembershipEnabled = $authMembershipEnabled;

        return $this;
    }

    public function getAuthMembershipIdentifier(): ?string
    {
        return $this->authMembershipIdentifier;
    }

    public function setAuthMembershipIdentifier(?string $authMembershipIdentifier): self
    {
        $this->authMembershipIdentifier = $authMembershipIdentifier;

        return $this;
    }
}
