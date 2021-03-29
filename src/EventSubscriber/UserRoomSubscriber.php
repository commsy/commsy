<?php

namespace App\EventSubscriber;

use App\Event\AccountChangedEvent;
use App\Event\RoomSettingsChangedEvent;
use App\Event\UserJoinedRoomEvent;
use App\Event\UserLeftRoomEvent;
use App\Event\UserStatusChangedEvent;
use App\Utils\UserroomService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class UserRoomSubscriber implements EventSubscriberInterface
{
    /**
     * @var UserroomService
     */
    private $userroomService;

    public function __construct(UserroomService $userroomService)
    {
        $this->userroomService = $userroomService;
    }

    public static function getSubscribedEvents()
    {
        return [
            UserJoinedRoomEvent::class => 'onUserJoinedRoom',
            UserLeftRoomEvent::class => 'onUserLeftRoom',
            UserStatusChangedEvent::class => 'onUserStatusChanged',
            AccountChangedEvent::class => 'onAccountChanged',
            RoomSettingsChangedEvent::class => 'onRoomSettingsChanged',
        ];
    }

    public function onUserJoinedRoom(UserJoinedRoomEvent $event)
    {
        $user = $event->getUser();
        $room = $event->getRoom();

        // only create a user room if the feature has been enabled for this project room (in room settings > extensions)
        if (!($room->isProjectRoom() && $room->getShouldCreateUserRooms())) {
            return;
        }

        // only create a user room if there isn't already a user room for this user
        $existingUserroom = $user->getLinkedUserroomItem();
        if ($existingUserroom) {
            return;
        }

        // create a user room within $room, and create its initial users (for $user as well as all $room moderators)
        $this->userroomService->createUserroom($room, $user);
    }

    public function onUserLeftRoom(UserLeftRoomEvent $event)
    {
        $user = $event->getUser();
        $room = $event->getRoom();

        if (!$room->isProjectRoom() || !$user->isDeleted()) {
            return;
        }

        // NOTE: a user's user room will be deleted again via cs_user_item->delete()

        $this->userroomService->removeUserFromUserroomsForRoom($room, $user);
    }

    public function onUserStatusChanged(UserStatusChangedEvent $event)
    {
        $user = $event->getUser();
        $room = $user->getContextItem();

        if (!$room->isProjectRoom()) {
            return;
        }

        // a user room contains a single regular user (who "owns" this user room), plus one or more moderators;
        // thus we ignore the status change unless the status was changed to a regular user (2) or moderator (3)
        $userStatus = $user->getStatus();
        if ($userStatus !== 2 && $userStatus !== 3) {
            return;
        }

        $this->userroomService->changeUserStatusInUserroomsForRoom($room, $user);
    }

    public function onAccountChanged(AccountChangedEvent $event)
    {
        $oldUser = $event->getOldAccount();
        $newUser = $event->getNewAccount();

        $portalUser = $newUser->getRelatedPortalUserItem();

        /**
         * @var \cs_user_item[] $relatedUsers
         */
        $relatedUsers = $portalUser->getRelatedUserList(true)->to_array();

        foreach ($relatedUsers as $relatedUser) {
            $room = $relatedUser->getContextItem();

            // if the user's email just changed, update the email for each of the related user room users
            if ($room->isUserroom()) {
                $newUserEmail = $newUser->getRoomEmail();
                if ($oldUser->getRoomEmail() !== $newUserEmail) {
                    $relatedUser->setEmail($newUserEmail);
                    $relatedUser->save();
                }
            }

            if (!$room->isProjectRoom() || !($relatedUser->isUser() || $relatedUser->isModerator())) {
                continue;
            }

            // if the user's name just changed, rename the project user's user room as well as all corresponding user room users
            if ($oldUser->getFullName() !== $newUser->getFullName()) {
                // NOTE: after a user was renamed in the account settings, for some $relatedUser item, the first/last name
                // has not been fully updated yet, thus we provide the first/last names explicitly
                $this->userroomService->updateNameInUserroomsForUser($relatedUser, $newUser->getFirstname(), $newUser->getLastname());
            }

            // NOTE: a change of the user's user ID is handled in cs_user_manager->changeUserID() (called via UserTransformer)

            // if the user's chosen system language just changed, also update the user's system language in her user room
            if ($oldUser->getLanguage() !== $newUser->getLanguage()) {
                $this->userroomService->updateLanguageInUserroomOfUser($relatedUser, $newUser->getLanguage());
            }
        }
    }

    public function onRoomSettingsChanged(RoomSettingsChangedEvent $event)
    {
        $oldRoom = $event->getOldRoom();
        $newRoom = $event->getNewRoom();

        if (!$newRoom->isProjectRoom()) {
            return;
        }

        // if the 'CREATE_USER_ROOMS' setting was just enabled, create user rooms for all existing users
        if (!$oldRoom->getShouldCreateUserRooms() && $newRoom->getShouldCreateUserRooms()) {
            $this->userroomService->createUserroomsForRoomUsers($newRoom);
        }

        // if the room's title was just changed, rename all user rooms accordingly
        if ($oldRoom->getTitle() !== $newRoom->getTitle()) {
            $this->userroomService->renameUserroomsForRoom($newRoom);
        }

        if ($oldRoom->getUserRoomTemplateID() !== $newRoom->getUserRoomTemplateID()){
            $this->userroomService->updateTemplateInUserroomsForRoom($newRoom);
        }
    }
}
