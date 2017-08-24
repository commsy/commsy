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

if (!empty($_POST['option'])) {
   $command = $_POST['option'];
} else {
   $command = '';
}

if (!empty($_GET['back_tool'])) {
   $back_file = $_GET['back_tool'].'.php';
} else {
   $back_file = '';
}

// case: login with CommSy
if ( isset($session) ) {
   $history = $session->getValue('history');
   $cookie = $session->getValue('cookie');
   $javascript = $session->getValue('javascript');
   $https = $session->getValue('https');
   $flash = $session->getValue('flash');
}
// case: login with external login box
else {
   $history = array();
   $cookie = '';
   $javascript = '';
   $https = '';
   $flash = '';
}
$portal_item = $environment->getCurrentPortalItem();
if(!empty($portal_item)){
	// shibboleth auth source and direct login configured?
	$shib_auth_source = NULL;
	$auth_source_list = $portal_item->getAuthSourceList();
	$auth_item = $auth_source_list->getFirst();
	while($auth_item) {
		if($auth_item->getSourceType() == 'Shibboleth') {
			$shib_auth_source = $auth_item;
		}
		$auth_item = $auth_source_list->getNext();
	}
	if(!empty($shib_auth_source)) {
		// activate shibboleth redirect if configured
		$shib_direct_login = $shib_auth_source->getShibbolethDirectLogin();
	}
}

// user_id and password
$user_id = '';
if ( !empty($_POST['user_id']) ) {
   $user_id = $_POST['user_id'];
} elseif ( !empty($_GET['user_id']) ) {
   $user_id = $_GET['user_id'];
} elseif ($shib_direct_login) {
	$user_id = $_SERVER['Shib_uid'];
}

$password = '';
if ( !empty($_POST['password']) ) {
   $password = $_POST['password'];
} elseif ( !empty($_GET['password']) ) {
   $password = $_GET['password'];
}

$count = 0;

$redirectAfterLogin = false;

if (!$shib_direct_login){
	//Shibboleth
	// Über das Portal überprüfen, ob Shibboleth als Auth eingestellt ist
	
	if(!empty($_POST['auth_source'])){
		$auth_source_manager = $environment->getAuthSourceManager();
		$auth_source_item = $auth_source_manager->getItem($_POST['auth_source']);
		$source_type = $auth_source_item->getSourceType();
		$auth_data = $auth_source_item->getAuthData();
		//$host = $auth_data['HOST'];
	}
	if(!empty($source_type) AND $source_type == "Shibboleth"){
	    if(!empty($_SERVER['uid']) AND !empty($_SERVER['Shib_Session_ID'])){
	    	$authentication = $environment->getAuthenticationObject();
	    	if ( isset($_POST['auth_source']) and !empty($_POST['auth_source']) ) {
	      		$auth_source = $_POST['auth_source'];
	   		} else {
	      		$auth_source = '';
	   		}
	        #if($host == $_SERVER['HTTP_HOST']){
	            // Benutzer ist eingeloggt // root extra!?
	            $session = new cs_session_item();
	            // Session from Shibboleth identity provider
	            $session->setSessionID(substr($_SERVER['Shib_Session_ID'],1));
	            $session->setValue("user_id", $_SERVER["Shib_uid"]);
	            // Benutzer muss erstellt werden
	            
	            #$session->createSessionID($user_id);
	            
	            if ( $cookie == '1' ) {
	                $session->setValue('cookie',2);
	            } elseif ( empty($cookie) ) {
	                // do nothing, so CommSy will try to save cookie
	            } else {
	                $session->setValue('cookie',0);
	            }
	            if ($javascript == '1') {
	                $session->setValue('javascript',1);
	            } elseif ($javascript == '-1') {
	                $session->setValue('javascript',-1);
	            }
	            if ($https == '1') {
	                $session->setValue('https',1);
	            } elseif ($https == '-1') {
	                $session->setValue('https',-1);
	            }
	            if ($flash == '1') {
	                $session->setValue('flash',1);
	            } elseif ($flash == '-1') {
	                $session->setValue('flash',-1);
	            }
	
	            // save portal id in session to be sure, that user didn't
	            // switch between portals
	            if ( $environment->inServer() ) {
	 				$session->setValue('commsy_id',$environment->getServerID());
	            } else {
	 				$session->setValue('commsy_id',$environment->getCurrentPortalID());
	            }
	
	            // auth_source
	            if ( empty($auth_source) ) {
	                $auth_source = $authentication->getAuthSourceItemID();
	            }
	            $session->setValue('auth_source',$auth_source);
	       # }
	    } else {
	        // Benutzer nicht beim IDP eingeloggt, redirect zum idp?
	        redirect_with_url('https://'.$_SERVER["SERVER_NAME"].'/Shibboleth.sso/Login');
	    }# and $source_type != "SHIBBOLETH"
	} elseif (!empty($user_id) and !empty($password) and (empty($source_type) || $source_type != "Shibboleth")) { 
	//if (!empty($user_id) and !empty($password) ) {
	   $authentication = $environment->getAuthenticationObject();
	   if ( isset($_POST['auth_source']) and !empty($_POST['auth_source']) ) {
	      $auth_source = $_POST['auth_source'];
	   } else {
	      $auth_source = '';
	   }
	   if(!empty($auth_source)){
	   	$auth_manager = $environment->getAuthSourceManager();
	   	$auth_item = $auth_manager->getItem($auth_source);
	   	unset($auth_manager);
	   }
	   $portal_item = $environment->getCurrentContextItem();
	   // get user item if temporary lock is enabled
	   $userExists = false;
	   $locked_temp = false;
	   $locked = false;
	   $login_status = $authentication->isAccountGranted($user_id,$password,$auth_source);
	   if(isset($auth_item) AND !empty($auth_item)){
   		if($portal_item->isTemporaryLockActivated() or $portal_item->getInactivityLockDays() > 0){
	   		$user_manager = $environment->getUserManager();
	   		$userExists = $user_manager->exists($user_id);
	   		unset($user_manager);
	   		if($userExists){
	   			$user_locked = $authentication->_getPortalUserItem($user_id,$authentication->_auth_source_granted);
		   		if(isset($user_locked)){
		   			$locked = $user_locked->isLocked();
			   		$locked_temp = $user_locked->isTemporaryLocked();
		   		}
	
	   		}
	   	}
	   }
	   // user access granted
	   if ($login_status AND !$locked_temp AND !$locked) {
	      $session = new cs_session_item();
	      $session->createSessionID($user_id);
	      if ( $cookie == '1' ) {
	         $session->setValue('cookie',2);
	      } elseif ( empty($cookie) ) {
	         // do nothing, so CommSy will try to save cookie
	      } else {
	         $session->setValue('cookie',0);
	      }
	      if ($javascript == '1') {
	         $session->setValue('javascript',1);
	      } elseif ($javascript == '-1') {
	         $session->setValue('javascript',-1);
	      }
	      if ($https == '1') {
	         $session->setValue('https',1);
	      } elseif ($https == '-1') {
	         $session->setValue('https',-1);
	      }
	      if ($flash == '1') {
	         $session->setValue('flash',1);
	      } elseif ($flash == '-1') {
	         $session->setValue('flash',-1);
	      }
	
	      // save portal id in session to be sure, that user didn't
	      // switch between portals
	      if ( $environment->inServer() ) {
	         $session->setValue('commsy_id',$environment->getServerID());
	      } else {
	         $session->setValue('commsy_id',$environment->getCurrentPortalID());
	      }
	
	      // auth_source
	      if ( empty($auth_source) ) {
	         $auth_source = $authentication->getAuthSourceItemID();
	      }
	      $session->setValue('auth_source',$auth_source);

           if(isset($auth_item) && !empty($auth_item)) {
               $userManager = $environment->getUserManager();
               $userExists = $userManager->exists($user_id);

               if($userExists){
                   $userItem = $authentication->_getPortalUserItem($user_id,$authentication->_auth_source_granted);
                   if(isset($userItem)) {
                       global $symfonyContainer;
                       $router = $symfonyContainer->get('router');

                       $dashboardUrl = $router->generate('commsy_dashboard_overview', [
                           'roomId' => $userItem->getOwnRoom()->getItemId(),
                       ]);

                       $redirectAfterLogin = $dashboardUrl;
                   }
               }
           }
	
	   } else {
	   	  // user access is not granted 
	   	  // Datenschutz
	   	  $current_context = $environment->getCurrentContextItem();
	      $error_array = $authentication->getErrorArray();
	      
	      if ( isset($_POST['auth_source']) and !empty($_POST['auth_source']) ) {
	      	$auth_source = $_POST['auth_source'];
	      } else {
	      	$auth_source = '';
	      }
	      // auth_source
	      if ( empty($auth_source) ) {
	      	$auth_source = $authentication->getAuthSourceItemID();
	      }
	      if(!empty($auth_source)){
	      	$auth_manager = $environment->getAuthSourceManager();
	      	$auth_item = $auth_manager->getItem($auth_source);
	      	unset($auth_manager);
	      }
	            

	    $portal_item = $environment->getCurrentPortalItem();
      	if($portal_item && $portal_item->isTemporaryLockActivated()){
	      	// Erster Fehlversuch // Timestamp in session speichern und
		      // Password tempLock
		      $userExists = false;
		      $user_manager = $environment->getUserManager();
		      $userExists = $user_manager->exists($user_id);
		      $tempUser = $session->getValue('userid');
		      if(!isset($tempUser)){
		      	$session->setValue('userid', $user_id);
		      	$tempUser = $user_id;
		      }
		      if(!$session->issetValue('TMSP_'.$user_id) or $session->getValue('TMSP_'.$user_id) < getCurrentDateTimeMinusSecondsInMySQL($current_context->getLockTimeInterval())){
		      	$session->setValue('TMSP_'.$user_id, getCurrentDateTimeInMySQL());
		      }
		      $count = $session->getValue('countWrongPassword');
		      // Password tempLock ende
	      }
	      if ( !isset($session) ) {
	         $session = new cs_session_item();
	         $session->createSessionID('guest');
	         //Password tempLock
	         $session->setValue('countWrongPassword', 1);
	      } else {
      		if($portal_item && $portal_item->isTemporaryLockActivated()){
		       	$count = $session->getValue('countWrongPassword');
		       	if(!isset($count) AND empty($count)){
		       		$session->setValue('countWrongPassword', 1);
		       	}
		       	if(!isset($count)){
		       		$count = 0;
		       	}
		       	if($user_id == $tempUser){
		       		$count++;
		       	} else {
		       		$count = 0;
		       		$session->setValue('countWrongPassword', 0);
		       		$session->setValue('userid', $user_id);
		       	}
		       	$trys_login = $current_context->getTryUntilLock();
		       	if(empty($trys_login)){
		       		$trys_login = 3;
		       	}
	       		if($count >= $trys_login AND $userExists AND !$locked AND !$locked_temp AND $session->getValue('TMSP_'.$session->getValue('userid')) >= getCurrentDateTimeMinusSecondsInMySQL($current_context->getLockTimeInterval())){
	       			$user = $authentication->_getPortalUserItem($tempUser,$authentication->_auth_source_granted);
	       			$user->setTemporaryLock();
	       			$user->save();
	       			$count = 0;
	       			$session->setValue('countWrongPassword', 0);
		       	}
	      	}
	       	$session->setValue('countWrongPassword', $count);
	      }
	      // Password tempLock ende 
	      $session->setValue('error_array',$error_array);
	      unset($user_manager);
	   } 
	   if($locked){
	   	$translator = $environment->getTranslationObject();
	   	$error_array = array();
	   	$error_array[] = $translator->getMessage('COMMON_TEMPORARY_LOCKED_DAYS');#'Kennung ist vorübergehend gesperrt';
	   	$session->setValue('error_array',$error_array);
	   }
	   if($locked_temp){
	   	$translator = $environment->getTranslationObject();
	   	$error_array = array();
	   	$error_array[] = $translator->getMessage('COMMON_TEMPORARY_LOCKED', $current_context->getLockTime());#'Kennung ist vorübergehend gesperrt';
	   	$session->setValue('error_array',$error_array);
	   }
	} elseif ( empty($user_id) or empty($password) ) {
	   $translator = $environment->getTranslationObject();
	   $error_array = array();
	   if ( empty($user_id) ) {
	      $error_array[] = $translator->getMessage('COMMON_ERROR_FIELD',$translator->getMessage('COMMON_ACCOUNT'));
	   }
	   if ( empty($password) ) {
	      $error_array[] = $translator->getMessage('COMMON_ERROR_FIELD',$translator->getMessage('COMMON_PASSWORD'));
	   }
	   if ( !isset($session) ) {
	      $session = new cs_session_item();
	      $session->createSessionID('guest');
	   }
	   $session->setValue('error_array',$error_array);
	}
} else {
    $user_manager = $environment->getUserManager();
    
    if(empty($_SERVER['Shib_Session_ID']) AND empty($_SERVER['Shib_uid'])){
    	// Normales Anmelden funktioniert nicht mehr!
//     	$session = new cs_session_item();
// 		$session->createSessionID('guest');
// 		redirect($environment->getCurrentPortalID(), 'home', 'index', $parameterArray);
// 		exit;
    }
    
    // if the user object does not exists, make sure we are in portal context before creating it
    if (!$environment->inPortal() && !$user_manager->exists($_SERVER['Shib_uid'])) {
        $parameterArray = $environment->getCurrentParameterArray();
        $parameterArray['room_id'] = $environment->getCurrentContextID();
        redirect($environment->getCurrentPortalID(), 'home', 'index', $parameterArray);
        exit;
    }
    
	// shibboleth direct login
	
	$portal_item = $environment->getCurrentContextItem();
	// get user item if temporary lock is enabled
	
	// user access granted

	$session = new cs_session_item();
	$session->createSessionID($user_id);
	if ( $cookie == '1' ) {
		$session->setValue('cookie',2);
	} elseif ( empty($cookie) ) {
		// do nothing, so CommSy will try to save cookie
	} else {
		$session->setValue('cookie',0);
	}
	if ($javascript == '1') {
		$session->setValue('javascript',1);
	} elseif ($javascript == '-1') {
		$session->setValue('javascript',-1);
	}
	if ($https == '1') {
		$session->setValue('https',1);
	} elseif ($https == '-1') {
		$session->setValue('https',-1);
	}
	if ($flash == '1') {
		$session->setValue('flash',1);
	} elseif ($flash == '-1') {
		$session->setValue('flash',-1);
	}
	
	$authentication = $environment->getAuthenticationObject();
	$shibboleth_auth = $authentication->getShibbolethAuthSource();
	
	// get shibboleth keys from configuration
	if(isset($shibboleth_auth)){
		$uidKey = $shibboleth_auth->getShibbolethUsername();
		$mailKey = $shibboleth_auth->getShibbolethEmail();
		$commonNameKey = $shibboleth_auth->getShibbolethFirstname();
		$sureNameKey = $shibboleth_auth->getShibbolethLastname();
	}
	
	// create new user item if not exist
	if (!empty($_SERVER[$uidKey]) AND !$user_manager->exists($_SERVER[$uidKey])) {
		$user_item = $user_manager->getNewItem();
		$user_item->setUserID($_SERVER[$uidKey]);
		$user_item->setEmail($_SERVER[$mailKey]);
		$user_item->setFirstname($_SERVER[$commonNameKey]);
		$user_item->setLastname($_SERVER[$sureNameKey]);
		$user_item->setAuthSource($shibboleth_auth->getItemID());
		$user_item->setStatus('2');
		$user_item->makeUser();
		$user_item->save();
		$user_item->getOwnRoom();
		$environment->setCurrentUser($user_item);
	}

	// save portal id in session to be sure, that user didn't
	// switch between portals
	if ( $environment->inServer() ) {
		$session->setValue('commsy_id',$environment->getServerID());
	} else {
		$session->setValue('commsy_id',$environment->getCurrentPortalID());
	}
	
	$session->setValue('auth_source',$shibboleth_auth->getItemID());
	
}

if ( isset($session) ) {
   $environment->setSessionItem($session);
}

// redirect
if (!empty($_POST['login_redirect'])) {
    redirect_with_url($_POST['login_redirect']);
} elseif ( !empty($_GET['target_cid']) ) {
   $mod = 'home';
   $fct = 'index';
   $params = array();
   redirect($_GET['target_cid'],$mod,$fct,$params);
} else {
   if ( !empty($history[0]['context']) ) {
      $cid = $history[0]['context'];
   } else {
      $cid = $environment->getCurrentContextID();
   }

   if ( !empty($history[0]['module']) ) {
      $mod = $history[0]['module'];
   } else {
      $mod = $environment->getCurrentModule();
   }

   if ( !empty($history[0]['function']) ) {
      $fct = $history[0]['function'];
   } else {
      $fct = $environment->getCurrentFunction();
   }

   if ( !isset($history[0]['parameter']) ) {
      $params = $environment->getCurrentParameterArray();
   } else {
      $params = $history[0]['parameter'];
   }

   if ( isset($error_array) and !empty($error_array) ) {
      if ( isset($auth_source) and !empty($auth_source) ) {
         $params['auth_source'] = $auth_source;
      }
   }
   if ($mod == 'context' && $fct == 'login') {
      $mod = 'home';
      $fct = 'index';

      if ($redirectAfterLogin) {
          redirect_with_url($redirectAfterLogin);
	  }
   }

   redirect($cid,$mod,$fct,$params,'','',$back_file);
}
?>
