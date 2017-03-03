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

    public function createAccountMenu(RequestStack $requestStack)
    {
       // create profile
        $currentStack = $requestStack->getCurrentRequest();
        $currentUser = $this->legacyEnvironment->getCurrentUser();

        $menu = $this->factory->createItem('root');

        if ($currentUser->getItemId() != '') {

            $menu->addChild('account', [
                //'label' => $currentUser->getFullname(),
                'label' => 'profile',
                'route' => 'commsy_profile_account',
                'routeParameters' => [
                    'roomId' => $currentStack->attributes->get('roomId'),
                    'itemId' => $currentUser->getItemId(),
                ],
                'extras' => [
                    'icon' => 'uk-icon-user-secret uk-icon-small uk-icon-justify',
                    'user' => $currentUser,
                ]
            ])
            ->setExtra('translation_domain', 'menu');

            $menu->addChild('notifications', [
                'label' => 'notifications',
                'route' => 'commsy_profile_notifications',
                'routeParameters' => [
                    'roomId' => $currentStack->attributes->get('roomId'),
                    'itemId' => $currentUser->getItemId(),
                ],
                'extras' => [
                    'icon' => 'uk-icon-envelope uk-icon-small uk-icon-justify',
                    'user' => $currentUser,
                ]
            ])
            ->setExtra('translation_domain', 'menu');

            $menu->addChild('additional', [
                'label' => 'additional_functions',
                'route' => 'commsy_profile_additional',
                'routeParameters' => [
                    'roomId' => $currentStack->attributes->get('roomId'),
                    'itemId' => $currentUser->getItemId(),
                ],
                'extras' => [
                    'icon' => 'uk-icon-plus-square uk-icon-small uk-icon-justify',
                    'user' => $currentUser,
                ]
            ])
            ->setExtra('translation_domain', 'menu');
        }

        return $menu;
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
        $roomId = $currentStack->attributes->get('roomId');

        $menu = $this->factory->createItem('root');

        if ($currentUser->getItemId() != '') {

            $menu->addChild('personal', [
                'label' => 'personal_data',
                'route' => 'commsy_profile_general',
                'routeParameters' => [
                    'roomId' => $currentStack->attributes->get('roomId'),
                    'itemId' => $currentUser->getItemId(),
                ],
                'extras' => [
                    'icon' => 'uk-icon-user uk-icon-small uk-icon-justify',
                    'user' => $currentUser,
                ]
            ])
            ->setExtra('translation_domain', 'menu');


            $menu->addChild('address', [
                'label' => 'address',
                'route' => 'commsy_profile_address',
                'routeParameters' => [
                    'roomId' => $currentStack->attributes->get('roomId'),
                    'itemId' => $currentUser->getItemId(),
                ],
                'extras' => [
                    'icon' => 'uk-icon-map-o uk-icon-small uk-icon-justify',
                    'user' => $currentUser,
                ]
            ])
            ->setExtra('translation_domain', 'menu');


            $menu->addChild('contact', [
                'label' => 'contact',
                'route' => 'commsy_profile_contact',
                'routeParameters' => [
                    'roomId' => $currentStack->attributes->get('roomId'),
                    'itemId' => $currentUser->getItemId(),
                ],
                'extras' => [
                    'icon' => 'uk-icon-at uk-icon-small uk-icon-justify',
                    'user' => $currentUser,
                ]
            ])
            ->setExtra('translation_domain', 'menu');
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
            $menu->addChild('General', array(
                'label' => 'General',
                'route' => 'commsy_settings_general',
                'routeParameters' => array('roomId' => $roomId),
                'extras' => array('icon' => 'uk-icon-server uk-icon-small uk-icon-justify'),
            ))
            ->setExtra('translation_domain', 'menu');

            // moderation
            $menu->addChild('Moderation', array(
                'label' => 'Moderation',
                'route' => 'commsy_settings_moderation',
                'routeParameters' => array('roomId' => $roomId),
                'extras' => array('icon' => 'uk-icon-sitemap uk-icon-small uk-icon-justify'),
            ))
            ->setExtra('translation_domain', 'menu');            

            // additional settings
            $menu->addChild('Additional', array(
                'label' => 'Additional',
                'route' => 'commsy_settings_additional',
                'routeParameters' => array('roomId' => $roomId),
                'extras' => array('icon' => 'uk-icon-plus uk-icon-small uk-icon-justify'),
            ))
            ->setExtra('translation_domain', 'menu');

            // appearance
            $menu->addChild('Appearance', array(
                'label' => 'appearance',
                'route' => 'commsy_settings_appearance',
                'routeParameters' => array('roomId' => $roomId),
                'extras' => array('icon' => 'uk-icon-paint-brush uk-icon-small uk-icon-justify'),
            ))
            ->setExtra('translation_domain', 'menu');
            
            // extensions
            $menu->addChild('Extensions', array(
                'label' => 'extensions',
                'route' => 'commsy_settings_extensions',
                'routeParameters' => array('roomId' => $roomId),
                'extras' => array('icon' => 'uk-icon-gears uk-icon-small uk-icon-justify'),
            ))
            ->setExtra('translation_domain', 'menu');

            // delete
            $menu->addChild('Delete', [
                'label' => 'delete',
                'route' => 'commsy_settings_delete',
                'routeParameters' => [
                    'roomId' => $roomId,
                ],
                'extras' => [
                    'icon' => 'uk-icon-trash uk-icon-small uk-icon-justify'
                ],
            ])
            ->setAttributes([
                'class' => 'uk-button-danger',
            ])
            ->setExtra('translation_domain', 'menu');
        }

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
            $currentUser = $this->legacyEnvironment->getCurrentUserItem();

            $inPrivateRoom = false;
            if (!$currentUser->isRoot()) {
                $portalUser = $this->userService->getPortalUserFromSessionId();
                $authSourceManager = $this->legacyEnvironment->getAuthSourceManager();
                $authSource = $authSourceManager->getItem($portalUser->getAuthSource());
                $this->legacyEnvironment->setCurrentPortalID($authSource->getContextId());
                $privateRoomManager = $this->legacyEnvironment->getPrivateRoomManager();
                $privateRoom = $privateRoomManager->getRelatedOwnRoomForUser($portalUser, $this->legacyEnvironment->getCurrentPortalID());

                if ($roomId === $privateRoom->getItemId()) {
                    $inPrivateRoom = true;
                }
            }

            $rubrics = [];
            $label = "home";
            $icon = "uk-icon-home";
            $route = "commsy_room_home";

            if (!$inPrivateRoom) {
                // rubric room information
                $rubrics = $this->roomService->getRubricInformation($roomId);
            }
            // dashboard menu
            else {
                $rubrics = [
                    "announcement" => "announcement",
                    "material" => "material",
                    "discussion" => "discussion",
                    "date" => "date",
                    "todo" => "todo",
                ];
                $label = "overview";
                $icon = "uk-icon-justify uk-icon-qrcode";
                $route = "commsy_dashboard_overview";
            }

            // home navigation
            $menu->addChild('room_home', array(
                'label' => $label,
                'route' => $route,
                'routeParameters' => array('roomId' => $roomId),
                'extras' => array('icon' => $icon . ' uk-icon-small')
            ))
            ->setExtra('translation_domain', 'menu');

            // loop through rubrics to build the menu
            foreach ($rubrics as $value) {
                $route = 'commsy_'.$value.'_list';
                if ($value == 'date') {
                    $room = $this->roomService->getRoomItem($roomId);
                    if ($room->getDatesPresentationStatus() != 'normal') {
                        $route = 'commsy_date_calendar';
                    }
                }

                $menu->addChild($value, [
                    'label' => $value,
                    'route' => $route,
                    'routeParameters' => array('roomId' => $roomId),
                    'extras' => [
                        'icon' => $this->getRubricIcon($value),
                    ]
                ])
                ->setExtra('translation_domain', 'menu');
            }


            $roomItem = $this->roomService->getRoomItem($roomId);
            if ($roomItem->isGroupRoom()) {
                $menu->addChild('room_navigation_space_3', array(
                    'label' => ' ',
                    'route' => 'commsy_room_home',
                    'routeParameters' => array('roomId' => $roomId),
                    'extras' => array('icon' => 'uk-icon-small')
                ));
                $projectRoomItem = $roomItem->getLinkedProjectItem();
                $menu->addChild('room', array(
                    'label' => 'Back to room',
                    'route' => 'commsy_room_home',
                    'routeParameters' => array('roomId' => $projectRoomItem->getItemId()),
                    'extras' => array('icon' => 'uk-icon-reply uk-icon-small uk-icon-justify')
                ))
                ->setExtra('translation_domain', 'menu');
            }

            if (!$inPrivateRoom) {
                if ($currentUser) {
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
                        ))
                            ->setExtra('translation_domain', 'menu');
                    }
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
                $class = "uk-icon-justify uk-icon-check-square-o uk-icon-small";
                break;
            case 'topic':
                $class = "uk-icon-justify uk-icon-book uk-icon-small";
                break;
            case 'project':
                $class = "uk-icon-justify uk-icon-sitemap uk-icon-small";
                break;
            case 'institution':
                $class = "uk-icon-justify uk-icon-institution uk-icon-small";
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

        $portal = $this->legacyEnvironment->getCurrentPortalItem();
        if ($portal) {
            $menu->addChild('portal', [
                'uri' => $currentStack->getSchemeAndHttpHost() . '?cid=' . $portal->getItemId(),
                'attributes' => ['breadcrumb_portal' => true],
            ]);
        }

        $roomId = $currentStack->attributes->get('roomId');
        if ($roomId) {
            $user = $this->userService->getPortalUserFromSessionId();

            if ($user) {
                $authSourceManager = $this->legacyEnvironment->getAuthSourceManager();
                $authSource = $authSourceManager->getItem($user->getAuthSource());
                $this->legacyEnvironment->setCurrentPortalID($authSource->getContextId());
                $privateRoomManager = $this->legacyEnvironment->getPrivateRoomManager();
                $privateRoom = $privateRoomManager->getRelatedOwnRoomForUser($user,$this->legacyEnvironment->getCurrentPortalID());
            }

            $itemId = $currentStack->attributes->get('itemId');
            $roomItem = $this->roomService->getRoomItem($roomId);
    
            if ($roomItem) {

                // get route information
                $route = explode('_', $currentStack->attributes->get('_route'));

                $accountSettings = ['account', 'notifications', 'additional'];
                $roomProfileSettings = ['general', 'address', 'contact'];

                if ($roomItem->isGroupRoom()) {
                    $projectRoomItem = $roomItem->getLinkedProjectItem();
                    $menu->addChild($projectRoomItem->getTitle(), array(
                        'route' => 'commsy_room_home',
                        'routeParameters' => array('roomId' => $projectRoomItem->getItemId()),
                        'attributes' => ['breadcrumb_grouproom_parent' => true],
                    ));
                }

                if ($roomItem->isPrivateRoom()) {
                    // dashboard
                    $menu->addChild('Dashboard', array(
                        'route' => 'commsy_dashboard_overview',
                        'routeParameters' => array('roomId' => $roomId),
                        'attributes' => ['breadcrumb_room' => true],
                    ));
                }
                elseif (isset($route[2]) && !in_array($route[2], $accountSettings)) {
                    // home
                    $menu->addChild($roomItem->getTitle(), array(
                        'route' => 'commsy_room_home',
                        'routeParameters' => array('roomId' => $roomId),
                        'attributes' => ['breadcrumb_room' => true],
                    ));
                }

                if (isset($route[1]) && !in_array($route[1], ['room', 'dashboard', 'search', 'hashtag', 'category', 'item', 'profile', 'settings'])) {

                    // rubric
                    $tempRoute = 'commsy_'.$route[1].'_'.'list';
                    $changeRubrikcLinkForDate = false;
                    if ($route[1] == 'date') {
                        $room = $this->roomService->getRoomItem($roomId);
                        if ($room->getDatesPresentationStatus() != 'normal') {
                            $tempRoute = 'commsy_date_calendar';
                            if (!$itemId) {
                                $changeRubrikcLinkForDate = true;
                            }
                        }
                    }
                    
                    if (!$changeRubrikcLinkForDate) {
                        $menu->addChild($route[1], array(
                            'route' => $tempRoute,
                            'routeParameters' => array('roomId' => $roomId),
                            'attributes' => ['breadcrumb_rubric' => true],
                        ));
                    } else {
                        $menu->addChild($route[1]);
                    }
        
                    if (isset($route[2]) && $route[2] != "list") {
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
                                ),
                                'attributes' => ['breadcrumb_item' => true],
                            ));
                        }
                    }
                }

                elseif (isset($route[1]) && $route[1] == 'settings') {
                    $menu->addChild('settings', array(
                        'route' => 'commsy_settings_general',
                        'routeParameters' => array('roomId' => $roomId),
                        'attributes' => ['breadcrumb_room' => true],
                    ));
                }

                elseif (isset($route[1]) && isset($route[2]) && $route[1] == 'profile') {
                    // room profile
                    if (in_array($route[2], $roomProfileSettings)) {
                        $menu->addChild('Room profile', array(
                            'route' => 'commsy_profile_' . $route[2],
                            'routeParameters' => array('roomId' => $roomId, 'itemId' => $currentStack->attributes->get('itemId')),
                            'attributes' => ['breadcrumb_room' => true],
                        ));
                    }
                    // account
                    elseif (in_array($route[2], $accountSettings)) {
                        $menu->addChild('Account', array(
                            'route' => 'commsy_profile_' . $route[2],
                            'routeParameters' => array('roomId' => $roomId, 'itemId' => $currentStack->attributes->get('itemId')),
                            'attributes' => ['breadcrumb_room' => false],
                        ));
                    }
                }
            }
        }

        return $menu;
    }

}
