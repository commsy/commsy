<?php


namespace App\Proxy;


use App\Entity\AuthSource;
use App\Entity\AuthSourceGuest;
use App\Entity\Portal;
use App\Services\LegacyEnvironment;

class PortalProxy
{
    /**
     * @var Portal
     */
    private $portal;

    /**
     * @var \cs_environment
     */
    private $legacyEnvironment;

    public function __construct(Portal $portal, \cs_environment $legacyEnvironment)
    {
        $this->portal = $portal;
        $this->legacyEnvironment = $legacyEnvironment;
    }

    public function getItemId(): int
    {
        return $this->portal->getId();
    }

    public function getId(): int
    {
        return $this->getItemId();
    }

    public function getTitle(): string
    {
        return $this->portal->getTitle();
    }

    public function showTime(): bool
    {
        return (isset($this->portal->getExtras()['TIME_SHOW']) && $this->portal->getExtras()['TIME_SHOW'] == '1') ? true : false;
    }

    public function getTimeList()
    {
        $retour = NULL;
        $time_manager = $this->legacyEnvironment->getTimeManager();
        $time_manager->setContextLimit($this->getItemID());
        $time_manager->setSortOrder('title');
        $time_manager->select();
        $retour = $time_manager->get();
        unset($time_manager);
        return $retour;
    }

    public function getTimeListRev()
    {
        $retour = NULL;
        $time_manager = $this->legacyEnvironment->getTimeManager();
        $time_manager->setContextLimit($this->getItemID());
        $time_manager->setSortOrder('title_rev');
        $time_manager->select();
        $retour = $time_manager->get();
        unset($time_manager);
        return $retour;
    }

    public function getType(): string
    {
        return 'portal';
    }

    public function isPortal(): bool
    {
        return true;
    }

    public function getAvailableLanguageArray(): array
    {
        return ['de', 'en'];
    }

    public function withGroupRoomFunctions(): bool
    {
        return true;
    }

    public function getCommunityRoomCreationStatus(): string
    {
        return ($this->portal->getExtras()['COMMUNITYROOMCREATIONSTATUS']) ?? 'all';
    }

    public function getProjectRoomCreationStatus(): string
    {
        return ($this->portal->getExtras()['PROJECTCREATIONSTATUS']) ?? 'portal';
    }

    public function getTimeTextArray(): array
    {
        return ($this->portal->getExtras()['TIME_TEXT_ARRAY']) ?? [];
    }

    public function withAGB(): bool
    {
        $agbStatus = ($this->portal->getExtras()['SERVER_NEWS']) ?? '2';
        return $agbStatus == 1;
    }

    public function getMaxRoomActivityPoints(): int
    {
        return (int)($this->portal->getExtras()['MAX_ROOM_ACTIVITY'] ?? 0);
    }

    public function isActivatedDeletingUnusedRooms(): bool
    {
        $deletingRoomExtra = ($this->portal->getExtras()['DELETING_ROOMS_STATUS']) ?? -1;
        return $deletingRoomExtra == 1;
    }

    public function getDaysUnusedBeforeDeletingRooms(): int
    {
        return (int)(($this->portal->getExtras()['DELETING_ROOMS_STATUS']) ?? 365);
    }

    public function getSupportPageLink(): string
    {
        return ($this->portal->getExtras()['SUPPORTPAGELINK']) ?? '';
    }

    public function getSupportPageLinkTooltip(): string
    {
        return ($this->portal->getExtras()['SUPPORTPAGELINKTOOLTIP']) ?? '';
    }

    public function showServiceLink(): bool
    {
        $serviceLinkExtra = ($this->portal->getExtras()['SERVICELINK']) ?? '';
        return $serviceLinkExtra == 1;
    }

    public function getServiceLinkExternal(): string
    {
        return ($this->portal->getExtras()['SERVICELINKEXTERNAL']) ?? '';
    }

    public function getServiceEmail(): string
    {
        return ($this->portal->getExtras()['SERVICEEMAIL']) ?? '';
    }

    public function getCurrentTimeName(): string
    {
        $timeNamesByLanguage = $this->portal->getTimeNameArray();
        $lang = strtoupper($this->legacyEnvironment->getSelectedLanguage());
        $timeName = $timeNamesByLanguage[$lang] ?? '';

        return $timeName;
    }

    public function getProjectRoomLinkStatus(): string
    {
        return ($this->portal->getExtras()['PROJECTROOMLINKSTATUS']) ?? 'optional';
    }

    public function isTagMandatory(): bool
    {
        $tagStatus = ($this->portal->getExtras()['TAGMANDATORY']) ?? -1;
        return $tagStatus == 1;
    }

    public function getDefaultProjectTemplateID(): string
    {
        return ($this->portal->getExtras()['DEFAULTPROJECTTEMPLATEID']) ?? '-1';
    }

    public function getDefaultCommunityTemplateID(): string
    {
        return ($this->portal->getExtras()['DEFAULTCOMMUNITYTEMPLATEID']) ?? '-1';
    }

    function getShowRoomsOnHome(): ?string
    {
        return ($this->portal->getExtras()['SHOWROOMSONHOME']) ?? '';
    }

    function setShowRoomsOnHome(?string $text)
    {
        $this->portal->getExtras()['SHOWROOMSONHOME'] = $text;
    }

    public function getLanguage(): string
    {
        return $this->portal->getExtras()['LANGUAGE'] ?? 'de';
    }

    public function setLanguage($language): PortalProxy {
        $extras = $this->portal->getExtras();
        $extras['LANGUAGE'] = $language;
        $this->portal->setExtras($extras);
        return $this;
    }

    public function isProjectRoom(): bool
    {
        return false;
    }

    public function isCommunityRoom(): bool
    {
        return false;
    }

    public function isPrivateRoom(): bool
    {
        return false;
    }

    public function isGroupRoom(): bool
    {
        return false;
    }

    public function isUserroom(): bool
    {
        return false;
    }

    public function isServer(): bool
    {
        return false;
    }

    public function getRubricTranslationArray(): array
    {
        return [];
    }

    public function getEmailTextArray(): array
    {
        return ($this->portal->getExtras()['MAIL_TEXT_ARRAY']) ?? [];
    }

    public function setEmailTextArray($array)
    {
        $this->portal->getExtras()['MAIL_TEXT_ARRAY'] = $array;
        // $this->portal->setEmailTextArray($array);
    }

    public function setEmailText($message_tag, $array)
    {
        //  foreach($array as $language => $message){
        //     $this->portal->getExtras()['MAIL_TEXT_ARRAY'][$message_tag] = [$language => $message];
        // }

        $this->portal->setEmailText($message_tag, $array);
        $this->portal->setTagMandatory(true);
    }

    public function isArchived(): bool
    {
        return false;
    }

    public function isLocked(): bool
    {
        return $this->portal->getStatus() === 3;
    }

    public function isClosed(): bool
    {
        return $this->portal->getStatus() === 2;
    }

    public function isOpenForGuests(): bool
    {
        return $this->portal->getAuthSources()->filter(function (AuthSource $authSource) {
            return $authSource instanceof AuthSourceGuest && $authSource->isEnabled();
        })->count() > 0;
    }

    public function save()
    {
        $this->portal->save();
    }

    public function getConfigurationHideMailByDefault(): bool
    {
        $hideMailByDefault = ($this->portal->getExtras()['HIDE_MAIL_BY_DEFAULT']) ?? 0;
        return $hideMailByDefault === 1;
    }

    public function setConfigurationHideMailByDefault(bool $enabled)
    {
        $this->portal->getExtras()['HIDE_MAIL_BY_DEFAULT'] = ($enabled === true) ? 1 : 0;
    }

    public function getModeratorList(): \cs_list
    {
        $userManager = $this->legacyEnvironment->getUserManager();
        $userManager->resetLimits();
        $userManager->setContextLimit($this->getItemID());
        $userManager->setModeratorLimit();
        $userManager->select();

        /** @var \cs_list $moderators */
        $moderators = $userManager->get();

        if ($moderators->isEmpty()) {
            if ($this->isClosed() && !$this->legacyEnvironment->isArchiveMode()) {
                $userManager = $this->legacyEnvironment->getZzzUserManager();
                $userManager->resetLimits();
                $userManager->setContextLimit($this->getItemID());
                $userManager->setModeratorLimit();
                $userManager->select();
                $moderators = $userManager->get();
            }
        }

        return $moderators;
    }

    public function getCommunityList(): \cs_list
    {
        $communityManager = $this->legacyEnvironment->getCommunityManager();
        $communityManager->resetLimits();
        $communityManager->setContextLimit($this->getItemId());
        $communityManager->select();

        /** @var \cs_list $communityList */
        $communityList = $communityManager->get();

        return $communityList;
    }

    /**
     * isDeleted
     *
     * @return boolean
     */
    public function isDeleted(): bool
    {
        return ($this->portal->getDeleter() !== null && $this->portal->getDeletionDate() !== null);
    }

    public function showNewsFromServer(): bool
    {
        $showNewsFromServer = ($this->portal->getExtras()['SERVER_NEWS']['SHOW_NEWS_FROM_SERVER']) ?? 0;
        return $showNewsFromServer === 1;
    }

    public function getHideAccountname(): bool
    {
        $hideAccountName = ($this->portal->getExtras()['EXTRA_CONFIG']['HIDE_ACCOUNTNAME']) ?? 0;
        return $hideAccountName === '1';

    }

    public function getRoomType(): string
    {
        return 'portal';
    }
}