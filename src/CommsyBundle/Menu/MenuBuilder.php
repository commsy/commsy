<?php

namespace CommsyBundle\Menu;

use Knp\Menu\FactoryInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class MenuBuilder 
{
    /**
    * @var Knp\Menu\FactoryInterface $factory
    */
    private $factory;

    /**
    * @param FactoryInterface $factory
    */
    public function __construct(FactoryInterface $factory)
    {
        $this->factory = $factory;
    }

    public function createMainMenu(RequestStack $requestStack)
    {

        $currentStack = $requestStack->getCurrentRequest();
        $roomId = $currentStack->attributes->get('roomId');

        $menu = $this->factory->createItem('root');

        $menu->addChild('dashboard', array(
            'label' => 'DASHBOARD',
            'route' => 'commsy_dashboard_index',
            'extras' => array('icon' => 'uk-icon-home uk-icon-small')
        ));

        // add divider
        $menu->addChild('')->setAttribute('class', 'uk-nav-divider');

        if ($roomId)
        {
            $menu->addChild('room_navigation', array(
                'label' => 'Raum-Navigation',
                'route' => 'commsy_room_home',
                'routeParameters' => array('roomId' => $roomId),
                'extras' => array('icon' => 'uk-icon-home uk-icon-small')
            ));

            // add divider
            $menu->addChild(' ')->setAttribute('class', 'uk-nav-divider');

            $menu->addChild('announcement', array(
                'label' => 'ANKÜNDIGUNGEN',
                'route' => 'commsy_announcement_list',
                'routeParameters' => array('roomId' => $roomId),
                'extras' => array('icon' => 'uk-icon-home uk-icon-small')
            ));

            $menu->addChild('date', array(
                'label' => 'TERMINE',
                'route' => 'commsy_date_list',
                'routeParameters' => array('roomId' => $roomId),
                'extras' => array('icon' => 'uk-icon-calendar uk-icon-small')
            ));

            $menu->addChild('material', array(
                'label' => 'MATERIALIEN',
                'route' => 'commsy_material_list',
                'routeParameters' => array('roomId' => $roomId),
                'extras' => array('icon' => 'uk-icon-file-o uk-icon-small')
            ));

            $menu->addChild('discussion', array(
                'label' => 'DISKUSSIONEN',
                'route' => 'commsy_discussion_list',
                'routeParameters' => array('roomId' => $roomId),
                'extras' => array('icon' => 'uk-icon-comments-o uk-icon-small')
            ));

            $menu->addChild('person', array(
                'label' => 'PERSONEN',
                'route' => 'commsy_person_list',
                'routeParameters' => array('roomId' => $roomId),
                'extras' => array('icon' => 'uk-icon-users uk-icon-small')
            ));

            $menu->addChild('group', array(
                'label' => 'GRUPPEN',
                'route' => 'commsy_group_list',
                'routeParameters' => array('roomId' => $roomId),
                'extras' => array('icon' => 'uk-icon-home uk-icon-small')
            ));

            $menu->addChild('todo', array(
                'label' => 'AUFGABEN',
                'route' => 'commsy_todo_list',
                'routeParameters' => array('roomId' => $roomId),
                'extras' => array('icon' => 'uk-icon-check uk-icon-small')
            ));

            $menu->addChild('topic', array(
                'label' => 'THEMEN',
                'route' => 'commsy_topic_list',
                'routeParameters' => array('roomId' => $roomId),
                'extras' => array('icon' => 'uk-icon-home uk-icon-small')
            ));
        }
        



        // 'routeParameters' => array('id' => $blog->getId())
        // add more children

        return $menu;
    }
}