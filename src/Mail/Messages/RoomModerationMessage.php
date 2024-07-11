<?php

namespace App\Mail\Messages;

use App\Mail\Message;
use App\Proxy\PortalProxy;
use App\Services\LegacyEnvironment;
use cs_context_item;
use cs_environment;
use cs_room_item;

class RoomModerationMessage extends Message
{
    private cs_environment $legacyEnvironment;

    public function __construct(
        private readonly cs_room_item $room,
        private readonly PortalProxy|cs_context_item|null $parentContext,
        private readonly string $changeType,
        LegacyEnvironment $legacyEnvironment,
        private readonly array $oldLinkedIds = [],
        private readonly array $newLinkedIds = [],
    ) {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
    }

    public function getSubject(): string
    {
        return match ($this->changeType) {
            'open' => 'mail.room_moderation.subject.open',
            'reopen' => 'mail.room_moderation.subject.reopen',
            'delete' => 'mail.room_moderation.subject.deleted',
            'undelete' => 'mail.room_moderation.subject.undeleted',
            'archive' => 'mail.room_moderation.subject.archived',
            'link' => 'mail.room_moderation.subject.linked',
            'lock' => 'mail.room_moderation.subject.locked',
            'unlock' => 'mail.room_moderation.subject.unlocked',
        };
    }

    public function getTemplateName(): string
    {
        return 'mail/room_moderation.html.twig';
    }

    public function getParameters(): array
    {
        $currentUserItem = $this->legacyEnvironment->getCurrentUserItem();
        $translator = $this->legacyEnvironment->getTranslationObject();

        $linkedCommunityRoomNames = [];
        $unlinkedCommunityRoomNames = [];
        if ($this->changeType === 'link') {
            $roomManager = $this->legacyEnvironment->getCommunityManager();
            foreach ($this->newLinkedIds as $roomId) {
                $communityRoom = $roomManager->getItem($roomId);
                if ($communityRoom) {
                    $linkedCommunityRoomNames[] = $communityRoom->getTitle() . (!in_array($roomId, $this->oldLinkedIds) ?
                        " [{$translator->getMessage('COMMON_NEW')}]" :
                        '');
                }
            }

            foreach ($this->oldLinkedIds as $roomId) {
                if (!in_array($roomId, $this->newLinkedIds)) {
                    $communityRoom = $roomManager->getItem($roomId);
                    if ($communityRoom) {
                        $unlinkedCommunityRoomNames[] = $communityRoom->getTitle();
                    }
                }
            }
        }

        return [
            'changeType' => $this->changeType,
            'room' => $this->room,
            'editorName' => $currentUserItem->getFullName(),
            'parentContext' => $this->parentContext,
            'linkedCommunityRoomNames' => $linkedCommunityRoomNames,
            'unlinkedCommunityRoomNames' => $unlinkedCommunityRoomNames,
        ];
    }

    public function getTranslationParameters(): array
    {
        return [
            '%room_title%' => $this->room->getTitle(),
        ];
    }
}
