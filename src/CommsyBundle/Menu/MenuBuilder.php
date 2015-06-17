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
        $this->translator = new Translator('de');
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
                $translation = $this->translator->trans($value.'s');
                $menu->addChild($value, array(
                    'label' => strtoupper($translation),
                    'route' => 'commsy_'.$value.'_list',
                    'routeParameters' => array('roomId' => $roomId),
                    'extras' => array('icon' => $this->getRubricIcon($value))
                ));
            }

            // $menu->addChild('announcement', array(
            //     'label' => 'ANKÜNDIGUNGEN',
            //     'route' => 'commsy_announcement_list',
            //     'routeParameters' => array('roomId' => $roomId),
            //     'extras' => array('icon' => 'uk-icon-home uk-icon-small')
            // ));

            // $menu->addChild('date', array(
            //     'label' => 'TERMINE',
            //     'route' => 'commsy_date_list',
            //     'routeParameters' => array('roomId' => $roomId),
            //     'extras' => array('icon' => 'uk-icon-calendar uk-icon-small')
            // ));

            // $menu->addChild('material', array(
            //     'label' => 'MATERIALIEN',
            //     'route' => 'commsy_material_list',
            //     'routeParameters' => array('roomId' => $roomId),
            //     'extras' => array('icon' => 'uk-icon-file-o uk-icon-small')
            // ));

            // $menu->addChild('discussion', array(
            //     'label' => 'DISKUSSIONEN',
            //     'route' => 'commsy_discussion_list',
            //     'routeParameters' => array('roomId' => $roomId),
            //     'extras' => array('icon' => 'uk-icon-comments-o uk-icon-small')
            // ));

            // $menu->addChild('person', array(
            //     'label' => 'PERSONEN',
            //     'route' => 'commsy_person_list',
            //     'routeParameters' => array('roomId' => $roomId),
            //     'extras' => array('icon' => 'uk-icon-users uk-icon-small')
            // ));

            // $menu->addChild('group', array(
            //     'label' => 'GRUPPEN',
            //     'route' => 'commsy_group_list',
            //     'routeParameters' => array('roomId' => $roomId),
            //     'extras' => array('icon' => 'uk-icon-home uk-icon-small')
            // ));

            // $menu->addChild('todo', array(
            //     'label' => 'AUFGABEN',
            //     'route' => 'commsy_todo_list',
            //     'routeParameters' => array('roomId' => $roomId),
            //     'extras' => array('icon' => 'uk-icon-check uk-icon-small')
            // ));

            // $menu->addChild('topic', array(
            //     'label' => 'THEMEN',
            //     'route' => 'commsy_topic_list',
            //     'routeParameters' => array('roomId' => $roomId),
            //     'extras' => array('icon' => 'uk-icon-home uk-icon-small')
            // ));
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
}