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

            global $symfonyContainer;
            $c_proxy_ip = $symfonyContainer->getParameter('commsy.settings.proxy_ip');
            $c_proxy_port = $symfonyContainer->getParameter('commsy.settings.proxy_port');

	   		if ($c_proxy_ip && ($proxy == CS_YES || empty($proxy))) {
	   			$options['proxy_host'] = $c_proxy_ip;
	   		}
	   		if ($c_proxy_port && ($proxy == CS_YES || empty($proxy))) {
	   			$options['proxy_port'] = $c_proxy_port;
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
   	
   	$temp_array = array();
   	$temp_array['in'] = array();
   	$temp_array['out'] = array();
   	$temp_array['out']['result'] = 'string';
   	$retour['getPortalListAsJson'] = $temp_array;
   	unset($temp_array);
   	
   	$temp_array = array();
   	$temp_array['in'] = array();
   	$temp_array['in']['session_id'] = 'string';
   	$temp_array['in']['user_key'] = 'string';
   	$temp_array['out'] = array();
   	$temp_array['out']['result'] = 'string';
   	$retour['saveExternalConnectionKey'] = $temp_array;
   	unset($temp_array);
   	
   	$temp_array = array();
   	$temp_array['in'] = array();
   	$temp_array['in']['session_id'] = 'string';
   	$temp_array['out'] = array();
   	$temp_array['out']['result'] = 'string';
   	$retour['getOwnConnectionKey'] = $temp_array;
   	unset($temp_array);
   	
   	$temp_array = array();
   	$temp_array['in'] = array();
   	$temp_array['in']['session_id'] = 'string';
   	$temp_array['in']['server_key'] = 'string';
   	$temp_array['in']['portal_id'] = 'integer';
   	$temp_array['in']['tab_id'] = 'string';
   	$temp_array['in']['user_key'] = 'string';
   	$temp_array['out'] = array();
   	$temp_array['out']['result'] = 'string';
   	$retour['setPortalConnectionInfo'] = $temp_array;
   	unset($temp_array);
   	
   	$temp_array = array();
   	$temp_array['in'] = array();
   	$temp_array['in']['session_id'] = 'string';
   	$temp_array['in']['tab_id'] = 'string';
   	$temp_array['out'] = array();
   	$temp_array['out']['result'] = 'string';
   	$retour['deleteConnection'] = $temp_array;
   	unset($temp_array);
   	
   	return $retour;
   }
   
   public function getSoapFunctionArray () {
   	return $this->_getNeededFunctionArray();
   }
   
   public function getPortalArrayFromServer ( $server_id ) {
   	$retour = array();
   	
   	$server_item = $this->_environment->getServerItem();
   	$server_info = $server_item->getServerConnectionInfo($server_id);
   	if ( !empty($server_info)
   		  and !empty($server_info['url'])
   		  and !empty($server_info['proxy'])
   	   ) {
   		if ( $this->_initConnection($server_info['url'],$server_info['proxy']) ) {
  				$result = $this->_connection->getPortalListAsJson($sid);
  				if ( !empty($result) ) {
  					$retour = json_decode($result,true);
  				}
   		}
   	}
   	
   	return $retour;
   }
   
   public function getAllOpenContextsForCurrentUser ( $tab_id ) {
   	$retour = array();
   	
   	$connect_info_array = $this->_getConnectInfosFromTabID($tab_id);
   	if ( !empty($connect_info_array['server_url'])
   		  and !empty($connect_info_array['server_key'])
   		  and !empty($connect_info_array['portal_id'])
   		  and !empty($connect_info_array['user_key'])
   		  and !empty($connect_info_array['user_key_external'])
   		  and !empty($connect_info_array['proxy'])
   	   ) {
   		if ( $this->_initConnection($connect_info_array['server_url'],$connect_info_array['proxy']) ) {
   			$sid = $this->_connection->getGuestSession($connect_info_array['portal_id']);
   			if ( !empty($sid) ) {
   		      $sid = $this->_connection->getSessionIdFromConnectionKey($sid,$connect_info_array['portal_id'],$connect_info_array['user_key_external'],$connect_info_array['server_key']);
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
   	$retour['user_key_external'] = $tab_info['user_key_external'];
   	
   	// get info from server connection
   	$server_item = $this->_environment->getServerItem();
   	$retour['server_key'] = $server_item->getOwnConnectionKey();
   	
   	$server_info = $server_item->getServerConnectionInfo($tab_info['server_connection_id']);
   	$retour['server_url'] = $server_info['url'];
   	$retour['proxy'] = $server_info['proxy'];
   	
   	// add info from external portal
   	$retour['id_external'] = $tab_info['id_external'];
   	
   	return $retour;
   }
   
   public function deleteConnection ( $tab_id ) {
   	$retour = false;
   	
   	// get connection infos
   	$connect_info_array = $this->_getConnectInfosFromTabID($tab_id);   	
   	if ( !empty($connect_info_array['server_url'])
   			and !empty($connect_info_array['server_key'])
   			and !empty($connect_info_array['portal_id'])
   			and !empty($connect_info_array['user_key'])
   			and !empty($connect_info_array['user_key_external'])
   			and !empty($connect_info_array['proxy'])
   			and !empty($connect_info_array['id_external'])
   	   ) {
   		if ( $this->_initConnection($connect_info_array['server_url'],$connect_info_array['proxy']) ) {
   			$sid = $this->_connection->getGuestSession($connect_info_array['portal_id']);
   			if ( !empty($sid) ) {
   				$sid = $this->_connection->getSessionIdFromConnectionKey($sid,$connect_info_array['portal_id'],$connect_info_array['user_key_external'],$connect_info_array['server_key']);
   				if ( !empty($sid) ) {
   					$result = $this->_connection->deleteConnection($sid,$connect_info_array['id_external']);
   					if ( !empty($result)
   						  and !is_soap_fault($result)
   						  and $result == 'success'
   						) {
   						$retour = true;
   					}
   				}
   			}
   		}
   	}
   	
   	return $retour;
   }
   
   public function saveNewConnection ( $server_id, $portal_id, $userid, $password ) {
   	$retour = '';
   	
   	if ( !empty($server_id)
   			and !empty($portal_id)
   			and !empty($userid)
   			and !empty($password)
   	   ) {
   		
   	   // server info
   	   $server_item = $this->_environment->getServerItem();
   	   $server_info = $server_item->getServerConnectionInfo($server_id);
   	   
   		if ( $this->_initConnection($server_info['url'],$server_info['proxy']) ) {
   			
   			// login
   			$sid = $this->_connection->authenticate($userid,$password,$portal_id);
   			if ( !empty($sid) ) {
   				$userid_session = $this->_connection->authenticateViaSession($sid);
   			}
   			if ( !empty($sid)
   				  and !is_soap_fault($sid)
   				  and $userid_session != 'guest'
   				  and $userid_session == $userid
   				) {
   				
   				// change personal keys
   				// first: save local key to external portal user
   				
   				// getPortalUser
   				$current_user = $this->_environment->getCurrentUserItem();
   				if ( !$this->_environment->inPortal() ) {
   					$portal_user = $current_user->getRelatedCommSyUserItem();
   				} else {
   					$portal_user = $current_user;
   				}
   				$result = $this->_connection->saveExternalConnectionKey($sid,$portal_user->getOwnConnectionKey());
   				if ( $result == 'success' ) {
   					
   					// second: get external own key and save
   					$result = $this->_connection->getOwnConnectionKey($sid);
   					if ( !empty($result)
   						  and !is_soap_fault($result)
   						) {
   						$portal_user->addExternalConnectionKey($result);
   						$portal_user->save();
   						$external_own_key = $result;
   					}

   					// save tabs  					
   					// first: save tab local
   					$new_tab = array();
   					$new_tab['server_connection_id'] = $server_id;
   					$new_tab['portal_connection_id'] = $portal_id;
   					$portal_array = $this->getPortalArrayFromServer($server_id);
   					foreach ( $portal_array as $portal_info ) {
   						if ( $portal_info['id'] == $portal_id ) {
   							$new_tab['title'] = $portal_info['title'];
   							$new_tab['title_original'] = $portal_info['title'];
   							break;
   						}
   					}
   					if ( empty($new_tab['title']) ) {
   					   $new_tab['title'] = 'Portal';
   					}
   				   if ( empty($new_tab['title_original']) ) {
   					   $new_tab['title_original'] = 'unknown';
   					}
   					$new_tab['id'] = md5($new_tab['portal_connection_id'].rand(0,100).date(YmdHis).rand(0,100).$new_tab['title_original']);
   					if ( !empty($external_own_key) ) {
   						$new_tab['user_key_external'] = $external_own_key;
   					}
   					
   					// second: save tab external
   					$result = $this->_connection->setPortalConnectionInfo($sid,$server_item->getOwnConnectionKey(),$portal_user->getContextID(), $new_tab['id'],$portal_user->getOwnConnectionKey());
   					if ( !empty($result)
   						  and !is_soap_fault($result)
   						  and $result != 'failed'
   					   ) {
   						$new_tab['id_external'] = $result;
   					} else {
   					   $retour = 'SAVE_TAB_FAILED';
   					}
   					
   					$tab_array = $portal_user->getPortalConnectionArrayDB();
   					$tab_array[] = $new_tab;
   					unset($temp_array);
   					$portal_user->setPortalConnectionInfoDB($tab_array);
   					$portal_user->save();
   					
   					$retour = $new_tab;
   							
   				} else {
   					$retour = 'SAVE_KEY_FAILED';
   				}
   				
   			} else {
   				$retour = 'LOGIN_FAILED';
   			}
   		}
   	} else {
   		$retour = 'DATA_LOST';
   	}

   	// return   	
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
   	
   	// find user with user_key
   	if ( $auth ) {
   		$user_manager = $this->_environment->getUserManager();
   		$user_manager->setContextLimit($portal_id);
   		#$user_manager->setExternalConnectionUserKeyLimit($user_key);
   		$user_manager->setOwnConnectionUserKeyLimit($user_key);
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
   
   public function getPortalListAsJsonSOAP() {
   	$retour = '';
   	
   	$result = array();
   	$portal_manager = $this->_environment->getPortalManager();
   	$portal_manager->select();
   	$portal_list = $portal_manager->get();
   	$xml = "<portal_list>\n";
   	$portal_item = $portal_list->getFirst();
   	while($portal_item) {
   		$temp_array = array();
   		$temp_array['id'] = $portal_item->getItemID();
   		$temp_array['title'] = $portal_item->getTitle();
   		$result[] = $temp_array;
   		unset($temp_array);
   		$portal_item = $portal_list->getNext();
   	}
   	$xml .= "</portal_list>";
      
   	// result to json
      if ( !empty($result) ) {
      	$retour = json_encode($result);
      }
      
   	return $retour;
   }
   
   public function saveExternalConnectionKeySOAP ($session_id,$user_key) {
   	$retour = 'failed';
   	
   	if ( !empty($session_id)
   		  and !empty($user_key)
   		) {
   	
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
         $retour = $user_list->getCount();
         if ( $user_list->getCount() == 1 ) {
         	$user_item = $user_list->getFirst();
            if ( !empty($user_item) ) {
               $this->_environment->setCurrentUserItem($user_item);
               $context = true;
            }
         }
         
         // save key
         if ( $context ) {
         	$user_item->addExternalConnectionKey($user_key);
         	$user_item->save();
         	$retour = 'success';
         }   		   	
   	}
   	
   	return $retour;
   }

   public function getOwnConnectionKeySOAP ($session_id) {
   	$retour = '';
   	
   	if ( !empty($session_id) ) {
   	
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
         $retour = $user_list->getCount();
         if ( $user_list->getCount() == 1 ) {
         	$user_item = $user_list->getFirst();
            if ( !empty($user_item) ) {
               $this->_environment->setCurrentUserItem($user_item);
               $context = true;
            }
         }
         
         // get key
         if ( $context ) {
         	$retour = $user_item->getOwnConnectionKey();
         }
   		   	
   	}
   	
   	return $retour;
   }
   
   public function setPortalConnectionInfoSOAP ($session_id,$server_key,$portal_id,$tab_id,$user_key) {
   	$retour = 'failed';
   	
   	if ( !empty($session_id)
   		  and !empty($server_key)
   		  and !empty($portal_id)
   		  and !empty($tab_id)
   		  and !empty($user_key)
   	   ) {
   		
   	   // set context - get portal user
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
         	$portal_user = $user_list->getFirst();
            if ( !empty($portal_user) ) {
               $this->_environment->setCurrentUserItem($portal_user);
               $context = true;
            }
         }

         // get server connection infos
         if ( $context ) {
         	$server_item = $this->_environment->getServerItem();
         	$server_info = $server_item->getServerConnectionInfoByKey($server_key);
         	
            if ( !empty($server_info['id'])
            	  and !empty($server_info['url'])
            	) {
            	// get portal name
            	$new_tab = array();
            	$new_tab['server_connection_id'] = $server_info['id'];
            	$new_tab['portal_connection_id'] = $portal_id;
            	$portal_array = $this->getPortalArrayFromServer($server_info['id']);
            	foreach ( $portal_array as $portal_info ) {
            		if ( $portal_info['id'] == $portal_id ) {
            			$new_tab['title'] = $portal_info['title'];
            			$new_tab['title_original'] = $portal_info['title'];
            			break;
            		}
            	}
            	if ( empty($new_tab['title']) ) {
            		$new_tab['title'] = 'Portal';
            	}
            	if ( empty($new_tab['title_original']) ) {
            		$new_tab['title_original'] = 'unknown';
            	}
            	$new_tab['id'] = md5($new_tab['portal_connection_id'].rand(0,100).date(YmdHis).rand(0,100).$new_tab['title_original']);
            	$new_tab['id_external'] = $tab_id;
            	$new_tab['user_key_external'] = $user_key;
            	
            	$tab_array = $portal_user->getPortalConnectionArrayDB();
            	$tab_array[] = $new_tab;
            	unset($temp_array);
            	$portal_user->setPortalConnectionInfoDB($tab_array);
            	
            	// is connection activated? if not, active it
            	$own_room = $portal_user->getOwnRoom();
            	if ( !empty($own_room)
            		  and !$own_room->showCSBarConnection()
            		) {
            		$own_room->switchOnCSBarConnection();
            		$own_room->save();
            	}
            	
            	$portal_user->save();
            	$retour = $new_tab['id'];
            }
         }
   	}
   	
   	return $retour;
   }

   public function deleteConnectionSOAP ($session_id, $tab_id) {
   	$retour = 'failed';
   	
   	if ( !empty($session_id)
   		  and !empty($tab_id)
   		) {
   	
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
         $retour = $user_list->getCount();
         if ( $user_list->getCount() == 1 ) {
         	$user_item = $user_list->getFirst();
            if ( !empty($user_item) ) {
               $this->_environment->setCurrentUserItem($user_item);
               $context = true;
            }
         }
         
         // save key
         if ( $context ) {
         	$delete = false;
         	$portal_conn_array = $user_item->getPortalConnectionArrayDB();
         	foreach ( $portal_conn_array as $key =>  $connection ) {
         		if ( $connection['id'] == $tab_id ) {
         			unset($portal_conn_array[$key]);
         			$delete = true;
         		}
         		break;
         	}
         	
         	if ( $delete ) {
         		$new_portal_conn_array = array();
         		foreach ( $portal_conn_array as $conn ) {
         			$new_portal_conn_array[] = $conn;
         		}
         		$portal_conn_array = $new_portal_conn_array;
         		
         		$user_item->setPortalConnectionInfoDB($portal_conn_array);
         		$user_item->save();
         		
         		$retour = 'success';	
         	}	
         }
   	}
   	
   	return $retour;
   }
}
?>