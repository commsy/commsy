<?php

namespace CommsyBundle\Menu;

use Knp\Menu\FactoryInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Commsy\LegacyBundle\Utils\RoomService;
use Symfony\Component\Translation\Translator;
use Commsy\LegacyBundle\Services\LegacyEnvironment;
use Commsy\LegacyBundle\Utils\UserService;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;

class MenuBuilder
{
    /**
    * @var Knp\Menu\FactoryInterface $factory
    */
    private $factory;

    private $roomService;

    private $legacyEnvironment;
    
    private $userService;

    private $authorizationChecker;

    /**
    * @param FactoryInterface $factory
    */
    public function __construct(FactoryInterface $factory, RoomService $roomService, LegacyEnvironment $legacyEnvironment, UserService $userService, AuthorizationChecker $authorizationChecker )
    {
        $this->factory = $factory;
        $this->roomService = $roomService;
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
        $this->userService = $userService;
        $this->authorizationChecker = $authorizationChecker;
    }

    /**
     * creates the profile sidebar
     * @param  RequestStack $requestStack [description]
     * @return knpMenu                    KnpMenu
     */
    public function createProfileMenu(RequestStack $requestStack)
    {
        // create profile
        $currentStack = $requestStack->getCurrentRequest();
        $currentUser = $this->legacyEnvironment->getCurrentUser();

        $menu = $this->factory->createItem('root');

        if ($currentUser->getItemId() != '') {

            $menu->addChild('profileImage', array(
                'label' => $currentUser->getFullname(),
                // 'route' => 'commsy_user_detail',
                'route' => 'commsy_profile_room',
                'routeParameters' => array('roomId' => $currentStack->attributes->get('roomId'), 'itemId' => $currentUser->getItemId()),
                'extras' => array(
                    'user' => $currentUser
                )
            ));
        }

        // profile configuration
        // if ($currentUser->getItemId() != '') {
        //     $menu->addChild('profileConfig', array(
        //         'label' => ' ',

        return $menu;
    }

    /**
     * creates the roomlist sidebar
     * @param  RequestStack $requestStack [description]
     * @return knpMenu                    KnpMenu
     */
    public function createRoomlistMenu(RequestStack $requestStack)
    {
        // create roomlist
        $currentStack = $requestStack->getCurrentRequest();
        $currentUser = $this->legacyEnvironment->getCurrentUser();

        $menu = $this->factory->createItem('root');

        $roomId = $currentStack->attributes->get('roomId');

        if ($roomId) {
               // room navigation
                $menu->addChild('room_navigation', array(
                    'label' => 'Raum-Navigation',
                    'route' => 'commsy_room_home',
                    'routeParameters' => array('roomId' => $roomId),
                    'extras' => array(
                        'icon' => 'uk-icon-list uk-icon-small',
                        'showList' => true,
                        'user' => $currentUser,
                        'roomId' => $roomId
                        )
                ));
                $menu['room_navigation']->setLinkAttribute('id', 'rooms');
        }
        return $menu;
    }



    public function createSettingsMenu(RequestStack $requestStack)
    {
        // get room Id
        $currentStack = $requestStack->getCurrentRequest();
        $roomId = $currentStack->attributes->get('roomId');

        // create root item
        $menu = $this->factory->createItem('root');

        if ($roomId) {
            // general settings
            $menu->addChild('general', array(
                'label' => 'General',
                'route' => 'commsy_settings_general',
                'routeParameters' => array('roomId' => $roomId),
                'extras' => array('icon' => 'uk-icon-server uk-icon-small'),
            ));

            // moderation
            $menu->addChild('moderation', array(
                'label' => 'Moderation',
                'route' => 'commsy_settings_moderation',
                'routeParameters' => array('roomId' => $roomId),
                'extras' => array('icon' => 'uk-icon-sitemap uk-icon-small'),
            ));            

            // appearance
            $menu->addChild('appearance', array(
                'label' => 'appearance',
                'route' => 'commsy_settings_appearance',
                'routeParameters' => array('roomId' => $roomId),
                'extras' => array('icon' => 'uk-icon-eyedropper uk-icon-small'),
            ));
            
            // extensions
            $menu->addChild('extensions', array(
                'label' => 'extensions',
                'route' => 'commsy_settings_extensions',
                'routeParameters' => array('roomId' => $roomId),
                'extras' => array('icon' => 'uk-icon-gears uk-icon-small'),
            ));
        }
        
        // identifier
        
        // additional
        
        // extensions
        
        // plugins

        return $menu;
    }

    /**
     * creates rubric menu
     * @param  RequestStack $requestStack [description]
     * @return KnpMenu                    KnpMenu
     */
    public function createMainMenu(RequestStack $requestStack)
    {
        // get room id
        $currentRequest = $requestStack->getCurrentRequest();

        // create root item for knpmenu
        $menu = $this->factory->createItem('root');

        $roomId = $currentRequest->attributes->get('roomId');

        if ($roomId) {
            // dashboard
            $user = $this->userService->getPortalUserFromSessionId();
            $authSourceManager = $this->legacyEnvironment->getAuthSourceManager();
            $authSource = $authSourceManager->getItem($user->getAuthSource());
            $this->legacyEnvironment->setCurrentPortalID($authSource->getContextId());
            $privateRoomManager = $this->legacyEnvironment->getPrivateRoomManager();
            $privateRoom = $privateRoomManager->getRelatedOwnRoomForUser($user, $this->legacyEnvironment->getCurrentPortalID());
            // $current_user = $this->userService->getUser($user->getUserID());
            $current_user = $this->legacyEnvironment->getCurrentUserItem();

            
/*            $menu->addChild('dashboard', array(
                'label' => 'DASHBOARD',
                'route' => 'commsy_dashboard_index',
                'routeParameters' => array('roomId' => $privateRoom->getItemId()),
                'extras' => array('icon' => 'uk-icon-dashboard uk-icon-small')
            ));*/

            if ($roomId != $privateRoom->getItemId()) {
                // rubric room information
                $rubrics = $this->roomService->getRubricInformation($roomId);
                

                // home navigation
                $menu->addChild('room_home', array(
                    'label' => 'Home',
                    'route' => 'commsy_room_home',
                    'routeParameters' => array('roomId' => $roomId),
                    'extras' => array('icon' => 'uk-icon-home uk-icon-small')
                ));
    
                // loop through rubrics to build the menu
                foreach ($rubrics as $value) {
                    $route = 'commsy_'.$value.'_list';
                    if ($value == 'date') {
                        $room = $this->roomService->getRoomItem($roomId);
                        if ($room->getDatesPresentationStatus() != 'normal') {
                            $route = 'commsy_date_calendar';
                        }
                    }
                    
                    $menu->addChild($value, array(
                        'label' => $value,
                        'route' => $route,
                        'routeParameters' => array('roomId' => $roomId),
                        'extras' => array('icon' => $this->getRubricIcon($value))
                    ));
                }
            } else {
                $menu->addChild('')->setAttribute('class', 'uk-nav-divider');
                
                $projectArray = array();
                $projectList = $user->getRelatedProjectList();
                $project = $projectList->getFirst();
                while ($project) {
                    $menu->addChild($project->getTitle(), array(
                        'label' => $project->getTitle(),
                        'route' => 'commsy_room_home',
                        'routeParameters' => array('roomId' => $project->getItemId()),
                        'extras' => array('icon' => 'uk-icon-home uk-icon-small')
                    ));
                    $project = $projectList->getNext();
                }
            }

            if ($current_user) {
                if ($this->authorizationChecker->isGranted('MODERATOR')) {
                    $menu->addChild('room_navigation_space_2', array(
                        'label' => ' ',
                        'route' => 'commsy_room_home',
                        'routeParameters' => array('roomId' => $roomId),
                        'extras' => array('icon' => 'uk-icon-small')
                    ));
                    $menu->addChild('room_configuration', array(
                        'label' => 'settings',
                        'route' => 'commsy_settings_general',
                        'routeParameters' => array('roomId' => $roomId),
                        'extras' => array('icon' => 'uk-icon-wrench uk-icon-small')
                    ));
                }
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
                $class = "uk-icon-justify uk-icon-comment-o uk-icon-small";
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
                $class = "uk-icon-justify uk-icon-user uk-icon-small";
                break;
            case 'group':
                $class = "uk-icon-justify uk-icon-group uk-icon-small";
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

        // create breadcrumb menu
        $menu = $this->factory->createItem('root');

        $roomId = $currentStack->attributes->get('roomId');
        if ($roomId) {
            // this item will always be displayed
            $user = $this->userService->getPortalUserFromSessionId();
            $authSourceManager = $this->legacyEnvironment->getAuthSourceManager();
            $authSource = $authSourceManager->getItem($user->getAuthSource());
            $this->legacyEnvironment->setCurrentPortalID($authSource->getContextId());
            $privateRoomManager = $this->legacyEnvironment->getPrivateRoomManager();
            $privateRoom = $privateRoomManager->getRelatedOwnRoomForUser($user,$this->legacyEnvironment->getCurrentPortalID());
            
            $menu->addChild('dashboard', array(
                'route' => 'commsy_dashboard_overview',
                'routeParameters' => array('roomId' => $privateRoom->getItemId()),
            ));

            $itemId = $currentStack->attributes->get('itemId');
            $roomItem = $this->roomService->getRoomItem($roomId);
    
            if ($roomItem) {
                // get route information
                $route = explode('_', $currentStack->attributes->get('_route'));
                
                // room
                $menu->addChild($roomItem->getTitle(), array(
                    'route' => 'commsy_room_home',
                    'routeParameters' => array('roomId' => $roomId)
                ));
        
                if ($route[1] && $route[1] != "room" && $route[1] != "dashboard" && $route[2] != "search") {
                    // rubric
                    $menu->addChild($route[1], array(
                        'route' => 'commsy_'.$route[1].'_'.'list',
                        'routeParameters' => array('roomId' => $roomId)
                    ));
        
                    if ($route[2] != "list") {
                        // item
                        if ($itemId) {
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
                        } else {
                            $menu->addChild('create', array());
                        }
                    }
                }
            }
        }
        

        return $menu;
    }

}
