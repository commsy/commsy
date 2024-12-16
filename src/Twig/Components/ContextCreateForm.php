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

namespace App\Twig\Components;

use App\Event\UserJoinedRoomEvent;
use App\Form\Model\ContextCreateData;
use App\Form\Model\RoomData;
use App\Form\Type\Context\ContextType;
use App\Room\Copy\LegacyCopy;
use App\Services\CalendarsService;
use App\Services\LegacyEnvironment;
use App\Services\RoomCategoriesService;
use App\Utils\RoomService;
use App\Utils\UserService;
use cs_environment;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use UnexpectedValueException;

#[AsLiveComponent]
class ContextCreateForm extends AbstractController
{
    use DefaultActionTrait;
    use ComponentWithFormTrait;

    private readonly cs_environment $legacyEnvironment;

    #[LiveProp]
    public int $roomId;

    #[LiveProp]
    public ?RoomData $initialFormData = null;

    #[LiveProp]
    public bool $backToRoom = false;

    #[LiveProp]
    public ?string $forceType = null;

    public function __construct(
        LegacyEnvironment                         $legacyEnvironment,
        private readonly RoomCategoriesService    $roomCategoriesService,
        private readonly UserService              $userService,
        private readonly CalendarsService         $calendarsService,
        private readonly RoomService              $roomService,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly LegacyCopy               $legacyCopy
    ) {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
    }

    protected function instantiateForm(): FormInterface
    {
        $this->initialFormData = $this->initialFormData ?? new RoomData();

        if ($this->forceType) {
            $this->formValues['type_select'] = $this->forceType;
        }

        return $this->createForm(ContextType::class, $this->initialFormData, [
            'roomContextId' => $this->roomId,
            'forceType' => $this->forceType,
        ]);
    }

    #[LiveAction]
    public function save()
    {
        $this->submitForm();

        $form = $this->getForm();

        $roomData = $this->initialFormData;

        if ('project' == $form->get('type_select')->getData()) {
            $roomManager = $this->legacyEnvironment->getProjectManager();
        } elseif ('community' == $form->get('type_select')->getData()) {
            $roomManager = $this->legacyEnvironment->getCommunityManager();
        } else {
            throw new UnexpectedValueException('Error Processing Request: Unrecognized room type', 1);
        }

        $legacyRoom = $roomManager->getNewItem();

        $currentUser = $this->legacyEnvironment->getCurrentUserItem();
        $legacyRoom->setCreatorItem($currentUser);
        $legacyRoom->setCreationDate(getCurrentDateTimeInMySQL());
        $legacyRoom->setModificatorItem($currentUser);
        $legacyRoom->setContextID($this->legacyEnvironment->getCurrentPortalID());
        $legacyRoom->open();

        if ('project' == $form->get('type_select')->getData() && !empty($roomData->getCommunityRooms())) {
            $legacyRoom->setCommunityListByID($roomData->getCommunityRooms());
        }

        // fill in form values from the new entity object
        $legacyRoom->setTitle($roomData->getTitle());
        $legacyRoom->setDescription($roomData->getRoomDescription());

        // user room-related options will only be set in project workspaces
        $legacyRoom->setShouldCreateUserRooms($roomData->isCreateUserRooms());

        if ($roomData->getUserroomTemplate()) {
            $userroomTemplate = $this->roomService->getRoomItem($roomData->getUserroomTemplate());
            if ($userroomTemplate) {
                $legacyRoom->setUserRoomTemplateID($userroomTemplate->getItemID());
            }
        }

        $timeIntervals = $roomData->getTimeInterval();
        if (empty($timeIntervals) || in_array('cont', $timeIntervals)) {
            $legacyRoom->setContinuous();
            $legacyRoom->setTimeListByID([]);
        } else {
            $legacyRoom->setNotContinuous();
            $legacyRoom->setTimeListByID($timeIntervals);
        }

        // persist with legacy code
        $legacyRoom->save();

        $this->calendarsService->createCalendar($legacyRoom, null, null, true);

        // take values from a template?
        if ($roomData->getMasterTemplate()) {
            $masterRoom = $this->roomService->getRoomItem($roomData->getMasterTemplate());
            if ($masterRoom) {
                $legacyRoom = $this->copySettings($masterRoom, $legacyRoom, $this->legacyEnvironment, $this->legacyCopy);
            }
        }

        // NOTE: we can only set the language after copying settings from any room template, otherwise the language
        // would get overwritten by the room template's language setting
        $legacyRoom->setLanguage($roomData->getLanguage());
        $legacyRoom->save();

        $legacyRoomUsers = $this->userService->getListUsers($legacyRoom->getItemID(), null, null, true);
        foreach ($legacyRoomUsers as $user) {
            $this->eventDispatcher->dispatch(new UserJoinedRoomEvent($user, $legacyRoom));
        }

        // mark the room as edited
        $linkModifierItemManager = $this->legacyEnvironment->getLinkModifierItemManager();
        $linkModifierItemManager->markEdited($legacyRoom->getItemID());

        if ($roomData->getCategories()) {
            $this->roomCategoriesService->setRoomCategoriesLinkedToContext(
                $legacyRoom->getItemId(),
                $roomData->getCategories()
            );
        }

        // redirect to the project detail page
        return $this->redirectToRoute($this->backToRoom ? 'app_project_detail' : 'app_roomall_detail', [
            'portalId' => $this->legacyEnvironment->getCurrentPortalID(),
            'roomId' => $this->roomId,
            'itemId' => $legacyRoom->getItemId(),
        ]);
    }

    #[LiveAction]
    public function cancel(): RedirectResponse
    {
        return $this->redirectToRoute($this->backToRoom ? 'app_project_list' : 'app_room_listall', [
            'roomId' => $this->roomId,
        ]);
    }

    /** @noinspection PhpUnused */
    public function getTemplateDescription(): string
    {
        $masterId = $this->initialFormData->getMasterTemplate();
        if ($masterId) {
            $masterRoom = $this->roomService->getRoomItem($masterId);
            return $masterRoom?->getTemplateDescription();
        }

        return '';
    }

    private function copySettings($masterRoom, $targetRoom, cs_environment $legacyEnvironment, LegacyCopy $legacyCopy)
    {
        $old_room = $masterRoom;
        $new_room = $targetRoom;

        $userManager = $legacyEnvironment->getUserManager();
        $creator_item = $userManager->getItem($new_room->getCreatorID());
        if ($creator_item->getContextID() != $new_room->getItemID()) {
            $userManager->resetLimits();
            $userManager->setContextLimit($new_room->getItemID());
            $userManager->setUserIDLimit($creator_item->getUserID());
            $userManager->setAuthSourceLimit($creator_item->getAuthSource());
            $userManager->setModeratorLimit();
            $userManager->select();
            $user_list = $userManager->get();
            if ($user_list->isNotEmpty() && 1 == $user_list->getCount()) {
                $creator_item = $user_list->getFirst();
            } else {
                throw new Exception('can not get creator of new room');
            }
        }
        $creator_item->setAccountWantMail('yes');
        $creator_item->setOpenRoomWantMail('yes');
        $creator_item->save();

        // copy room settings
        $legacyCopy->copySettings($old_room, $new_room);

        // save new room
        $new_room->save();

        // copy data
        $legacyCopy->copyData($old_room, $new_room, $creator_item);

        return $new_room;
    }
}
