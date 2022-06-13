<?php

namespace App\EventSubscriber;

use App\Services\LegacyEnvironment;
use App\Utils\ItemService;
use App\Utils\RoomService;
use cs_environment;
use cs_room_item;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use WhiteOctober\BreadcrumbsBundle\Model\Breadcrumbs;

class BreadcrumbSubscriber implements EventSubscriberInterface
{
    /**
     * @var cs_environment
     */
    private cs_environment $legacyEnvironment;

    /**
     * @var RoomService
     */
    private RoomService $roomService;

    /**
     * @var ItemService
     */
    private ItemService $itemService;

    /**
     * @var TranslatorInterface
     */
    private TranslatorInterface $translator;

    /**
     * @var Breadcrumbs
     */
    private Breadcrumbs $breadcrumbs;

    /**
     * @param LegacyEnvironment $legacyEnvironment
     * @param RoomService $roomService
     * @param ItemService $itemService
     * @param TranslatorInterface $translator
     * @param Breadcrumbs $whiteOctoberBreadcrumbs
     */
    public function __construct(
        LegacyEnvironment $legacyEnvironment,
        RoomService $roomService,
        ItemService $itemService,
        TranslatorInterface $translator,
        Breadcrumbs $whiteOctoberBreadcrumbs
    ) {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
        $this->roomService = $roomService;
        $this->itemService = $itemService;
        $this->breadcrumbs = $whiteOctoberBreadcrumbs;
        $this->translator = $translator;
    }

    /**
     * @param ControllerEvent $event
     */
    public function onControllerEvent(ControllerEvent $event)
    {
        if ($event->getRequestType() != HttpKernelInterface::MASTER_REQUEST) {
            return;
        }
        $request = $event->getRequest();

        $route = explode('_', $request->get('_route'));

        if (count($route) < 3) {
            return;
        }

        list($bundle, $controller, $action) = $route;

        $routeParameters = $request->get('_route_params');

        $roomItem = $this->roomService->getCurrentRoomItem();

        // Longest case:
        // Portal / CommunityRoom / ProjectRooms / ProjectRoomName / Groups / GroupName / Grouproom / Rubric / Entry

        $this->addPortalCrumb($request);

        // portal settings
        if ($controller == 'portal') {

            $portal = $this->legacyEnvironment->getCurrentPortalItem();

            $this->breadcrumbs->addRouteItem($this->translator->trans('settings', [], 'portal'),
                "app_portal_legacysettings", ["roomId" => $portal->getItemId()]);

            $this->breadcrumbs->addItem($this->translator->trans($action, [], 'portal'));

            return;
        }

        if ($roomItem == null) {
            return;
        }

        if ($controller == 'profile') {
            $this->addProfileCrumbs($roomItem, $routeParameters, $action);
        } elseif ($controller == 'room' && $action == 'home') {
            $this->addRoom($roomItem, false);
        } elseif ($controller == 'dashboard' && $action == 'overview') {
            $this->breadcrumbs->addItem($this->translator->trans($controller, [], 'menu'));
        } elseif ($controller == 'category') {
            $this->addRoom($roomItem, true);
            $this->breadcrumbs->addItem($this->translator->trans('Categories', [], 'category'));
        } elseif ($controller == 'hashtag') {
            $this->addRoom($roomItem, true);
            $this->breadcrumbs->addItem($this->translator->trans('hashtags', [], 'room'));
        }
        elseif ($controller == 'cancellablelockanddelete' && $action == 'deleteorlock') {
            $itemId = $routeParameters['itemId'] ?? null;
            if ($itemId) {
                $room = $this->roomService->getRoomItem(intval($itemId));
                $this->addRoom($room, true);
            }
        }
        else {
            $this->addRoom($roomItem, true);

            // rubric & entry
            if (array_key_exists('itemId', $routeParameters)) {

                // link to rubric
                $route[2] = 'list';
                unset($routeParameters['itemId']);
                try {
                    if ($controller == 'context' && $action == 'request') {
                        if ($roomItem->isCommunityRoom()) {
                            $this->addChildRoomListCrumb($roomItem, 'project');
                        } elseif ($roomItem->isProjectRoom()) {
                            $this->addChildRoomListCrumb($roomItem, 'group');
                        }
                    } else {
                        $routerImplode = implode("_", $route);
                        if($this->isDateCalendar($roomItem, $controller)){
                            $routerImplode = 'app_date_calendar';
                        }
                        $this->breadcrumbs->addRouteItem($this->translator->trans($controller, [], 'menu')
                            , $routerImplode
                            , $routeParameters);
                    }
                } catch (RouteNotFoundException $e) {
                    // we don't need breadcrumbs for routes like app_item_editdetails etc. to ajax controller actions
                }

                // entry title
                $item = $this->itemService->getTypedItem($request->get('itemId'));
                if ($item) {
                    $this->breadcrumbs->addItem($item->getItemType() == 'user' ? $item->getFullName() : $item->getTitle());
                }
            } // rubric only
            else {
                if ($controller == 'room' && $action == 'listall') {
                    $this->breadcrumbs->addItem($this->translator->trans('All rooms', [], 'room'));
                } else {
                    $this->breadcrumbs->addItem($this->translator->trans($controller, [], 'menu'));
                }
            }
        }
    }

    /**
     * @return string[]
     */
    public static function getSubscribedEvents()
    {
        return [
            ControllerEvent::class => 'onControllerEvent',
        ];
    }

    /**
     * @param $request
     */
    private function addPortalCrumb($request)
    {
        $portal = $this->legacyEnvironment->getCurrentPortalItem();
        if ($portal) {
            $this->breadcrumbs->addRouteItem($portal->getTitle(), "app_helper_portalenter",
                ["context" => $portal->getItemId()]);
        }
    }

    /**
     * @param $roomItem
     * @param $asLink
     */
    private function addRoom($roomItem, $asLink)
    {
        if ($roomItem->isGroupRoom()) {
            $this->addGroupRoom($roomItem, $asLink);
        } elseif ($roomItem->isUserroom()) {
            $this->addUserRoom($roomItem, $asLink);
        } elseif ($roomItem->isProjectRoom()) {
            $this->addProjectRoom($roomItem, $asLink);
        } elseif ($roomItem->isCommunityRoom()) {
            $this->addCommunityRoom($roomItem, $asLink);
        } elseif ($roomItem->isPrivateRoom()) {
            $this->addDashboard($roomItem, $asLink);
        }
    }

    /**
     * @param $roomItem
     * @param $asLink
     */
    private function addDashboard($roomItem, $asLink)
    {
        $this->breadcrumbs->addRouteItem($this->translator->trans('dashboard', [], 'menu'), "app_dashboard_overview",
            ["roomId" => $roomItem->getItemId()]);
    }

    /**
     * @param $roomItem
     * @param $asLink
     */
    private function addCommunityRoom($roomItem, $asLink)
    {
        $this->addRoomCrumb($roomItem, $asLink);
    }

    /**
     * @param $roomItem
     * @param $asLink
     */
    private function addProjectRoom($roomItem, $asLink)
    {
        // TODO: when called from addUserRoom(), $communityRoomItem is empty (even if the project room belongs to a community room)
        $communityRoomItem = $roomItem->getCommunityList()->getFirst();
        if ($communityRoomItem) {
            $this->addCommunityRoom($communityRoomItem, true);
            $this->addChildRoomListCrumb($communityRoomItem, 'project');
        }
        $this->addRoomCrumb($roomItem, $asLink);
    }

    /**
     * @param $roomItem
     * @param $asLink
     */
    private function addGroupRoom($roomItem, $asLink)
    {
        $groupItem = $roomItem->getLinkedGroupItem();
        if ($groupItem) {
            $projectRoom = $roomItem->getLinkedProjectItem();
            if ($projectRoom) {
                // ProjectRoom
                $this->addProjectRoom($projectRoom, true);
                // "Groups" rubric in project room
                $this->addChildRoomListCrumb($projectRoom, 'group');
                // Group (with name)
                $this->breadcrumbs->addRouteItem($groupItem->getTitle(), "app_group_detail",
                    ['roomId' => $projectRoom->getItemId(), 'itemId' => $groupItem->getItemId()]);
                // Grouproom
                $this->addRoomCrumb($roomItem, $asLink);
            }
        }
    }

    /**
     * @param $roomItem
     * @param $asLink
     */
    private function addUserRoom($roomItem, $asLink)
    {
        $projectRoom = $roomItem->getLinkedProjectItem();
        if ($projectRoom) {
            // ProjectRoom
            $this->addProjectRoom($projectRoom, true);
            // "Persons" rubric in project room
            $this->addChildRoomListCrumb($projectRoom, 'userroom');
            // Userroom
            $this->addRoomCrumb($roomItem, $asLink);
        }
    }

    /**
     * @param cs_room_item $roomItem
     * @param $asZelda
     */
    private function addRoomCrumb(cs_room_item $roomItem, $asZelda)
    {
        // NOTE: the "Archived room: " room title prefix may be replaced by the template with a matching icon
        $title = $roomItem->getTitle();
        if ($roomItem->isArchived()) {
            $title = $this->translator->trans('Archived room', [], 'room') . ": " . $title;
        }

        $asZelda &= $roomItem->mayEnter($this->legacyEnvironment->getCurrentUserItem());

        if ($asZelda == true) {
            $this->breadcrumbs->addRouteItem($title, "app_room_home", [
                'roomId' => $roomItem->getItemID(),
            ]);
        } else {
            $this->breadcrumbs->addItem($title);
        }
    }

    /**
     * @param $roomItem
     * @param $routeParameters
     * @param $action
     */
    private function addProfileCrumbs($roomItem, $routeParameters, $action)
    {
        if ($action == 'general' || $action == 'address' || $action == 'contact' || $action == 'deleteroomprofile' || $action == 'notifications') {
            $this->addRoom($roomItem, true);
            $this->breadcrumbs->addRouteItem($this->translator->trans('Room profile', [], 'menu'),
                "app_profile_" . $action, $routeParameters);
        } else {
            $this->breadcrumbs->addRouteItem($this->translator->trans('Account', [], 'menu'), "app_profile_" . $action,
                $routeParameters);
        }

    }

    /**
     * @param $roomItem
     * @param $childRoomClass
     */
    private function addChildRoomListCrumb($roomItem, $childRoomClass)
    {
        if ($childRoomClass == 'project' || $childRoomClass == 'group') {
            $asLink = $roomItem->mayEnter($this->legacyEnvironment->getCurrentUserItem());
            $title = ucfirst($this->translator->trans($childRoomClass, [], 'menu'));
            if ($asLink) {
                $this->breadcrumbs->addRouteItem($title,
                    "app_" . $childRoomClass . "_list", ['roomId' => $roomItem->getItemId()]);
            } else {
                $this->breadcrumbs->addItem($title);
            }

        } else {
            if ($childRoomClass == 'userroom') {
                $this->breadcrumbs->addRouteItem(ucfirst($this->translator->trans($childRoomClass, [], 'menu')),
                    "app_user_list", ['roomId' => $roomItem->getItemId()]);
            }
        }
    }


    /**
     * Return true, if configuration is  week or month.
     * @param $room
     * @param $controller
     * @return bool
     */
    private function isDateCalendar($room, $controller): bool
    {
        if($controller == 'date' and $room->getDatesPresentationStatus() !== 'normal'){
            return true;
        }
        return false;
    }
}
