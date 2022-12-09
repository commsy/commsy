<?PHP
// $Id$
//
// Release $Name$
//
// Copyright (c)2002-2003 Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
// Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
// Edouard Simon, Monique Strauss, Jose Mauel Gonzalez Vazquez, Johannes Schultze
//
//    This file is part of CommSy.
//
//    CommSy is free software; you can redistribute it and/or modify
//    it under the terms of the GNU General Public License as published by
//    the Free Software Foundation; either version 2 of the License, or
//    (at your option) any later version.
//
//    CommSy is distributed in the hope that it will be useful,
//    but WITHOUT ANY WARRANTY; without even the implied warranty of
//    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//    GNU General Public License for more details.
//
//    You have received a copy of the GNU General Public License
//    along with CommSy.

/** upper class of all managers
 */

use App\Entity\Portal;
use App\Helper\SessionHelper;
use App\Proxy\PortalProxy;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

include_once('classes/cs_manager.php');
include_once('functions/text_functions.php');
include_once('classes/cs_list.php');
include_once('classes/cs_userroom_item.php');

   /** This class returns an instance of a cs_mananger subclass on request.
   *It also contains often needed environment variables.
   */
class cs_environment {
  /**
   * array - containing the objects
   */
   var $instance = array();
  /**
   * cs_user_item - containing the current user
   */
   var $current_user;
   var $_portal_user = NULL;

  /**
   * integer - id of current room
   */
   var $current_context_id = 0;
  /**
   * cs_context_item - current room
   */
   var $current_context = NULL;

  /**
   * @var \cs_portal_item portal item
   */
   var $_current_portal = NULL;

   var $_current_portal_id = 0;

    /**
     * @var \cs_server_item server item
     */
   var $_server_item = NULL;

   var $_server_id = 99;

  /**
   * string - current module name
   */
   var $current_module;
  /**
   * string - current function name
   */
   var $current_function;

  /**
   * string - current parameter of the page
   */
   var $_current_parameter_string = NULL;
   var $_current_parameter_array = NULL;

  /**
   * string - selected language of the current user
   */
   var $_selected_language = '';

   var $_browser = NULL;
   var $_browser_version = NULL;

   var $_plugin_class_array = NULL;
   var $_rubric_plugin_class_list = NULL;

    private $_db_mysql_connector = NULL;
   private $_cache_on = true;
   private $_output_mode = 'html';
   private $_misc_text_converter = NULL;
   private $_class_factory = NULL;

   # multi master implementation
   private $_db_portal_id = 0;

  /** constructor: cs_environment
   * the only available constructor, initial values for internal variables
   */


  /** get the current user
   * returns the current user. If there is no current user it will be returned an emtpy user_item.
   *
   * @return \cs_user_item
   */
   function getCurrentUserItem () {
      if ( !isset($this->current_user) ) {
         require_once('classes/cs_user_item.php');
         $this->current_user = new cs_user_item($this);
      }
      return $this->current_user;
   }

   function getPortalUserItem () {
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
            if ($list->isNotEmpty() and $list->getCount() == 1) {
               $this->_portal_user = $list->getFirst();
            }
         }
      }
      return $this->_portal_user;
   }

   function getCurrentUser () {
      return $this->getCurrentUserItem();
   }

   function setCurrentUser ($current_user) {
       $this->setCurrentUserItem($current_user);
   }

   function setCurrentUserItem ($current_user) {
       $this->current_user = $current_user;
       $this->unsetSelectedLanguage();
   }

   function getCurrentUserID () {
      $current_user = $this->getCurrentUserItem();
      return $current_user->getItemID();
   }

  /** get id of the current room
   * returns the current room id.
   *
   * @return integer        current_context_id
   *
   * @author CommSy Development Group
   */
   function getCurrentContextID() {
      return $this->current_context_id;
   }

  /** set id of the current room
   * sets the current room id.
   *
   * @var integer        id
   */
   function setCurrentContextID($id) {
      $this->current_context_id = $id;
   }

  /** set id of the current room
   * sets the current room as object.
   *
   * @var object value context item
   */
   public function setCurrentContextItem ($value) {
      $this->current_context = $value;
   }

    /** get the current room item
     * current context id must be set
     *
     * @return cs_context_item
     */
    public function getCurrentContextItem()
    {
        if (
            $this->current_context_id === null ||
            $this->current_context_id === 0 ||
            $this->current_context_id == $this->getServerID()
        ) {
            $this->current_context_id = $this->getServerID();
            $this->current_context = $this->getServerItem();
        }

        if ($this->current_context === null || $this->current_context->getItemID() != $this->current_context_id) {
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
                include_once('functions/error_functions.php');
                trigger_error('can not initiate room [' . $this->current_context_id . '] -> bug in item table',
                    E_USER_ERROR);
            }

            if (!empty($manager) && is_object($manager)) {
                $this->current_context = $manager->getItem($this->current_context_id);
            }
        }

        return $this->current_context;
    }

  /** get server object
   * returns the server object.
   *
   * @return \cs_server_item server item
   */
   function getServerItem () {
      if (!isset($this->_server_item)) {
         $manager = $this->getServerManager();
         $this->_server_item = $manager->getItem($this->_server_id);
      }
      return $this->_server_item;
   }

   function getServerID () {
      return $this->_server_id;
   }

  /** get portal object
   * returns the portal object.
   *
   * @return \cs_portal_item portal item
   */
   public function getCurrentPortalItem()
   {
       if ($this->_current_portal) {
           return $this->_current_portal;
       }

       if (empty($this->_current_portal_id)) {
           $contextItem = $this->getCurrentContextItem();
           if ( $contextItem->isServer() ) {
               $this->_current_portal = NULL;
           } elseif ( $contextItem->isPortal() ) {
               $this->_current_portal = $contextItem;
           } else {
               $currentPortalId = $contextItem->getContextID();

               if ($contextItem->getType() === cs_userroom_item::ROOM_TYPE_USER) {
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

   function getCurrentPortalID () {
      if ( empty($this->_current_portal_id) ) {
         $this->getCurrentPortalItem();
      }
      return $this->_current_portal_id;
   }

   function setCurrentPortalID ( $value ) {
      $this->_current_portal_id = (int)$value;
   }

  /** get name of the current module
   * returns the current module.
   *
   * @return string  current_module
   *
   * @author CommSy Development Group
   */
   function getCurrentModule() {
      return $this->current_module;
   }

  /** set name of the current module
   * set the current module.
   *
   * @var string        module
   *
   * @author CommSy Development Group
   */
   function setCurrentModule($module) {
      $this->current_module = $module;
   }

  /** get name of the current function
   * returns the current function.
   *
   * @return string        current_function
   *
   * @author CommSy Development Group
   */
   function getCurrentFunction() {
      return $this->current_function;
   }

  /** set name of the current function
   * set the current function.
   *
   * @var string        function
   *
   * @author CommSy Development Group
   */
   function setCurrentFunction($function) {
      $this->current_function = $function;
   }

  /** get string of the current parameter of the page
   * returns the current function.
   *
   * @return string        current parameter
   *
   * @author CommSy Development Group
   */
   function getCurrentParameterString () {
      if ( !isset($this->_current_parameter_string) ) {
         $array = $this->_getCurrentParameterArray();
         if (!empty($array)) {
            $this->_current_parameter_string = implode('&',$array);
         } else {
            $this->_current_parameter_string = '';
         }
      }
      return $this->_current_parameter_string;
   }

   function getCurrentParameterStringWithout ($value) {
      $retour = '';
      $array = $this->_getCurrentParameterArray();
      if (!empty($array)) {
         $result_array = array();
         foreach ($array as $parameter) {
            if ( !mb_stristr($parameter,$value) ) {
               $result_array[] = $parameter;
            }
         }
         $retour = implode('&',$result_array);
      }
      return $retour;
   }

   function getValueOfParameter ($parameter) {
      $value = '';
      $array = $this->_getCurrentParameterArray();
      if (!empty($array)) {
         foreach ( $array as $current_parameter ) {
            if (mb_stristr($current_parameter,$parameter.'=')) {
               $temp_array = explode('=',$current_parameter);
               if ( $temp_array[0] == $parameter ) {
                  $value = $temp_array[1];
               }
            }
         }
      }
      return $value;
   }

   function _getCurrentParameterArray () {
      global $_SERVER;

      if (!isset($this->_current_parameter_array)) {
         $this->_current_parameter_array = array();
         if (isset($_SERVER['QUERY_STRING'])) {
            $retour = explode('&',encode(FROM_GET,$_SERVER['QUERY_STRING']));

            // GetParameterSäubern
            $textConverter = $this->getTextConverter();
            // delete cid, mod and fct
            $tmpRetour = array();
            foreach ($retour as $param) {
            	if (empty($param)) continue;

            	list($key, $value) = explode("=", $param);

            	if ($key !== 'cid' && $key !== 'mod' && $key !== 'fct') {
            		$tmpRetour[] = $key . "=" . $textConverter->_htmlentities_cleanbadcode($value);
            		//$tmpRetour[] = $key . "=" . $value;
            	}
            }



            $retour = $tmpRetour;

            /*
            $go_on = true;
            while ($go_on and isset($retour[0])) {
               if (mb_stristr($retour[0],'cid=') or mb_stristr($retour[0],'mod=') or mb_stristr($retour[0],'fct=') ) {
                  array_shift($retour);
               } else {
                  $go_on = false;
               }
            }
            */

            // delete SID or empty array element
            if (count($retour) > 0) {
               $retour2 = array();
               foreach ($retour as $element) {
                  if (!mb_stristr($element,'SID') and !empty($element)) {
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

   function getCurrentParameterArrayWithout ($value) {
      $parameter_array = $this->_getCurrentParameterArray();
      $retour = array();
      if ( count($parameter_array) > 0 ) {
         foreach ($parameter_array as $parameter) {
            $temp_parameter_array = explode('=',$parameter);
            if ($value != $temp_parameter_array[0]) {
               $retour[$temp_parameter_array[0]] = $temp_parameter_array[1];
            }
         }
      }
      return $retour;
   }

   function getCurrentPostParameterArray () {
      global $_POST;
      $retour = $_POST;
      return $retour;
   }

   function getCurrentParameterArray () {
      $parameter_array = $this->_getCurrentParameterArray();
      $retour = array();
      if ( count($parameter_array) > 0 ) {
         foreach ($parameter_array as $parameter) {
            $temp_parameter_array = explode('=',$parameter);
            if ( !empty($temp_parameter_array[1]) ) {
               $retour[$temp_parameter_array[0]] = $temp_parameter_array[1];
            } else {
               $retour[$temp_parameter_array[0]] = '';
            }
         }
      }
      $translator = $this->getTranslationObject();
      if (isset($retour['search']) and ($retour['search'] == $translator->getMessage('COMMON_SEARCH_IN_ROOM') || $retour['search'] == $translator->getMessage('COMMON_SEARCH_IN_RUBRIC'))){
         unset($retour['search']);
      }
       array_walk_recursive($retour, array($this, 'cleanBadCode'));
      return $retour;
   }

   function cleanBadCode (&$item, $key){
		$item = $this->getTextConverter()->_htmlentities_cleanbadcode($item);
   }

   function setCurrentParameter ( $key, $value ) {
      $this->_current_parameter_array[] = $key.'='.$value;
   }

   function removeCurrentParameter($del_key) {
	   $temp_array = array();
	   foreach($this->_current_parameter_array as $param) {
		   list($key, $value) = explode('=', $param);

		   if($key !== $del_key) $temp_array[] = $param;
	   }

	   $this->_current_parameter_array = $temp_array;
   }

  /** get instance of cs_announcement_manager
   *
   * @return cs_announcement_manager
   * @access public
   */
   public function getAnnouncementManager(): cs_announcement_manager
   {
       /** @noinspection PhpIncompatibleReturnTypeInspection */
       return $this->_getInstance('cs_announcement_manager');
   }

    /**
     * @return cs_portfolio_manager
     */
   public function getPortfolioManager(): cs_portfolio_manager
   {
      return $this->_getInstance('cs_portfolio_manager');
   }

  /** get instance of cs_annotation_manager
   *
   * @return cs_annotations_manager
   * @access public
   */
   public function getAnnotationManager(): cs_annotations_manager
   {
       /** @noinspection PhpIncompatibleReturnTypeInspection */
       return $this->_getInstance('cs_annotations_manager');
   }

    /**
     * @return cs_assessments_manager
     */
   public function getAssessmentManager(): cs_assessments_manager
   {
       /** @noinspection PhpIncompatibleReturnTypeInspection */
       return $this->_getInstance('cs_assessments_manager');
   }

  /** get instance of cs_disc_manager
   *
   * @return cs_disc_manager
   * @access public
   */
   function getDiscManager() {
      $name = 'cs_disc_manager';
      if (!isset($this->instance[$name])) {
         $file = realpath(dirname(__FILE__)) . '/'.$name.'.php';
         if ( file_exists($file) ) {
            require_once($file);
         } else {
            $path = $this->getConfiguration('c_commsy_path_file');
            $current_path = getcwd();
            if ( $current_path != $path
                 and file_exists($path.'/'.$file)
               ) {
               require_once($path.'/'.$file);
            } else {
               include_once('functions/error_functions.php');
               trigger_error('can\'t find '.$file.' - current path: '.$current_path.' - config path: '.$path,E_USER_ERROR);
            }
         }
         $this->instance[$name] = new $name($this->getCurrentPortalID(),$this->getCurrentContextID());
         if (!$this->inServer()) {
            $this->instance[$name]->setPortalID($this->getCurrentPortalID());
            $this->instance[$name]->setContextID($this->getCurrentContextID());
         } else {
            $this->instance[$name]->setServerID($this->getServerID());
         }
      }
      return $this->instance[$name];
   }

  /** get instance of cs_todo_manager
   *
   * @return cs_todos_manager
   * @access public
   */
   public function getTodosManager(): cs_todos_manager
   {
      return $this->getTodoManager();
   }

   public function getTodoManager(): cs_todos_manager
   {
       /** @noinspection PhpIncompatibleReturnTypeInspection */
       return $this->_getInstance('cs_todos_manager');
   }

  /** get instance of cs_dates_manager
   *
   * @return cs_dates_manager
   * @access public
   */
   public function getDateManager(): cs_dates_manager
   {
       /** @noinspection PhpIncompatibleReturnTypeInspection */
       return $this->_getInstance('cs_dates_manager');
   }

   /** get instance of cs_dates_manager
   *
   * @return cs_dates_manager
   * @access public
   */
   public function getDatesManager(): cs_dates_manager
   {
      return $this->getDateManager();
   }

  /** get instance of cs_material_manager
   *
   * @return cs_material_manager
   * @access public
   */
   public function getMaterialManager()
   {
       /** @noinspection PhpIncompatibleReturnTypeInspection */
       return $this->_getInstance('cs_material_manager');
   }

  /** get instance of cs_ftsearch_manager
   *
   * @return cs_ftsearch_manager
   * @access public
   */
   function getFTSearchManager() {
      return $this->_getInstance('cs_ftsearch_manager');
   }

  /** get instance of cs_section_manager
   *
   * @return cs_section_manager
   * @access public
   */
   public function getSectionManager(): cs_section_manager
   {
       /** @noinspection PhpIncompatibleReturnTypeInspection */
       return $this->_getInstance('cs_section_manager');
   }

    /**
     * @return cs_step_manager
     */
   public function getStepManager(): cs_step_manager
   {
       /** @noinspection PhpIncompatibleReturnTypeInspection */
       return $this->_getInstance('cs_step_manager');
   }

  /** get instance of cs_discussion_manager
   *
   * @return cs_discussion_manager
   * @access public
   */
   public function getDiscussionManager(): cs_discussion_manager
   {
       /** @noinspection PhpIncompatibleReturnTypeInspection */
       return $this->_getInstance('cs_discussion_manager');
   }

  /** get instance of cs_discussion_manager, DON't USE !!!
   * USE: getDiscussionArticleManager
   *
   * @return cs_discussionarticles_manager
   * @access public
   */
   public function getDiscussionArticlesManager(): cs_discussionarticles_manager
   {
      return $this->getDiscussionArticleManager();
   }

  /** get instance of cs_discussion_manager
   *
   * @return cs_discussionarticles_manager
   * @access public
   */
   public function getDiscussionArticleManager(): cs_discussionarticles_manager
   {
       /** @noinspection PhpIncompatibleReturnTypeInspection */
       return $this->_getInstance('cs_discussionarticles_manager');
   }

  /** get instance of cs_links_manager
   *
   * @return cs_links_manager
   * @access public
   */
   public function getLinkManager(): cs_links_manager
   {
       /** @noinspection PhpIncompatibleReturnTypeInspection */
       return $this->_getInstance('cs_links_manager');
   }

  /** get instance of cs_link_manager
   *
   * @return cs_link_manager
   * @access public
   */
   public function getLinkItemManager(): cs_link_manager
   {
       /** @noinspection PhpIncompatibleReturnTypeInspection */
       return $this->_getInstance('cs_link_manager');
   }

    /** get instance of cs_user_manager
     *
     * @return cs_user_manager
     * @access public
     */
    public function getUserManager(): cs_user_manager
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->_getInstance('cs_user_manager');
    }

  /** get instance of cs_labels_manager
   *
   * @return cs_labels_manager
   * @access public
   */
   public function getLabelManager(): cs_labels_manager
   {
       /** @noinspection PhpIncompatibleReturnTypeInspection */
       return $this->_getInstance('cs_labels_manager');
   }

    /** get instance of cs_topic_manager
     *
     * @return cs_topic_manager
     * @access public
     */
    public function getTopicManager(): cs_topic_manager
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->_getInstance('cs_topic_manager');
    }

    /**
     * @return cs_group_manager
     */
    public function getGroupManager() : cs_group_manager
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->_getInstance('cs_group_manager');
    }

   /** get instance of cs_link_modifier_item_manager
   *
   * @return cs_link_modifier_item_manager
   * @access public
   */
   public function getLinkModifierItemManager(): cs_link_modifier_item_manager
   {
       /** @noinspection PhpIncompatibleReturnTypeInspection */
       return $this->_getInstance('cs_link_modifier_item_manager');
   }

   public function unsetLinkModifierItemManager(): void
   {
       $this->_unsetInstance('cs_link_modifier_item_manager');
   }

  /** get instance of cs_link_item_file_manager
   *
   * @return cs_link_item_file_manager
   * @access public
   */
   public function getLinkItemFileManager(): cs_link_item_file_manager
   {
       /** @noinspection PhpIncompatibleReturnTypeInspection */
       return $this->_getInstance('cs_link_item_file_manager');
   }

  /** get instance of cs_community_manager
   *
   * @return cs_Community_manager
   * @access public
   */
   public function getCommunityManager(): cs_community_manager
   {
       /** @noinspection PhpIncompatibleReturnTypeInspection */
       return $this->_getInstance('cs_community_manager');
   }

  /** get instance of cs_privateroom_manager
   *
   * @return cs_PrivateRoom_manager
   * @access public
   */
   function getPrivateRoomManager () {
      return $this->_getInstance('cs_privateroom_manager');
   }

  /** get instance of cs_grouproom_manager
   *
   * @return cs_grouproom_manager
   * @access public
   */
   public function getGroupRoomManager(): cs_grouproom_manager
   {
       /** @noinspection PhpIncompatibleReturnTypeInspection */
       return $this->_getInstance('cs_grouproom_manager');
   }

  /** get instance of cs_userroom_manager
   *
   * @return cs_userroom_manager
   * @access public
   */
   public function getUserRoomManager(): cs_userroom_manager
   {
       /** @noinspection PhpIncompatibleReturnTypeInspection */
       return $this->_getInstance('cs_userroom_manager');
   }

  /** get instance of cs_myroom_manager
   *
   * @return cs_myroom_manager
   * @access public
   */
   public function getMyRoomManager(): cs_myroom_manager
   {
       /** @noinspection PhpIncompatibleReturnTypeInspection */
       return $this->_getInstance('cs_myroom_manager');
   }

    /** get instance of cs_log_manager
     *
     * @return cs_log_manager
     * @access public
     */
    public function getLogManager(): cs_log_manager
    {
        return $this->_getInstance('cs_log_manager');
    }

    /** get instance of cs_log_manager
     *
     * @return cs_log_archive_manager
     * @access public
     */
    public function getLogArchiveManager(): cs_log_archive_manager
    {
        return $this->_getInstance('cs_log_archive_manager');
    }

  /** get instance of cs_log_error_manager
   *
   * @return cs_log_error_manager
   * @access public
   */
   function getLogErrorManager() {
      return $this->_getInstance('cs_log_error_manager');
   }

  /** get instance of cs_project_manager
   *
   * @return cs_project_manager
   * @access public
   */
   public function getProjectManager(): cs_project_manager
   {
       /** @noinspection PhpIncompatibleReturnTypeInspection */
       return $this->_getInstance('cs_project_manager');
   }

  /** get instance of cs_time_manager
   *
   * @return cs_time_manager
   * @access public
   */
   public function getTimeManager(): cs_time_manager
   {
       /** @noinspection PhpIncompatibleReturnTypeInspection */
       return $this->_getInstance('cs_time_manager');
   }

  /** get instance of cs_buzzword_manager
   *
   * @return cs_buzzword_manager
   * @access public
   */
   public function getBuzzwordManager(): cs_buzzword_manager
   {
       /** @noinspection PhpIncompatibleReturnTypeInspection */
       return $this->_getInstance('cs_buzzword_manager');
   }

  /** get instance of cs_file_manager
   *
   * @return cs_file_manager
   * @access public
   */
   public function getFileManager(): cs_file_manager
   {
       /** @noinspection PhpIncompatibleReturnTypeInspection */
       return $this->_getInstance('cs_file_manager');
   }

  /** get instance of cs_reader_manager
   *
   * @return cs_reader_manager
   * @access public
   */
   public function getReaderManager(): cs_reader_manager
   {
       /** @noinspection PhpIncompatibleReturnTypeInspection */
       return $this->_getInstance('cs_reader_manager');
   }

    /** get instance of cs_noticed_manager
     *
     * @return cs_noticed_manager
     * @access public
     */
    public function getNoticedManager(): cs_noticed_manager
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->_getInstance('cs_noticed_manager');
    }

  /** get instance of cs_room_manager
   *
   * @return cs_room_manager
   * @access public
   */
   public function getRoomManager(): cs_room_manager
   {
       /** @noinspection PhpIncompatibleReturnTypeInspection */
       return $this->_getInstance('cs_room_manager');
   }

  /** get instance of cs_task_manager
   *
   * @return cs_tasks_manager
   * @access public
   */
   public function getTaskManager(): cs_tasks_manager
   {
       /** @noinspection PhpIncompatibleReturnTypeInspection */
       return $this->_getInstance('cs_tasks_manager');
   }

  /** get instance of cs_tag_manager
   *
   * @return cs_tag_manager
   * @access public
   */
   public function getTagManager(): cs_tag_manager
   {
       /** @noinspection PhpIncompatibleReturnTypeInspection */
       return $this->_getInstance('cs_tag_manager');
   }

  /** get instance of cs_tag2tag_manager
   *
   * @return cs_tag2tag_manager
   * @access public
   */
   public function getTag2TagManager(): cs_tag2tag_manager
   {
       /** @noinspection PhpIncompatibleReturnTypeInspection */
       return $this->_getInstance('cs_tag2tag_manager');
   }

  /** get instance of cs_item_manager
   *
   * @return cs_item_manager
   * @access public
   */
   public function getItemManager($force = false): cs_item_manager
   {
       /** @noinspection PhpIncompatibleReturnTypeInspection */
       return $this->_getInstance('cs_item_manager');
   }

  /** get instance of cs_server_manager
   *
   * @return cs_server_manager
   * @access public
   */
   public function getServerManager(): cs_server_manager
   {
       /** @noinspection PhpIncompatibleReturnTypeInspection */
       return $this->_getInstance('cs_server_manager');
   }

  /** get instance of cs_portal_manager
   *
   * @return cs_portal_manager
   * @access public
   */
   public function getPortalManager(): cs_portal_manager
   {
       /** @noinspection PhpIncompatibleReturnTypeInspection */
       return $this->_getInstance('cs_portal_manager');
   }

  /** get instance of cs_hash_manager
   *
   * @return cs_hash_manager
   * @access public
   */
   public function getHashManager(): cs_hash_manager
   {
       /** @noinspection PhpIncompatibleReturnTypeInspection */
       return $this->_getInstance('cs_hash_manager');
   }

   function getExternalIdManager() {
      return $this->_getInstance('cs_external_id_manager');
   }

   function getEntryManager() {
      return $this->_getInstance('cs_entry_manager');
   }

    /**
     * @deprecated
     */
   public function getSessionManager()
   {
       throw new LogicException('Calling cs_environment::getSessionManager is no longer supported');
   }

    /**
     * @deprecated
     */
   public function getSessionID ()
   {
       throw new LogicException('Calling cs_environment::getSessionID is no longer supported');
   }

  /** get instance of a class, INTERNAL
   * returns a single instance of a class. a reference to the returned object must
   * be assigned, otherwise a copy is created.
   * Example:
   * $news_manager = $enviroment->_getInstance('cs_news_manager');
   *
   * @param string           name      name of the class to be instantiated
   * @return cs_manager
   * @access private
   */
   function _getInstance($name) {
      if ( !isset($this->instance[$name]) ) {
         $file = realpath(dirname(__FILE__)) . '/'.$name.'.php';
         if ( file_exists($file) ) {
            require_once($file);
         } else {
            $path = $this->getConfiguration('c_commsy_path_file');
            $current_path = getcwd();
            if ( $current_path != $path
                 and file_exists($path.'/'.$file)
               ) {
               require_once($path.'/'.$file);
            } else {
               include_once('functions/error_functions.php');
               trigger_error('can\'t find '.$file.' - current path: '.$current_path.' - config path: '.$path,E_USER_ERROR);
            }
         }
         $this->instance[$name] = new $name($this);
      }
      $this->instance[$name]->resetLimits();
      if ( !$this->_cache_on ) {
         $this->instance[$name]->resetData();
         $this->instance[$name]->setCacheOff();
      }
      return $this->instance[$name];
   }

   function _unsetInstance($name) {
      if ( isset($this->instance[$name]) ) {
      	unset($this->instance[$name]);
      }
   }

   function unsetAllInstancesExceptTranslator() {
   	foreach($this->instance as $instance => $value) {
   		if ($instance !== "translation_object") {
   			unset($this->instance[$instance]);
   		}
   	}
   }

    /** get instance of cs_XXX_manager by item_type
     *
     * @param string type of an item
     * @return object|null
     * @access public
     */
    public function getManager($type): ?object
    {
        if (!empty($type)) {
            if ($type == CS_DATE_TYPE) {
                return $this->getDateManager();
            } elseif ($type == CS_TODO_TYPE || $type == 'todos') {
                return $this->getTodosManager();
            } elseif ($type == 'contact' || $type == 'contacts' || $type == CS_USER_TYPE || $type == 'users' || $type == 'account') {
                return $this->getUserManager();
            } elseif ($type == CS_MATERIAL_TYPE || $type == 'materials') {
                return $this->getMaterialManager();
            } elseif ($type == CS_ANNOTATION_TYPE || $type == 'annotations') {
                return $this->getAnnotationManager();
            } elseif ($type == CS_ASSESSMENT_TYPE || $type == 'assessments') {
                return $this->getAssessmentManager();
            } elseif ($type == 'discussion' || $type == 'discussions') {
                return $this->getDiscussionManager();
            } elseif ($type == 'discarticle' || $type == 'discarticles') {
                return $this->getDiscussionArticlesManager();
            } elseif ($type == 'announcements' || $type == CS_ANNOUNCEMENT_TYPE) {
                return $this->getAnnouncementManager();
            } elseif ($type == 'portfolio' || $type == CS_PORTFOLIO_TYPE) {
                return $this->getPortfolioManager();
            } elseif ($type == CS_TOPIC_TYPE) {
                return $this->getTopicManager();
            } elseif ($type == 'group' || $type == 'groups') {
                return $this->getGroupManager();
            } elseif ($type == 'task' || $type == 'tasks') {
                return $this->getTaskManager();
            } elseif ($type == 'section') {
                return $this->getSectionManager();
            } elseif ($type == 'label') {
                return $this->getLabelManager();
            } elseif ($type == 'log') {
                return $this->getLogManager();
            } elseif ($type == 'log_archive') {
                return $this->getLogArchiveManager();
            } elseif ($type == CS_PROJECT_TYPE) {
                return $this->getProjectManager();
            } elseif ($type == CS_STEP_TYPE) {
                return $this->getStepManager();
            } elseif ($type == CS_ROOM_TYPE) {
                return $this->getRoomManager();
            } elseif ($type == CS_COMMUNITY_TYPE) {
                return $this->getCommunityManager();
            } elseif ($type == CS_PRIVATEROOM_TYPE) {
                return $this->getPrivateRoomManager();
            } elseif ($type == CS_GROUPROOM_TYPE) {
                return $this->getGroupRoomManager();
            } elseif ($type == cs_userroom_item::ROOM_TYPE_USER) {
                return $this->getUserRoomManager();
            } elseif ($type == CS_MYROOM_TYPE) {
                return $this->getMyRoomManager();
            } elseif ($type == CS_PORTAL_TYPE) {
                return $this->getPortalManager();
            } elseif ($type == CS_SERVER_TYPE) {
                return $this->getServerManager();
            } elseif ($type == CS_FILE_TYPE) {
                return $this->getFileManager();
            } elseif ($type == CS_LINK_TYPE) {
                return $this->getLinkManager();
            } elseif ($type == CS_LINKITEM_TYPE) {
                return $this->getLinkItemManager();
            } elseif ($type == CS_LINKMODITEM_TYPE) {
                return $this->getLinkModifierItemManager();
            } elseif ($type == CS_LINKITEMFILE_TYPE) {
                return $this->getLinkItemFileManager();
            } elseif ($type == CS_ITEM_TYPE || $type == 'items') {
                return $this->getItemManager();
            } elseif ($type == CS_READER_TYPE) {
                return $this->getReaderManager();
            } elseif ($type == CS_NOTICED_TYPE) {
                return $this->getNoticedManager();
            } elseif ($type == CS_TIME_TYPE) {
                return $this->getTimeManager();
            } elseif ($type == CS_TAG_TYPE) {
                return $this->getTagManager();
            } elseif ($type == CS_TAG2TAG_TYPE) {
                return $this->getTag2TagManager();
            } elseif ($type == CS_BUZZWORD_TYPE) {
                return $this->getBuzzwordManager();
            } elseif ($type == CS_ENTRY_TYPE) {
                return $this->getEntryManager();
            } elseif (!$this->isPlugin($type)) {
                include_once('functions/error_functions.php');
                trigger_error('do not know this type [' . $type . ']', E_USER_ERROR);
            }
        }
        return null;
    }

  /** get boolean, if you are in the community room or not
   *
   * @return boolean, true  = you are in the community room
   *                  false = you are not in the community room
   */
   function inCommunityRoom () {
      $context_item = $this->getCurrentContextItem();
      return $context_item->isCommunityRoom();
   }

  /** get boolean, if you are in the private room or not
   *
   * @return boolean, true  = you are in the private room
   *                  false = you are not in the private room
   */
   function inPrivateRoom () {
      $context_item = $this->getCurrentContextItem();
      return $context_item->isPrivateroom();
   }

   function isContextOpenForGuests() {
      $context_item = $this->getCurrentContextItem();
      return $context_item->isOpenForGuests();
   }

  /** get boolean, if you are in a group room or not
   *
   * @return boolean, true  = you are in a group room
   *                  false = you are not in a group room
   */
   function inGroupRoom () {
      $context_item = $this->getCurrentContextItem();
      return $context_item->isGroupRoom();
   }

  /** get boolean, if you are in a user room or not
   *
   * @return boolean, true  = you are in a user room
   *                  false = you are not in a user room
   */
  function inUserroom () {
      $context_item = $this->getCurrentContextItem();
      return $context_item->isUserroom();
  }

  /** get boolean, if you are in a project room or not
   *
   * @return boolean, true  = you are in a project room
   *                  false = you are not in a project room
   */
   function inProjectRoom () {
      $context_item = $this->getCurrentContextItem();
      return $context_item->isProjectRoom();
   }

  /** get boolean, if you are in a portal or not
   *
   * @return boolean, true  = you are in a portal
   *                  false = you are not in a portal
   */
   function inPortal () {
      $context_item = $this->getCurrentContextItem();
      return $context_item->isPortal();
   }

  /** get boolean, if you are in a server or not
   *
   * @return boolean, true  = you are in a server
   *                  false = you are not in a server
   */
   function inServer () {
      $context_item = $this->getCurrentContextItem();
      return $context_item->isServer();
   }

   /** get Instance of the translation object
    * returns an object for translation of message tags
    *
    * @return \cs_translator
    */
   function getTranslationObject () {
      global $dont_resolve_messagetags;

      if ( !isset($this->instance['translation_object']) ) {
         include_once('classes/cs_translator.php');
         $this->instance['translation_object'] = new cs_translator;
         if ($dont_resolve_messagetags) {
            $this->instance['translation_object']->dontResolveMessageTags();
         }
         $this->instance['translation_object']->setSelectedLanguage($this->getSelectedLanguage());
         $context_item = $this->getCurrentContextItem();
         if ( $this->inCommunityRoom() ) {
            $this->instance['translation_object']->setContext('community');
            $portal_item = $context_item->getContextItem();
            $this->instance['translation_object']->setTimeMessageArray($portal_item->getTimeTextArray());
         } elseif ( $this->inProjectRoom() ) {
            $this->instance['translation_object']->setContext('project');
            $portal_item = $context_item->getContextItem();
            $this->instance['translation_object']->setTimeMessageArray($portal_item->getTimeTextArray());
         } elseif ( $this->inGroupRoom() ) {
            $this->instance['translation_object']->setContext(CS_GROUPROOM_TYPE);
            $portal_item = $context_item->getContextItem();
            $this->instance['translation_object']->setTimeMessageArray($portal_item->getTimeTextArray());
         } elseif ( $this->inUserroom() ) {
             $this->instance['translation_object']->setContext(cs_userroom_item::ROOM_TYPE_USER);
             $portal_item = $context_item->getPortalItem();
             $this->instance['translation_object']->setTimeMessageArray($portal_item->getTimeTextArray());
         } elseif ( $this->inPrivateRoom() ) {
            $this->instance['translation_object']->setContext('private');
            $portal_item = $context_item->getContextItem();
            $this->instance['translation_object']->setTimeMessageArray($portal_item->getTimeTextArray());
         } elseif ( $this->inPortal() ) {
            $this->instance['translation_object']->setContext('portal');
            $this->instance['translation_object']->setTimeMessageArray($context_item->getTimeTextArray());
         } else {
            $this->instance['translation_object']->setContext('server');
         }
         if ( isset($context_item) ) {
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

    /** getSelectedLanguage
     * get selected language, form user, room or browser
     *
     * @return string selected language
     */
    public function getSelectedLanguage()
    {
        if (empty($this->_selected_language)) {
            $contextItem = $this->getCurrentContextItem();

            if (get_class($contextItem) == PortalProxy::class) {
                // If in portal context we have to use the session value to set the current language.
                // See https://symfony.com/doc/4.4/session/locale_sticky_session.html
                global $symfonyContainer;

                $sessionHelper = $symfonyContainer->get(SessionHelper::class);
                $session = $sessionHelper->getSession();
                $this->_selected_language = $session->get('_locale', 'de');
            } else {
                // If in room context (and the room will fall back to the user's choice), we'll
                // get the language from cs_environment::getUserLanguage. This method returns the
                // language extra from the user table. All user table entries + session value get updated
                // when the user changes the account language.
                // TODO: Only rely on account + session value and get rid of the profile languages.
                $this->_selected_language = $contextItem->getLanguage();
                if ($this->_selected_language === 'user') {
                    $this->_selected_language = $this->getUserLanguage();
                }
            }
        }
        return $this->_selected_language;
    }

   public function unsetSelectedLanguage () {
      $this->_selected_language = NULL;
   }

   public function setSelectedLanguage ( $value ) {
      $this->_selected_language = $value;
   }

    function getUserLanguage()
    {
        $current_user = $this->getCurrentUserItem();

        if ($current_user && $current_user->isUser()) {
            $retour = $current_user->getLanguage();
            if ($retour == 'browser') {
                $retour = $this->getBrowserLanguage();
            }
        } else {
            $retour = $this->getBrowserLanguage();
        }
        return $retour;
    }

   function getBrowserLanguage () {
      $browser_languages = $this->parseAcceptLanguage();
      $available_languages = $this->getAvailableLanguageArray();
      // there is no central default language yet, so this needs to be hardcoded
      $language = 'de'; //default language
      if ( !empty($browser_languages)
           and is_array($browser_languages)
         ) {
         foreach ($browser_languages as $lang) {
            if ($lang == 'ro'){
               $lang = 'ru';
            }
            if (in_array($lang, $available_languages)) {
               $language = $lang;
               break;
            }
         }
      }
      return $language;
   }

   function getAvailableLanguageArray () {
      if ( !isset($this->_available_languages) ) {
         if ( $this->inServer() ) {
            $context_item = $this->getServerItem();
         } else {
            $context_item = $this->getCurrentPortalItem();
         }
         $this->_available_languages = $context_item->getAvailableLanguageArray();
      }
      return $this->_available_languages;
   }

    /**
     * Taken from http://www.shredzone.de/articles/php/snippets/acceptlang/?SID=uf4h8rf736v35afbi90844qsc0
     *
     * Parse the Accept-Language HTTP header sent by the browser. It
     * will return an array with the languages the user accepts, sorted
     * from most preferred to least preferred.
     *
     *
     * @return  Array: key is the importance, value is the language code.
     */
    private function parseAcceptLanguage()
    {
        $ayLang = array();
        $aySeen = array();
        if (getenv('HTTP_ACCEPT_LANGUAGE') != '') {
            foreach (explode(',', getenv('HTTP_ACCEPT_LANGUAGE')) as $llang) {
                preg_match("~^(.*?)([-_].*?)?(\;q\=(.*))?$~iu", $llang, $ayM);
                $q = $ayM[4] ?? '1.0';
                $lang = mb_strtolower(trim($ayM[1]));
                if (!in_array($lang, $aySeen)) {
                    $ayLang[$q] = $lang;
                    $aySeen[] = $lang;
                }
            }

            uksort($ayLang, function($a, $b) {
                return ($a > $b) ? -1 : 1;
            });
        }
        return $ayLang;
    }

   function getCurrentBrowser () {
      $retour = '';
      if ( !isset($this->_browser) ) {
         $this->_parseBrowser();
      }
      if ( !empty($this->_browser) ) {
         $retour = $this->_browser;
      }
      return $retour;
   }

   function _parseBrowser() {
      global $_SERVER;

      $browser = array ( //reversed array
      //   "IPAD",
      //   "IPHONE",
         "OPERA",
         "MSIE",            // parent
         "NETSCAPE",
         "FIREFOX",
         "SAFARI",
         "KONQUEROR",
         "CAMINO",
         "MOZILLA"        // parent
      );

      $this->_browser = 'OTHER';
      $this->_browser_version = '';

      foreach ($browser as $parent) {
         if ( ($s = mb_strpos(mb_strtoupper($_SERVER['HTTP_USER_AGENT'], 'UTF-8'), $parent)) !== FALSE ) {
            $f = $s + mb_strlen($parent);
            $version = mb_substr($_SERVER['HTTP_USER_AGENT'], $f, 5);
            $version = preg_replace('~[^0-9,.]~u','',$version);

            $this->_browser = $parent;
            $this->_browser_version = $version;
            break; // first match wins
         }
      }

      $this->getCurrentOperatingSystem();
   }

   function getCurrentOperatingSystem () {
      global $_SERVER;
      $HTTP_USER_AGENT = $_SERVER['HTTP_USER_AGENT'];
      $os = 'UNKNOWN';
      if ( $os == 'UNKNOWN' and (mb_strpos($HTTP_USER_AGENT, "Win95") || mb_strpos($HTTP_USER_AGENT, "Windows 95")) ) {
         $os = "Windows 95";
      }
      if ( $os == 'UNKNOWN' and (mb_strpos($HTTP_USER_AGENT, "Win98") || mb_strpos($HTTP_USER_AGENT, "Windows 98")) ) {
         $os = "Windows 98";
      }
      if ( $os == 'UNKNOWN' and (mb_strpos($HTTP_USER_AGENT, "WinNT") || mb_strpos($HTTP_USER_AGENT, "Windows NT")) ) {
         $os = "Windows NT";
      }
      if ( $os == 'Windows NT' and (mb_strpos($HTTP_USER_AGENT, "WinNT 5.0") || mb_strpos($HTTP_USER_AGENT, "Windows NT 5.0")) ) {
         $os = "Windows 2000";
      }
      if ( $os == 'Windows NT' and (mb_strpos($HTTP_USER_AGENT, "WinNT 5.1") || mb_strpos($HTTP_USER_AGENT, "Windows NT 5.1")) ) {
         $os = "Windows XP";
      }
      if ( $os == 'UNKNOWN' and (mb_strpos($HTTP_USER_AGENT, "Linux")) ) {
         $os = "Linux";
      }
      if ( $os == 'UNKNOWN' and (mb_strpos($HTTP_USER_AGENT, "OS/2")) ) {
         $os = "OS/2";
      }
      if ( $os == 'UNKNOWN' and (mb_strpos($HTTP_USER_AGENT, "Sun")) ) {
         $os = "Sun OS";
      }
      if ( $os == 'UNKNOWN' and (mb_strpos($HTTP_USER_AGENT, "Macintosh") || mb_strpos($HTTP_USER_AGENT, "Mac_PowerPC")) ) {
         $os = "Mac OS";
      }
      if ( $os == 'UNKNOWN' and (mb_strpos($HTTP_USER_AGENT, "iPhone") || mb_strpos($HTTP_USER_AGENT, "Mac_PowerPC")) ) {
         $os = "iPhone";
         // iPhone Textarea without FCK-/ CK-Editor
         $currentContextItem = $this->getCurrentContextItem();
         if($currentContextItem->isPluginOn('ckeditor')){
            $currentContextItem->setPluginOff('ckeditor');
         }
         if($currentContextItem->withHtmlTextArea()){
            $currentContextItem->setHtmlTextAreaStatus(3);
         }
         unset($currentContextItem);
      }
      if ( $os == 'UNKNOWN' and (mb_strpos($HTTP_USER_AGENT, "iPad") || mb_strpos($HTTP_USER_AGENT, "Mac_PowerPC")) ) {
         $os = "iPad";
         // iPad Textarea without FCK-/ CK-Editor
         $currentContextItem = $this->getCurrentContextItem();
         if($currentContextItem->isPluginOn('ckeditor')){
            $currentContextItem->setPluginOff('ckeditor');
         }
         if($currentContextItem->withHtmlTextArea()){
            $currentContextItem->setHtmlTextAreaStatus(3);
         }
         unset($currentContextItem);
      }
      return $os;
   }

   function getRootUserItem () {
      $user_manager = $this->getUserManager();
      return $user_manager->getRootUser();
   }

   function getRootUserItemID () {
      $retour = NULL;
      $root_user = $this->getRootUserItem();
      if ( isset($root_user) ) {
         $item_id = $root_user->getItemID();
         if ( !empty($item_id) ) {
            $retour = $item_id;
         }
         unset($root_user);
      }
      return $retour;
   }

   ################################################################
   # plugin: begin
   ################################################################

   function getPluginClass ($plugin) {
      $retour = NULL;
      if ( !is_array($plugin) ) {
         if ( empty($this->_plugin_class_array[$plugin]) ) {
            $plugin_class_name = 'class_'.$plugin;
            $plugin_filename = 'plugins/'.$plugin.'/'.$plugin_class_name.'.php';
            if ( file_exists($plugin_filename) ) {
               include_once($plugin_filename);
               $this->_plugin_class_array[$plugin] = new $plugin_class_name($this);
            }
         }
         if ( !empty($this->_plugin_class_array[$plugin]) ) {
            $retour = $this->_plugin_class_array[$plugin];
         }
      }
      return $retour;
   }

   function getRubrikPluginClassList ( $cid = '' ) {
      $key = $cid;
      if ( empty($key) ) {
         $key = 'all';
      }
      $retour = '';
      if ( !empty($this->_rubric_plugin_class_list[$key]) ) {
         $retour = $this->_rubric_plugin_class_list[$key];
      }
      if ( empty($retour) ) {
         if ( !empty($cid) ) {
            // only portal
            $portal_manager = $this->getPortalManager();
            $portal_item = $portal_manager->getItem($cid);
         }
         global $c_plugin_array;
         include_once('classes/cs_list.php');
         $this->_rubric_plugin_class_list[$key] = new cs_list();
         if ( isset($c_plugin_array)
              and !empty($c_plugin_array)
            ) {
            foreach ($c_plugin_array as $plugin ) {
               $plugin_class = $this->getPluginClass($plugin);
               if ( !empty($plugin_class)
                    and method_exists($plugin_class,'isRubricPlugin')
                    and $plugin_class->isRubricPlugin()
                    and ( empty($cid)
                          or ( isset($portal_item)
                               and $portal_item->isPluginOn($plugin)
                             )
                        )
                  ) {
                  $this->_rubric_plugin_class_list[$key]->add($plugin_class);
               }
            }
         }
         $retour = $this->_rubric_plugin_class_list[$key];
      }

      return $retour;
   }

   function isPlugin ( $value ) {
      $retour = false;
      global $c_plugin_array;
      if ( isset($c_plugin_array)
              and !empty($c_plugin_array)
            ) {
         $retour = in_array(mb_strtolower($value, 'UTF-8'),$c_plugin_array);
      }
      return $retour;
   }

   ################################################################
   # plugin: end
   ################################################################

    public function getDBConnector(): db_mysql_connector
    {
        if (empty($this->_db_mysql_connector)) {
            include_once('classes/db_mysql_connector.php');
            $this->_db_mysql_connector = new db_mysql_connector();
            $this->_db_mysql_connector->setLogQueries();
        }

        return $this->_db_mysql_connector;
    }

   ##################################################################
   # multi master implemenation - BEGIN
   ##################################################################
   public function getDBPortalID () {
      return $this->_db_portal_id;
   }

   public function setDBPortalID ( $value ) {
      $this->_db_portal_id = (int)$value;
   }
   ##################################################################
   # multi master implemenation - BEGIN
   ##################################################################

   public function getCurrentCommSyVersion () {
      $server_item = $this->getServerItem();
      return $server_item->getCurrentCommSyVersion();
   }

   public function isCurlForPHPAvailable(){
      return function_exists("curl_init");
   }

   public function setCacheOff () {
      $this->_cache_on = false;
   }

   public function getClassFactory () {
      if ( !isset($this->_class_factory) ) {
         include_once('classes/cs_class_factory.php');
         $this->_class_factory = new cs_class_factory();
      }
      return $this->_class_factory;
   }

   public function setOutputMode ( $value ) {
      $this->_output_mode = $value;
   }

   public function getOutputMode ( ) {
      return $this->_output_mode;
   }

   public function isOutputMode ( $value ) {
      $retour = false;
      $mode = $this->getOutputMode();
      if ( !empty($mode)
           and mb_strtolower($mode, 'UTF-8') == mb_strtolower($value, 'UTF-8')
         ) {
         $retour = true;
      }
      return $retour;
   }

   public function isOutputModeNot ( $value ) {
      return !$this->isOutputMode($value);
   }

   public function getConfiguration ( $var ) {
      global $$var;
      $retour = NULL;
      if ( isset($$var) ) {
         $retour = $$var;
      }
      return $retour;
   }

   public function setConfiguration ( $var, $data ) {
      global $$var;
      if ( isset($$var)
           and isset($data)
         ) {
         $$var = $data;
      }
   }

   public function getTextConverter () {
      if ( !isset($this->_misc_text_converter) ) {
         $class_factory = $this->getClassFactory();
         $this->_misc_text_converter = $class_factory->getClass('misc_text_converter',array('environment' => $this));
         unset($class_factory);
      }
      return $this->_misc_text_converter;
   }

   public function inConfigArray ( $config_array_name, $value ) {
      $retour = false;
      global $$config_array_name;
      if ( !empty($value)
           and !empty($$config_array_name)
           and is_array($$config_array_name)
           and in_array($value,$$config_array_name)
         ) {
         $retour = true;
      }

      return $retour;
   }

   public function changeContextToPrivateRoom($contextId = null) {
   	$currentUser = $this->getCurrentUserItem();
   	$privateRoomItem = $currentUser->getOwnRoom();
   	$privateRoomContextID = $privateRoomItem->getItemID();

   	$contextIdToSet = ($contextId) ? $contextId : $privateRoomContextID;

   	// set new context information and reset the loaded manager
   	$this->setCurrentContextID($contextIdToSet);
   	$this->setCurrentContextItem($privateRoomItem);
   	$this->setCurrentUserItem($currentUser->getRelatedPrivateRoomUserItem());
   	$this->unsetAllInstancesExceptTranslator();
   }

   public function unsetPortalItem () {
       $this->_current_portal = NULL;
   }

    /**
     * @return ContainerInterface
     */
   public function getSymfonyContainer(): ContainerInterface
   {
       global $symfonyContainer;
       return $symfonyContainer;
   }
}
