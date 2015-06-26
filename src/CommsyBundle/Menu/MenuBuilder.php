<?php

namespace CommsyBundle\Menu;

use Knp\Menu\FactoryInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Commsy\LegacyBundle\Utils\RoomService;
use Symfony\Component\Translation\Translator;
use Commsy\LegacyBundle\Services\LegacyEnvironment;

class MenuBuilder
{
    /**
    * @var Knp\Menu\FactoryInterface $factory
    */
    private $factory;

    private $roomService;

    private $legacyEnvironment;

    /**
    * @param FactoryInterface $factory
    */
    public function __construct(FactoryInterface $factory, RoomService $roomService, LegacyEnvironment $legacyEnvironment)
    {
        $this->factory = $factory;
        $this->roomService = $roomService;
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
    }

    public function createMainMenu(RequestStack $requestStack)
    {
        // get room id
        $currentStack = $requestStack->getCurrentRequest();
        $roomId = $currentStack->attributes->get('roomId');

        // rubric room information
        $rubrics = $this->roomService->getRubricInformation($roomId);

        // create root item for knpmenu
        $menu = $this->factory->createItem('root');

        // dashboard
        $menu->addChild('dashboard', array(
            'label' => 'DASHBOARD',
            'route' => 'commsy_dashboard_index',
            'extras' => array('icon' => 'uk-icon-home uk-icon-small')
        ));

        // add divider
        $menu->addChild('')->setAttribute('class', 'uk-nav-divider');

        if ($roomId)
        {
            // room navigation
            $menu->addChild('room_navigation', array(
                'label' => 'Raum-Navigation',
                'route' => 'commsy_room_home',
                'routeParameters' => array('roomId' => $roomId),
                'extras' => array('icon' => 'uk-icon-home uk-icon-small')
            ));

            // add divider
            $menu->addChild(' ')->setAttribute('class', 'uk-nav-divider');

            // loop through rubrics to build the menu
            foreach ($rubrics as $value) {
                $menu->addChild($value, array(
                    'label' => $value,
                    'route' => 'commsy_'.$value.'_list',
                    'routeParameters' => array('roomId' => $roomId),
                    'extras' => array('icon' => $this->getRubricIcon($value))
                ));
            }
        }

        return $menu;
    }

    /**
     * returns the uikit icon classname for a specific rubric
     * @param  string $rubric rubric name
     * @return string         uikit icon class
     */
    public function getRubricIcon($rubric)
    {
        // return uikit icon class for rubric
        switch ($rubric) {
            case 'announcement':
                $class = "uk-icon-justify uk-icon-home uk-icon-small";
                break;
            case 'date':
                $class = "uk-icon-justify uk-icon-calendar uk-icon-small";
                break;
            case 'material':
                $class = "uk-icon-justify uk-icon-file-o uk-icon-small";
                break;
            case 'discussion':
                $class = "uk-icon-justify uk-icon-comments-o uk-icon-small";
                break;
            case 'user':
                $class = "uk-icon-justify uk-icon-users uk-icon-small";
                break;
            case 'group':
                $class = "uk-icon-justify uk-icon-home uk-icon-small";
                break;
            case 'todo':
                $class = "uk-icon-justify uk-icon-home uk-icon-small";
                break;
            case 'topic':
                $class = "uk-icon-justify uk-icon-check uk-icon-small";
                break;
            
            default:
                $class = "uk-icon-justify uk-icon-home uk-icon-small";
                break;
        }
        return $class;
    }

    /**
     * creates the breadcrumb
     * @param  RequestStack $requestStack [description]
     * @return menuItem                   [description]
     */
    public function createBreadcrumbMenu(RequestStack $requestStack)
    {
        // get room id
        $currentStack = $requestStack->getCurrentRequest();

        $roomId = $currentStack->attributes->get('roomId');
        $itemId = $currentStack->attributes->get('itemId');
        $roomItem = $this->roomService->getRoomItem($roomId);

        // get route information
        $route = explode('_', $currentStack->attributes->get('_route'));

        // create breadcrumb menu
        $menu = $this->factory->createItem('root');

        // this item will always be displayed
        $menu->addChild('DASHBOARD', array('route' => 'commsy_dashboard_index'));

        // room
        $menu->addChild($roomItem->getTitle(), array(
            'route' => 'commsy_room_home',
            'routeParameters' => array('roomId' => $roomId)
        ));

        if ($route[1] && $route[1] != "room") {
            // rubric
            $menu->addChild($route[1], array(
                'route' => 'commsy_'.$route[1].'_'.'list',
                'routeParameters' => array('roomId' => $roomId)
            ));

            if ($route[2] != "list" && $route[2] != "search") {
                // item
                $itemService = $this->legacyEnvironment->getItemManager();
                $item = $itemService->getItem($itemId);
                $tempManager = $this->legacyEnvironment->getManager($item->getItemType());
                $tempItem = $tempManager->getItem($itemId);
                $itemText = '';
                if ($tempItem->getItemType() == 'user') {
                    $itemText = $tempItem->getFullname();
                } else {
                    $itemText = $tempItem->getTitle();
                }
                $menu->addChild($itemText, array(
                    'route' => 'commsy_'.$route[1].'_'.$route[2],
                    'routeParameters' => array(
                        'roomId' => $roomId,
                        'itemId' => $itemId
                    )
                ));
            }
        }
        

        return $menu;
    }

}