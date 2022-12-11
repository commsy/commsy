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
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Contracts\Translation\TranslatorInterface;
use WhiteOctober\BreadcrumbsBundle\Model\Breadcrumbs;

class BreadcrumbSubscriber implements EventSubscriberInterface
{
    private \cs_environment $legacyEnvironment;

    public function __construct(
        LegacyEnvironment $legacyEnvironment,
        private RoomService $roomService,
        private ItemService $itemService,
        private TranslatorInterface $translator,
        private Breadcrumbs $breadcrumbs
    ) {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
    }

    public function onControllerEvent(ControllerEvent $event)
    {
        if (HttpKernelInterface::MAIN_REQUEST != $event->getRequestType()) {
            return;
        }
        $request = $event->getRequest();

        $route = explode('_', $request->get('_route'));

        if (count($route) < 3) {
            return;
        }

        [$bundle, $controller, $action] = $route;

        $routeParameters = $request->get('_route_params');

        $roomItem = $this->roomService->getCurrentRoomItem();

        // Longest case:
        // Portal / CommunityRoom / ProjectRooms / ProjectRoomName / Groups / GroupName / Grouproom / Rubric / Entry

        $this->addPortalCrumb($request);

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

            // rubric & entry
            if (array_key_exists('itemId', $routeParameters)) {
                // link to rubric
                $route[2] = 'list';
                unset($routeParameters['itemId']);
                try {
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
                } catch (RouteNotFoundException) {
                    // we don't need breadcrumbs for routes like app_item_editdetails etc. to ajax controller actions
                }

                // entry title
                $item = $this->itemService->getTypedItem($request->get('itemId'));
                if ($item) {
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

    private function addPortalCrumb($request)
    {
        $portal = $this->legacyEnvironment->getCurrentPortalItem();
        if ($portal) {
            $this->breadcrumbs->addRouteItem($portal->getTitle(), 'app_helper_portalenter',
                ['context' => $portal->getItemId()]);
        }
    }

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

    private function addDashboard($roomItem, $asLink)
    {
        $this->breadcrumbs->addRouteItem($this->translator->trans('dashboard', [], 'menu'), 'app_dashboard_overview',
            ['roomId' => $roomItem->getItemId()]);
    }

    private function addCommunityRoom($roomItem, $asLink)
    {
        $this->addRoomCrumb($roomItem, $asLink);
    }

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
                $this->breadcrumbs->addRouteItem($groupItem->getTitle(), 'app_group_detail',
                    ['roomId' => $projectRoom->getItemId(), 'itemId' => $groupItem->getItemId()]);
                // Grouproom
                $this->addRoomCrumb($roomItem, $asLink);
            }
        }
    }

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

    private function addRoomCrumb(\cs_room_item $roomItem, $asZelda)
    {
        // NOTE: the "Archived room: " room title prefix may be replaced by the template with a matching icon
        $title = $roomItem->getTitle();
        if ($roomItem->getArchived()) {
            $title = $this->translator->trans('Archived room', [], 'room').': '.$title;
        }

        $asZelda &= $roomItem->mayEnter($this->legacyEnvironment->getCurrentUserItem());

        if (true == $asZelda) {
            $this->breadcrumbs->addRouteItem($title, 'app_room_home', [
                'roomId' => $roomItem->getItemID(),
            ]);
        } else {
            $this->breadcrumbs->addItem($title);
        }
    }

    private function addProfileCrumbs($roomItem, $routeParameters, $action)
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

    private function addChildRoomListCrumb($roomItem, $childRoomClass)
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

    /**
     * Return true, if configuration is  week or month.
     */
    private function isDateCalendar($room, $controller): bool
    {
        if ('date' == $controller and 'normal' !== $room->getDatesPresentationStatus()) {
            return true;
        }

        return false;
    }
}
