<?PHP
// $Id$
//
// Release $Name$
//
// Copyright (c)2002-2003 Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
// Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
// Edouard Simon, Monique Strauss, Jose Mauel Gonzalez Vazquez
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


include_once('functions/text_functions.php');
include_once('functions/curl_functions.php');

/** class checks authentication
 * this class checks the authentication of a authentication_item or a session_id
 */
class cs_authentication {

   /**
    * object cs_manager - containing an object for database connection to the table "auth" or ldap-connection
    */
   var $_auth_manager = array();

   /**
    * object cs_manager - containing an object for database connection
    * to the table "auth" of the commsy server, for authentication
    * for the root user
    * authentication of user root only via this connection
    */
   var $_default_auth_manager;

   var $_ims_auth_manager;

   var $_commsy_auth_manager;

   var $_used_auth_manager = NULL;

   var $_auth_source_list = NULL;

   var $_auth_source_granted = NULL;

   /**
    * object environment - containing an object for the environment information
    */
   var $_environment;

   /**
    * object cs_item - containing an object item with all user information
    */
   var $_user_item;

   /**
    * string - containing an module name as a limit
    */
   var $_module_limit;

   /**
    * string - containing an function name as a limit
    */
   var $_function_limit;

   /**
    * integer - containing the error number if an error occured
    */
   var $_dberrno;

   /**
   * string - containing the error text if an error occured
   */
   var $_dberror;

   /**
   * array - containing the error messages
   */
   var $_error_array = array();

   /**
   * Translator
   */
   private $_translator = null;

   /** constructor
    * the only available constructor, initial values for internal variables
    *
    * @param object environment of commsy
    */
   function __construct($environment) {
     $this->_environment = $environment;
     $this->_translator = $this->_environment->getTranslationObject();
     $this->reset();
   }

   /** reset this object
    * reset this object: data, limits, manager
    */
   function reset () {
      $this->_resetManager();
      $this->resetData();
      $this->resetLimits();
   }

   /** reset manager
    * reset manager of this class: auth and user manager
    */
   function _resetManager () {
      $this->_auth_manager = array();
   }

   /** reset data
    * reset data of this class: user_item
    * @author CommSy Development Group
    */
   function resetData () {
      unset($this->_user_item);
   }

   /** reset limits
    * reset limits of this class
    */
   function resetLimits () {
      $this->_module_limit = 'home';
      $this->_function_limit = 'index';
      $this->_ask_for_root = false;
   }

   /** get user item
    * this method returns the tested user item
    *
    * @return object cs_item the tested user item
    *
    * @author CommSy Development Group
    */
   function getUserItem () {
      return $this->_user_item;
   }

   /** set module limit
    * this method sets a module limit
    *
    * @param string value name of the module
    *
    * @author CommSy Development Group
    */
   function setModule ($value) {
      $this->_module_limit = (string)$value;
   }

   /** set function limit
    * this method sets a function limit
    *
    * @param string value name of the function
    *
    * @author CommSy Development Group
    */
   function setFunction ($value) {
      $this->_function_limit = (string)$value;
   }

   /** get auth manager
    * this method gets the authentication management object
    *
    * @param integer auth_source item id of auth source
    *
    * @return object cs_manager manager for password authentication
    */
   function getAuthManager ( $auth_source_id ) {
      $retour = NULL;
      if ( isset($this->_auth_manager[$auth_source_id]) and !empty($this->_auth_manager[$auth_source_id]) ) {
         $retour = $this->_auth_manager[$auth_source_id];
      } else {
         if ( isset($this->_auth_source_list) and !$this->_auth_source_list->isEmpty() ) {
            $auth_source_item = $this->_auth_source_list->getFirst();
            $found = false;
            while ( $auth_source_item and !$found ) {
               if ( $auth_source_item->getItemID() == $auth_source_id ) {
                  $found = true;
               } else {
                  $auth_source_item = $this->_auth_source_list->getNext();
               }
            }
            if ( $found ) {
               $auth_manager = $this->_getAuthManagerByAuthSourceItem($auth_source_item);
            } else {
               $auth_manager = $this->_getAuthManagerByAuthSourceID($auth_source_id);
            }

            $current_context = $this->_environment->getCurrentPortalItem();
            if ( !isset($current_context) ) {
               $current_context = $this->_environment->getServerItem();
            }
            if ( $auth_source_id == $current_context->getAuthDefault() ) {
               $this->setDefaultAuthManager($auth_manager);
            }
            $this->_auth_manager[$auth_source_id] = $auth_manager;
            $retour = $this->_auth_manager[$auth_source_id];
         }
      }
      return $retour;
   }

   function _getAuthManagerByAuthSourceID ( $id ) {
      $auth_source_manager = $this->_environment->getAuthSourceManager();
      $auth_source_item = $auth_source_manager->getItem($id);
      $this->_auth_source_list->add($auth_source_item);
      return $this->_getAuthManagerByAuthSourceItem($auth_source_item);
   }

   function _getAuthManagerByAuthSourceItem ($auth_source_item) {
      if ( !$auth_source_item->isCommSyDefault() ) {
         $type = $auth_source_item->getSourceType();
         if ( $type == 'MYSQL' ) {
            // other MySQL Database
            include_once('classes/cs_auth_mysql_mysql.php');
            $auth_manager = new cs_auth_mysql_mysql();
            $auth_manager->setAuthSourceItem($auth_source_item);
         } elseif ( $type == 'LDAP' ) {
            include_once('classes/cs_auth_ldap.php');
            $auth_manager = new cs_auth_ldap();
            $auth_manager->setAuthSourceItem($auth_source_item);
         } elseif ( $type == 'Typo3' ) {
            include_once('classes/cs_auth_mysql_typo3.php');
            $auth_manager = new cs_auth_mysql_typo3();
            $auth_manager->setAuthSourceItem($auth_source_item);
         } elseif ( $type == 'Typo3Web' ) {
            include_once('classes/cs_auth_typo3.php');
            $auth_manager = new cs_auth_typo3();
            $auth_manager->setAuthSourceItem($auth_source_item);
         } elseif ( $type == 'Shibboleth' ) {
         	include_once('classes/cs_auth_shibboleth.php');
         	$auth_manager = new cs_auth_shibboleth();
         	$auth_manager->setAuthSourceItem($auth_source_item);
         }
      } else {
         include_once('classes/cs_auth_mysql_commsy.php');
         $auth_manager = new cs_auth_mysql_commsy();
         $auth_manager->setContextLimit($auth_source_item->getContextID());
         $auth_manager->setDBConnector($this->_environment->getDBConnector());
         $auth_manager->setAuthSourceItem($auth_source_item);
         $this->setCommSyAuthManager($auth_manager);
      }
      return $auth_manager;
   }

   function getAuthManagerByAuthSourceItem ($auth_source_item) {
      if ( isset($this->_auth_manager[$auth_source_item->getItemID()]) and !empty($this->_auth_manager[$auth_source_item->getItemID()]) ) {
         $retour = $this->_auth_manager[$auth_source_item->getItemID()];
      } else {
         $auth_manager = $this->_getAuthManagerByAuthSourceItem($auth_source_item);
         $current_context = $this->_environment->getCurrentContextItem();
         if ( ( $current_context->isPortal()
                or $current_context->isServer()
              )
              and $auth_source_item->getItemID() == $current_context->getAuthDefault()
            ) {
            $this->setDefaultAuthManager($auth_manager);
         }
         $this->_auth_manager[$auth_source_item->getItemID()] = $auth_manager;
         $retour = $this->_auth_manager[$auth_source_item->getItemID()];
      }
      return $retour;
   }

   function getAuthManagerByType ( $value ) {
      $auth_manager = NULL;
      if ( $value == 'CommSy' ) {
         include_once('classes/cs_auth_mysql_commsy.php');
         $auth_manager = new cs_auth_mysql_commsy();
      } elseif ( $value == 'LDAP' ) {
         include_once('classes/cs_auth_ldap.php');
         $auth_manager = new cs_auth_ldap();
      } elseif ( $value == 'Typo3' ) {
         include_once('classes/cs_auth_mysql_typo3.php');
         $auth_manager = new cs_auth_mysql_typo3();
      } elseif ( $value == 'MYSQL' ) {
         include_once('classes/cs_auth_mysql_mysql.php');
         $auth_manager = new cs_auth_mysql_mysql();
      } elseif ( $value == 'Typo3Web' ) {
         include_once('classes/cs_auth_typo3.php');
         $auth_manager = new cs_auth_typo3();
      } elseif ( $value == 'Shibboleth' ) {
      	 include_once ('classes/cs_auth_shibboleth.php');
      	 $auth_manager = new cs_auth_shibboleth();
      } else {
         include_once('functions/error_functions.php');
         trigger_error('don\'t know '.$value,E_USER_WARNING);
      }
      return $auth_manager;
   }

   /** set auth manager
    * this method sets the authentication management object to verify the password
    *
    * @param object cs_manager value manager for password authentication
    */
   function setDefaultAuthManager ($value) {
      $this->_default_auth_manager = $value;
   }

   /** get auth manager
    * this method gets the authentication management object
    *
    * @return object cs_manager manager for password authentication
    */
   function getDefaultAuthManager () {
      return $this->_default_auth_manager;
   }

   /** set ims auth manager
    * this method sets the authentication management object to verify the password
    *
    * @param object cs_manager value manager for password authentication
    */
   function setIMSAuthManager ($value) {
      $this->_ims_auth_manager = $value;
   }

   /** get ims auth manager
    * this method gets the authentication management object
    *
    * @return object cs_manager manager for password authentication
    */
   function getIMSAuthManager () {
      return $this->_ims_auth_manager;
   }

   /** set commsy auth manager
    * this method sets the commsy authentication management object
    *
    * @param object cs_manager value manager for password authentication
    */
   function setCommSyAuthManager ($value) {
      $this->_commsy_auth_manager = $value;
   }

   /** get commsy auth manager
    * this method gets the commsy authentication management object
    *
    * @return object cs_manager manager for password authentication
    */
   function getCommSyAuthManager () {
      if ( !isset($this->_commsy_auth_manager) ) {
         $current_context = $this->_environment->getCurrentContextItem();
         $auth_source_list = $current_context->getAuthSourceList();
         if ( $auth_source_list->isNotEmpty() ) {
            $auth_source_item = $auth_source_list->getFirst();
            $found = false;
            while ($auth_source_item and !$found) {
               if ( $auth_source_item->isCommSyDefault() ) {
                  $found = true;
               } else {
                  $auth_source_item = $auth_source_list->getNext();
               }
            }
            if ( $found ) {
               $this->_commsy_auth_manager = $this->getAuthManagerByAuthSourceItem($auth_source_item);
            }
         }
      }
      return $this->_commsy_auth_manager;
   }

   function setCommSyIDLimit ($value) {
      foreach ($this->_auth_manager as $key => $manager) {
         $this->_auth_manager[$key]->setCommSyIDLimit($value);
      }
   }

   function setAuthSourceList ($value) {
      $this->_auth_source_list = $value;
   }

   private function getAuthSourceList () {
      return $this->_auth_source_list;
   }
   
   public function getShibbolethAuthSource () {
   	$auth_source_list = $this->getAuthSourceList();
   	if(isset($auth_source_list) and !empty($auth_source_list)){
   		$auth_source_item = $auth_source_list->getFirst();
   		$found = false;
   		while ( $auth_source_item and !$found ) {
   			if ( $auth_source_item->getSourceType() == 'Shibboleth') {
   				$found = true;
   			} else {
   				$auth_source_item = $auth_source_list->getNext();
   			}
   		}
   		return $auth_source_item;
   	}
   	
   }

   public function checkAccount ($uid, $password, $auth_source = '') {
      $allowed = false;

      // verify password to user id
      if ( $uid == 'root' or $uid == 'IMS_USER' ) { // if root or ims_user use default auth manager
         if ( !isset($this->_commsy_auth_manager) ) {
            $portal_item = $this->_environment->getCurrentPortalItem();
            if ( !isset( $portal_item ) ) {
               $portal_item = $this->_environment->getServerItem();
            }
            $auth_source_list = $portal_item->getAuthSourceList();
            if ( isset($auth_source_list) and !empty($auth_source_list) ) {
               $auth_source_item = $auth_source_list->getFirst();
               $found = false;
               while ( $auth_source_item and !$found ) {
                  if ( $auth_source_item->isCommSyDefault() ) {
                     $found = true;
                  } else {
                     $auth_source_item = $auth_source_list->getNext();
                  }
               }
               $auth_manager = $this->getAuthManager($auth_source_item->getItemID());
               $auth_manager->setContextLimit($this->_environment->getServerID());
            }
         }
         $allowed = $auth_manager->checkAccount($uid,$password);
         $this->_used_auth_manager = $this->_commsy_auth_manager;
         $this->_ask_for_root = true;
         $this->_auth_source_granted = $auth_source_item->getItemID();
      } elseif ( !empty($auth_source) ) {
         $auth_manager = $this->getAuthManager($auth_source);
         $allowed = $auth_manager->checkAccount($uid,$password);
         $this->_used_auth_manager = $auth_manager;
         $this->_auth_source_granted = $auth_source;
      } elseif ( isset($this->_auth_source_list) and !$this->_auth_source_list->isEmpty() ) {
         
      	// first: default auth source
      	$current_portal = $this->_environment->getCurrentPortalItem();
      	if ( !empty($current_portal) ) {
      	   $default_auth_source_id = $current_portal->getAuthDefault();
      	   if ( !empty($default_auth_source_id) ) {
      	   	$default_auth_source_item = NULL;
               $auth_source_item = $this->_auth_source_list->getFirst();
      	   	while ( $auth_source_item ) {
      	   		if ( $default_auth_source_id == $auth_source_item->getItemID() ) {
      	   			$default_auth_source_item = $auth_source_item;
      	   			break;
      	   		}
     	   			$auth_source_item = $this->_auth_source_list->getNext();
      	   	}
      	   	if ( !empty($default_auth_source_item) ) {
      	   		$auth_manager = $this->getAuthManager($auth_source_item->getItemID());
      	   		$allowed = $auth_manager->checkAccount($uid,$password);
      	   		if ( $allowed ) {
      	   			$auth_source = $auth_source_item->getItemID();
      	   			$this->_used_auth_manager = $auth_manager;
      	   			$this->_auth_source_granted = $auth_source;
      	   		}      	   		
      	   	}
      	   }
      	}
      	
      	// second: all auth sources
         $auth_source_item = $this->_auth_source_list->getFirst();
         while ( $auth_source_item and !$allowed ) {
            if ( $auth_source_item->show() ) {
               $auth_manager = $this->getAuthManager($auth_source_item->getItemID());
               $allowed = $auth_manager->checkAccount($uid,$password);
               if ( !$allowed ) {
                  $auth_source_item = $this->_auth_source_list->getNext();
               } else {
                  $auth_source = $auth_source_item->getItemID();
                  $this->_used_auth_manager = $auth_manager;
                  $this->_auth_source_granted = $auth_source;
               }
            } else {
               $auth_source_item = $this->_auth_source_list->getNext();
            }
         }
      } else {
         include_once('functions/error_functions.php');
         trigger_error('need auth source to check account '.$uid,E_USER_ERROR);
      }

      if ($allowed) {
          // add CalDAV Hash
          global $symfonyContainer;
          $caldavService = $symfonyContainer->get('commsy.caldav_service');
          $caldavService->setCalDAVHash($uid, $password, 'CommSy');
      }

      return $allowed;
   }

   /** is the account granted ?
    * this method returns a boolean, if the account is granted. First verify password, Second verify status at portal.
    *
    * @param string uid user id of the current user
    * @param string password the password of the current user
    * @param int auth_source the item id of the auth_source of the current user
    *
    * @return boolean true, account is granted
    *                 false, account is not granted
    */
   function isAccountGranted ($uid, $password, $auth_source = '') {
      $user_manager = $this->_environment->getUserManager();
      $translator = $this->_environment->getTranslationObject();
      $granted = false;

      $allowed = $this->checkAccount($uid, $password, $auth_source);

      if ($allowed and !$this->_ask_for_root) {
         $user_item = $this->_getPortalUserItem($uid,$this->_auth_source_granted);
         if ( isset($user_item) and $user_item->getItemID() > 0) {

            // if there is an profile, test status, if status = user -> okay
            if ($user_item->isUser()) {
               $granted = true;
            }

            // if status is not user, but profile exists
            else {
               $portal = $this->_environment->getCurrentPortalItem();

               // user has just requested for membership
               if ($user_item->isRequested()) {
                  $this->_error_array[] = $translator->getMessage('AUTH_ERROR_ACCOUNT_NOT_FREE',$user_item->getUserID(),$portal->getTitle());
               }

               // or has been rejected
               else {
                  $this->_error_array[] = $translator->getMessage('AUTH_ERROR_ACCOUNT_REJECTED',$user_item->getUserID(),$portal->getTitle());
               }
            }
         } else {
            if ( isset($this->_used_auth_manager) ) {
               $new_account_data = $this->_used_auth_manager->get_data_for_new_account($uid, $password);
               if ( !empty($new_account_data)
                    and !empty($new_account_data['firstname'])
                    and !empty($new_account_data['lastname'])
                  ) {
                  $user_item = $user_manager->getNewItem();
                  $user_item->setUserID($uid);
                  $user_item->setFirstname($new_account_data['firstname']);
                  $user_item->setLastname($new_account_data['lastname']);
                  if(!empty($new_account_data['email'])){
                     $user_item->setEmail($new_account_data['email']);
                  } else {
                     $server_item = $this->_environment->getServerItem();
                     $email = $server_item->getDefaultSenderAddress();
                     $user_item->setEmail($email);
                     $user_item->setHasToChangeEmail();
                  }
                  $user_item->setAuthSource($this->_used_auth_manager->getAuthSourceItemID());
                  $user_item->makeUser();
                  $user_item->save();
                  $this->_environment->setCurrentUser($user_item);
                  $granted = true;
               }
            }

            if(!$granted){
               $session_item = $this->_environment->getSessionItem();
               $params = array();
               $params = $this->_environment->getCurrentParameterArray();
               $params['user_id'] = $uid;
               $params['auth_source'] = $auth_source;
               $params['cs_modus'] = 'portalmember2';
               if ( empty($params['cid']) ) {
                  $portal_item = $this->_environment->getCurrentPortalItem();
                  $params['cid'] = $portal_item->getItemID();
               }
               if ( isset($session_item) ) {
                  $history = $session_item->getValue('history');
                  $module = $history[0]['module'];
                  $funct = $history[0]['function'];
                  unset($session_item);
               } else {
                  $module = $this->_environment->getCurrentModule();
                  $funct = $this->_environment->getCurrentFunction();
               }
               redirect( $this->_environment->getCurrentContextID(),
                         $module,
                         $funct,
                         $params
                       );
               unset($params);
               exit();
            }
         }
      } elseif ($allowed and $this->_ask_for_root) {
         $granted = true;
      }
      return $granted;
   }

   /** exists an user_id ?
    * this method returns a boolean whether the user_id exists in commsy or not
    *
    * @param integer user_id id of the user (not item id)
    * @param integer auth_source id of the auth_source (item id)
    *
    * @return boolean true, if user_id already exists
    *                 false, if user_id not exists -> needed for new user
    */
   function exists ($user_id, $auth_source = '') {
      $retour = false;
      // guest and root are system user_ids, the can not be created be users
      // guest is for not logged in users
      // root is for the super admin
      if (cs_strtoupper($user_id) == 'GUEST' or cs_strtoupper($user_id) == 'ROOT') {
         $retour = true;
      } elseif ( !empty($auth_source) ) {
         $auth_manager = $this->getAuthManager($auth_source);
         $this->_used_auth_manager = $auth_manager;
         $retour = $auth_manager->exists($user_id);
      } else {
         $auth_source_list = $this->getAuthSourceList();
         if ( isset($auth_source_list)
              and $auth_source_list->isNotEmpty()
            ) {
            $auth_source_item = $auth_source_list->getFirst();
            while ($auth_source_item) {
               $auth_manager = $this->_getAuthManagerByAuthSourceItem($auth_source_item);
               $this->_used_auth_manager = $auth_manager;
               if ( $auth_manager->exists($user_id) ) {
                  $retour = true;
                  break;
               }
               $auth_source_item = $auth_source_list->getNext();
            }
         }
      }
      return $retour;
   }

   /** is an user_id free?
    * this method returns a boolean whether the user_id is free to choose
    * needed because of german umlauts
    *
    * @param string user_id id of the user (not item id)
    *
    * @return boolean true, if user_id is free to choose
    *                 false, if user_id is not free
    */
   function is_free ($user_id, $auth_source) {
      // guest and root are system user_ids, the can not be created be users
      // guest is for not logged in users
      // root is for the super admin
      if (cs_strtoupper($user_id) == 'GUEST' or cs_strtoupper($user_id) == 'ROOT') {
         return false;
      }
      $auth_manager = $this->getAuthManager($auth_source);
      $this->_used_auth_manager = $auth_manager;
      return $auth_manager->is_free($user_id);
   }

   /** save authentication (user_id and password) and user data
    * this method saves the user_id, password in the authentication storage and the user data in the user table
    *
    * @param object cs_auth_item auth_item with all informations about the new account
    * @param booloean only_user true, only user data will stored, not auth data
    *                           false (default), also authentication will be saved - switch for ldap
    */
   function save ($auth_item, $only_user = false) {
      $user_manager = $this->_environment->getUserManager();
      $this->_dberror = '';

      // save the information in authentication database
      if (!$only_user) {
         if ($auth_item->getUserID() != 'root') {
            $auth_source_id = $auth_item->getAuthSourceID();
            if ( isset($auth_source_id) and !empty($auth_source_id) ) {
               $auth_manager = $this->getAuthManager($auth_source_id);
            } else {
               $auth_manager = $this->getDefaultAuthManager();
            }
            if ( !empty($auth_manager) ) {
               $auth_manager->save($auth_item);
               $this->_used_auth_manager = $auth_manager;
               $this->_dberror = $this->_used_auth_manager->getErrorMessage();
            } else {
              include_once('functions/error_functions.php');
              trigger_error('Problems selecting auth manager. - '.__FILE__.' - '.__LINE__,E_USER_ERROR);
            }
         } else {
            $this->_commsy_auth_manager->save($auth_item);
            $this->_used_auth_manager = $this->_commsy_auth_manager;
            $this->_dberror = $this->_commsy_auth_manager->getErrorMessage();
         }
      }

      // and now save the information in the database table "user" of commsy
      if (empty($this->_dberror)) {
         // get the user profile on the portal
         $user_manager->resetLimits();
         $user_manager->setContextLimit($auth_item->getPortalID());
         $user_manager->setAuthSourceLimit($auth_item->getAuthSourceID());
         $user_manager->setUserIDLimit($auth_item->getUserID());
         $user_manager->select();
         $user_list = $user_manager->get();

         // user allready exists
         if ($user_list->getCount() == 1) {
            $this->_user_item = $user_list->getFirst();
         }

         // user saved for the first time, create user in portal
         else {
            $this->_user_item = $user_manager->getNewItem();
            $this->_user_item->setAuthSource($auth_item->getAuthSourceID());
            $context_item = $this->_environment->getCurrentContextItem();
            if ($context_item->isProjectRoom() or $context_item->isCommunityRoom()) {
               $context_item = $this->_environment->getCurrentPortalItem();
            }
            // init user in request mode
            $this->_user_item->request();
            $this->_user_item->setContextID($auth_item->getPortalID());
            $this->_user_item->setUserID($auth_item->getUserID());
            $explanation = $auth_item->getExplanation();
            if (!empty($explanation)) {
               $this->_user_item->setUserComment($explanation);
            }
         }
         $this->_user_item->setFirstname($auth_item->getFirstname());
         $this->_user_item->setLastname($auth_item->getLastname());
         $this->_user_item->setEmail($auth_item->getEmail());
         $this->_user_item->setLanguage($auth_item->getLanguage());
         $this->_user_item->save();
         $this->_dberror .= $user_manager->getErrorMessage();

         //change all related users too
         $dummy_user = $user_manager->getNewItem();
         $dummy_user->setFirstname($auth_item->getFirstname());
         $dummy_user->setLastname($auth_item->getLastname());
         $dummy_user->setEmail($auth_item->getEmail());
         $dummy_user->setLanguage($auth_item->getLanguage());
         $this->_user_item->changeRelatedUser($dummy_user);
      }
   }

   /** get error number
    * this method returns the number of an error, if an error occured
    *
    * @return integer error number
    *
    * @author CommSy Development Group
    */
   function getErrorNumber () {
      return $this->_dberrno;
   }

   /** get error text
    * this method returns the text of an error, if an error occured
    *
    * @return string error number
    *
    * @author CommSy Development Group
    */
   function getErrorMessage () {
      return $this->_dberror;
   }

   /** get commsy error text
    * this method returns the text of an error in commsy style, if an error occured
    *
    * @return string error number
    */
   function getErrorArray () {
      // get error array from auth_manager
      $error_array = array();
      if (isset($this->_auth_manager)) {
         foreach ($this->_auth_manager as $key => $manager) {
            $error_array = array_merge($error_array,$manager->getErrorArray());
         }
      }

      // add own errors to the array
      $error_array = array_merge($error_array,$this->_error_array);

      return $error_array;
   }

   /** delete an account
    * this method deletes an account by the item_id
    *
    * @param integer item_id item_id of the user to delete
    */
   function delete ($item_id) {
      $user_manager = $this->_environment->getUserManager();
      $user_to_delete = $user_manager->getItem($item_id);
      $user_list = $user_to_delete->getRelatedUserList();
      $user_item = $user_list->getFirst();
      while ($user_item) {
         $user_item->delete();
         $user_item = $user_list->getNext();
      }
      $auth_manager = $this->getAuthManager($user_to_delete->getAuthSource());
      $auth_manager->delete($user_to_delete->getUserID());
      $user_to_delete->delete();
      $this->_used_auth_manager = $auth_manager;
   }

   function deleteByUserId ($user_id,$auth_source) {
      $context_id = $this->_environment->getCurrentContextId();
      $user_manager = $this->_environment->getUserManager();
      $user_manager->setContextLimit($context_id);
      $user_manager->setUserIdLimit($user_id);
      $user_manager->setAuthSourceLimit($auth_source);
      $user_manager->select();
      $user_list = $user_manager->get();
      if ($user_list->getCount() == 1) {
         $user_to_delete = $user_list->getFirst();

         $user_list = $user_to_delete->getRelatedUserList();
         $user_item = $user_list->getFirst();
         while ($user_item) {
            $user_item->delete();
            $user_item = $user_list->getNext();
         }
         $auth_manager = $this->getAuthManager($user_to_delete->getAuthSource());
         $auth_manager->delete($user_to_delete->getUserID());
         $user_to_delete->delete();
         $this->_used_auth_manager = $auth_manager;
      }
   }

   /** get empty auth item
    * this method returns an empty auth item
    *
    * @return object auth_item of the user
    */
   function getNewItem () {
      include_once('classes/cs_auth_item.php');
      return new cs_auth_item();
   }

   function getPortalUserItem ($uid, $auth_source) {
      return $this->_getPortalUserItem($uid,$auth_source);
   }

   private function _getUserItemInternal ($uid, $auth_source, $force = false) {
      $retour = NULL;
      $user_manager = $this->_environment->getUserManager($force);
      $user_manager->resetLimits();
      $user_manager->setUserIDLimit($uid);
      $user_manager->setAuthSourceLimit($auth_source);
      $user_manager->setContextLimit($this->_environment->getCurrentPortalID());
      $user_manager->select();
      $user_list = $user_manager->get();
      // if there are more than one uids at the portal, than something is wrong
      if ($user_list->getCount() == 1) {
         $retour = $user_list->getFirst();
      } elseif ($user_list->getCount() > 1) {
         // display error text for multible user ids in this context
         $portal = $this->_environment->getCurrentPortalItem();
         $translator = $this->_environment->getTranslationObject();
         if (!empty($portal)) {
            $mod_list = $portal->getModeratorList();
            $text = $translator->getMessage('AUTH_ERROR_ACCOUNT_TO_MANY',$uid,$portal->getTitle());
            if (!$mod_list->isEmpty()) {
               $mod_item = $mod_list->getFirst();
               $text .= '<br />'."\n";
               while ($mod_item) {
                  $text .= '<br />'.$mod_item->getFullname().' [<a href="mailto:"'.$mod_item->getEmail().'">'.$mod_item->getEmail().'</a>]'."\n";
                  $mod_item = $mod_list->getNext();
               }
            }
            $this->_error_array[] = $text;
         } else {
            $this->_error_array[] = $translator->getMessage('COMMON_DATABASE_ERROR');
         }
      }
      return $retour;
   }

   function _getPortalUserItem ($uid, $auth_source) {
      $user_manager = $this->_environment->getUserManager();
      $user_item = $user_manager->getNewItem();
      if ($uid == 'guest') {
         $user_item->setUserId($uid);
         $user_item->reject();
         $translator = $this->_environment->getTranslationObject();
         $user_item->setLastname('GUEST');
      } else {
         $user_item = $this->_getUserItemInternal($uid,$auth_source,false);
         if ( !isset($user_item) ) {
            $user_item = $this->_getUserItemInternal($uid,$auth_source,true);
         }
      }
      return $user_item;
   }

   function _getContextUserItem ($uid, $auth_source) {
      $user_item = NULL;
      $user_manager = $this->_environment->getUserManager();
      $user_manager->resetLimits();
      $user_manager->setContextLimit($this->_environment->getCurrentContextID());
      $user_manager->setUserIDLimit($uid);
      $user_manager->setAuthSourceLimit($auth_source);
      $user_manager->select();
      $user_list = $user_manager->get();
      if ($user_list->getCount() == 1) {
         $user_item = $user_list->getFirst();
      } elseif ($user_list->getCount() > 1) {
         $user_item = NULL;
         $user_item = $user_list->getFirst();
         $error_log_manager = $this->_environment->getLogErrorManager();

         $error_array = array();
         $error_array['number'] = "1024";
         $error_array['type'] = "Error";
         $error_array['message'] = "multiple userids existing for itemid: ".$user_item->getItemID();
         $error_array['file'] = "";
         $error_array['line'] = "";
         $error_array['context'] = "";
         $error_array['module'] = "";
         $error_array['function'] = "";
         $error_array['user'] = $user_item->getFullname();

         $error_log_manager->saveArray($error_array);
      }
      return $user_item;
   }

   /** check access to current module, internal -> do ot use
    * this method returns a boolean, if the current user is allowed in the current module
    *
    * @return boolean true, account is granted
    *                 false, account is not granted
    */
   function _isUserAllowedHere ($user_item) {
      $granted = false;
      $user_item = $this->_environment->getCurrentUserItem();
      if ( $this->_module_limit == 'help'
           or ($this->_module_limit == 'context'  and $this->_function_limit == 'forward')
           or ($this->_module_limit == 'picture'  and $this->_function_limit == 'getfile')
           or ($this->_module_limit == 'material' and $this->_function_limit == 'getfile')
         ) {
         $granted = true;
      } elseif ( $this->_environment->inProjectRoom()
                 or $this->_environment->inCommunityRoom()
                 or $this->_environment->inPrivateRoom()
                 or $this->_environment->inGroupRoom()
               ) {
         if ($this->_module_limit == 'language') {
            $granted = $user_item->isModerator();
         } else {
            $context = $this->_environment->getCurrentContextItem();
            if ( !$context->isOpenForGuests() ) {
               $granted = $user_item->isUser();
            } else {
               $granted = true;
            }
         }
      } elseif ($this->_environment->inPortal()) { // at the portal
         if ($this->_module_limit == 'account') {
            $granted = $user_item->isModerator();
         } else {
            $granted = true;
         }
      } else {
         $granted = true; // TBD
      }
      unset($user_item);
      return $granted;
   }

   function _isExternalUserAllowedToSee($uid,$iid){
   	 $item_manager = $this->_environment->getItemManager();
   	 return $item_manager->getExternalViewerForItem($iid,$uid);
   }

    function check ($uid, $auth_source) {
        $value = false;
        $context_user = NULL;
        $portal_user = NULL;
        $context = $this->_environment->getCurrentContextItem();

        if (!$this->_environment->inServer() && $uid != 'root') {
            $portal_user = $this->_getPortalUserItem($uid,$auth_source);

            if (isset($portal_user) && $portal_user->isUser()) {
                $context_user = $this->_getContextUserItem($uid,$auth_source);

                if (isset($context_user)) {
                    $this->_environment->setCurrentUserItem($context_user);
                    $value = $this->_isUserAllowedHere($context_user);

                    if (!$value) {
                        $translator = $this->_environment->getTranslationObject();

                        if ($context_user->isRejected()) {
                            $this->_error_array[] = $translator->getMessage('ROOM_JOIN_ERROR_IS_DENIED',$context->getTitle(),$context->getTitle());
                        } elseif ($context_user->isRequested()) {
                            $this->_error_array[] = $translator->getMessage('ROOM_JOIN_ERROR_HAS_REQUESTED',$context->getTitle(),$context->getTitle());
                        } else {
                            $this->_error_array[] = $translator->getMessage('LOGIN_NOT_ALLOWED');
                        }
                    }
                } elseif (
                    isset($_GET['iid']) &&
                    ($this->_environment->getCurrentFunction() == 'detail') &&
                    $this->_isExternalUserAllowedToSee($uid, $_GET['iid'])
                ) {
                    $value = true;
                } elseif (
                    ($this->_environment->getCurrentModule() == 'material') &&
                    ($this->_environment->getCurrentFunction() == 'getfile') &&
                    isset($_GET['iid'])
                ) {
                    $current_user_item = $this->_environment->getCurrentUserItem();
                    $manager = $this->_environment->getLinkItemFileManager();
                    $manager->setFileIDLimit($_GET['iid']);
                    $manager->select();
                    $list = $manager->get();

                    if (isset($list) && $list->isNotEmpty()) {
                        $item = $list->getFirst();
                        $item_manager = $this->_environment->getItemManager();
                        $item_item = $item_manager->getItem($item->getLinkedItemID());
                        $item_type = $item_item->getItemType();

                        if ($item_type == 'section') {
                            $section_manager = $this->_environment->getSectionManager();
                            $section_item = $section_manager->getItem($item_item->getItemID());
                            $material_item = $section_item->getLinkedItem();
                            $value = $this->_isExternalUserAllowedToSee($uid,$material_item->getItemID()) || $material_item->mayPortfolioSee($portal_user->getRelatedPrivateRoomUserItem());

                            if (!$value) {
                                // check if this is a community room and materials are open for guests
                                $currentContextItem = $this->_environment->getCurrentContextItem();
                                $value = $currentContextItem->isOpenForGuests() && $currentContextItem->isMaterialOpenForGuests();
                            }
                        } elseif ($item_type == 'material') {
                            $value = $this->_isExternalUserAllowedToSee($uid,$item_item->getItemID()) || $item_item->mayPortfolioSee($portal_user->getRelatedPrivateRoomUserItem());

                            if (!$value) {
                                // check if this is a community room and materials are open for guests
                                $currentContextItem = $this->_environment->getCurrentContextItem();
                                $value = $currentContextItem->isOpenForGuests() && $currentContextItem->isMaterialOpenForGuests();
                            }
                        } elseif ($item_type == 'discarticle') {
                            $discarticle_manager = $this->_environment->getDiscussionArticleManager();
                            $discarticle_item = $discarticle_manager->getItem($item_item->getItemID());
                            $discussion_item = $discarticle_item->getLinkedItem();
                            $value = $this->_isExternalUserAllowedToSee($uid,$discussion_item->getItemID()) || $discussion_item->mayPortfolioSee($portal_user->getRelatedPrivateRoomUserItem());
                        } elseif ($item_type == 'step') {
                            $step_manager = $this->_environment->getStepManager();
                            $step_item = $step_manager->getItem($item_item->getItemID());
                            $step_item = $step_item->getLinkedItem();
                            $value = $this->_isExternalUserAllowedToSee($uid,$step_item->getItemID()) || $step_item->mayPortfolioSee($portal_user->getRelatedPrivateRoomUserItem());
                        }
                    }
                } elseif ($this->_environment->getCurrentModule() == 'annotation') {
                    $value = false;

                    if (
                        ($this->_environment->getCurrentFunction() == 'edit') &&
                        isset($_GET['ref_iid']) &&
                        $this->_isExternalUserAllowedToSee($uid, $_GET['ref_iid'])
                    ) {
                        $value = true;
                    } elseif (
                        ($this->_environment->getCurrentModule() == 'annotation') &&
                        ($this->_environment->getCurrentFunction() == 'edit')
                        && isset($_GET['iid'])
                    ) {
                        $annotation_manager = $this->_environment->getAnnotationManager();
                        $annotation_item = $annotation_manager->getItem($_GET['iid']);
                        $linked_item = $annotation_item->getLinkedItem();

                        if ($this->_isExternalUserAllowedToSee($uid, $linked_item->getItemID())) {
                            $value = true;
                        }
                    } elseif (
                        ($this->_environment->getCurrentModule() == 'annotation') &&
                        ($this->_environment->getCurrentFunction() == 'edit') &&
                        isset($_POST['iid'])
                    ) {
                        $annotation_manager = $this->_environment->getAnnotationManager();
                        $annotation_item = $annotation_manager->getItem($_POST['iid']);
                        $linked_item = $annotation_item->getLinkedItem();

                        if ($this->_isExternalUserAllowedToSee($uid, $linked_item->getItemID())) {
                            $value = true;
                        }
                    }

                    $value = true;
                } elseif ($context->isOpenForGuests() || $this->_module_limit == 'agb') {
                    $value = true;
                } else {
                    $context = $this->_environment->getCurrentContextItem();
                    $portal = $this->_environment->getCurrentPortalItem();
                    $translator = $this->_environment->getTranslationObject();

                    if ( !$context->isClosed() ) {
                        $params = array();
                        $params['cs_modus'] = 'become_member';
                        $link_to_register = ahref_curl($this->_environment->getCurrentContextID(), 'home', 'index',$params,$this->_translator->getMessage('COMMON_REGISTER_HERE'));
                        unset($params);

                        if ($context->isProjectRoom()) {
                            $this->_error_array[] = $translator->getMessage('ROOMS_ACCESS_NOT_GRANTED',$context->getTitle(),$link_to_register);
                        } elseif ($context->isCommunityRoom()) {
                            $this->_error_array[] = $translator->getMessage('COMMUNITY_ACCESS_NOT_GRANTED',$context->getTitle(),$link_to_register);
                        } elseif ($context->isGroupRoom()) {
                            $this->_error_array[] = $translator->getMessage('GROUPROOM_ACCESS_NOT_GRANTED',$context->getTitle());
                        }
                    } else {
                        $this->_error_array[] = $translator->getMessage('ROOM_IS_CLOSED',$context->getTitle()).' '.$this->_translator->getMessage('ROOM_IS_CLOSED_APPLY_FOR_MEMBERSHIP');
                    }
                }
            } else {
                $value = $this->_isUserAllowedHere($portal_user);
                $this->_environment->setCurrentUserItem($portal_user);

                if (!$value) {
                    $translator = $this->_environment->getTranslationObject();
                    $this->_error_array[] = $translator->getMessage('LOGIN_NOT_ALLOWED');
                }
            }
        } else { // server or uid == root
            if ($uid == 'root') {
                $userManager = $this->_environment->getUserManager();
                $context_user = $userManager->getRootUser();
                $this->_environment->setCurrentUserItem($context_user);
            } else {
                $portal_user = $this->_getPortalUserItem($uid,$auth_source); // for create guest user
            }

            $value = true;
        }

        if (isset($context_user)) {
            $this->_user_item = $context_user;
        } elseif (isset($portal_user)) {
            $this->_user_item = $portal_user;
            $this->_user_item->setStatus(0);
        } else {
            $this->_user_item = NULL;
        }

        if ($this->_module_limit == 'help') { //help
            $value = true;
        } elseif ( $this->_module_limit == 'picture' && $this->_function_limit == 'getfile') { // get picture
            $value = true;
        }

        return $value;
    }
   
   private function _mergeAccountSingleRoom ($account_new,$auth_source_new,$account_old,$auth_source_old,$room,$user_new) {
   	$room_user = $room->getUserByUserID($account_old,$auth_source_old);
   	$room_user->setUserID($account_new);
   	$room_user->setAuthSource($auth_source_new);
   	if (isset($user_new)) {
   		$room_user->setFirstname($user_new->getFirstname());
   		$room_user->setLastname($user_new->getLastname());
   	}
   	$room_user->save();
   }
   
   private function _mergeAccountDoubleRoom ($account_new,$auth_source_new,$account_old,$auth_source_old,$room) {
   	$room_user_old = $room->getUserByUserID($account_old,$auth_source_old);
   	$room_user_new = $room->getUserByUserID($account_new,$auth_source_new);
   	if ( isset($room_user_old) ) {
   		$id_old = $room_user_old->getItemID();
   		$status_old = $room_user_old->getStatus();
   	}
   	if ( isset($room_user_new) ) {
   		$id_new = $room_user_new->getItemID();
   		$status_new = $room_user_new->getStatus();
   	}
   	
   	if ( isset($status_old) and isset($status_new) and $status_old > $status_new) {
   		$room_user_new->setStatus($status_old);
   		$room_user_new->save();
   	}
   	
   	$manager_array = array();
   	$manager_array[] = CS_ANNOTATION_TYPE;
   	$manager_array[] = CS_ANNOUNCEMENT_TYPE;
   	$manager_array[] = CS_DATE_TYPE;
   	$manager_array[] = CS_DISCARTICLE_TYPE;
   	$manager_array[] = CS_DISCUSSION_TYPE;
   	$manager_array[] = CS_FILE_TYPE;
   	$manager_array[] = CS_LABEL_TYPE;
   	$manager_array[] = CS_LINK_TYPE;
   	$manager_array[] = CS_LINKITEM_TYPE;
   	$manager_array[] = CS_LINKMODITEM_TYPE;
   	$manager_array[] = CS_MATERIAL_TYPE;
   	$manager_array[] = CS_READER_TYPE;
   	$manager_array[] = CS_ROOM_TYPE;
   	$manager_array[] = CS_SECTION_TYPE;
   	$manager_array[] = CS_TASK_TYPE;
   	$manager_array[] = CS_PORTAL_TYPE;
   	$manager_array[] = CS_TODO_TYPE;
   	$manager_array[] = CS_TAG_TYPE;
   	$manager_array[] = CS_TAG2TAG_TYPE;
   	#$manager_array[] = CS_LOG_TYPE;
   	#$manager_array[] = CS_LOGARCHIVE_TYPE;
   	$manager_array[] = CS_ITEM_TYPE;
   	
   	if ( isset($id_new) and !empty($id_new)
   			and isset($id_old) and !empty($id_old)
   	   ) {
   		foreach ($manager_array as $manager_type) {
   			$manager = $this->_environment->getManager($manager_type);
   			$manager->mergeAccounts($id_new,$id_old);
   			unset($manager);
   		}
   	}
   	if ( isset($room_user_old) ) {
 			$room_user_old->delete();
   	}
   	unset($room_user_old);
   	unset($room_user_new);
   }
   
   private function _mergeAccountPrivateRoom ($account_new,$auth_source_new,$account_old,$auth_source_old,$user_new,$user_old) {
      $privateroom_manager = $this->_environment->getPrivateRoomManager();
      $private_room_item_old = $privateroom_manager->getRelatedOwnRoomForUser($user_old,$this->_environment->getCurrentPortalID());
   	$private_room_item_new = $privateroom_manager->getRelatedOwnRoomForUser($user_new,$this->_environment->getCurrentPortalID());
   	$user_private_room_new = $private_room_item_new->getUserByUserID($account_new,$auth_source_new);
   	$creator_id = $user_private_room_new->getItemID();
   	$old_room_id = $private_room_item_old->getItemID();
   	$new_room_id = $private_room_item_new->getItemID();
   	$new_id_array = array();
   	
   	// copy data
   	$data_type_array   = array();
   	$data_type_array[] = CS_DATE_TYPE;
   	$data_type_array[] = CS_LABEL_TYPE;
   	$data_type_array[] = CS_MATERIAL_TYPE;
   	$data_type_array[] = CS_FILE_TYPE;
   	$data_type_array[] = CS_TAG_TYPE;
   	#$data_type_array[] = CS_ANNOUNCEMENT_TYPE;
   	#$data_type_array[] = CS_TODO_TYPE;
   	
   	foreach ($data_type_array as $type) {
   		$manager = $this->_environment->getManager($type);
   		$id_array = $manager->copyDataFromRoomToRoom($old_room_id,$new_room_id,$creator_id);
   		$new_id_array = $new_id_array + $id_array;
   	}
   	unset($data_type_array);
   	
   	// copy secondary data
   	$data_type_array   = array();
   	$data_type_array[] = CS_ANNOTATION_TYPE;
   	$data_type_array[] = CS_SECTION_TYPE;
   	#$data_type_array[] = CS_DISCARTICLE_TYPE;
   	
   	foreach ($data_type_array as $type) {
   		$manager = $this->_environment->getManager($type);
   		$id_array = $manager->copyDataFromRoomToRoom($old_room_id,$new_room_id,$creator_id,$new_id_array);
   		$new_id_array = $new_id_array + $id_array;
   	}
   	unset($data_type_array);
   	
   	// copy links
   	$data_type_array   = array();
   	$data_type_array[] = CS_LINK_TYPE;
   	$data_type_array[] = CS_LINKITEM_TYPE;
   	$data_type_array[] = CS_LINKITEMFILE_TYPE;
   	$data_type_array[] = CS_TAG2TAG_TYPE;
   	
   	foreach ($data_type_array as $type) {
   		$manager = $this->_environment->getManager($type);
   		$id_array = $manager->copyDataFromRoomToRoom($old_room_id,$new_room_id,$creator_id,$new_id_array);
   		$new_id_array = $new_id_array + $id_array;
   	}
   	unset($data_type_array);
   	
   	// link modifier item
   	$manager = $this->_environment->getLinkModifierItemManager();
   	foreach ($id_array as $value) {
   		if ( !mb_stristr($value,CS_FILE_TYPE) ) {
   			$manager->markEdited($value,$creator_id);
   		}
   	}
   	
   	// now change all old item ids in descriptions with new IDs
   	// copy data
   	$data_type_array   = array();
   	#$data_type_array[] = CS_ANNOUNCEMENT_TYPE;
   	$data_type_array[] = CS_DATE_TYPE;
   	$data_type_array[] = CS_LABEL_TYPE;
   	$data_type_array[] = CS_MATERIAL_TYPE;
   	#$data_type_array[] = CS_TODO_TYPE;
   	$data_type_array[] = CS_ANNOTATION_TYPE;
   	#$data_type_array[] = CS_DISCARTICLE_TYPE;
   	$data_type_array[] = CS_SECTION_TYPE;
   	foreach ($data_type_array as $type) {
   		$manager = $this->_environment->getManager($type);
   		$manager->refreshInDescLinks($new_room_id,$new_id_array);
   	}
   	unset($data_type_array);
   	
   	// delete old private room
   	$private_room_item_old->delete();
   }
   
   private function _mergeAccountPrepareRoomLists ($user_new,$user_old) {
   	$retour = array();

      // separate rooms in
      // - rooms where old user and new user are in
      // - rooms where only old user is in
      include_once('classes/cs_list.php');
      $list_only = new cs_list();
      $list_double = new cs_list();

      $user_new_project_array = array();
      $user_new_community_array = array();
   	
      $user_old_project_list = $user_old->getRelatedProjectListAllUserStatus();
      $user_old_community_list = $user_old->getRelatedCommunityListAllUserStatus();
      // grouproom with projectroom
      $user_new_project_list = $user_new->getRelatedProjectListAllUserStatus();
      $user_new_community_list = $user_new->getRelatedCommunityListAllUserStatus();
      // grouproom with projectroom

      if ($user_new_project_list->isNotEmpty()) {
         $user_new_room = $user_new_project_list->getFirst();
         while ($user_new_room) {
            $user_new_project_array[] = $user_new_room->getItemID();
            $user_new_room = $user_new_project_list->getNext();
         }
         unset($user_new_room);
      }
      if ($user_new_community_list->isNotEmpty()) {
         $user_new_room = $user_new_community_list->getFirst();
         while ($user_new_room) {
            $user_new_community_array[] = $user_new_room->getItemID();
            $user_new_room = $user_new_community_list->getNext();
         }
         unset($user_new_room);
      }

      if ($user_old_project_list->isNotEmpty()) {
         $user_old_room = $user_old_project_list->getFirst();
         while ($user_old_room) {
            if (in_array($user_old_room->getItemID(),$user_new_project_array)) {
               $list_double->add($user_old_room);
            } else {
               $list_only->add($user_old_room);
            }
            $user_old_room = $user_old_project_list->getNext();
         }
         unset($user_old_room);
      }
      if ($user_old_community_list->isNotEmpty()) {
         $user_old_room = $user_old_community_list->getFirst();
         while ($user_old_room) {
            if (in_array($user_old_room->getItemID(),$user_new_community_array)) {
               $list_double->add($user_old_room);
            } else {
               $list_only->add($user_old_room);
            }
            $user_old_room = $user_old_community_list->getNext();
         }
         unset($user_old_room);
      }
      
      $retour['single'] = $list_only;
      $retour['double'] = $list_double;
      
   	return $retour;
   }
   
   private function _mergeAccountGetUser ($account,$auth_source) {
   	$retour = NULL;
   	$user_manager = $this->_environment->getUserManager();
   	$user_manager->setContextLimit($this->_environment->getCurrentPortalID());
   	$user_manager->setAuthSourceLimit($auth_source);
   	$user_manager->setUserIdLimit($account);
   	$user_manager->select();
   	$user_list = $user_manager->get();
   	$retour = $user_list->getFirst();
   	return $retour;
   }

   function mergeAccount ($account_new,$auth_source_new,$account_old,$auth_source_old) {

      if ( $account_new == $account_old
           and $auth_source_new == $auth_source_old
         ) {
         include_once('functions/error_functions.php');
         trigger_error('you can not merge your account with yourself',E_USER_WARNING);
      } else {

         $user_old = $this->_mergeAccountGetUser($account_old,$auth_source_old);
      	$user_new = $this->_mergeAccountGetUser($account_new,$auth_source_new);
      	
      	// archive rooms - BEGIN
      	$this->_environment->toggleArchiveMode();
      	
         $room_list_array = $this->_mergeAccountPrepareRoomLists($user_new,$user_old);
         $list_only = $room_list_array['single'];
         $list_double = $room_list_array['double'];

         // room list only -> change user id, auth source and name
         if ($list_only->isNotEmpty()) {
            $room = $list_only->getFirst();
            while ($room) {
            	$this->_mergeAccountSingleRoom($account_new,$auth_source_new,$account_old,$auth_source_old,$room,$user_new);
   	         unset($room);
            	$room = $list_only->getNext();
            }
         }

         // room list double -> change user item id
         if ($list_double->isNotEmpty()) {
            $room = $list_double->getFirst();
            while ($room) {
            	$this->_mergeAccountDoubleRoom($account_new,$auth_source_new,$account_old,$auth_source_old,$room);
   	         unset($room);
            	$room = $list_double->getNext();
            }
         }
      	
         $this->_environment->toggleArchiveMode();
      	// archive rooms - END
      	    
      	
      	$room_list_array = $this->_mergeAccountPrepareRoomLists($user_new,$user_old);
         $list_only = $room_list_array['single'];
         $list_double = $room_list_array['double'];

         // room list only -> change user id, auth source and name
         if ($list_only->isNotEmpty()) {
            $room = $list_only->getFirst();
            while ($room) {
            	$this->_mergeAccountSingleRoom($account_new,$auth_source_new,$account_old,$auth_source_old,$room,$user_new);
   	         unset($room);
            	$room = $list_only->getNext();
            }
         }

         // room list double -> change user item id
         if ($list_double->isNotEmpty()) {
            $room = $list_double->getFirst();
            while ($room) {
            	$this->_mergeAccountDoubleRoom($account_new,$auth_source_new,$account_old,$auth_source_old,$room);
   	         unset($room);
            	$room = $list_double->getNext();
            }
         }

         // merge private room
         $this->_mergeAccountPrivateRoom($account_new,$auth_source_new,$account_old,$auth_source_old,$user_new,$user_old);

         // delete portal
         $this->_mergeAccountDoubleRoom($account_new,$auth_source_new,$account_old,$auth_source_old,$this->_environment->getCurrentPortalItem());
         
         // delete auth information
         $auth_manager = $this->getAuthManager($auth_source_old);
         $auth_manager->delete($account_old);
      }
   }

   function changeUserID ($new, $old_item) {
      $old_user_id = $old_item->getUserID();
      $auth_manager = $this->getAuthManager($old_item->getAuthSource());
      $success = $auth_manager->changeUserID($new,$old_user_id);
      if ($success) {
         $user_manager = $this->_environment->getUserManager();
         $success = $user_manager->changeUserID($new,$old_item);
      }
      if (!$success) {
         $auth_manager->changeUserID($old_user_id,$new);
      }
      $this->_used_auth_manager = $auth_manager;
      return $success;
   }

   function getAuthSourceItemID () {
      return $this->_used_auth_manager->getAuthSourceItemID();
   }

   function getGrantedAuthSourceItemID () {
      return $this->_auth_source_granted;
   }

   function logToFile($msg){
     $fd = fopen('', "a");
     $str = "[" . date("Y/m/d h:i:s", mktime()) . "] " . $msg;
     fwrite($fd, $str . "\n");
     fclose($fd);
   }
}
?>
