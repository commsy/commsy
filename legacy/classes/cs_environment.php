<?php

/*
 * This file is part of CommSy.
 *
 * (c) Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
 * Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
 * Edouard Simon, Monique Strauss, Jose Mauel Gonzalez Vazquez, Johannes Schultze
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

use App\Entity\Portal;
use App\Helper\LocaleHelper;
use App\Proxy\PortalProxy;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/** This class returns an instance of a cs_mananger subclass on request.
 *It also contains often needed environment variables.
 */
class cs_environment
{
    /**
     * array - containing the objects.
     */
    public array $instance = [];

    /**
     * cs_user_item - containing the current user.
     */
    public $current_user;

    public $_portal_user = null;

    /**
     * integer - id of current room.
     */
    public $current_context_id = 0;

    /**
     * cs_context_item - current room.
     */
    public $current_context = null;

    /**
     * @var cs_portal_item portal item
     */
    public $_current_portal = null;

    public $_current_portal_id = 0;

    public ?cs_server_item $_server_item = null;

    /**
     * string - current module name.
     */
    public $current_module;

    /**
     * string - current function name.
     */
    public $current_function;

    public $_current_parameter_array = null;

    /**
     * string - selected language of the current user.
     */
    public $_selected_language = '';

    private ?db_mysql_connector $_db_mysql_connector = null;
    private bool $_cache_on = true;
    private ?misc_text_converter $_misc_text_converter = null;
    private ?array $_available_languages = null;

    /**
     * get the current user
     * returns the current user. If there is no current user it will be returned an empty user_item.
     */
    public function getCurrentUserItem(): cs_user_item
    {
        if (!isset($this->current_user)) {
            $this->current_user = new cs_user_item($this);
        }

        return $this->current_user;
    }

    public function getPortalUserItem()
    {
        if (!isset($this->_portal_user)) {
            $current_user = $this->getCurrentUserItem();
            if ($current_user->isRoot() or $this->inPortal()) {
                $this->_portal_user = $current_user;
            } else {
                $manager = $this->getUserManager();
                $manager->resetLimits();
                $manager->setContextLimit($this->getCurrentPortalID());
                $manager->setUserIDLimit($current_user->getUserID());
                $manager->setAuthSourceLimit($current_user->getAuthSource());
                $manager->select();
                $list = $manager->get();
                if ($list->isNotEmpty() and 1 == $list->getCount()) {
                    $this->_portal_user = $list->getFirst();
                }
            }
        }

        return $this->_portal_user;
    }

    public function getCurrentUser()
    {
        return $this->getCurrentUserItem();
    }

    public function setCurrentUser($current_user)
    {
        $this->setCurrentUserItem($current_user);
    }

    public function setCurrentUserItem($current_user)
    {
        $this->current_user = $current_user;
    }

    public function getCurrentUserID()
    {
        $current_user = $this->getCurrentUserItem();

        return $current_user->getItemID();
    }

    /** get id of the current room
     * returns the current room id.
     *
     * @return int current_context_id
     *
     * @author CommSy Development Group
     */
    public function getCurrentContextID()
    {
        return intval($this->current_context_id);
    }

    /** set id of the current room
     * sets the current room id.
     *
     * @var int id
     */
    public function setCurrentContextID($id)
    {
        $this->current_context_id = $id;
    }

    /** set id of the current room
     * sets the current room as object.
     *
     * @var object value context item
     */
    public function setCurrentContextItem($value)
    {
        $this->current_context = $value;
    }

    /** get the current room item
     * current context id must be set.
     */
    public function getCurrentContextItem(): cs_context_item|PortalProxy
    {
        if (
            null === $this->current_context_id ||
            0 === $this->current_context_id ||
            $this->current_context_id == $this->getServerID()
        ) {
            $this->current_context_id = $this->getServerID();
            $this->current_context = $this->getServerItem();
        }

        if (null === $this->current_context || $this->current_context->getItemID() != $this->current_context_id) {
            global $symfonyContainer;
            /** @var EntityManagerInterface $entityManager */
            $entityManager = $symfonyContainer->get('doctrine.orm.entity_manager');
            $portal = $entityManager->getRepository(Portal::class)->find($this->current_context_id);

            if ($portal) {
                $this->current_context = new PortalProxy($portal, $this);

                return $this->current_context;
            }

            $item_manager = $this->getItemManager();
            $item = $item_manager->getItem($this->current_context_id);
            if (isset($item)) {
                $type = $item->getItemType();
                $manager = $this->getManager($type);
            } else {
                trigger_error('can not initiate room [' . $this->current_context_id . '] -> bug in item table',
                    E_USER_ERROR);
            }

            if (!empty($manager) && is_object($manager)) {
                $this->current_context = $manager->getItem($this->current_context_id);
            }
        }

        return $this->current_context;
    }

    public function getServerItem(): cs_server_item
    {
        if (!isset($this->_server_item)) {
            $manager = $this->getServerManager();
            $this->_server_item = $manager->getItem($this->getServerID());
        }

        return $this->_server_item;
    }

    public function getServerID(): int
    {
        return 99;
    }

    /** get portal object
     * returns the portal object.
     *
     * @return cs_portal_item portal item
     */
    public function getCurrentPortalItem()
    {
        if ($this->_current_portal) {
            return $this->_current_portal;
        }

        if (empty($this->_current_portal_id)) {
            $contextItem = $this->getCurrentContextItem();
            if ($contextItem->isServer()) {
                $this->_current_portal = null;
            } elseif ($contextItem->isPortal()) {
                $this->_current_portal = $contextItem;
            } else {
                $currentPortalId = $contextItem->getContextID();

                if (cs_userroom_item::ROOM_TYPE_USER === $contextItem->getType()) {
                    // NOTE: for user rooms, the context item is the project room that hosts the user room (not the portal item)
                    $currentPortalId = $contextItem->getPortalId();
                }

                global $symfonyContainer;
                /** @var EntityManagerInterface $entityManager */
                $entityManager = $symfonyContainer->get('doctrine.orm.entity_manager');
                $portal = $entityManager->getRepository(Portal::class)->find($currentPortalId);

                if ($portal) {
                    $this->_current_portal = new PortalProxy($portal, $this);
                }
            }
        } else {
            global $symfonyContainer;
            /** @var EntityManagerInterface $entityManager */
            $entityManager = $symfonyContainer->get('doctrine.orm.entity_manager');
            $portal = $entityManager->getRepository(Portal::class)->find($this->_current_portal_id);

            if ($portal) {
                $this->_current_portal = new PortalProxy($portal, $this);
            }
        }

        if (isset($this->_current_portal)) {
            $this->_current_portal_id = $this->_current_portal->getItemID();

            return $this->_current_portal;
        }

        return null;
    }

    public function getCurrentPortalID()
    {
        if (empty($this->_current_portal_id)) {
            $this->getCurrentPortalItem();
        }

        return $this->_current_portal_id;
    }

    public function setCurrentPortalID($value)
    {
        $this->_current_portal_id = (int)$value;
    }

    /** get name of the current module
     * returns the current module.
     *
     * @return string current_module
     *
     * @author CommSy Development Group
     */
    public function getCurrentModule()
    {
        return $this->current_module;
    }

    /** get name of the current function
     * returns the current function.
     *
     * @return string current_function
     *
     * @author CommSy Development Group
     */
    public function getCurrentFunction()
    {
        return $this->current_function;
    }

    public function getValueOfParameter($parameter)
    {
        $value = '';
        $array = $this->_getCurrentParameterArray();
        if (!empty($array)) {
            foreach ($array as $current_parameter) {
                if (mb_stristr((string) $current_parameter, $parameter . '=')) {
                    $temp_array = explode('=', (string) $current_parameter);
                    if ($temp_array[0] == $parameter) {
                        $value = $temp_array[1];
                    }
                }
            }
        }

        return $value;
    }

    public function _getCurrentParameterArray()
    {
        global $_SERVER;

        if (!isset($this->_current_parameter_array)) {
            $this->_current_parameter_array = [];
            if (isset($_SERVER['QUERY_STRING'])) {
                $retour = explode('&', (string) encode(FROM_GET, $_SERVER['QUERY_STRING']));

                // GetParameterSäubern
                $textConverter = $this->getTextConverter();
                // delete cid, mod and fct
                $tmpRetour = [];
                foreach ($retour as $param) {
                    if (empty($param)) {
                        continue;
                    }

                    [$key, $value] = explode('=', $param);

                    if ('cid' !== $key && 'mod' !== $key && 'fct' !== $key) {
                        $tmpRetour[] = $key . '=' . $textConverter->_htmlentities_cleanbadcode($value);
                    }
                }

                $retour = $tmpRetour;

                // delete SID or empty array element
                if (count($retour) > 0) {
                    $retour2 = [];
                    foreach ($retour as $element) {
                        if (!mb_stristr($element, 'SID') and !empty($element)) {
                            $retour2[] = $element;
                        }
                    }
                    $retour = $retour2;
                    unset($retour2);
                }
                $this->_current_parameter_array = $retour;
            }
        }

        return $this->_current_parameter_array;
    }

    public function getCurrentParameterArray()
    {
        $parameter_array = $this->_getCurrentParameterArray();
        $retour = [];
        if ((is_countable($parameter_array) ? count($parameter_array) : 0) > 0) {
            foreach ($parameter_array as $parameter) {
                $temp_parameter_array = explode('=', (string) $parameter);
                if (!empty($temp_parameter_array[1])) {
                    $retour[$temp_parameter_array[0]] = $temp_parameter_array[1];
                } else {
                    $retour[$temp_parameter_array[0]] = '';
                }
            }
        }
        $translator = $this->getTranslationObject();
        if (isset($retour['search']) and ($retour['search'] == $translator->getMessage('COMMON_SEARCH_IN_ROOM') || $retour['search'] == $translator->getMessage('COMMON_SEARCH_IN_RUBRIC'))) {
            unset($retour['search']);
        }
        array_walk_recursive($retour, $this->cleanBadCode(...));

        return $retour;
    }

    public function cleanBadCode(&$item, $key)
    {
        $item = $this->getTextConverter()->_htmlentities_cleanbadcode($item);
    }

    /** get instance of cs_announcement_manager.
     *
     */
    public function getAnnouncementManager(): cs_announcement_manager
    {
        /* @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->_getInstance('cs_announcement_manager');
    }

    public function getPortfolioManager(): cs_portfolio_manager
    {
        /* @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->_getInstance('cs_portfolio_manager');
    }

    /** get instance of cs_annotation_manager.
     *
     */
    public function getAnnotationManager(): cs_annotations_manager
    {
        /* @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->_getInstance('cs_annotations_manager');
    }

    public function getAssessmentManager(): cs_assessments_manager
    {
        /* @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->_getInstance('cs_assessments_manager');
    }

    /** get instance of cs_disc_manager.
     *
     * @return cs_disc_manager
     */
    public function getDiscManager()
    {
        $name = 'cs_disc_manager';

        if (!isset($this->instance[$name])) {
            $this->instance[$name] = new cs_disc_manager();

            if (!$this->inServer()) {
                $this->instance[$name]->setPortalID($this->getCurrentPortalID());
                $this->instance[$name]->setContextID($this->getCurrentContextID());
            } else {
                $this->instance[$name]->setServerID($this->getServerID());
            }
        }

        return $this->instance[$name];
    }

    public function getTodosManager(): cs_todos_manager
    {
        /* @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->_getInstance('cs_todos_manager');
    }

    public function getDateManager(): cs_dates_manager
    {
        /* @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->_getInstance('cs_dates_manager');
    }

    public function getDatesManager(): cs_dates_manager
    {
        return $this->getDateManager();
    }

    public function getMaterialManager(): cs_material_manager
    {
        /* @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->_getInstance('cs_material_manager');
    }

    public function getSectionManager(): cs_section_manager
    {
        /* @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->_getInstance('cs_section_manager');
    }

    public function getStepManager(): cs_step_manager
    {
        /* @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->_getInstance('cs_step_manager');
    }

    public function getDiscussionManager(): cs_discussion_manager
    {
        /* @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->_getInstance('cs_discussion_manager');
    }

    public function getDiscussionArticlesManager(): cs_discussionarticles_manager
    {
        /* @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->_getInstance('cs_discussionarticles_manager');
    }

    public function getLinkManager(): cs_links_manager
    {
        /* @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->_getInstance('cs_links_manager');
    }

    public function getLinkItemManager(): cs_link_manager
    {
        /* @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->_getInstance('cs_link_manager');
    }

    public function getUserManager(): cs_user_manager
    {
        /* @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->_getInstance('cs_user_manager');
    }

    public function getLabelManager(): cs_labels_manager
    {
        /* @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->_getInstance('cs_labels_manager');
    }

    public function getTopicManager(): cs_topic_manager
    {
        /* @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->_getInstance('cs_topic_manager');
    }

    public function getGroupManager(): cs_group_manager
    {
        /* @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->_getInstance('cs_group_manager');
    }

    public function getLinkModifierItemManager(): cs_link_modifier_item_manager
    {
        /* @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->_getInstance('cs_link_modifier_item_manager');
    }

    public function unsetLinkModifierItemManager(): void
    {
        $this->_unsetInstance('cs_link_modifier_item_manager');
    }

    public function getLinkItemFileManager(): cs_link_item_file_manager
    {
        /* @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->_getInstance('cs_link_item_file_manager');
    }

    public function getCommunityManager(): cs_community_manager
    {
        /* @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->_getInstance('cs_community_manager');
    }

    public function getPrivateRoomManager(): cs_privateroom_manager
    {
        /* @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->_getInstance('cs_privateroom_manager');
    }

    public function getGroupRoomManager(): cs_grouproom_manager
    {
        /* @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->_getInstance('cs_grouproom_manager');
    }

    public function getUserRoomManager(): cs_userroom_manager
    {
        /* @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->_getInstance('cs_userroom_manager');
    }

    public function getProjectManager(): cs_project_manager
    {
        /* @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->_getInstance('cs_project_manager');
    }

    public function getTimeManager(): cs_time_manager
    {
        /* @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->_getInstance('cs_time_manager');
    }

    public function getBuzzwordManager(): cs_buzzword_manager
    {
        /* @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->_getInstance('cs_buzzword_manager');
    }

    public function getFileManager(): cs_file_manager
    {
        /* @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->_getInstance('cs_file_manager');
    }

    public function getReaderManager(): cs_reader_manager
    {
        /* @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->_getInstance('cs_reader_manager');
    }

    public function getRoomManager(): cs_room_manager
    {
        /* @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->_getInstance('cs_room_manager');
    }

    public function getTaskManager(): cs_tasks_manager
    {
        /* @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->_getInstance('cs_tasks_manager');
    }

    public function getTagManager(): cs_tag_manager
    {
        /* @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->_getInstance('cs_tag_manager');
    }

    public function getTag2TagManager(): cs_tag2tag_manager
    {
        /* @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->_getInstance('cs_tag2tag_manager');
    }

    public function getItemManager($force = false): cs_item_manager
    {
        /* @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->_getInstance('cs_item_manager');
    }

    public function getServerManager(): cs_server_manager
    {
        /* @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->_getInstance('cs_server_manager');
    }

    public function getPortalManager(): cs_portal_manager
    {
        /* @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->_getInstance('cs_portal_manager');
    }

    public function getEntryManager(): cs_entry_manager
    {
        /* @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->_getInstance('cs_entry_manager');
    }

    /** get instance of a class, INTERNAL
     * returns a single instance of a class. a reference to the returned object must
     * be assigned, otherwise a copy is created.
     * Example:
     * $news_manager = $enviroment->_getInstance('cs_news_manager');.
     *
     * @param string $name name of the class to be instantiated
     */
    private function _getInstance(string $name): cs_manager|cs_reader_manager
    {
        if (!isset($this->instance[$name])) {
            $this->instance[$name] = new $name($this);
        }
        $this->instance[$name]->resetLimits();
        if (!$this->_cache_on) {
            $this->instance[$name]->resetData();
            $this->instance[$name]->setCacheOff();
        }

        return $this->instance[$name];
    }

    public function _unsetInstance($name)
    {
        if (isset($this->instance[$name])) {
            unset($this->instance[$name]);
        }
    }

    public function unsetAllInstancesExceptTranslator()
    {
        foreach ($this->instance as $instance => $value) {
            if ('translation_object' !== $instance) {
                unset($this->instance[$instance]);
            }
        }
    }

    /**
     * Get an instance of cs_XXX_manager by item type.
     *
     * @param string $type The item type
     */
    public function getManager(string $type): cs_manager|cs_reader_manager|null
    {
        if (empty($type)) {
            return null;
        }

        if (CS_DATE_TYPE == $type) {
            return $this->getDateManager();
        } elseif (CS_TODO_TYPE == $type || 'todos' == $type) {
            return $this->getTodosManager();
        } elseif ('contact' == $type || 'contacts' == $type || CS_USER_TYPE == $type || 'users' == $type || 'account' == $type) {
            return $this->getUserManager();
        } elseif (CS_MATERIAL_TYPE == $type || 'materials' == $type) {
            return $this->getMaterialManager();
        } elseif (CS_ANNOTATION_TYPE == $type || 'annotations' == $type) {
            return $this->getAnnotationManager();
        } elseif (CS_ASSESSMENT_TYPE == $type || 'assessments' == $type) {
            return $this->getAssessmentManager();
        } elseif ('discussion' == $type || 'discussions' == $type) {
            return $this->getDiscussionManager();
        } elseif ('discarticle' == $type || 'discarticles' == $type) {
            return $this->getDiscussionArticlesManager();
        } elseif ('announcements' == $type || CS_ANNOUNCEMENT_TYPE == $type) {
            return $this->getAnnouncementManager();
        } elseif ('portfolio' == $type || CS_PORTFOLIO_TYPE == $type) {
            return $this->getPortfolioManager();
        } elseif (CS_TOPIC_TYPE == $type) {
            return $this->getTopicManager();
        } elseif ('group' == $type || 'groups' == $type) {
            return $this->getGroupManager();
        } elseif ('task' == $type || 'tasks' == $type) {
            return $this->getTaskManager();
        } elseif ('section' == $type) {
            return $this->getSectionManager();
        } elseif ('label' == $type) {
            return $this->getLabelManager();
        } elseif (CS_PROJECT_TYPE == $type) {
            return $this->getProjectManager();
        } elseif (CS_STEP_TYPE == $type) {
            return $this->getStepManager();
        } elseif (CS_ROOM_TYPE == $type) {
            return $this->getRoomManager();
        } elseif (CS_COMMUNITY_TYPE == $type) {
            return $this->getCommunityManager();
        } elseif (CS_PRIVATEROOM_TYPE == $type) {
            return $this->getPrivateRoomManager();
        } elseif (CS_GROUPROOM_TYPE == $type) {
            return $this->getGroupRoomManager();
        } elseif (cs_userroom_item::ROOM_TYPE_USER == $type) {
            return $this->getUserRoomManager();
        } elseif (CS_PORTAL_TYPE == $type) {
            return $this->getPortalManager();
        } elseif (CS_SERVER_TYPE == $type) {
            return $this->getServerManager();
        } elseif (CS_FILE_TYPE == $type) {
            return $this->getFileManager();
        } elseif (CS_LINK_TYPE == $type) {
            return $this->getLinkManager();
        } elseif (CS_LINKITEM_TYPE == $type) {
            return $this->getLinkItemManager();
        } elseif (CS_LINKMODITEM_TYPE == $type) {
            return $this->getLinkModifierItemManager();
        } elseif (CS_LINKITEMFILE_TYPE == $type) {
            return $this->getLinkItemFileManager();
        } elseif (CS_ITEM_TYPE == $type || 'items' == $type) {
            return $this->getItemManager();
        } elseif (CS_READER_TYPE == $type) {
            return $this->getReaderManager();
        } elseif (CS_TIME_TYPE == $type) {
            return $this->getTimeManager();
        } elseif (CS_TAG_TYPE == $type) {
            return $this->getTagManager();
        } elseif (CS_TAG2TAG_TYPE == $type) {
            return $this->getTag2TagManager();
        } elseif (CS_BUZZWORD_TYPE == $type) {
            return $this->getBuzzwordManager();
        } elseif (CS_ENTRY_TYPE == $type) {
            return $this->getEntryManager();
        } else {
            throw new LogicException('do not know this type [' . $type . ']');
        }
    }

    /** get boolean, if you are in the community room or not.
     *
     * @return boolean, true  = you are in the community room
     *                  false = you are not in the community room
     */
    public function inCommunityRoom()
    {
        $context_item = $this->getCurrentContextItem();

        return $context_item->isCommunityRoom();
    }

    /** get boolean, if you are in the private room or not.
     *
     * @return boolean, true  = you are in the private room
     *                  false = you are not in the private room
     */
    public function inPrivateRoom()
    {
        $context_item = $this->getCurrentContextItem();

        return $context_item->isPrivateroom();
    }

    public function isContextOpenForGuests()
    {
        $context_item = $this->getCurrentContextItem();

        return $context_item->isOpenForGuests();
    }

    /** get boolean, if you are in a group room or not.
     *
     * @return boolean, true  = you are in a group room
     *                  false = you are not in a group room
     */
    public function inGroupRoom()
    {
        $context_item = $this->getCurrentContextItem();

        return $context_item->isGroupRoom();
    }

    /** get boolean, if you are in a user room or not.
     *
     * @return boolean, true  = you are in a user room
     *                  false = you are not in a user room
     */
    public function inUserroom()
    {
        $context_item = $this->getCurrentContextItem();

        return $context_item->isUserroom();
    }

    /** get boolean, if you are in a project room or not.
     *
     * @return boolean, true  = you are in a project room
     *                  false = you are not in a project room
     */
    public function inProjectRoom()
    {
        $context_item = $this->getCurrentContextItem();

        return $context_item->isProjectRoom();
    }

    /** get boolean, if you are in a portal or not.
     *
     * @return boolean, true  = you are in a portal
     *                  false = you are not in a portal
     */
    public function inPortal()
    {
        $context_item = $this->getCurrentContextItem();

        return $context_item->isPortal();
    }

    /** get boolean, if you are in a server or not.
     *
     * @return boolean, true  = you are in a server
     *                  false = you are not in a server
     */
    public function inServer()
    {
        $context_item = $this->getCurrentContextItem();

        return $context_item->isServer();
    }

    /** get Instance of the translation object
     * returns an object for translation of message tags.
     *
     * @return \cs_translator
     */
    public function getTranslationObject()
    {
        global $dont_resolve_messagetags;

        if (!isset($this->instance['translation_object'])) {
            $this->instance['translation_object'] = new cs_translator();
            if ($dont_resolve_messagetags) {
                $this->instance['translation_object']->dontResolveMessageTags();
            }
            $this->instance['translation_object']->setSelectedLanguage($this->getSelectedLanguage());
            $context_item = $this->getCurrentContextItem();
            if ($this->inCommunityRoom()) {
                $this->instance['translation_object']->setContext('community');
                $portal_item = $context_item->getContextItem();
                $this->instance['translation_object']->setTimeMessageArray($portal_item->getTimeTextArray());
            } elseif ($this->inProjectRoom()) {
                $this->instance['translation_object']->setContext('project');
                $portal_item = $context_item->getContextItem();
                $this->instance['translation_object']->setTimeMessageArray($portal_item->getTimeTextArray());
            } elseif ($this->inGroupRoom()) {
                $this->instance['translation_object']->setContext(CS_GROUPROOM_TYPE);
                $portal_item = $context_item->getContextItem();
                $this->instance['translation_object']->setTimeMessageArray($portal_item->getTimeTextArray());
            } elseif ($this->inUserroom()) {
                $this->instance['translation_object']->setContext(cs_userroom_item::ROOM_TYPE_USER);
                $portal_item = $context_item->getPortalItem();
                $this->instance['translation_object']->setTimeMessageArray($portal_item->getTimeTextArray());
            } elseif ($this->inPrivateRoom()) {
                $this->instance['translation_object']->setContext('private');
                $portal_item = $context_item->getContextItem();
                $this->instance['translation_object']->setTimeMessageArray($portal_item->getTimeTextArray());
            } elseif ($this->inPortal()) {
                $this->instance['translation_object']->setContext('portal');
                $this->instance['translation_object']->setTimeMessageArray($context_item->getTimeTextArray());
            } else {
                $this->instance['translation_object']->setContext('server');
            }
            if (isset($context_item)) {
                $this->instance['translation_object']->setRubricTranslationArray($context_item->getRubricTranslationArray());
                $this->instance['translation_object']->setEmailTextArray($context_item->getEmailTextArray());
            }
        }

        // we need sometimes the language, even if the user is unknown at that time
        // so we must change the language, when we know the user and selected language has changed
        else {
            $language_now = $this->getSelectedLanguage();
            $language_stored = $this->instance['translation_object']->getSelectedLanguage();
            if ($language_now != $language_stored) {
                $this->instance['translation_object']->setSelectedLanguage($language_now);
            }
        } // end of if statement

        return $this->instance['translation_object'];
    }

    public function getSelectedLanguage(): string
    {
        return $this->getUserLanguage();
    }

    public function getUserLanguage(): string
    {
        /** @var ContainerInterface $symfonyContainer */
        global $symfonyContainer;

        /** @var RequestStack $requestStack */
        $requestStack = $symfonyContainer->get('request_stack');
        $request = $requestStack->getCurrentRequest();
        if ($request) {
            return $request->getLocale();
        }

        return 'de';
    }

    public function getRootUserItem()
    {
        $user_manager = $this->getUserManager();

        return $user_manager->getRootUser();
    }

    public function getRootUserItemID()
    {
        $retour = null;
        $root_user = $this->getRootUserItem();
        if (isset($root_user)) {
            $item_id = $root_user->getItemID();
            if (!empty($item_id)) {
                $retour = $item_id;
            }
            unset($root_user);
        }

        return $retour;
    }

    public function getDBConnector(): db_mysql_connector
    {
        if (empty($this->_db_mysql_connector)) {
            $this->_db_mysql_connector = new db_mysql_connector();
            $this->_db_mysql_connector->setLogQueries();
        }

        return $this->_db_mysql_connector;
    }

    public function setCacheOff()
    {
        $this->_cache_on = false;
    }

    public function getConfiguration($var)
    {
        global ${$var};
        $retour = null;
        if (isset(${$var})) {
            $retour = ${$var};
        }

        return $retour;
    }

    public function getTextConverter(): misc_text_converter
    {
        if (!isset($this->_misc_text_converter)) {
            $this->_misc_text_converter = new misc_text_converter(['environment' => $this]);
        }

        return $this->_misc_text_converter;
    }

    public function changeContextToPrivateRoom($contextId = null)
    {
        $currentUser = $this->getCurrentUserItem();
        $privateRoomItem = $currentUser->getOwnRoom();
        $privateRoomContextID = $privateRoomItem->getItemID();

        $contextIdToSet = $contextId ?: $privateRoomContextID;

        // set new context information and reset the loaded manager
        $this->setCurrentContextID($contextIdToSet);
        $this->setCurrentContextItem($privateRoomItem);
        $this->setCurrentUserItem($currentUser->getRelatedPrivateRoomUserItem());
        $this->unsetAllInstancesExceptTranslator();
    }

    public function getSymfonyContainer(): ContainerInterface
    {
        global $symfonyContainer;

        return $symfonyContainer;
    }
}
