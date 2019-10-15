<?php


namespace App\Proxy;


use App\Entity\Portal;

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
        return $this->portal->getType();
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

        return ($show == 1) ? true : false;
    }

    public function withAGB(): bool
    {
        $agbStatus = ($this->portal->getExtras()['SERVER_NEWS']) ?? '2';
        return ($agbStatus == 1) ? true : false;
    }

    public function getMaxRoomActivityPoints(): int
    {
        return (int) $this->portal->getExtras()['MAX_ROOM_ACTIVITY'] ?? 0;
    }

    public function showServiceLink(): bool
    {
        $serviceLinkExtra = ($this->portal->getExtras()['SERVICELINK']) ?? '';
        return ($serviceLinkExtra == 1) ? true : false;
    }

    public function isActivatedDeletingUnusedRooms(): bool
    {
        $deletingRoomExtra = ($this->portal->getExtras()['DELETING_ROOMS_STATUS']) ?? -1;
        return ($deletingRoomExtra == 1) ? true : false;
    }

    public function getDaysUnusedBeforeDeletingRooms(): int
    {
        return (int) ($this->portal->getExtras()['DELETING_ROOMS_STATUS']) ?? 365;
    }
}