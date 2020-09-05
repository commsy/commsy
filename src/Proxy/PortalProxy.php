<?php


namespace App\Proxy;


use App\Entity\Portal;
use App\Services\LegacyEnvironment;

class PortalProxy
{
    /**
     * @var Portal
     */
    private $portal;

    public function __construct(Portal $portal)
    {
        $this->portal = $portal;
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

    public function isCountRoomRedundancy(): bool
    {
        return false;
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

    public function showServerNews(): bool
    {
        $serverNewsExtra = ($this->portal->getExtras()['SERVER_NEWS']) ?? [];
        $show = $serverNewsExtra[strtoupper('show')] ?? '';

        return $show == 1;
    }

    public function withAGB(): bool
    {
        $agbStatus = ($this->portal->getExtras()['SERVER_NEWS']) ?? '2';
        return $agbStatus == 1;
    }

    public function getMaxRoomActivityPoints(): int
    {
        return (int) ($this->portal->getExtras()['MAX_ROOM_ACTIVITY'] ?? 0);
    }

    public function isActivatedDeletingUnusedRooms(): bool
    {
        $deletingRoomExtra = ($this->portal->getExtras()['DELETING_ROOMS_STATUS']) ?? -1;
        return $deletingRoomExtra == 1;
    }

    public function getDaysUnusedBeforeDeletingRooms(): int
    {
        return (int) (($this->portal->getExtras()['DELETING_ROOMS_STATUS']) ?? 365);
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
        $timeNamesByLanguage = ($this->portal->getExtras()['TIME_NAME_ARRAY']) ?? [];

        return '';


//        $lang = strtoupper($this->_environment->getSelectedLanguage());
//
//        $timeName = '';
//        if ($timeNamesByLanguage && !empty($timeNamesByLanguage)) {
//            if (isset($timeNamesByLanguage[$lang])) {
//                $timeName = $timeNamesByLanguage[$lang];
//            }
//        }
//
//        return $timeName;
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

    function getShowRoomsOnHome():? string
    {
        return ($this->portal->getExtras()['SHOWROOMSONHOME']) ?? '';
    }

    function setShowRoomsOnHome(?string $text)
    {
        $this->portal->getExtras()['SHOWROOMSONHOME'] = $text;
    }

//    function getShowTime():? string
//    {
//        return ($this->portal->getExtras()['SHOW_TIME']) ?? '';
//    }
//
//    function setShowTime(?string $text)
//    {
//        $this->portal->getExtras()['SHOW_TIME'] = $text;
//    }

    public function setShowTemplatesInRoomListON ()
    {
        $this->portal->getExtras()['SHOW_TEMPLATE_IN_ROOM_LIST'] = 1;
    }

    public function setShowTemplatesInRoomListOFF ()
    {
        $this->portal->getExtras()['SHOW_TEMPLATE_IN_ROOM_LIST'] = -1;
    }

    public function getShowTemplatesInRoomList()
    {
        return ($this->portal->getExtras()['SHOW_TEMPLATE_IN_ROOM_LIST']) ?? 1;
    }

    public function getLanguage(): string
    {
        return 'de';
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

    public function isOpenForGuests(): bool
    {
        return $this->portal->getIsOpenForGuests();
    }

    public function save()
    {
        $this->portal->save();
    }
}