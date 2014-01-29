<?php
// $Id$
//
// Release $Name$
//
// Copyright (c)2009 Fabian Gebert (Mediabird), Dr. Iver Jackewitz (CommSy),
//                   Frank Wolf (Mediabird)
//
// This file is part of the mediabird plugin for CommSy.
//
// This plugin is free software; you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation; either version 3 of the License, or
// (at your option) any later version.
//
// This plugin is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You have received a copy of the GNU General Public License
// along with the plugin.

// only rubric for private room
$output = '';
if ( !empty($_GET['output']) ) {
   $output = $_GET['output'];
} elseif ( !empty($_POST['output']) ) {
   $output = $_POST['output'];
}
if ( !empty($output)
     and $output != 'pure'
   ) {
   $output = '';
}

if ( !$environment->inPrivateRoom() and empty($output) ) {
   redirect($environment->getCurrentContextID(),'home','index');
}

// some settings
$plugin_folder = 'plugins';
$plugin_name = '/mediabird';
$plugin_dir = $plugin_folder.$plugin_name;
$external_id_manager = $environment->getExternalIDManager();
$system = 'mediabird';
$language = $environment->getSelectedLanguage();
$commsy_mediabird = $environment->getPluginClass($system);

// mediabird: start
include($plugin_dir.'/server/helper.php');
include($plugin_dir.'/config/config_default.php');
include($plugin_dir.'/config/config.php');
include ($plugin_dir.'/server/dbo.php');

include($plugin_dir.'/server/utility.php');

// create a helper instance (having to do this after the static function calls since config has not been loaded otherwise)
$helper = new MediabirdHtmlHelper();
include_once($plugin_dir.'/server/db_mysql.php');
$mediabirdDb = new MediabirdDboMySql();
if ( !$mediabirdDb->connect() ) {
   include_once('function/error_function.php');
   trigger_error('cannot open database - aborting execution',E_USER_ERROR);
   exit();
}

// Uebersetzungstabelle commsy -> mediabird
$current_user_item = $environment->getCurrentUserItem();
$portal_user_item = $current_user_item->getRelatedCommSyUserItem();

// last login
include_once('functions/date_functions.php');
$current_user_item->setLastLoginPlugin(getCurrentDateTimeInMySQL(),$system);
$current_user_item->setChangeModificationOnSave(false);
$current_user_item->save();

if ( !$environment->inPortal()
     and !$environment->inServer()
   ) {
   if ( isset($portal_user_item) ) {
      $portal_user_item->setLastLoginPlugin(getCurrentDateTimeInMySQL(),$system);
      $portal_user_item->setChangeModificationOnSave(false);
      $portal_user_item->save();
   }
}

$commsy_user_id = $portal_user_item->getItemID();
$fullname = $portal_user_item->getFullname();
$pic_url = $portal_user_item->getPictureUrl(true,false);

unset($portal_user_item);
unset($current_user_item);

$mbuser = $external_id_manager->getExternalId($system,$commsy_user_id);

if ( !empty($mbuser) ) {
   //load last login info before it will be updated
   if($userRecord = $mediabirdDb->getRecord(MediabirdConfig::tableName('User',true),"id=$mbuser")) {
      //get last login time
      $lastLogin = $mediabirdDb->timestamp($userRecord->last_login);
      //save login time
      $_SESSION['mb_session_time'] = $lastLogin;
   }

   $helper->updateUser($mbuser,$fullname,1,null, $pic_url, $mediabirdDb);
} else {
   $mbuser = $helper->registerUser($fullname,1,null, $pic_url, $mediabirdDb);
   if ( !empty($mbuser) ) {
      $external_id_manager->addIDsToDB($system,$mbuser,$commsy_user_id);
   } else {
      include_once('functions/error_functions.php');
      trigger_error('can not initiate mediabird account',E_USER_ERROR);
   }
}
unset($external_id_manager);


if ( !empty($language)
     and strtolower($language) == 'de'
   ) {
   $helper->defaultOptions['language'] = MediabirdHtmlHelper::langGerman;
}
// if card_id provided for in-place note-taking, then refered card is loaded
if ( !empty($_GET['mb_card_id']) ) {
   $helper->defaultOptions['loadCard']=$_GET['mb_card_id'];
}

//include commsy auth interface
include ($plugin_dir.'/commsy_auth.php');
$auth = new CommsyAuthManager($environment);


//restore user settings
if(!$helper->loadUser($auth->userId, $mediabirdDb)) {
   $helper->user = array (
          'name' => $fullname,
          'settings' => '{}',
          'id' => $mbuser
     );
}
// $url_params is a parameter required by commsy-function _curl
$url_params = array();
$url_params['name'] = $system;
$url_params['SID'] = $environment->getSessionID();
include_once('functions/security_functions.php');
$url_params['security_token'] = getToken();

// defaultOptions that are processed in helper function mainBodyScript
$args = array(
      'dummyPath'		=>  _curl(false,$environment->getCurrentContextID(),$system,'dummy',$url_params),
      'loadPath'		=>  _curl(false,$environment->getCurrentContextID(),$system,'load', $url_params).'&url=',
      'uploadPath'		=>  _curl(false,$environment->getCurrentContextID(),$system,'upload',$url_params),
      'imagePath'		=>  $c_commsy_domain.$c_commsy_url_path.'/'.$plugin_dir.'/images/',
      'prefixData'    	=>  false,
      'loadLogon'     	=>  false,
      'headerBarId'   	=>  'dummy_header',
      'furtherArgs'   	=>  'sessionPath: "'._curl(false,$environment->getCurrentContextID(),$system,'session',$url_params).'"',
      'containerId'		=>  'mediabirdcontainer',
     'viewPath' 		=>  _curl(false,$environment->getCurrentContextID(),$system,'file',$url_params)
   );
// mb_url is the url of the calling page in case of in-place note-taking for potential link insertions
if ( isset($_GET['mb_url']) ) {
   $args['linkUrl'] = $_GET['mb_url'];
   $link_text = 'CommSy';
   $values = array();
   preg_match('#mod=([A-Za-z0-9]*)#',$_GET['mb_url'],$values);
   if ( !empty($values[1]) ) {
      $module = $values[1];
      $values = array();
      preg_match('#iid=([0-9]*)#',$_GET['mb_url'],$values);
      if ( !empty($values[1]) ) {
         $iid = $values[1];
         $manager = $environment->getManager(module2type($module));
         if ( isset($manager) ) {
            $item = $manager->getItem($iid);
            unset($manager);
            if ( isset($item) ) {
               $link_text = $item->getTitle();
               unset($item);
            }
         }
      }
   }
   // args for potentially inserted links to commsy-pages
   $args['linkTitle']               = $link_text;
   $args['linkPrefix']              = $c_commsy_domain.$c_commsy_url_path;
   $args['linkTarget']              = '_parent';
   $args['reduceFeatureSet']        = true;

   $current_user_item = $environment->getCurrentUserItem();
   $own_room = $current_user_item->getOwnRoom();
   if ( !empty($_GET['mb_card_id']) ) {
      $url_params['mb_card_id'] = $_GET['mb_card_id'];
   }
   if ( isset($own_room) ) {
      $args['fullLocationFromOverlay'] = _curl(false,$own_room->getItemID(), $system, 'index', $url_params);
   }
   unset($current_user_item);
}

$script = $helper->bodyScript($args);

// plugin view
$current_user_item = $environment->getCurrentUserItem();
$plugin_view = $class_factory->getClass(PLUGIN_VIEW,array('environment' => $environment));
$plugin_view->setName($system);
$plugin_view->setTitle($commsy_mediabird->getDisplayName());
$plugin_view->setIcon($commsy_mediabird->getRubricTitleIcon());

// $output = pure indicates that html for whole page has be provided (case of in-place note-taking)

if ( !$current_user_item->isOnlyReadUser() ) {

   $plugin_view->addContent('<div class="overlay" id="mediabirdcontainer"></div>');
   $plugin_view->addContent($script);

   // commsy header info
   $plugin_view->addForHead('<title>'.$translator->getMessage('MEDIABIRD_HEAD_TITLE').'</title>');
   $plugin_view->addForHead('<link rel="shortcut icon" href="'.$c_commsy_domain.$c_commsy_url_path.'/'.$plugin_dir.'/favicon.ico" type="image/x-icon"/>');

   $plugin_view->addForHead('<link type="text/css" rel="stylesheet" href="'.$c_commsy_domain.$c_commsy_url_path.'/'.$plugin_dir.'/css/style.css" />');
   $plugin_view->addForHead('<link type="text/css" rel="stylesheet" href="'.$c_commsy_domain.$c_commsy_url_path.'/'.$plugin_dir.'/css/commsy.css" />');

   //jquery will be loaded in normal view by default
   $plugin_view->addForHead('<script type="text/javascript" src="'.$c_commsy_domain.$c_commsy_url_path.'/'.$plugin_dir.'/js/jquery.js">\n</script>');
   $plugin_view->addForHead('<script type="text/javascript" src="'.$c_commsy_domain.$c_commsy_url_path.'/'.$plugin_dir.'/js/client.js">\n</script>');
} else {
   $plugin_view->addContent('<div id="mediabirdcontainer">'.$translator->getMessage('COMMON_NO_ACTION').'</div>');
}

// output for iframe for in-place note-taking
if ( !empty($output)
     and $output == 'pure'
   ) {
   $plugin_view->notDisplayTitle();
   header("Content-Type: text/html; charset=utf-8");
   $html  = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">'.LF;
   $html .= '<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">'.LF;
   $html .= '   <head>'.LF;
   $html .= $plugin_view->getInfoForHeaderAsHTML();
   $html .= '   </head>'.LF;
   $html .= '   <body class="margin">'.LF;
   $html .= $plugin_view->asHTML();
   $html .= '   </body>'.LF;
   $html .= '</html>'.LF;
   echo($html);
   exit();
} else {
   $plugin_view->notDisplayTitle();
   header("Content-Type: text/html; charset=utf-8");
   $html  = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">'.LF;
   $html .= '<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">'.LF;
   $html .= '   <head>'.LF;
   $html .= $plugin_view->getInfoForHeaderAsHTML();
   $html .= '   </head>'.LF;
   $html .= '   <body class="study_notes">'.LF;

   $html .= $commsy_mediabird->headerAsHTML();
   //footer must be included before main HTML since script tag must be placed before end of body tag
   $html .= $commsy_mediabird->footerAsHTML();

   $html .= $plugin_view->asHTML();

   $html .= '   </body>'.LF;
   $html .= '</html>'.LF;
   echo($html);
   exit();
#} else {
#   $page->add($plugin_view);
}
?>
