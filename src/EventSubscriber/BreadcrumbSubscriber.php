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

use App\Services\LegacyEnvironment;
use App\Utils\ItemService;
use App\Utils\RoomService;
use cs_community_item;
use cs_environment;
use cs_grouproom_item;
use cs_privateroom_item;
use cs_project_item;
use cs_room_item;
use cs_userroom_item;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Contracts\Translation\TranslatorInterface;
use WhiteOctober\BreadcrumbsBundle\Model\Breadcrumbs;

class BreadcrumbSubscriber implements EventSubscriberInterface
{
    private cs_environment $legacyEnvironment;

    public function __construct(
        LegacyEnvironment $legacyEnvironment,
        private RoomService $roomService,
        private ItemService $itemService,
        private TranslatorInterface $translator,
        private Breadcrumbs $breadcrumbs
    ) {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
    }

    /**
     * @return string[]
     */
    public static function getSubscribedEvents(): array
    {
        return [
            ControllerEvent::class => 'onControllerEvent',
        ];
    }

    public function onControllerEvent(ControllerEvent $event): void
    {
        if (HttpKernelInterface::MAIN_REQUEST != $event->getRequestType()) {
            return;
        }
        $request = $event->getRequest();

        $route = explode('_', $request->get('_route'));

        if (count($route) < 3) {
            return;
        }

        [, $controller, $action] = $route;

        $routeParameters = $request->get('_route_params');

        $roomItem = $this->roomService->getCurrentRoomItem();

        // Longest case:
        // Portal / CommunityRoom / ProjectRooms / ProjectRoomName / Groups / GroupName / Grouproom / Rubric / Entry

        $this->addPortalCrumb();

        $portal = $this->legacyEnvironment->getCurrentPortalItem();
        if ($portal && $request->get('_route') === 'app_room_listall') {
            $privateRoomManager = $this->legacyEnvironment->getPrivateRoomManager();
            $privateRoom = $privateRoomManager->getRelatedOwnRoomForUser(
                $this->legacyEnvironment->getCurrentUser(),
                $portal->getId()
            );

            if ($privateRoom) {
                $this->addDashboard($privateRoom);
            }

            $this->addRubricAndEntry($request, $controller, $action);
            return;
        }

        if (null == $roomItem) {
            return;
        }

        if ('profile' == $controller) {
            $this->addProfileCrumbs($roomItem, $routeParameters, $action);
        } elseif ('room' == $controller && 'home' == $action) {
            $this->addRoom($roomItem, false);
        } elseif ('dashboard' == $controller && 'overview' == $action) {
            $this->breadcrumbs->addItem($this->translator->trans($controller, [], 'menu'));
        } elseif ('category' == $controller) {
            $this->addRoom($roomItem, true);
            $this->breadcrumbs->addItem($this->translator->trans('Categories', [], 'category'));
        } elseif ('hashtag' == $controller) {
            $this->addRoom($roomItem, true);
            $this->breadcrumbs->addItem($this->translator->trans('hashtags', [], 'room'));
        } elseif ('cancellablelockanddelete' == $controller && 'deleteorlock' == $action) {
            $itemId = $routeParameters['itemId'] ?? null;
            if ($itemId) {
                $room = $this->roomService->getRoomItem(intval($itemId));
                $this->addRoom($room, true);
            }
        } else {
            $this->addRoom($roomItem, true);

            $this->addRubricAndEntry($request, $controller, $action, $roomItem);
        }
    }

    private function addPortalCrumb(): void
    {
        $portal = $this->legacyEnvironment->getCurrentPortalItem();
        if ($portal) {
            $this->breadcrumbs->addRouteItem($portal->getTitle(), 'app_helper_portalenter',
                ['context' => $portal->getItemId()]);
        }
    }

    private function addRoom(cs_room_item $roomItem, bool $asLink): void
    {
        if ($roomItem->isGroupRoom()) {
            /** @var cs_grouproom_item $roomItem */
            $this->addGroupRoom($roomItem, $asLink);
        } elseif ($roomItem->isUserroom()) {
            /** @var cs_userroom_item $roomItem */
            $this->addUserRoom($roomItem, $asLink);
        } elseif ($roomItem->isProjectRoom()) {
            /** @var cs_project_item $roomItem */
            $this->addProjectRoom($roomItem, $asLink);
        } elseif ($roomItem->isCommunityRoom()) {
            /** @var cs_community_item $roomItem */
            $this->addCommunityRoom($roomItem, $asLink);
        } elseif ($roomItem->isPrivateRoom()) {
            /** @var cs_privateroom_item $roomItem */
            $this->addDashboard($roomItem);
        }
    }

    private function addDashboard(cs_privateroom_item $roomItem): void
    {
        $this->breadcrumbs->addRouteItem($this->translator->trans('dashboard', [], 'menu'), 'app_dashboard_overview',
            ['roomId' => $roomItem->getItemId()]);
    }

    private function addCommunityRoom(cs_community_item $roomItem, bool $asLink): void
    {
        $this->addRoomCrumb($roomItem, $asLink);
    }

    private function addProjectRoom(cs_project_item $roomItem, bool $asLink): void
    {
        // TODO: when called from addUserRoom(), $communityRoomItem is empty (even if the project room belongs to a community room)
        $communityRoomItem = $roomItem->getCommunityList()->getFirst();
        if ($communityRoomItem) {
            $this->addCommunityRoom($communityRoomItem, true);
            $this->addChildRoomListCrumb($communityRoomItem, 'project');
        }
        $this->addRoomCrumb($roomItem, $asLink);
    }

    private function addGroupRoom(cs_grouproom_item $roomItem, bool $asLink): void
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
                $this->breadcrumbs->addRouteItem($groupItem->getTitle(), 'app_group_detail',
                    ['roomId' => $projectRoom->getItemId(), 'itemId' => $groupItem->getItemId()]);
                // Grouproom
                $this->addRoomCrumb($roomItem, $asLink);
            }
        }
    }

    private function addUserRoom(cs_userroom_item $roomItem, bool $asLink): void
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

    private function addRoomCrumb(cs_room_item $roomItem, bool $asZelda): void
    {
        // NOTE: the "Archived room: " room title prefix may be replaced by the template with a matching icon
        $title = $roomItem->getTitle();
        if ($roomItem->getArchived()) {
            $title = $this->translator->trans('Archived room', [], 'room').': '.$title;
        }

        $asZelda &= $roomItem->mayEnter($this->legacyEnvironment->getCurrentUserItem());

        if ($asZelda) {
            $this->breadcrumbs->addRouteItem($title, 'app_room_home', [
                'roomId' => $roomItem->getItemID(),
            ]);
        } else {
            $this->breadcrumbs->addItem($title);
        }
    }

    private function addProfileCrumbs(cs_room_item $roomItem, array $routeParameters, string $action): void
    {
        if ('general' == $action || 'address' == $action || 'contact' == $action || 'deleteroomprofile' == $action || 'notifications' == $action) {
            $this->addRoom($roomItem, true);
            $this->breadcrumbs->addRouteItem($this->translator->trans('Room profile', [], 'menu'),
                'app_profile_'.$action, $routeParameters);
        } else {
            $this->breadcrumbs->addRouteItem($this->translator->trans('Account', [], 'menu'), 'app_profile_'.$action,
                $routeParameters);
        }
    }

    private function addChildRoomListCrumb(cs_room_item $roomItem, string $childRoomClass): void
    {
        if ('project' == $childRoomClass || 'group' == $childRoomClass) {
            $asLink = $roomItem->mayEnter($this->legacyEnvironment->getCurrentUserItem());
            $title = ucfirst($this->translator->trans($childRoomClass, [], 'menu'));
            if ($asLink) {
                $this->breadcrumbs->addRouteItem($title,
                    'app_'.$childRoomClass.'_list', ['roomId' => $roomItem->getItemId()]);
            } else {
                $this->breadcrumbs->addItem($title);
            }
        } else {
            if ('userroom' == $childRoomClass) {
                $this->breadcrumbs->addRouteItem(ucfirst($this->translator->trans($childRoomClass, [], 'menu')),
                    'app_user_list', ['roomId' => $roomItem->getItemId()]);
            }
        }
    }

    private function addRubricAndEntry(
        Request $request,
        string $controller,
        string $action,
        ?cs_room_item $roomItem = null
    ): void
    {
        // rubric & entry
        $route = explode('_', (string) $request->get('_route'));
        $routeParameters = $request->get('_route_params');
        if (array_key_exists('itemId', $routeParameters)) {
            // link to rubric
            $route[2] = 'list';
            unset($routeParameters['itemId']);
            try {
                if ($roomItem) {
                    if ('context' == $controller && 'request' == $action) {
                        if ($roomItem->isCommunityRoom()) {
                            $this->addChildRoomListCrumb($roomItem, 'project');
                        } elseif ($roomItem->isProjectRoom()) {
                            $this->addChildRoomListCrumb($roomItem, 'group');
                        }
                    } else {
                        $routerImplode = implode('_', $route);
                        if ($this->isDateCalendar($roomItem, $controller)) {
                            $routerImplode = 'app_date_calendar';
                        }
                        $this->breadcrumbs->addRouteItem($this->translator->trans($controller, [], 'menu'), $routerImplode, $routeParameters);
                    }
                }
            } catch (RouteNotFoundException) {
                // we don't need breadcrumbs for routes like app_item_editdetails etc. to ajax controller actions
            }

            // entry title
            $item = $this->itemService->getTypedItem($request->get('itemId'));
            if ($item) {
                /** @noinspection PhpPossiblePolymorphicInvocationInspection */
                $this->breadcrumbs->addItem('user' == $item->getItemType() ? $item->getFullName() : $item->getTitle());
            }
        } // rubric only
        else {
            if ('room' == $controller && 'listall' == $action) {
                $this->breadcrumbs->addItem($this->translator->trans('All rooms', [], 'room'));
            } else {
                $this->breadcrumbs->addItem($this->translator->trans($controller, [], 'menu'));
            }
        }
    }

    /**
     * Return true, if configuration is week or month.
     */
    private function isDateCalendar(cs_room_item $room, string $controller): bool
    {
        if ('date' == $controller and 'normal' !== $room->getDatesPresentationStatus()) {
            return true;
        }

        return false;
    }
}
