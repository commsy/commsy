<?PHP
// $Id$
//
// Release $Name$
//
// Copyright (c)2002-2003 Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
// Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
// Edouard Simon, Monique Strauss, José Manuel González Vázquez
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

/** cs_auth_item is needed to create auth items
 */
include_once('classes/cs_auth_item.php');
include_once('classes/cs_auth_manager.php');

/** class for database connection to a LDAP-server
 * this class implements a manager for LDAP authentication
 */
class cs_auth_ldap extends cs_auth_manager {

   /**
    * strong - containing to the url of the ldap-server
    */
   var $_server;

   /**
    * integer - containing the port of the ldap-server
    */
   var $_server_port;

   /**
    * string - containing a string with baseuser information
    */
   var $_baseuser;

   /**
    * string - containing a string with baseuser information
    */
   var $_rootuser;

   /**
    * string - containing a string with password information
    */
   var $_rootuser_password;

  /**
   * string - containing LDAP field containing User-ID
   */
   var $_field_userid = 'uid';

  /**
   * string - search userid in LDAP field
   */
   var $_search_userid = 'samaccountname';

   /**
    * string - containing a uid from a user with write access
    */
   var $_user;

   /**
    * string - containing the password of the user above
    */
   var $_password;

   /**
   * string - containing the error text if an error occured
   */
   var $_dberror;

   /**
   * boolean - containing the a flag if accounts can be deleted
   */
   var $_with_delete_accounts = false;

   /**
   * array - containing the error messages
   */
   var $_error_array = array();

   private $_user_dn   = NULL;
   private $_user_data = NULL;

   /*
    * Translation Object
    */
   private $_translator = null;
   
   private $_server_all           = array();
   private $_server_failed        = array();
   private $_server_selected      = NULL;
   private $_server_selected_save = NULL;
    
   /** constructor: cs_auth_ldap
    * the only available constructor, initial values for internal variables
    *
    * @param string server url to ldap-server
    * @param string baseuser information about baseuser
    */
   function __construct() {
      global $environment;
      $this->_translator = $environment->getTranslationObject();
   }

   function setAuthSourceItem ($value) {
      parent::setAuthSourceItem($value);
      $auth_data_array = $value->getAuthData();
      $this->_server = $auth_data_array['HOST'];
      $this->_server_port = $auth_data_array['PORT'];
      $this->_baseuser = $auth_data_array['BASE'];
      if ( !empty($auth_data_array['USER']) ) {
         $this->_rootuser = $auth_data_array['USER'];
      }
      if ( !empty($auth_data_array['PASSWORD']) ) {
         $this->_rootuser_password = $auth_data_array['PASSWORD'];
      }
      if ( !empty($auth_data_array['ENCRYPTION']) ) {
         $this->_encryption = $auth_data_array['ENCRYPTION'];
      }
      if ( !empty($auth_data_array['DBCOLUMNUSERID']) ) {
         $this->_field_userid = $auth_data_array['DBCOLUMNUSERID'];
      }
      if ( !empty($auth_data_array['DBSEARCHUSERID']) ) {
         $this->_search_userid = $auth_data_array['DBSEARCHUSERID'];
      } elseif ( !empty($this->_field_userid) ) {
      	$this->_search_userid = $this->_field_userid;
      }
      
      // additional server - BEGIN
      if ( !empty($auth_data_array['additional_server']) ) {
      	$temp_array = array();
      	$temp_array['host'] = $this->_server;
      	$temp_array['port'] = $this->_server_port;
      	$this->_server_all[] = $temp_array;
      	unset($temp_array);
      	$this->_server_all = array_merge($this->_server_all,$auth_data_array['additional_server']);
      }
      if ( !empty($auth_data_array['select_server']) ) {
      	$this->_server_selected = $auth_data_array['select_server'];
      	$this->_server_selected_save = $auth_data_array['select_server'];
      }
      if ( !empty($this->_server_selected)
      	  and $this->_server_selected != $this->_server
      	) {
      	$this->_server = $this->_server_selected;
      	$this->_server_port = $this->_getPort($this->_server_all,$this->_server_selected);
      }
      // additional server - END
   }
   
   private function _getPort ($array, $name) {
   	$retour = '';
   	foreach ( $array as $value_array ) {
   		if ( $value_array['host'] == $name ) {
   			if ( !empty($value_array['port']) ) {
   			   $retour = $value_array['port'];
   			}
   			break; 
   		}
   	}
   	return $retour;
   }

   /** set user with write access
    * this method sets the user with write access
    *
    * @param string value user id
    */
   function setUser ($value) {
      $this->_user = (string)$value;
   }

   /** set password for user with write access
    * this method sets the password for the user with write access
    *
    * @param string value password
    */
   function setPassword ($value) {
      $this->_password = (string)$value;
   }

   /** is the account granted ?
    * this method returns a boolean, if the account is granted in MySQL.
    *
    * @param string uid user id of the current user
    * @param string password the password of the current user
    *
    * @return boolean true, account is granted in MySQL
    *                 false, account is not granted in MySQL
    */
   function checkAccount ($uid, $password) {

      $granted = false;
      /** check if password is correct */
      if ( empty($password) or mb_strlen($password) == 0 ) {
         $password = microtime();
      }
      if ( stristr($uid,'=')
           and stristr($uid,',')
         ) {
         $access = $uid;
      } else {
         $access = $this->_field_userid.'='.$uid.','.$this->_baseuser;
      }
      $connect = @ldap_connect( $this->_server, $this->_server_port );
      if ( !$connect ) {
         // additional server
         if ( !empty($this->_server_all)
         	  and count($this->_server_all) > 1 
         	) {
         	// delete current server from array
         	$temp_array = array();
         	foreach ( $this->_server_all as $server_array ) {
         		if ( $server_array['host'] != $this->_server_selected ) {
         			$temp_array[] = $server_array;
         		}
         	}
         	$this->_server_all = $temp_array;
         	unset($temp_array);
         	$this->_server_selected = $this->_server_all[0]['host'];
         	$this->_server = $this->_server_all[0]['host'];
         	if ( !empty($this->_server_all[0]['port']) ) {
         	   $this->_server_port = $this->_server_all[0]['port'];
         	} else {
         		$this->_server_port = '';
         	}
         	$granted = $this->checkAccount($uid, $password);
         } else {
      	   include_once('functions/error_functions.php');
            trigger_error('could not connect to server '.$this->_server.', '.$this->_server_port,E_USER_WARNING);
         }
      } else {
      	@ldap_set_option($connect,LDAP_OPT_PROTOCOL_VERSION,3);
         @ldap_set_option($connect,LDAP_OPT_REFERRALS,0);
         $bind = @ldap_bind( $connect, $access, $this->encryptPassword($password) );
         if ( $bind ) {
            $granted = true;
            $this->_user_dn = $access;
         } elseif ( !empty($this->_rootuser)
                    and !empty($this->_rootuser_password)
                  ) {
            $access_first = $access;
            $suchfilter = "(".$this->_search_userid."=".$uid.")";
            if ( ( strstr($this->_rootuser,',')
                   and strstr($this->_rootuser,'=')
                 )
                 or strstr($this->_rootuser,'@')
               ) {
               $access_root = $this->_rootuser;
            } else {
               $access_root = $this->_field_userid.'='.$this->_rootuser.','.$this->_baseuser;
            }
            $bind = @ldap_bind($connect, $access_root, $this->encryptPassword($this->_rootuser_password));
            if ( $bind ) {
            	
            	// additional server
            	// save auth source item
            	if ( !empty($this->_server_all) ) {
            		if ( $this->_server_selected != $this->_server_selected_save ) {
            	      $auth_data_array = $this->_auth_source_item->getAuthData();
            	      $auth_data_array['select_server'] = $this->_server_selected;
            	      $this->_auth_source_item->setAuthData($auth_data_array);
            	      $this->_auth_source_item->save();
            		}
               }
            	// additional server
            	
               $base_user_array = explode(',',$this->_baseuser);
               $count = count($base_user_array);
               for ( $i=0; $i<$count; $i++  ) {
                  if ( $bind ) {
                     $baseuser = implode(',',$base_user_array);
                     $search = @ldap_search($connect,$baseuser,$suchfilter);
                     $result = ldap_get_entries($connect,$search);
                     if ( $result['count'] != 0 ) {
                        $this->_user_data[$uid] = $this->_cleanLDAPArray($result[0]);
                        $access = $result[0]['dn'];
                        break;
                     }
                  }
                  array_shift($base_user_array);
               }

               if ( mb_strtolower($access, 'UTF-8') != mb_strtolower($access_first, 'UTF-8') ) {
                  $bind = @ldap_bind( $connect, $access, $this->encryptPassword($password) );
                  if ( $bind ) {
                     $granted = true;
                     $this->_user_dn = $access;
                  } else {
                     $this->_error_array[] = $this->_translator->getMessage('AUTH_ERROR_ACCOUNT_OR_PASSWORD',$uid);
                  }
               } else {
                  $this->_error_array[] = $this->_translator->getMessage('AUTH_ERROR_ACCOUNT_OR_PASSWORD',$uid);
               }
            } else {
            	// additional server
            	if ( !empty($this->_server_all)
            		  and count($this->_server_all) > 1 
            		) {
            		// delete current server from array
            		$temp_array = array();
            		foreach ( $this->_server_all as $server_array ) {
            			if ( $server_array['host'] != $this->_server_selected ) {
            				$temp_array[] = $server_array;
            			}
            		}
            		$this->_server_all = $temp_array;
            		unset($temp_array);
            		$this->_server_selected = $this->_server_all[0]['host'];
              		$this->_server = $this->_server_all[0]['host'];
            		if ( !empty($this->_server_all[0]['port']) ) {
            		   $this->_server_port = $this->_server_all[0]['port'];
            		} else {
            			$this->_server_port = '';
            		}
            		$granted = $this->checkAccount($uid, $password);
            	} else {
                  $this->_error_array[] = $this->_translator->getMessage('AUTH_ERROR_LDAP_ROOTUSER');
            	}
            }
         } else {
            $this->_error_array[] = $this->_translator->getMessage('AUTH_ERROR_ACCOUNT_OR_PASSWORD',$uid);
         }
         @ldap_unbind($connect);
         @ldap_close($connect);
      }
      return $granted;
   }


   /** exists an user_id ? - NOT IMPLEMENTED YET
    * this method returns a boolean whether the user_id exists in the ldap-database or not
    *
    * @param integer user_id id of the user (not item id)
    *
    * @return boolean true, if authentication already exists
    *                 false, if authentication not exists -> new user
    */
  public function exists ($user_id) {
     // not implemented yet
     include_once('functions/error_functions.php');
     trigger_error('The methode EXISTS [LDAP] is not implemented!',E_USER_ERROR);
     return true;
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

  /** save an authentication - NOT IMPLEMENTED YET
    * save an authentication into the ldap-database
    *
    * @param object cs_item item the authentication item
    */
  function save ($item) {
     // not implemented yet
     include_once('functions/error_functions.php');
     trigger_error('The methode SAVE [LDAP] is not implemented!',E_USER_ERROR);
  }

  /** change password - NOT IMPLEMENTED YET
    * this method changes the user password in the ldap-database
    *
    * @param string user_id the user id of the user
    * @param string password the new password of the user
    */
  function changePassword ($user_id, $password) {
     // not implemented yet
     include_once('functions/error_functions.php');
     trigger_error('The methode CHANGEPASSWORD [LDAP] is not implemented!',E_USER_ERROR);
  }

  /** delete an LDAP account - NOT IMPLEMENTED YET
    * this method deletes an LDAP account in the ldap-database
    *
    * @param string user_id the user id of the user
    */
  function delete ($user_id) {
     if ($this->_with_delete_accounts) {
        // not implemented yet
        include_once('functions/error_functions.php');
        trigger_error('The methode DELETE [LDAP] is not implemented!',E_USER_ERROR);
     }
  }

  /** get authentication item for a user (user_id) - NOT IMPLEMENTED YET
    * this method returns a authentication item for a user
    *
    * @param integer user_id id of the user (not item id)
    *
    * @return object cs_item an authentication item
    */
  function get ($user_id) {
     // not implemented yet
     include_once('functions/error_functions.php');
     trigger_error('The methode GET [LDAP] is not implemented!',E_USER_ERROR);
  }

  /** get commsy error text
    * this method returns the text of an error in commsy style, if an error occured
    *
    * @return string error number
    */
   function getErrorArray () {
      return $this->_error_array;
   }

   /** get auth item form the auth_manager - NOT IMPLEMENTED YET
    * this method returns an auth item form the auth_manager
    *
    * @return object auth_item of the user
    */
   function getItem () {
      #return $this->_item;
      include_once('functions/error_functions.php');
      trigger_error('The methode getItem [LDAP] is not implemented!',E_USER_ERROR);
   }

   /** get user information out of the auth source
    * this method returns an array of informations form the user
    * in the auth source
    *
    * @return array data of the user
    */
   public function get_data_for_new_account ($uid, $password) {
      $user_data_array = array();
      $retour = array( 'firstname' => '',
                       'lastname' => '',
                       'email' => '');
      if ( !empty($uid) ) {
         if ( empty($this->_user_data[$uid]) ) {
            $this->_fillUserData($uid,$password);
         }
         if ( !empty($this->_user_data[$uid]) ) {
            $user_data_array = $this->_user_data[$uid];
         }
         if ( !empty($user_data_array['givenname']) ) {
            $retour['firstname'] = $user_data_array['givenname'];
         }
         if ( !empty($user_data_array['sn']) ) {
            $retour['lastname'] = $user_data_array['sn'];
         }
         if ( !empty($user_data_array['mail']) ) {
            $retour['email'] = $user_data_array['mail'];
         }
      }
      return $retour;
   }

   private function _fillUserData ($uid, $password) {
      $user_dn = '';
      $user_uid = '';
      $user_password = '';
      if ( empty($password)
           and !empty($this->_rootuser)
           and !empty($this->_rootuser_password)
         ) {
         $user_uid = $this->_rootuser;
         $user_password = $this->_rootuser_password;
      } elseif ( !empty($uid)
                 and !empty($password)
               ) {
         $user_uid = $uid;
         $user_password = $password;
      } else {
         return;
      }
      if ( empty($this->_user_dn) ) {
         if ( !$this->checkAccount($user_uid,$user_password) ) {
            return;
         }
      }
      if ( !empty($this->_user_dn) ) {
         $user_dn = $this->_user_dn;
      }
      if ( !empty($user_dn) ) {
         $connect = @ldap_connect($this->_server,$this->_server_port);
         @ldap_set_option($connect,LDAP_OPT_PROTOCOL_VERSION,3);
         @ldap_set_option($connect,LDAP_OPT_REFERRALS,0);
         $bind = @ldap_bind( $connect, $user_dn, $this->encryptPassword($user_password) );
         if ( $bind ) {
            $suchfilter = "(".$this->_field_userid."=".$uid.")";
            $base_user_array = explode(',',$this->_baseuser);
            $count = count($base_user_array);
            for ( $i=0; $i<$count; $i++  ) {
               if ( $bind ) {
                  $baseuser = implode(',',$base_user_array);
                  $search = @ldap_search($connect,$baseuser,$suchfilter);
                  $result = ldap_get_entries($connect,$search);
                  if ( $result['count'] != 0 ) {
                     $this->_user_data[$uid] = $this->_cleanLDAPArray($result[0]);
                     $access = $result[0]['dn'];
                     break;
                  }
               }
               array_shift($base_user_array);
            }
         }
      }
   }

   private function _cleanLDAPArray ( $value ) {
      $retour = $value;
      if ( !empty($retour) ) {
         $retour2 = array();
         foreach ( $retour as $key => $value ) {
            if ( !is_numeric($key) ) {
               if ( is_array($value) ) {
                  array_shift($value);
                  if ( count($value) == 1 ) {
                     $value = $value[0];
                  }
               }
               $retour2[$key] = $value;
            }
         }
         $retour = $retour2;
      }
      unset($retour['dscorepropagationdata']);
      unset($retour['usncreated']);
      unset($retour['usnchanged']);
      unset($retour['objectguid']);
      unset($retour['codepage']);
      unset($retour['countrycode']);
      unset($retour['objectsid']);
      unset($retour['objectcategory']);
      unset($retour['samaccounttype']);
      if ( !empty($retour['whencreated']) ) {
         $retour['whencreated'] = $this->_OZ2Time($retour['whencreated']);
      }
      if ( !empty($retour['whenchanged']) ) {
         $retour['whenchanged'] = $this->_OZ2Time($retour['whenchanged']);
      }
      if ( !empty($retour['badpasswordtime']) ) {
         $retour['badpasswordtime'] = date('d.m.Y H:i:s',$this->_win_filetime_to_timestamp($retour['badpasswordtime']));
      }
      if ( !empty($retour['lastlogon']) ) {
         $retour['lastlogon'] = date('d.m.Y H:i:s',$this->_win_filetime_to_timestamp($retour['lastlogon']));
      }
      if ( !empty($retour['pwdlastset']) ) {
         $retour['pwdlastset'] = date('d.m.Y H:i:s',$this->_win_filetime_to_timestamp($retour['pwdlastset']));
      }
      if ( !empty($retour['lastlogontimestamp']) ) {
         $retour['lastlogontimestamp'] = date('d.m.Y H:i:s',$this->_win_filetime_to_timestamp($retour['lastlogontimestamp']));
      }
      if ( !empty($retour['accountexpires']) ) {
         $retour['accountexpires'] = date('d.m.Y H:i:s',$this->_win_filetime_to_timestamp($retour['accountexpires']));
      }
      ksort($retour);
      return $retour;
   }

   private function _OZ2Time ( $value ) {
      $retour = '';
      $tag = $value[6].$value[7];
      $monat = $value[4].$value[5];
      $jahr = $value[0].$value[1].$value[2].$value[3];
      $stunde = $value[8].$value[9];
      $minute = $value[10].$value[11];
      $sekunde = $value[12].$value[13];
      $retour = $tag.'.'.$monat.'.'.$jahr.' '.$stunde.':'.$minute.':'.$sekunde;
      return $retour;
   }

   private function _win_filetime_to_timestamp ( $filetime ) {
      $win_sec = substr($filetime,0,strlen($filetime)-7); // divide by 10 000 000 to get seconds
      $unix_timestamp = ($win_sec - 11644473600); // 1.1.1600 -> 1.1.1970 difference in seconds
      return $unix_timestamp;
   }
}
?>