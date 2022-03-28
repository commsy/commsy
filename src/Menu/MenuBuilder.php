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
use Symfony\Component\HttpFoundation\RequestStack;
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
     * @param FactoryInterface $factory
     * @param RoomService $roomService
     * @param LegacyEnvironment $legacyEnvironment
     * @param AuthorizationCheckerInterface $authorizationChecker
     * @param InvitationsService $invitationsService
     * @param PortalRepository $portalRepository
     * @param Security $security
     */
    public function __construct(
        FactoryInterface $factory,
        RoomService $roomService,
        LegacyEnvironment $legacyEnvironment,
        AuthorizationCheckerInterface $authorizationChecker,
        InvitationsService $invitationsService,
        PortalRepository $portalRepository,
        Security $security
    ) {
        $this->factory = $factory;
        $this->roomService = $roomService;
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
        $this->authorizationChecker = $authorizationChecker;
        $this->invitationsService = $invitationsService;
        $this->portalRepository = $portalRepository;
        $this->security = $security;
    }

    public function createAccountMenu()
    {
        // create profile
        $currentUser = $this->legacyEnvironment->getCurrentUser();

        /** @var Account $account */
        $account = $this->security->getUser();
        $authSource = $account !== null ? $account->getAuthSource() : null;

        $menu = $this->factory->createItem('root');

        if ($currentUser->getItemId() != '' && $account != null) {

            if (!$currentUser->isRoot()) {
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

            if ($currentUser->isRoot() || ($authSource !== null && $authSource->isChangePassword())) {
                $menu->addChild('changePassword', [
                    'route' => 'app_account_changepassword',
                    'extras' => [
                        'icon' => 'uk-icon-lock uk-icon-small uk-icon-justify',
                        'user' => $currentUser,
                    ]
                ])
                ->setExtra('translation_domain', 'profile');
            }

            if (!$currentUser->isRoot()) {
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

                if (!$currentUser->isRoot()) {
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
     * creates the profile sidebar
     * @param RequestStack $requestStack [description]
     * @return KnpMenu                    KnpMenu
     */
    public function createProfileMenu(RequestStack $requestStack)
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

    public function createSettingsMenu(RequestStack $requestStack)
    {
        // get room Id
        $currentStack = $requestStack->getCurrentRequest();
        $roomId = $currentStack->attributes->get('roomId');
        $room = $this->roomService->getRoomItem($roomId);

        /** @var Portal $portal */
        $portal = $this->portalRepository->find($room->getContextID());

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

    public function createPortalSettingsMenu(RequestStack $requestStack)
    {
        $menu = $this->factory->createItem('root');

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

            // terms
            $menu->addChild('Contents', [
                'label' => 'contents',
                'route' => 'app_portalsettings_contents',
                'routeParameters' => ['portalId' => $portalId],
                'extras' => ['icon' => 'file-text']
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

            // auth source
            $menu->addChild('Auth', [
                'label' => 'Auth',
                'route' => 'app_portalsettings_authlocal',
                'routeParameters' => ['portalId' => $portalId],
                'extras' => ['icon' => 'credit-card']
            ])
            ->setExtra('translation_domain', 'portal');
        }

        return $menu;
    }

    /**
     * creates rubric menu
     * @param RequestStack $requestStack [description]
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
                $privateRoom = $currentUser->getOwnRoom();

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
            if ((!$inPrivateRoom or ($action != "overview" and $action != "listall")) and
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
                    $route = 'app_' . $value . '_list';
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
                if ($currentUser && !$currentUser->isGuest()) {
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
        }

        return $menu;
    }

    /**
     * returns the uikit icon classname for a specific rubric
     * @param string $rubric rubric name
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
