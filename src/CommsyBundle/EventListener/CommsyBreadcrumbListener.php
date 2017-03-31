<?php

namespace CommsyBundle\EventListener;

use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Bundle\FrameworkBundle\Routing\Router;

use WhiteOctober\BreadcrumbsBundle\Model\Breadcrumbs;

use Commsy\LegacyBundle\Services\LegacyEnvironment;
use Commsy\LegacyBundle\Utils\RoomService;
use Commsy\LegacyBundle\Utils\ItemService;

class CommsyBreadcrumbListener
{
    private $legacyEnvironment;
    private $roomService;
    private $itemService;
    private $translator;
    private $breadcrumbs;
    private $router;

    public function __construct(LegacyEnvironment $legacyEnvironment, RoomService $roomService, ItemService $itemService, TranslatorInterface $translator, Router $router, Breadcrumbs $whiteOctoberBreadcrumbs)
    {
        $this->legacyEnvironment = $legacyEnvironment;
        $this->roomService = $roomService;
        $this->itemService = $itemService;
        $this->breadcrumbs = $whiteOctoberBreadcrumbs;
        $this->translator = $translator;
        $this->router = $router;
    }

    public function onKernelRequest(GetResponseEvent $event)
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

        if ($roomItem == null) {
            return;
        }

        if($controller == 'profile'){
            $this->addProfileCrumbs($roomItem, $action);
        }
        elseif ($controller == 'room' && $action == 'home') {
            $this->addRoom($roomItem, false);
        }
        else {
            $this->addRoom($roomItem, true);
            // rubric & entry
            if(array_key_exists('itemId', $routeParameters)) {

                // link to rubric
                $route[2] = 'list';
                unset($routeParameters['itemId']);
                $this->breadcrumbs->addRouteItem($this->translator->trans($controller, [], 'menu'), implode("_", $route), $routeParameters);

                // entry title
                $item = $this->itemService->getTypedItem($request->get('itemId'));
                $this->breadcrumbs->addItem($item->getTitle());
            }
            else {
                $this->breadcrumbs->addItem($this->translator->trans($controller, [], 'menu'));
            }
        }
    }

    private function addPortalCrumb($request)
    {
        $portal = $this->legacyEnvironment->getEnvironment()->getCurrentPortalItem();
        if ($portal) {
            $this->breadcrumbs->prependItem($portal->getTitle(), $request->getSchemeAndHttpHost() . '?cid=' . $portal->getItemId());
        }
    }

    private function addRoom($roomItem, $asLink)
    {
        if ($roomItem->isGroupRoom()) {
            $this->addGroupRoom($roomItem, $asLink);
       }
        elseif ($roomItem->isProjectRoom()) {
            $this->addProjectRoom($roomItem, $asLink);
        }
        elseif ($roomItem->isCommunityRoom()) {
            $this->addCommunityRoom($roomItem, $asLink);
        }
    }

    private function addCommunityRoom($roomItem, $asLink)
    {
        $this->addRoomCrumb($roomItem, $asLink);
    }

    private function addProjectRoom($roomItem, $asLink)
    {
        $communityRoomItem = $roomItem->getCommunityList()->getFirst();
        if ($communityRoomItem) {
            $this->addCommunityRoom($communityRoomItem, true);
            $this->breadcrumbs->addRouteItem($this->translator->trans('project', [], 'menu'), "commsy_project_list", array('roomId' => $communityRoomItem->getItemId()));
        }
        else {
            dump("No community room found for project room with ID " . $roomItem->getItemId());
        }
        $this->addRoomCrumb($roomItem, $asLink);
    }

    private function addGroupRoom($roomItem, $asLink)
    {
        $groupItem = $roomItem->getLinkedGroupItem();
        $projectRoom = $roomItem->getLinkedProjectItem();

        // ProjectRoom
        $this->addProjectRoom($projectRoom, true);
        // "Groups" rubric in project room
        $this->breadcrumbs->addItem($this->translator->trans('groups', [], 'menu'));
        // Group (with name)
        $this->breadcrumbs->addRouteItem($groupItem->getTitle(), "commsy_group_detail", ['roomId' => $projectRoom->getItemId(), 'itemId' => $groupItem->getItemId()]);
        // Grouproom
        $this->addRoomCrumb($roomItem, $asLink);
    }

    private function addRoomCrumb($roomItem, $asZelda)
    {
        $crumbText = $roomItem->isGroupRoom() ? $this->translator->trans('grouproom', [], 'group') : $roomItem->getTitle();
        if ($asZelda == true) {
            $this->breadcrumbs->addRouteItem($crumbText, "commsy_room_home", [
                'roomId' => $roomItem->getItemID(),
            ]);
        }
        else {
            $this->breadcrumbs->addItem($crumbText);
        }
    }

    private function addProfileCrumbs($roomItem, $action)
    {
        if($action == 'account') {

        }
        elseif ($action == 'general') {

        }
    }
}
