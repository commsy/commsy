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

namespace App\EventSubscriber;

use App\Entity\Portal;
use App\Event\Workspace\WorkspaceArchivedEvent;
use App\Event\Workspace\WorkspaceDeletedEvent;
use App\Event\Workspace\WorkspaceEventInterface;
use App\Event\Workspace\WorkspaceLinkUpdatedEvent;
use App\Event\Workspace\WorkspaceLockedEvent;
use App\Event\Workspace\WorkspaceOpenedEvent;
use App\Event\Workspace\WorkspaceUnarchivedEvent;
use App\Event\Workspace\WorkspaceUndeletedEvent;
use App\Event\Workspace\WorkspaceUnlockedEvent;
use cs_community_item;
use cs_grouproom_item;
use cs_project_item;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class WorkspaceSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            WorkspaceDeletedEvent::class => 'onWorkspaceDeletedEvent',
            WorkspaceUndeletedEvent::class => 'onWorkspaceUndeletedEvent',
            WorkspaceOpenedEvent::class => 'onWorkspaceOpenedEvent',
            WorkspaceLinkUpdatedEvent::class => 'onWorkspaceLinkUpdatedEvent',
            WorkspaceLockedEvent::class => 'onWorkspaceLockedEvent',
            WorkspaceUnlockedEvent::class => 'onWorkspaceUnlockedEvent',
            WorkspaceArchivedEvent::class => 'onWorkspaceArchivedEvent',
            WorkspaceUnarchivedEvent::class => 'onWorkspaceUnarchivedEvent',
        ];
    }

    public function onWorkspaceDeletedEvent(WorkspaceDeletedEvent $event): void
    {
        $workspace = $event->getWorkspace();

        if ($workspace instanceof cs_project_item) {
            $workspace->_sendMailRoomDeleteToProjectModeration();
            $this->executeIfValid($workspace->_sendMailRoomDeleteToCommunityModeration(...), $event);
            $workspace->_sendMailRoomDeleteToPortalModeration();
        } else if ($workspace instanceof cs_community_item) {
            $workspace->_sendMailRoomDeleteToCommunityModeration();
            $workspace->_sendMailRoomDeleteToPortalModeration();
        } else if ($workspace instanceof cs_grouproom_item) {
            // Legacy parity: cs_grouproom_item::delete() used to send
            // these three mails synchronously inline. Under the new
            // RoomDeleter pipeline the dispatch moves here so every
            // room type flows through the same event.
            $workspace->_sendMailRoomDeleteToGroupModeration();
            $workspace->_sendMailRoomDeleteToProjectModeration();
            $workspace->_sendMailRoomDeleteToPortalModeration();
        }
    }

    public function onWorkspaceUndeletedEvent(WorkspaceUndeletedEvent $event): void
    {
        $workspace = $event->getWorkspace();

        if ($workspace instanceof cs_project_item) {
            $workspace->_sendMailRoomUnDeleteToProjectModeration();
            $this->executeIfValid($workspace->_sendMailRoomUnDeleteToCommunityModeration(...), $event);
            $workspace->_sendMailRoomUnDeleteToPortalModeration();
        } else if ($workspace instanceof cs_community_item) {
            $workspace->_sendMailRoomUnDeleteToCommunityModeration();
            $workspace->_sendMailRoomUnDeleteToPortalModeration();
        } else if ($workspace instanceof cs_grouproom_item) {
            // Symmetric to onWorkspaceDeletedEvent above — legacy sent
            // these three un-delete mails inline from cs_grouproom_item::undelete().
            $workspace->_sendMailRoomUnDeleteToGroupModeration();
            $workspace->_sendMailRoomUnDeleteToProjectModeration();
            $workspace->_sendMailRoomUnDeleteToPortalModeration();
        }
    }

    public function onWorkspaceOpenedEvent(WorkspaceOpenedEvent $event): void
    {
        $workspace = $event->getWorkspace();

        if ($workspace instanceof cs_project_item) {
            $workspace->_sendMailRoomOpenToProjectModeration();
            $this->executeIfValid($workspace->_sendMailRoomOpenToCommunityModeration(...), $event);
            $workspace->_sendMailRoomOpenToPortalModeration();
        } else if ($workspace instanceof cs_community_item) {
            $workspace->_sendMailRoomOpenToCommunityModeration();
            $workspace->_sendMailRoomOpenToPortalModeration();
        } else if ($workspace instanceof cs_grouproom_item) {
            $workspace->_sendMailRoomOpenToGroupModeration();
            $workspace->_sendMailRoomOpenToProjectModeration();
            $workspace->_sendMailRoomOpenToPortalModeration();
        }
    }

    public function onWorkspaceLinkUpdatedEvent(WorkspaceLinkUpdatedEvent $event): void
    {
        $workspace = $event->getWorkspace();

        if ($workspace instanceof cs_project_item) {
            $workspace->_sendMailRoomLinkToProjectModeration();
            $this->executeIfValid($workspace->_sendMailRoomLinkToCommunityModeration(...), $event);
            $workspace->_sendMailRoomLinkToPortalModeration();
        }
    }

    public function onWorkspaceLockedEvent(WorkspaceLockedEvent $event): void
    {
        $workspace = $event->getWorkspace();

        if ($workspace instanceof cs_project_item) {
            $workspace->_sendMailRoomLockToProjectModeration();
            $this->executeIfValid($workspace->_sendMailRoomLockToCommunityModeration(...), $event);
            $workspace->_sendMailRoomLockToPortalModeration();
        } else if ($workspace instanceof cs_community_item) {
            $workspace->_sendMailRoomLockToCommunityModeration();
            $workspace->_sendMailRoomLockToPortalModeration();
        } else if ($workspace instanceof cs_grouproom_item) {
            $workspace->_sendMailRoomLockToGroupModeration();
            $workspace->_sendMailRoomLockToProjectModeration();
            $workspace->_sendMailRoomLockToPortalModeration();
        }
    }

    public function onWorkspaceUnlockedEvent(WorkspaceUnlockedEvent $event): void
    {
        $workspace = $event->getWorkspace();

        if ($workspace instanceof cs_project_item) {
            $workspace->_sendMailRoomUnlockToProjectModeration();
            $this->executeIfValid($workspace->_sendMailRoomUnlockToCommunityModeration(...), $event);
            $workspace->_sendMailRoomUnlockToPortalModeration();
        } else if ($workspace instanceof cs_community_item) {
            $workspace->_sendMailRoomUnlockToCommunityModeration();
            $workspace->_sendMailRoomUnlockToPortalModeration();
        } else if ($workspace instanceof cs_grouproom_item) {
            $workspace->_sendMailRoomUnlockToGroupModeration();
            $workspace->_sendMailRoomUnlockToProjectModeration();
            $workspace->_sendMailRoomUnlockToPortalModeration();
        }
    }

    public function onWorkspaceArchivedEvent(WorkspaceArchivedEvent $event): void
    {
        $workspace = $event->getWorkspace();

        if ($workspace instanceof cs_project_item) {
            $workspace->_sendMailRoomArchiveToProjectModeration();
            $this->executeIfValid($workspace->_sendMailRoomArchiveToCommunityModeration(...), $event);
            $workspace->_sendMailRoomArchiveToPortalModeration();
        } else if ($workspace instanceof cs_community_item) {
            $workspace->_sendMailRoomArchiveToCommunityModeration();
            $workspace->_sendMailRoomArchiveToPortalModeration();
        } else if ($workspace instanceof cs_grouproom_item) {
            $workspace->_sendMailRoomArchiveToGroupModeration();
            $workspace->_sendMailRoomArchiveToProjectModeration();
            $workspace->_sendMailRoomArchiveToPortalModeration();
        }
    }

    public function onWorkspaceUnarchivedEvent(WorkspaceUnarchivedEvent $event): void
    {
        $workspace = $event->getWorkspace();

        if ($workspace instanceof cs_project_item) {
            $workspace->_sendMailRoomReOpenToProjectModeration();
            $this->executeIfValid($workspace->_sendMailRoomReOpenToCommunityModeration(...), $event);
            $workspace->_sendMailRoomReOpenToPortalModeration();
        } else if ($workspace instanceof cs_community_item) {
            $workspace->_sendMailRoomReOpenToCommunityModeration();
            $workspace->_sendMailRoomReOpenToPortalModeration();
        } else if ($workspace instanceof cs_grouproom_item) {
            $workspace->_sendMailRoomReOpenToGroupModeration();
            $workspace->_sendMailRoomReOpenToProjectModeration();
            $workspace->_sendMailRoomReOpenToPortalModeration();
        }
    }

    function executeIfValid(callable $callback, WorkspaceEventInterface $event): void
    {
        $room = $event->getWorkspace();

        // only deal with project rooms, otherwise execute as is
        if (!($room instanceof cs_project_item)) {
            $callback();
        }

        /* @var Portal $portal */
        $portal = $room->getPortal();

        if ($portal->isNotifyCommunityModForAllProjectRooms()) {
            $callback();
        }
    }
}

