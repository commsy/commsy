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

// user_id and password
$user_id = '';
if ( !empty($_POST['user_id']) ) {
   $user_id = $_POST['user_id'];
} elseif ( !empty($_GET['user_id']) ) {
   $user_id = $_GET['user_id'];
}

$password = '';
if ( !empty($_POST['password']) ) {
   $password = $_POST['password'];
} elseif ( !empty($_GET['password']) ) {
   $password = $_GET['password'];
}
//Shibboleth
$auth_source_manager = $environment->getAuthSourceManager();
$auth_source_item = $auth_source_manager->getItem($_POST['auth_source']);
$source_type = $auth_source_item->getSourceType();
$auth_data = $auth_source_item->getAuthData();
$host = $auth_data['HOST'];
pr($_SERVER);
if($source_type == "Shibboleth"){
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
            $session->setValue("user_id", $_SERVER["uid"]);
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

            // external tool
            if ( mb_stristr($_SERVER['PHP_SELF'],'homepage.php') ) {
                $session->setToolName('homepage');
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
} elseif (!empty($user_id) and !empty($password) and $source_type != "Shibboleth") { 
//if (!empty($user_id) and !empty($password) ) {
   $authentication = $environment->getAuthenticationObject();
   if ( isset($_POST['auth_source']) and !empty($_POST['auth_source']) ) {
      $auth_source = $_POST['auth_source'];
   } else {
      $auth_source = '';
   }
   if ($authentication->isAccountGranted($user_id,$password,$auth_source)) {
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

      // external tool
      if ( mb_stristr($_SERVER['PHP_SELF'],'homepage.php') ) {
         $session->setToolName('homepage');
      }

      // auth_source
      if ( empty($auth_source) ) {
         $auth_source = $authentication->getAuthSourceItemID();
      }
      $session->setValue('auth_source',$auth_source);

   } else {
      $error_array = $authentication->getErrorArray();
      if ( !isset($session) ) {
         $session = new cs_session_item();
         $session->createSessionID('guest');
      }
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

if ( isset($session) ) {
   $environment->setSessionItem($session);
}

// redirect
if ( !empty($_POST['login_redirect']) ) {
   $cid = $environment->getCurrentContextID();
   if ( !empty($_POST['login_redirect']['cid']) ) {
      $cid = $_POST['login_redirect']['cid'];
   }
   $mod = 'home';
   if ( !empty($_POST['login_redirect']['mod']) ) {
      $mod = $_POST['login_redirect']['mod'];
   }
   $fct = 'index';
   if ( !empty($_POST['login_redirect']['fct']) ) {
      $fct = $_POST['login_redirect']['fct'];
   }
   $params = $_POST['login_redirect'];
   unset($params['cid']);
   unset($params['mod']);
   unset($params['fct']);
   redirect($cid,$mod,$fct,$params);
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
   if ( $mod == 'context'
        and $fct == 'login'
      ) {
      $mod = 'home';
      $fct = 'index';
   }
   redirect($cid,$mod,$fct,$params,'','',$back_file);
}
?>