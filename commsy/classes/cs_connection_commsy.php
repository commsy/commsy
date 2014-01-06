<?PHP
// $Id$
//
// Release $Name$
//
// Copyright (c)2013 Dr. Iver Jackewitz
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

class cs_connection_commsy {

   private $_environment = null;
   private $_translator = null;
   private $_error_array = array();
   private $_connection = null;

   public function __construct ($environment) {
      $this->_environment = $environment;
      $this->_translator = $this->_environment->getTranslationObject();
   }

   private function _addError ($text,$id="") {
   	if ( !empty($text) ) {
   		if ( !empty($id) ) {
   			$this->_error_array[$id] = $text;
   		} else {
   			$this->_error_array[] = $text;
   		}
   	}
   }
   
   public function getErrorArray () {
   	return $this->_error_array;
   }
   
   private function _initConnection ($url,$proxy="") {
   	$retour = false;
   	if ( !empty($url) ) {
	      if ( class_exists('SoapClient') ) {
	   		$options = array("trace" => 1, "exceptions" => 0, 'user_agent'=>'PHP-SOAP/php-version', 'connection_timeout' => 150);
	   		if ( $this->_environment->getConfiguration('c_proxy_ip')
	   			  and ( $proxy == CS_YES
	   			  		  or empty($proxy)
	   			  		)
	   		   ) {
	   			$options['proxy_host'] = $this->_environment->getConfiguration('c_proxy_ip');
	   		}
	   		if ( $this->_environment->getConfiguration('c_proxy_port')
	   			  and ( $proxy == CS_YES
	   			  		  or empty($proxy)
	   			  		)
	   		   ) {
	   			$options['proxy_port'] = $this->_environment->getConfiguration('c_proxy_port');
	   		}
	   		$soap_url = $url.'/soap_wsdl.php';
	   		$new_server = new SoapClient($soap_url, $options);
	   		if ( empty($new_server) ) {
	   			$this->_addError($this->_translator->getMessage('SERVER_CONNECTION_ERROR_SOAP_CONNECT',$url));
	   		} else {
	   			$this->_connection = $new_server;
	   			$retour = true;
	   		}
	   	} else {
	   		$this->_addError($this->_translator->getMessage('SERVER_CONNECTION_ERROR_PHP_SOAP'));
	   	}
   	} else {
   		$this->_addError($this->_translator->getMessage('SERVER_CONNECTION_ERROR_URL_EMPTY'));
   	}
   	return $retour;
   }
   
   public function testConnection ($url,$key,$proxy="") {
   	$retour = false;
   	if ( $this->_initConnection($url,$proxy) ) {
   		// are all required methods available??
   		$server_function_array = $this->_connection->__getFunctions();
   		$needed_function_array = $this->_getNeededFunctionArray();
   		$compatible = true;
   		
   		foreach ( $needed_function_array as $name => $params ) {
   			$result_type = $params['out']['result'];
   			$in = '';
   			$first = true;
   			foreach ( $params['in'] as $key => $value ) {
   				if ( $first ) {
   					$first = false;
   				} else {
   					$in .= ', ';
   				}
   				$in .= $value.' $'.$key;
   			}
   			$func_string = $result_type.' '.$name.'('.$in.')';
   			if ( !in_array($func_string,$server_function_array) ) {
   				$compatible = false;
   		      $this->_addError($this->_translator->getMessage('SERVER_CONNECTION_ERROR_SOAP_COMPATIBLE',$url));
   				break;
   			}
   		}
   		
   		if ( $compatible ) {
   			$retour = true;
   		}
   		
   	} else {
   		$this->_addError($this->_translator->getMessage('SERVER_CONNECTION_ERROR_SOAP_CONNECT',$url));
   	}
   	return $retour;
   }
   
   private function _getNeededFunctionArray () {
   	$retour = array();
   	
   	$temp_array = array();
   	$temp_array['in'] = array();
   	$temp_array['in']['session_id'] = 'string';
   	$temp_array['out'] = array();
   	$temp_array['out']['result'] = 'string';
   	$retour['getPortalsAsJson'] = $temp_array;
   	unset($temp_array);
   	
   	$temp_array = array();
   	$temp_array['in'] = array();
   	$temp_array['in']['session_id'] = 'string';
   	$temp_array['in']['portal_id'] = 'integer';
   	$temp_array['in']['user_key'] = 'string';
   	$temp_array['in']['server_key'] = 'string';
   	$temp_array['out'] = array();
   	$temp_array['out']['result'] = 'string';
   	$retour['getSessionIdFromConnectionKey'] = $temp_array;
   	unset($temp_array);
   	
   	$temp_array = array();
   	$temp_array['in'] = array();
   	$temp_array['in']['session_id'] = 'string';
   	$temp_array['out'] = array();
   	$temp_array['out']['result'] = 'string';
   	$retour['getRoomListAsJson'] = $temp_array;
   	unset($temp_array);
   	
   	return $retour;
   }
   
   public function getSoapFunctionArray () {
   	return $this->_getNeededFunctionArray();
   }
   
   public function getAllOpenContextsForCurrentUser ( $tab_id ) {
   	$retour = array();
   	
   	$connect_info_array = $this->_getConnectInfosFromTabID($tab_id);
   	if ( !empty($connect_info_array['server_url'])
   		  and !empty($connect_info_array['server_key'])
   		  and !empty($connect_info_array['portal_id'])
   		  and !empty($connect_info_array['user_key'])
   		  and !empty($connect_info_array['proxy'])
   	   ) {
   		if ( $this->_initConnection($connect_info_array['server_url'],$connect_info_array['proxy']) ) {
   			$sid = $this->_connection->getGuestSession($connect_info_array['portal_id']);
   			if ( !empty($sid) ) {
   		      $sid = $this->_connection->getSessionIdFromConnectionKey($sid,$connect_info_array['portal_id'],$connect_info_array['user_key'],$connect_info_array['server_key']);
   				if ( !empty($sid) ) {
   					$result = $this->_connection->getRoomListAsJson($sid);
   					if ( !empty($result) ) {
   						$retour = json_decode($result,true);
   					}
   				}
   			}
   		}
   	}
   	
   	return $retour;
   }
   
   private function _getConnectInfosFromTabID ( $tab_id ) {
   	$retour = array();
   	
   	// getPortalUser
   	$current_user = $this->_environment->getCurrentUserItem();
   	if ( !$this->_environment->inPortal() ) {
   	   $portal_user = $current_user->getRelatedCommSyUserItem();
   	} else {
   		$portal_user = $current_user;
   	}
   	
   	// get own connection key
   	$retour['user_key'] = $portal_user->getOwnConnectionKey();
   	
   	// get tab from current portal user
   	$tab_info = $portal_user->getPortalConnectionInfo($tab_id);
   	$retour['portal_id'] = $tab_info['portal_connection_id'];
   	
   	// get info from server connection
   	$server_item = $this->_environment->getServerItem();
   	$retour['server_key'] = $server_item->getOwnConnectionKey();
   	
   	$server_info = $server_item->getServerConnectionInfo($tab_info['server_connection_id']);
   	$retour['server_url'] = $server_info['url'];
   	$retour['proxy'] = $server_info['proxy'];
   	
   	return $retour;
   }
   
   // SOAP functions
   public function getSessionIdFromConnectionKeySOAP ($session_id,$portal_id,$user_key,$server_key) {
   	$retour = '';
   	$auth = true;
   	
   	// test server key
   	$server_item = $this->_environment->getServerItem();
   	$server_connection_info = $server_item->getServerConnectionInfoByKey($server_key);
   	if ( empty($server_connection_info) ) {
   		$auth = false;
   	}
   	
   	// find user with user_key [TBD]
   	if ( $auth ) {
   		$user_manager = $this->_environment->getUserManager();
   		$user_manager->setContextLimit($portal_id);
   		// TBD
   		/*
   	    [user_key] => 954c534de6646a0a71a8ed37e7374b98
   	   */
   		#$user_manager->setExternalConnectionUserKeyLimit($user_key);
   		$user_manager->setUserIDLimit('jackewit'); // test - delete [TBD]
   		$user_manager->select();
   		$user_list = $user_manager->get();
   		if ( !empty($user_list)
   			  and $user_list->isNotEmpty()
   			  and $user_list->getCount() == 1
   			) {
   			$user_item = $user_list->getFirst();
   			$user_id = $user_item->getUserID();
   			$auth_source_id = $user_item->getAuthSource();
   		} else {
   			$auth = false;
   		}
   	}
   	
   	if ( $auth ) {
         $result = $this->_getActiveSessionIDFromConnectionKey($user_key,$portal_id);
         if ( empty($result) ) {
            // make session
            include_once('classes/cs_session_item.php');
            $session = new cs_session_item();
            $session->createSessionID($user_id);
            // save portal id in session to be sure, that user didn't
            // switch between portals
            $session->setValue('user_id',$user_id);
            $session->setValue('commsy_id',$portal_id);
            $session->setValue('auth_source',$auth_source_id);
            $session->setValue('CONNECTION_KEY',$user_key);
            $session->setValue('cookie','3'); // special handling for commsy connections
            $session->setValue('javascript','1');
            
            // save session
            $session_manager = $this->_environment->getSessionManager();
            $session_manager->save($session);

            $retour = $session->getSessionID();
         } else {
         	// cookie management
         	$session_manager = $this->_environment->getSessionManager();
         	$session_item = $session_manager->get($result);
         	if ( $session_item->issetValue('cookie')
         		  and $session_item->getValue('cookie') != 3
         		) {
         		// save cookie again when user jump to other portal
         		$session_item->setValue('cookie',3);
         		$session_manager->save($session_item);
         	}
         	$retour = $result;
         }
      }
   	
   	return $retour;
   }
   
   private function _getActiveSessionIDFromConnectionKey($user_key,$portal_id) {
   	$retour = '';
   	
      $session_manager = $this->_environment->getSessionManager();
   	$result = $session_manager->getActiveSOAPSessionIDFromConnectionKey($user_key,$portal_id);
   	if ( !empty($result) ) {
   		$retour = $result;
   	}
   	
   	return $retour;
   }
   
   public function getRoomListAsJsonSOAP ( $session_id ) {
   	$retour = '';
   	
   	// set context
   	$context = false;
      $this->_environment->setSessionID($session_id);
      $session = $this->_environment->getSessionItem();
      $user_id = $session->getValue('user_id');
      $auth_source_id = $session->getValue('auth_source');
      $context_id = $session->getValue('commsy_id');
      $this->_environment->setCurrentContextID($context_id);
      $user_manager = $this->_environment->getUserManager();
      $user_manager->setContextLimit($context_id);
      $user_manager->setUserIDLimit($user_id);
      $user_manager->setAuthSourceLimit($auth_source_id);
      $user_manager->select();
      $user_list = $user_manager->get();
      if ( $user_list->getCount() == 1 ) {
         $user_item = $user_list->getFirst();
         if ( !empty($user_item) ) {
            $this->_environment->setCurrentUserItem($user_item);
            $context = true;
         }
      }
      
      // get room list
      if ( $context ) {
         include_once('classes/controller/ajax/popup/cs_popup_connection_controller.php');
   	   $controller = new cs_popup_connection_controller($this->_environment);
   	   $result = $controller->getRoomListArray();
      }
      
      // add infos
      if ( !empty($result) ) {
         foreach ( $result as $key => $headline ) {
         	foreach ( $headline as $key2 => $subline ) {
         		foreach ( $subline[rooms] as $key3 => $room ) {
         			$url_to_portal = '';
         			$current_portal = $this->_environment->getCurrentPortalItem();
         			if ( !empty($current_portal) ) {
         				$url_to_portal = $current_portal->getURL();
         			}
         			
         			if ( !empty($url_to_portal) ) {
         				$c_commsy_domain = $this->_environment->getConfiguration('c_commsy_domain');
         				if ( stristr($c_commsy_domain,'https://') ) {
         					$url = 'https://';
         				} else {
         					$url = 'http://';
         				}
         				$url .= $url_to_portal;
         			} else {
         				$url = $this->_environment->getConfiguration('c_commsy_domain');
         				$c_commsy_url_path = $this->_environment->getConfiguration('c_commsy_url_path');
         				if ( !empty($c_commsy_url_path) ) {
         					$url .= $c_commsy_url_path;
         				}
         			}
         			
         			$file = 'commsy.php';
         			$c_single_entry_point = $this->_environment->getConfiguration('c_single_entry_point');
         			if ( !empty($c_single_entry_point) ) {
         				$file = $c_single_entry_point;
         			}
         			$url .= '/'.$file.'?cid=';
         			
         			$url .= $room['item_id'];
         			$url .= '&SID='.$session_id;
         			
         			$result[$key][$key2]['rooms'][$key3]['url'] = $url;
         		}
         	}
         }
      }
      
      // result to json
      if ( !empty($result) ) {
      	$retour = json_encode($result);
      }
      
   	return $retour;
   }
}
?>