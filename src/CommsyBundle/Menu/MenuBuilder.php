<?php

namespace CommsyBundle\Menu;

use Knp\Menu\FactoryInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Commsy\LegacyBundle\Utils\RoomService;
use Symfony\Component\Translation\Translator;


class MenuBuilder 
{
    /**
    * @var Knp\Menu\FactoryInterface $factory
    */
    private $factory;

    private $roomService;

    /**
    * @param FactoryInterface $factory
    */
    public function __construct(FactoryInterface $factory, RoomService $roomService)
    {
        $this->factory = $factory;
        $this->roomService = $roomService;
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

            // loop every rubric to build the menu
            foreach($rubrics as $value) {
                $menu->addChild($value, array(
                    'label' => $value,
                    'route' => 'commsy_'.$value.'_list',
                    'routeParameters' => array('roomId' => $roomId),
                    'extras' => array('icon' => $this->getRubricIcon($value))
                ));
            }
        }
        
        // 'routeParameters' => array('id' => $blog->getId())
        // add more children

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
                $class = "uk-icon-home uk-icon-small";
                break;
            case 'date':
                $class = "uk-icon-calendar uk-icon-small";
                break;
            case 'material':
                $class = "uk-icon-file-o uk-icon-small";
                break;
            case 'discussion':
                $class = "uk-icon-comments-o uk-icon-small";
                break;
            case 'user':
                $class = "uk-icon-users uk-icon-small";
                break;
            case 'group':
                $class = "uk-icon-home uk-icon-small";
                break;
            case 'todo':
                $class = "uk-icon-home uk-icon-small";
                break;
            case 'topic':
                $class = "uk-icon-check uk-icon-small";
                break;
            
            default:
                $class = "uk-icon-home uk-icon-small";
                break;
        }
    return $class;
    }

    
    public function createBreadcrumbMenu(RequestStack $requestStack)
    {
        // get room id
        $currentStack = $requestStack->getCurrentRequest();
        $roomId = $currentStack->attributes->get('roomId');

        // create breadcrumb menu
        $menu = $this->factory->createItem('root');

        // this item will always be displayed
        $menu->addChild('Portal', array('route' => ''));
        $menu->addChild('Raum', array(
            'route' => 'commsy_room_home', 
            'routeParameters' => array('roomId' => $roomId)
        ));

        return $menu;

        // return $this->getCurrentMenuItem($menu);
    }

}