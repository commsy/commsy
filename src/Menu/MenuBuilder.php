<?php

namespace App\Menu;

use Knp\Menu\FactoryInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use App\Utils\RoomService;
use Symfony\Component\Translation\Translator;
use App\Services\LegacyEnvironment;
use App\Utils\UserService;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;
use App\Services\InvitationsService;

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

    private $invitationsService;

    /**
    * @param FactoryInterface $factory
    */
    public function __construct(FactoryInterface $factory, RoomService $roomService, LegacyEnvironment $legacyEnvironment, UserService $userService, AuthorizationChecker $authorizationChecker, InvitationsService $invitationsService )
    {
        $this->factory = $factory;
        $this->roomService = $roomService;
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
        $this->userService = $userService;
        $this->authorizationChecker = $authorizationChecker;
        $this->invitationsService = $invitationsService;
    }

    public function createAccountMenu(RequestStack $requestStack)
    {
       // create profile
        $currentStack = $requestStack->getCurrentRequest();
        $currentUser = $this->legacyEnvironment->getCurrentUser();
        $currentPortal = $this->legacyEnvironment->getCurrentPortalItem();
        $authSourceItem = $currentPortal->getAuthSource($currentUser->getAuthSource());

        $menu = $this->factory->createItem('root');

        if ($currentUser->getItemId() != '') {

            $menu->addChild('personal', [
                'route' => 'app_profile_personal',
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

            if((isset($authSourceItem) && $authSourceItem->allowChangePassword()) || $currentUser->isRoot()) {
                $menu->addChild('changePassword', [
                    'route' => 'app_profile_changepassword',
                    'routeParameters' => [
                        'roomId' => $currentStack->attributes->get('roomId'),
                        'itemId' => $currentUser->getItemId(),
                    ],
                    'extras' => [
                        'icon' => 'uk-icon-lock uk-icon-small uk-icon-justify',
                        'user' => $currentUser,
                    ]
                ])
                ->setExtra('translation_domain', 'profile');
            }

            if(!$currentUser->isRoot()) {
                $menu->addChild('mergeAccounts', [
                    'label' => 'combineAccount',
                    'route' => 'app_profile_mergeaccounts',
                    'routeParameters' => [
                        'roomId' => $currentStack->attributes->get('roomId'),
                        'itemId' => $currentUser->getItemId(),
                    ],
                    'extras' => [
                        'icon' => 'uk-icon-sitemap uk-icon-small uk-icon-justify',
                        'user' => $currentUser,
                    ]
                ])
                ->setExtra('translation_domain', 'profile');
            }

            $menu->addChild('newsletter', [
                'route' => 'app_profile_newsletter',
                'routeParameters' => [
                    'roomId' => $currentStack->attributes->get('roomId'),
                    'itemId' => $currentUser->getItemId(),
                ],
                'extras' => [
                    'icon' => 'uk-icon-newspaper-o uk-icon-small uk-icon-justify',
                    'user' => $currentUser,
                ]
            ])
            ->setExtra('translation_domain', 'menu');

            if ($currentUser->getRelatedPortalUserItem()->isAllowedToUseCalDAV()) {
                $menu->addChild('calendars', [
                    'route' => 'app_profile_calendars',
                    'routeParameters' => [
                        'roomId' => $currentStack->attributes->get('roomId'),
                        'itemId' => $currentUser->getItemId(),
                    ],
                    'extras' => [
                        'icon' => 'uk-icon-calendar uk-icon-small uk-icon-justify',
                        'user' => $currentUser,
                    ]
                ])
                    ->setExtra('translation_domain', 'menu');
            }

            $menu->addChild('additional', [
                'route' => 'app_profile_additional',
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

            $menu->addChild('deleteAccount', [
                'route' => 'app_profile_deleteaccount',
                'routeParameters' => [
                    'roomId' => $currentStack->attributes->get('roomId'),
                    'itemId' => $currentUser->getItemId(),
                ],
                'extras' => [
                    'icon' => 'uk-icon-trash uk-icon-small uk-icon-justify',
                    'user' => $currentUser,
                ],
            ])
            ->setAttributes([
                'class' => 'uk-button-danger',
            ])
            ->setExtra('translation_domain', 'profile');
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

            $menu->addChild('general', [
                'route' => 'app_profile_general',
                'routeParameters' => [
                    'roomId' => $currentStack->attributes->get('roomId'),
                    'itemId' => $currentUser->getItemId(),
                ],
                'extras' => [
                    'icon' => 'uk-icon-building-o uk-icon-small uk-icon-justify',
                    'user' => $currentUser,
                ]
            ])
            ->setExtra('translation_domain', 'menu');

            $menu->addChild('address', [
                'route' => 'app_profile_address',
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
                'route' => 'app_profile_contact',
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

            if ($this->authorizationChecker->isGranted('MODERATOR')) {
                $menu->addChild('notifications', [
                    'route' => 'app_profile_notifications',
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
            }

            $menu->addChild('cancelMembership', [
                'route' => 'app_profile_deleteroomprofile',
                'routeParameters' => [
                    'roomId' => $currentStack->attributes->get('roomId'),
                    'itemId' => $currentUser->getItemId(),
                ],
                'extras' => [
                    'icon' => 'uk-icon-trash uk-icon-small uk-icon-justify',
                    'user' => $currentUser,
                ]
            ])
            ->setAttributes([
                'class' => 'uk-button-danger',
            ])
            ->setExtra('translation_domain', 'profile');
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
                'route' => 'app_settings_general',
                'routeParameters' => array('roomId' => $roomId),
                'extras' => array('icon' => 'uk-icon-server uk-icon-small uk-icon-justify'),
            ))
            ->setExtra('translation_domain', 'menu');

            // moderation
            $menu->addChild('Moderation', array(
                'label' => 'Moderation',
                'route' => 'app_settings_moderation',
                'routeParameters' => array('roomId' => $roomId),
                'extras' => array('icon' => 'uk-icon-sitemap uk-icon-small uk-icon-justify'),
            ))
            ->setExtra('translation_domain', 'menu');            

            // additional settings
            $menu->addChild('Additional', array(
                'label' => 'Additional',
                'route' => 'app_settings_additional',
                'routeParameters' => array('roomId' => $roomId),
                'extras' => array('icon' => 'uk-icon-plus uk-icon-small uk-icon-justify'),
            ))
            ->setExtra('translation_domain', 'menu');

            // appearance
            $menu->addChild('Appearance', array(
                'label' => 'appearance',
                'route' => 'app_settings_appearance',
                'routeParameters' => array('roomId' => $roomId),
                'extras' => array('icon' => 'uk-icon-paint-brush uk-icon-small uk-icon-justify'),
            ))
            ->setExtra('translation_domain', 'menu');
            
            // extensions
            $menu->addChild('Extensions', array(
                'label' => 'extensions',
                'route' => 'app_settings_extensions',
                'routeParameters' => array('roomId' => $roomId),
                'extras' => array('icon' => 'uk-icon-gears uk-icon-small uk-icon-justify'),
            ))
            ->setExtra('translation_domain', 'menu');


            // invitations
            if ($this->invitationsService->invitationsEnabled()) {
                $menu->addChild('Invitations', array(
                    'label' => 'invitations',
                    'route' => 'app_settings_invitations',
                    'routeParameters' => array('roomId' => $roomId),
                    'extras' => array('icon' => 'uk-icon-envelope uk-icon-small uk-icon-justify'),
                ))
                    ->setExtra('translation_domain', 'menu');
            }

            // delete
            $menu->addChild('Delete', [
                'label' => 'delete',
                'route' => 'app_settings_delete',
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

            $menu->addChild(' ', ['uri' => '#']);
            $menu->addChild('room', array(
                'label' => 'Back to room',
                'route' => 'app_room_home',
                'routeParameters' => array('roomId' => $roomId),
                'extras' => array('icon' => 'uk-icon-reply uk-icon-small uk-icon-justify')
            ))
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

        $inPortal = false;
        if ($roomId == $this->legacyEnvironment->getCurrentPortalId()) {
            $inPortal = true;
        }

        if ($roomId && !$inPortal) {
            // dashboard
            $currentUser = $this->legacyEnvironment->getCurrentUserItem();

            $inPrivateRoom = false;
            if (!$currentUser->isRoot() && !$currentUser->isGuest()) {
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
            $route = "app_room_home";

            if (!$inPrivateRoom) {
                // rubric room information
                $rubrics = $this->roomService->getRubricInformation($roomId);

                // moderators _always_ need access to the user rubric (to manage room memberships)
                if (!in_array("user", $rubrics) and $currentUser->isModerator()) {
                    $rubrics[] = "user";
                }
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
                $route = "app_dashboard_overview";
            }

            list($bundle, $controller, $action) = explode("_", $currentRequest->attributes->get('_route'));

            // NOTE: hide dashboard menu in dashboard overview and room list!
            if ( (!$inPrivateRoom or ($action != "overview" and $action != "listall") ) and
                 ($controller != "copy" or $action != "list") and
                 ($controller != "room" or $action != "detail")) {
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
                    $route = 'app_'.$value.'_list';
                    if ($value == 'date') {
                        $room = $this->roomService->getRoomItem($roomId);
                        if ($room->getDatesPresentationStatus() != 'normal') {
                            $route = 'app_date_calendar';
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
            }

            if (!$inPrivateRoom) {
                if ($currentUser) {
                    if ($this->authorizationChecker->isGranted('MODERATOR')) {
                        $menu->addChild(' ', ['uri' => '#']);
                        $menu->addChild('room_configuration', array(
                            'label' => 'settings',
                            'route' => 'app_settings_general',
                            'routeParameters' => array('roomId' => $roomId),
                            'extras' => array('icon' => 'uk-icon-wrench uk-icon-small')
                        ))
                            ->setExtra('translation_domain', 'menu');
                    }
                }
            }
        } else {
            $menu->addChild('portal_configuration_room_categories', array(
                'label' => 'Room categories',
                'route' => 'app_portal_roomcategories',
                'routeParameters' => array('roomId' => $roomId),
                'extras' => array('icon' => 'uk-icon-tags uk-icon-small')
            ))
                ->setExtra('translation_domain', 'portal');
            $menu->addChild('portal_configuration_announcements', array(
                'label' => 'announcements',
                'route' => 'app_portal_announcements',
                'routeParameters' => array('roomId' => $roomId),
                'extras' => array('icon' => 'uk-icon-bullhorn uk-icon-small')
            ))
                ->setExtra('translation_domain', 'portal');
            $menu->addChild('portal_configuration_terms', array(
                'label' => 'terms',
                'route' => 'app_portal_terms',
                'routeParameters' => array('roomId' => $roomId),
                'extras' => array('icon' => 'uk-icon-file-text-o uk-icon-small')
            ))
                ->setExtra('translation_domain', 'portal');
            $menu->addChild('portal_configuration_help', array(
                'label' => 'help',
                'route' => 'app_portal_help',
                'routeParameters' => array('roomId' => $roomId),
                'extras' => array('icon' => 'uk-icon-question-circle uk-icon-small')
            ))
                ->setExtra('translation_domain', 'portal');
            $menu->addChild('portal_configuration_translations', array(
                'label' => 'Translations',
                'route' => 'app_portal_translations',
                'routeParameters' => array('roomId' => $roomId),
                'extras' => array('icon' => 'uk-icon-columns uk-icon-small')
            ))
                ->setExtra('translation_domain', 'portal');
            $menu->addChild('portal_configuration_licenses', array(
                'label' => 'Licenses',
                'route' => 'app_portal_licenses',
                'routeParameters' => array('roomId' => $roomId),
                'extras' => array('icon' => 'uk-icon-copyright uk-icon-small')
            ))
                ->setExtra('translation_domain', 'portal');

            // CSV-Import
            $menu->addChild('portal_configuration_csv_import', [
                'label' => 'CSV-Import',
                'route' => 'app_portal_csvimport',
                'routeParameters' => [
                    'roomId' => $roomId,
                ],
                'extras' => [
                    'icon' => 'uk-icon-user-plus uk-icon-small',
                ],
            ])
                ->setExtra('translation_domain', 'portal');

            $menu->addChild(' ', ['uri' => '#']);
            $menu->addChild('room', array(
                'label' => 'settings',
                'route' => 'app_portal_legacysettings',
                'routeParameters' => array('roomId' => $roomId),
                'extras' => array('icon' => 'uk-icon-reply uk-icon-small uk-icon-justify')
            ))
                ->setExtra('translation_domain', 'portal');
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
}
