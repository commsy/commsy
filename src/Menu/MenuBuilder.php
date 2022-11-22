<?php

namespace App\Menu;

use App\Entity\Account;
use App\Entity\Portal;
use App\Repository\PortalRepository;
use App\Services\InvitationsService;
use App\Services\LegacyEnvironment;
use App\Utils\RoomService;
use cs_environment;
use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Security;

class MenuBuilder
{
    /**
     * @var FactoryInterface $factory
     */
    private FactoryInterface $factory;

    /**
     * @var RoomService
     */
    private RoomService $roomService;

    /**
     * @var cs_environment
     */
    private cs_environment $legacyEnvironment;

    /**
     * @var AuthorizationCheckerInterface
     */
    private AuthorizationCheckerInterface $authorizationChecker;

    /**
     * @var InvitationsService
     */
    private InvitationsService $invitationsService;

    /**
     * @var PortalRepository
     */
    private PortalRepository $portalRepository;

    /**
     * @var Security
     */
    private Security $security;

    /**
     * @var RouterInterface
     */
    private RouterInterface $router;

    /**
     * @param FactoryInterface $factory
     * @param RoomService $roomService
     * @param LegacyEnvironment $legacyEnvironment
     * @param AuthorizationCheckerInterface $authorizationChecker
     * @param InvitationsService $invitationsService
     * @param PortalRepository $portalRepository
     * @param Security $security
     * @param RouterInterface $router
     */
    public function __construct(
        FactoryInterface $factory,
        RoomService $roomService,
        LegacyEnvironment $legacyEnvironment,
        AuthorizationCheckerInterface $authorizationChecker,
        InvitationsService $invitationsService,
        PortalRepository $portalRepository,
        Security $security,
        RouterInterface $router
    ) {
        $this->factory = $factory;
        $this->roomService = $roomService;
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
        $this->authorizationChecker = $authorizationChecker;
        $this->invitationsService = $invitationsService;
        $this->portalRepository = $portalRepository;
        $this->security = $security;
        $this->router = $router;
    }

    /**
     * @return ItemInterface
     */
    public function createAccountMenu(): ItemInterface
    {
        // create profile
        $currentUser = $this->legacyEnvironment->getCurrentUser();

        /** @var Account $account */
        $account = $this->security->getUser();
        $authSource = $account !== null ? $account->getAuthSource() : null;

        $userIsRoot = $this->security->isGranted('ROLE_ROOT');

        $menu = $this->factory->createItem('root');

        if ($currentUser->getItemId() != '' && $account != null) {

            if (!$userIsRoot) {
                $menu->addChild('personal', [
                    'route' => 'app_account_personal',
                    'routeParameters' => [
                        'portalId' => $account->getContextId(),
                    ],
                    'extras' => [
                        'icon' => 'uk-icon-user uk-icon-small uk-icon-justify',
                        'user' => $currentUser,
                    ]
                ])
                ->setExtra('translation_domain', 'menu');
            }

            if ($userIsRoot || ($authSource !== null && $authSource->isChangePassword())) {
                $menu->addChild('changePassword', [
                    'route' => 'app_account_changepassword',
                    'extras' => [
                        'icon' => 'uk-icon-lock uk-icon-small uk-icon-justify',
                        'user' => $currentUser,
                    ]
                ])
                ->setExtra('translation_domain', 'profile');
            }

            if (!$userIsRoot) {
                $menu->addChild('mergeAccounts', [
                    'label' => 'combineAccount',
                    'route' => 'app_account_mergeaccounts',
                    'routeParameters' => [
                        'portalId' => $account->getContextId(),
                    ],
                    'extras' => [
                        'icon' => 'uk-icon-sitemap uk-icon-small uk-icon-justify',
                        'user' => $currentUser,
                    ]
                ])
                ->setExtra('translation_domain', 'profile');

                $menu->addChild('newsletter', [
                    'route' => 'app_account_newsletter',
                    'routeParameters' => [
                        'portalId' => $account->getContextId(),
                    ],
                    'extras' => [
                        'icon' => 'uk-icon-newspaper-o uk-icon-small uk-icon-justify',
                        'user' => $currentUser,
                    ]
                ])
                ->setExtra('translation_domain', 'menu');

                if (!$userIsRoot) {
                    $menu->addChild('privacy', [
                        'label' => 'Privacy',
                        'route' => 'app_account_privacy',
                        'routeParameters' => [
                            'portalId' => $account->getContextId(),
                        ],
                        'extras' => [
                            'icon' => 'uk-icon-user-secret uk-icon-small uk-icon-justify',
                            'user' => $currentUser,
                        ]
                    ])
                    ->setExtra('translation_domain', 'profile');
                }

                $menu->addChild('additional', [
                    'route' => 'app_account_additional',
                    'routeParameters' => [
                        'portalId' => $account->getContextId(),
                    ],
                    'extras' => [
                        'icon' => 'uk-icon-plus-square uk-icon-small uk-icon-justify',
                        'user' => $currentUser,
                    ]
                ])
                ->setExtra('translation_domain', 'menu');

                $menu->addChild('deleteAccount', [
                    'route' => 'app_account_deleteaccount',
                    'routeParameters' => [
                        'portalId' => $account->getContextId(),
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
        }

        return $menu;
    }

    /**
     * @param RequestStack $requestStack
     * @return ItemInterface
     */
    public function createProfileMenu(RequestStack $requestStack): ItemInterface
    {
        // create profile
        $currentStack = $requestStack->getCurrentRequest();
        $currentUser = $this->legacyEnvironment->getCurrentUser();

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

    /**
     * @param RequestStack $requestStack
     * @param LegacyEnvironment $legacyEnvironment
     * @return ItemInterface
     */
    public function createSettingsMenu(RequestStack $requestStack, LegacyEnvironment $legacyEnvironment): ItemInterface
    {
        // get room Id
        $currentStack = $requestStack->getCurrentRequest();
        $roomId = $currentStack->attributes->get('roomId');
        $room = $this->roomService->getRoomItem($roomId);

        $portalItem = $legacyEnvironment->getEnvironment()->getCurrentPortalItem();
        $portalId = $portalItem->getItemId();

        /** @var Portal $portal */
        $portal = $this->portalRepository->find($portalId);

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
            if ($this->invitationsService->invitationsEnabled($portal)) {
                $menu->addChild('Invitations', array(
                    'label' => 'invitations',
                    'route' => 'app_settings_invitations',
                    'routeParameters' => array('roomId' => $roomId),
                    'extras' => array('icon' => 'uk-icon-envelope uk-icon-small uk-icon-justify'),
                ))
                ->setExtra('translation_domain', 'menu');
            }

            // delete
            if ($this->roomService->getRoomItem($roomId)->getType() !== 'userroom') {
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
            }

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
     * @param RequestStack $requestStack
     * @return ItemInterface
     */
    public function createPortalSettingsMenu(RequestStack $requestStack): ItemInterface
    {
        $menu = $this->factory->createItem('root');
        $menu->setChildrenAttribute('class', 'uk-nav uk-nav-default uk-nav-divider');
        $menu->setExtra('currentClass', 'asdf');

        $currentStack = $requestStack->getCurrentRequest();
        if ($currentStack) {
            $portalId = $currentStack->attributes->get('portalId');

            // general
            $menu->addChild('General', [
                'label' => 'General',
                'route' => 'app_portalsettings_general',
                'routeParameters' => ['portalId' => $portalId],
                'extras' => ['icon' => 'server'],
            ])
            ->setExtra('translation_domain', 'menu');

            // authentication
            $menu->addChild('Auth', [
                'label' => 'Auth',
                'route' => 'app_portalsettings_authlocal',
                'routeParameters' => ['portalId' => $portalId],
                'extras' => ['icon' => 'credit-card']
            ])
            ->setChildrenAttribute('class', 'uk-nav-sub')
            ->setExtra('translation_domain', 'portal');

            $menu['Auth']->addChild('Sources', [
                'label' => 'portal.auth.sources',
                'route' => 'app_portalsettings_authlocal',
                'routeParameters' => ['portalId' => $portalId],
            ])
            ->setExtra('translation_domain', 'portal');

            $menu['Auth']->addChild('Workspace Membership', [
                'label' => 'portal.auth.workspace_membership',
                'route' => 'app_portalsettings_authworkspacemembership',
                'routeParameters' => ['portalId' => $portalId],
            ])
            ->setExtra('translation_domain', 'portal');

            // accounts
            $menu->addChild('Accounts', [
                'label' => 'Accounts',
                'route' => 'app_portalsettings_accountindex',
                'routeParameters' => ['portalId' => $portalId],
                'extras' => ['icon' => 'users']
            ])
            ->setExtra('translation_domain', 'portal');

            // appearance
            $menu->addChild('Appearance', [
                'label' => 'appearance',
                'route' => 'app_portalsettings_appearance',
                'routeParameters' => ['portalId' => $portalId],
                'extras' => ['icon' => 'paint-bucket'],
            ])
            ->setExtra('translation_domain', 'menu');

            // support
            $menu->addChild('Support', [
                'label' => 'Support requests',
                'route' => 'app_portalsettings_support',
                'routeParameters' => ['portalId' => $portalId],
                'extras' => ['icon' => 'question']
            ])
            ->setExtra('translation_domain', 'portal');

            // announcements
            $menu->addChild('Announcements', [
                'label' => 'announcements',
                'route' => 'app_portalsettings_announcements',
                'routeParameters' => ['portalId' => $portalId],
                'extras' => ['icon' => 'bell']
            ])
            ->setExtra('translation_domain', 'portal');

            // Terms / Content
            $menu->addChild('Contents', [
                'label' => 'contents',
                'route' => 'app_portalsettings_contents',
                'routeParameters' => ['portalId' => $portalId],
                'extras' => ['icon' => 'file-text']
            ])
            ->setChildrenAttribute('class', 'uk-nav-sub')
            ->setExtra('translation_domain', 'portal');

            $menu['Contents']->addChild('Contents', [
                'label' => 'contents',
                'route' => 'app_portalsettings_contents',
                'routeParameters' => ['portalId' => $portalId],
            ])
            ->setExtra('translation_domain', 'portal');

            $menu['Contents']->addChild('RoomTermsTermplates', [
                'label' => 'roomtermstemplates',
                'route' => 'app_portalsettings_roomtermstemplates',
                'routeParameters' => ['portalId' => $portalId],
            ])
            ->setExtra('translation_domain', 'portal');

            // translations
            $menu->addChild('Translations', [
                'label' => 'Translations',
                'route' => 'app_portalsettings_translations',
                'routeParameters' => ['portalId' => $portalId],
                'extras' => ['icon' => 'location']
            ])
            ->setExtra('translation_domain', 'portal');

            // portal home
            $menu->addChild('Portalhome', [
                'label' => 'Portalhome',
                'route' => 'app_portalsettings_portalhome',
                'routeParameters' => ['portalId' => $portalId],
                'extras' => ['icon' => 'play-circle']
            ])
            ->setExtra('translation_domain', 'portal');

            // room creation
            $menu->addChild('Roomcreation', [
                'label' => 'Rooms',
                'route' => 'app_portalsettings_roomcreation',
                'routeParameters' => ['portalId' => $portalId],
                'extras' => ['icon' => 'plus-circle']
            ])
            ->setExtra('translation_domain', 'portal');

            // time pulses
            $menu->addChild('Time', [
                'label' => 'Time pulses',
                'route' => 'app_portalsettings_timepulses',
                'routeParameters' => ['portalId' => $portalId],
                'extras' => ['icon' => 'calendar']
            ])
            ->setExtra('translation_domain', 'portal');

            // room categories
            $menu->addChild('Roomcategories', [
                'label' => 'Room categories',
                'route' => 'app_portalsettings_roomcategories',
                'routeParameters' => ['portalId' => $portalId],
                'extras' => ['icon' => 'tag']
            ])
            ->setExtra('translation_domain', 'portal');

            // licenses
            $menu->addChild('Licenses', [
                'label' => 'Licenses',
                'route' => 'app_portalsettings_licenses',
                'routeParameters' => ['portalId' => $portalId],
                'extras' => ['icon' => 'ban']
            ])
            ->setExtra('translation_domain', 'portal');

            // privacy
            $menu->addChild('Privacy', [
                'label' => 'Privacy',
                'route' => 'app_portalsettings_privacy',
                'routeParameters' => ['portalId' => $portalId],
                'extras' => ['icon' => 'lock']
            ])
            ->setExtra('translation_domain', 'portal');

            // inactive
            $menu->addChild('Inactive', [
                'label' => 'Deprovisioning',
                'route' => 'app_portalsettings_inactive',
                'routeParameters' => ['portalId' => $portalId],
                'extras' => ['icon' => 'minus-circle']
            ])
            ->setExtra('translation_domain', 'portal');

            // CSV import
            $menu->addChild('Csvimport', [
                'label' => 'CSV-Import',
                'route' => 'app_portalsettings_csvimport',
                'routeParameters' => ['portalId' => $portalId],
                'extras' => ['icon' => 'move']
            ])
            ->setExtra('translation_domain', 'portal');

            // mail
            $menu->addChild('Mail', [
                'label' => 'Mailtexts',
                'route' => 'app_portalsettings_mailtexts',
                'routeParameters' => ['portalId' => $portalId],
                'extras' => ['icon' => 'mail']
            ])
            ->setExtra('translation_domain', 'portal');
        }

        return $menu;
    }

    /**
     * @param RequestStack $requestStack
     * @return ItemInterface
     */
    public function createMainMenu(RequestStack $requestStack): ItemInterface
    {
        // get room id
        $currentRequest = $requestStack->getCurrentRequest();

        // create root item for knpmenu
        $menu = $this->factory->createItem('root');

        $roomId = $currentRequest->attributes->get('roomId');
        if (!$roomId) {
            return $menu;
        }

        // dashboard
        $currentUser = $this->legacyEnvironment->getCurrentUserItem();

        $userIsRoot = $this->security->isGranted('ROLE_ROOT');

        $inPrivateRoom = false;
        if (!$userIsRoot && !$currentUser->isGuest()) {
            $privateRoom = $currentUser->getOwnRoom();

            if ($roomId == $privateRoom->getItemId()) {
                $inPrivateRoom = true;
            }
        }

        $label = "home";
        $icon = "uk-icon-home";
        $route = "app_room_home";

        if (!$inPrivateRoom) {
            // rubric room information
            $rubrics = $this->roomService->getRubricInformation($roomId) ?: [];

            // moderators _always_ need access to the user rubric (to manage room memberships)
            if (!in_array("user", $rubrics) && $currentUser->isModerator()) {
                $rubrics[] = "user";
            }
        } // dashboard menu
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
        if (!$userIsRoot &&
            (!$inPrivateRoom || ($action != "overview" && $action != "listall")) &&
            ($controller != "marked" || $action != "list") &&
            ($controller != "room" || $action != "detail")
        ) {
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
                $route = 'app_' . $value . '_list';
                if ($value == 'date') {
                    $room = $this->roomService->getRoomItem($roomId);
                    if ($room->getDatesPresentationStatus() != 'normal') {
                        $route = 'app_date_calendar';
                    }
                }

                try {
                    $this->router->generate($route, ['roomId' => $roomId]);
                    $menu
                        ->addChild($value, [
                            'label' => $value,
                            'route' => $route,
                            'routeParameters' => ['roomId' => $roomId],
                            'extras' => [
                                'icon' => $this->getRubricIcon($value),
                            ]
                        ])
                        ->setExtra('translation_domain', 'menu');
                } catch (RouteNotFoundException $e) {
                }
            }
        }

        if (!$inPrivateRoom) {
            if (!$userIsRoot &&
                $currentUser && !$currentUser->isGuest()) {
                $menu->addChild('', ['uri' => '#']);
                $menu->addChild('room_profile', [
                    'label' => 'Room profile',
                    'route' => 'app_profile_general',
                    'routeParameters' => ['roomId' => $roomId, 'itemId' => $currentUser->getItemID()],
                    'extras' => ['icon' => 'uk-icon-street-view uk-icon-small'],
                ])
                ->setExtra('translation_domain', 'menu');

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

        return $menu;
    }

    /**
     * returns the uikit icon classname for a specific rubric
     * @param string $rubric rubric name
     * @return string         uikit icon class
     */
    private function getRubricIcon(string $rubric): string
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
