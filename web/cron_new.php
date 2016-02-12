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
mb_internal_encoding('UTF-8');

global $environment;
global $file;
global $c_commsy_cron_split_scripts;
global $c_workflow;
global $c_commsy_cron_path;

function performRoomIDArray ($id_array,$portal_name,$privatrooms = false) {
   global $environment;
   global $file;
   if ( $privatrooms ) {
      $room_manager = $environment->getPrivateRoomManager();
   } else {
      $room_manager = $environment->getRoomManager();
   }
   $translator = $environment->getTranslationObject();
   $room_manager->setCacheOff();
   foreach ($id_array as $item_id) {
      $room = $room_manager->getItem($item_id);
      $type = '';
      $active = true;
      if ($room->isCommunityRoom()) {
         $type = 'Community';
         $title = $environment->getTextConverter()->text_as_html_short($room->getTitle());
         if ( $room->isOpen() ) {
            $active = $room->isActiveDuringLast99Days();
         } else {
            $active = false;
         }
      } elseif ($room->isProjectRoom()) {
         $type = 'Project';
         $title = $environment->getTextConverter()->text_as_html_short($room->getTitle());
         if ( $room->isOpen() ) {
            $active = $room->isActiveDuringLast99Days();
         } else {
            $active = false;
         }
      } elseif ($room->isGroupRoom()) {
         $type = 'Group';
         $title = $environment->getTextConverter()->text_as_html_short($room->getTitle());
         if ( $room->isOpen() ) {
            $active = $room->isActiveDuringLast99Days();
         } else {
            $active = false;
         }
      } elseif ($room->isPrivateRoom()) {
         $type = 'Private';
         $user = $room->getOwnerUserItem();
         if (isset($user) and $user->isUser()){
            $title = $translator->getMessage('COMMON_PRIVATE_ROOM').': '.$environment->getTextConverter()->text_as_html_short($user->getFullName()).' ('.$room->getItemID().')';
            $portal_user_item = $user->getRelatedCommSyUserItem();
            if ( isset($portal_user_item) and $portal_user_item->isUser() ) {
               $active = $portal_user_item->isActiveDuringLast99Days();
            } else {
               $active = false;
            }
            unset($portal_user_item);
         } else {
            $title = $translator->getMessage('COMMON_PRIVATE_ROOM').': '.$room->getItemID();
            $active = false;
         }
         unset($user);
      }
      fwrite($file, '<h4>'.$title.' - '.$type.' - '.$environment->getTextConverter()->text_as_html_short($portal_name).'</h4>'.LF);
      if ( $active ) {
      	global $c_commsy_cron_split_scripts;
      	if(isset($c_commsy_cron_split_scripts) and $c_commsy_cron_split_scripts){
	         if($room->isPrivateRoom()){
	      	   #passthru('php htdocs/cron_single_room.php '.$room->getItemID().' private');
	         	$output = array();
               exec('php htdocs/cron_single_room.php '.$room->getItemID().' private',$output);
               if ( !empty($output)
                    and !empty($output[0])
                  ) {
                  $cron_array = json_decode($output[0],true);
                  displayCronResults($cron_array);
               }
	         } else {
	         	#passthru('php htdocs/cron_single_room.php '.$room->getItemID());
	            $output = array();
               exec('php htdocs/cron_single_room.php '.$room->getItemID(),$output);
               if ( !empty($output)
                    and !empty($output[0])
                  ) {
                  $cron_array = json_decode($output[0],true);
                  displayCronResults($cron_array);
               }
	         }
      	} else {
      		displayCronResults($room->runCron());
      	}
      } else {
         fwrite($file, 'not active'.BRLF);
      }
      fwrite($file, 'Current time: '.date('d.m.Y H:i:s').BRLF);
      fwrite($file, 'Peak of allocated memory: '.memory_get_peak_usage(true).BRLF);
      fwrite($file, 'Current allocated memory: '.memory_get_usage(true).BRLF);
      fwrite($file, '----------------------------------------------------------'.BRLF);
      unset($room);
   }
   unset($room_manager);
   unset($translator);
}

function displayCronResults ( $array ) {
   global $file;
   $html = '';
   foreach ($array as $cron_status => $crons) {
      $html .= '<table border="0" summary="Layout">'.LF;
      $html .= '<tr>'.LF;
      $html .= '<td style="vertical-align:top; width: 4em;">'.LF;
      $html .= '<span style="font-weight: bold;">'.$cron_status.'</span>'.LF;
      $html .= '</td>'.LF;
      $html .= '<td>'.LF;
      if ( !empty($crons) ) {
         foreach ($crons as $cron) {
            $html .= '<div>'.LF;
            $html .= '<span style="font-weight: bold;">'.$cron['title'].'</span>'.BRLF;
            if (!empty($cron['description'])) {
               $html .= $cron['description'];
               if (isset($cron['success'])) {
                  if ($cron['success']) {
                     $html .= ' [<font color="#00ff00">done</font>]'.BRLF;
                  } else {
                     $html .= ' [<font color="#ff0000>failed</font>]'.BRLF;
                  }
               } else {
                  $html .= ' [<font color="#ff0000>failed</font>]'.BRLF;
               }
            }
            if ( !empty($cron['success_text']) ) {
               $html .= $cron['success_text'].BRLF;
            }
            if ( !empty($cron['time']) ) {
               $time = $cron['time'];
               if ( $time < 60 ) {
                  $time_text = 'Total execution time: '.$time.' seconds';
               } elseif ( $time < 3600 ) {
                  $time2 = floor($time / 60);
                  $sec2 = $time % 60;
                  $time_text = 'Total execution time: '.$time2.' minutes '.$sec2.' seconds';
               } else {
                  $hour = floor($time / 3600);
                  $sec = $time % 3660;
                  if ( $sec > 60 ) {
                     $minutes = floor($sec / 60);
                     $sec = $sec % 60;
                  }
                  $time_text = 'Total execution time: '.$hour.' hours '.$minutes.' minutes '.$sec.' seconds';
               }
               $html .= $time_text.BRLF;
            } elseif ( isset($cron['time']) ) {
               $time_text = 'Total execution time: 0 seconds';
               $html .= $time_text.BRLF;
            }
            $html .= '</div>'.LF;
         }
      } else {
         $html .= 'no crons defined';
      }
      $html .= '</td>'.LF;
      $html .= '</tr>'.LF;
      $html .= '</table>'.LF;
   }
   fwrite($file, $html);
   unset($html);
}

function cron_workflow($logFile, $portal){
   global $c_workflow;
   global $environment;
   global $cs_special_language_tags;
   if($c_workflow){
   	fwrite($logFile, 'Workflow is active'.LF);
   	
      $material_manager = $environment->getMaterialManager();
      $item_array = $material_manager->getResubmissionItemIDsByDate(date('Y'), date('m'), date('d'));
      foreach($item_array as $item){
      	$temp_material = $material_manager->getItem($item['item_id']);
         $item_id_current_version = $material_manager->getLatestVersionID($item['item_id']);
         if ( isset($temp_material) && !$temp_material->isDeleted() && ($item['version_id'] == $item_id_current_version))
         {
	      	//$temp_material = $material_manager->getItem($item['item_id']);
	         $room_manager = $environment->getRoomManager();
	         $temp_room = $room_manager->getItem($temp_material->getContextID());
	         
	         // check if context of room is current portal
	         if ( $temp_room->getContextID() != $portal->getItemID()) continue;
	
	         if($temp_material->getWorkflowResubmission() and $temp_room->withWorkflowResubmission()){
	            $email_receiver_array = array();
	            if($temp_material->getWorkflowResubmissionWho() == 'creator'){
	               $email_receiver_array[] = $temp_material->getCreator();
	            } else {
	               $temp_list = $temp_material->getModifierList();
	               $email_receiver_array = $temp_list->to_array();
	            }
	
	            $to = '';
	            $first = true;
	            foreach($email_receiver_array as $email_receiver){
	               if($first){
	                  $to .= $email_receiver->getEmail();
	                  $first = false;
	               } else {
	                  $to .= ','.$email_receiver->getEmail();
	               }
	            }
	
	            $additional_receiver = $temp_material->getWorkflowResubmissionWhoAdditional();
	            if(!empty($additional_receiver)){
	               $to .= ','.$additional_receiver;
	            }
	
	            include_once('classes/cs_mail.php');
	            $translator = $environment->getTranslationObject();
	            $mail = new cs_mail();
	            $mail->set_to($to);
	            $mail->set_from_name($translator->getMessage('SYSTEM_MAIL_MESSAGE', $portal->getTitle()));
	            $server_item = $environment->getServerItem();
	            $default_sender_address = $server_item->getDefaultSenderAddress();
	            if (!empty($default_sender_address)) {
	               $mail->set_from_email($default_sender_address);
	            } else {
	               $mail->set_from_email('@');
	            }
	            $mail->set_subject($translator->getMessage('COMMON_WORKFLOW_EMAIL_SUBJECT_RESUBMISSION', $portal->getTitle()));
	
	            $url_to_portal = '';
	            if ( !empty($portal) ) {
	               $url_to_portal = $portal->getURL();
	            }
	            global $c_commsy_cron_path;
	            if(isset($c_commsy_cron_path)){
	               $curl_text = $c_commsy_cron_path.'commsy.php?cid=';
	            } elseif ( !empty($url_to_portal) ) {
	               $c_commsy_domain = $environment->getConfiguration('c_commsy_domain');
	               if ( stristr($c_commsy_domain,'https://') ) {
	                  $curl_text = 'https://';
	               } else {
	                  $curl_text = 'http://';
	               }
	               $curl_text .= $url_to_portal;
	               $file = 'commsy.php';
	               $c_single_entry_point = $environment->getConfiguration('c_single_entry_point');
	               if ( !empty($c_single_entry_point) ) {
	                  $file = $c_single_entry_point;
	               }
	               $curl_text .= '/'.$file.'?cid=';
	            } else {
	               $commsy_file = $_SERVER['PHP_SELF'];
	               $commsy_file = str_replace('cron_new','commsy',$commsy_file);
	               $commsy_file = str_replace('cron','commsy',$commsy_file);
	               $curl_text = 'http://'.$_SERVER['HTTP_HOST'].$commsy_file.'?cid=';
	            }
	            $link = '<a href="'.$curl_text.$temp_room->getItemID().'&amp;mod=material&amp;fct=detail&amp;iid='.$temp_material->getItemID().'">'.$temp_material->getTitle().'</a>';
	
	            if (isset($cs_special_language_tags) and !empty($cs_special_language_tags)){
	            	$mail->set_message($translator->getMessage($cs_special_language_tags.'_WORKFLOW_EMAIL_BODY_RESUBMISSION', $temp_room->getTitle(), $temp_material->getTitle(), $link));
	            }else{
	            	$mail->set_message($translator->getMessage('COMMON_WORKFLOW_EMAIL_BODY_RESUBMISSION', $temp_room->getTitle(), $temp_material->getTitle(), $link));
	            }
	            $mail->setSendAsHTML();
	            if ( $mail->send() ) {
	               fwrite($logFile, 'workflow resubmission e-mail send for item: '.$item['item_id']);
	            }
	
	            // change the status of the material
	            $material_manager->setWorkflowStatus($temp_material->getItemID(), $temp_material->getWorkflowResubmissionTrafficLight(), $temp_material->getVersionID());
	            
	            unset($mail);
	            unset($translator);
	            unset($server_item);
	         }
	         unset($temp_material);
	         unset($temp_room);
	         unset($room_manager);
         }
      }

      $item_array = $material_manager->getValidityItemIDsByDate(date('Y'), date('m'), date('d'));
      foreach($item_array as $item){
      	$temp_material = $material_manager->getItem($item['item_id']);
         $item_id_current_version = $material_manager->getLatestVersionID($item['item_id']);
         if ( isset($temp_material) && !$temp_material->isDeleted() && ($item['version_id'] == $item_id_current_version))
         {
	      	//$temp_material = $material_manager->getItem($item['item_id']);
	
	         $room_manager = $environment->getRoomManager();
	         $temp_room = $room_manager->getItem($temp_material->getContextID());
	         
	         // check if context of room is current portal
	         if ( $temp_room->getContextID() != $portal->getItemID()) continue;
	
	         if($temp_material->getWorkflowValidity() and $temp_room->withWorkflowValidity()){
	            $email_receiver_array = array();
	            if($temp_material->getWorkflowValidityWho() == 'creator'){
	               $email_receiver_array[] = $temp_material->getCreator();
	            } else {
	               $temp_list = $temp_material->getModifierList();
	               $email_receiver_array = $temp_list->to_array();
	            }
	
	            $to = '';
	            $first = true;
	            foreach($email_receiver_array as $email_receiver){
	               if($first){
	                  $to .= $email_receiver->getEmail();
	                  $first = false;
	               } else {
	                  $to .= ','.$email_receiver->getEmail();
	               }
	            }
	
	            $additional_receiver = $temp_material->getWorkflowValidityWhoAdditional();
	            if(!empty($additional_receiver)){
	               $to .= ','.$additional_receiver;
	            }
	
	            include_once('classes/cs_mail.php');
	            $translator = $environment->getTranslationObject();
	            $mail = new cs_mail();
	            $mail->set_to($to);
	            $mail->set_from_name($translator->getMessage('SYSTEM_MAIL_MESSAGE', $portal->getTitle()));
	            $server_item = $environment->getServerItem();
	            $default_sender_address = $server_item->getDefaultSenderAddress();
	            if (!empty($default_sender_address)) {
	               $mail->set_from_email($default_sender_address);
	            } else {
	               $mail->set_from_email('@');
	            }
	            $mail->set_subject($translator->getMessage('COMMON_WORKFLOW_EMAIL_SUBJECT_VALIDITY', $portal->getTitle()));
	
	            $url_to_portal = '';
	            if ( !empty($portal) ) {
	               $url_to_portal = $portal->getURL();
	            }
	            global $c_commsy_cron_path;
	            if(isset($c_commsy_cron_path)){
	               $curl_text = $c_commsy_cron_path.'commsy.php?cid=';
	            } elseif ( !empty($url_to_portal) ) {
	               $c_commsy_domain = $environment->getConfiguration('c_commsy_domain');
	               if ( stristr($c_commsy_domain,'https://') ) {
	                  $curl_text = 'https://';
	               } else {
	                  $curl_text = 'http://';
	               }
	               $curl_text .= $url_to_portal;
	               $file = 'commsy.php';
	               $c_single_entry_point = $environment->getConfiguration('c_single_entry_point');
	               if ( !empty($c_single_entry_point) ) {
	                  $file = $c_single_entry_point;
	               }
	               $curl_text .= '/'.$file.'?cid=';
	            } else {
	               $commsy_file = $_SERVER['PHP_SELF'];
	               $commsy_file = str_replace('cron_new','commsy',$commsy_file);
	               $commsy_file = str_replace('cron','commsy',$commsy_file);
	               $curl_text = 'http://'.$_SERVER['HTTP_HOST'].$commsy_file.'?cid=';
	            }
	            $link = '<a href="'.$curl_text.$temp_room->getItemID().'&amp;mod=material&amp;fct=detail&amp;iid='.$temp_material->getItemID().'">'.$temp_material->getTitle().'</a>';
	
	            if (isset($cs_special_language_tags) and !empty($cs_special_language_tags)){
	            	$mail->set_message($translator->getMessage($cs_special_language_tags.'_WORKFLOW_EMAIL_BODY_RESUBMISSION', $temp_room->getTitle(), $temp_material->getTitle(), $link));
	            }else{
	            	$mail->set_message($translator->getMessage('COMMON_WORKFLOW_EMAIL_BODY_VALIDITY', $temp_room->getTitle(), $temp_material->getTitle(), $link));
	            }
	            $mail->setSendAsHTML();
	            if ( $mail->send() ) {
	               fwrite($logFile, 'workflow validity e-mail send for item: '.$item['item_id']);
	            }
	
	            // change the status of the material
	            $material_manager->setWorkflowStatus($temp_material->getItemID(), $temp_material->getWorkflowValidityTrafficLight(), $temp_material->getVersionID());
	            
	            unset($mail);
	            unset($translator);
	            unset($server_item);
	         }
	         unset($temp_material);
	         unset($temp_room);
	         unset($room_manager);
         }
      }
	   unset($item_array);
	   unset($material_manager);
   } else {
   	fwrite($logFile, 'Workflow is NOT active'.LF);
   }
}

set_time_limit(0);
header("Content-Type: text/html; charset=utf-8");

// if ( !empty($_GET['cid']) ) {
//    $context_id = $_GET['cid'];
// } else if ( !empty($_SERVER["argv"][1]) ) {
//    $context_id = $_SERVER["argv"][1];
// }
$context_id = $cid;

if ( !isset($context_id) ) {
   $filename = 'cronresult';
} else {
   $filename = 'cronresult_'.$context_id;
}

if ( file_exists('../files/'.$filename) ) {
   $file_contents = file_get_contents('../files/'.$filename);
   if(stristr($file_contents, '-----CRON-OK-----')){
      unlink('../files/'.$filename);
   } else {
      rename('../files/'.$filename, '../files/'.$filename.'_error_'.date('dmY'));
   }
}
$file = fopen('../files/'.$filename,'w+');

fwrite($file,'<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"/></head><body>');

$memory_limit2 = 640 * 1024 * 1024;
$memory_limit = ini_get('memory_limit');
if ( !empty($memory_limit) ) {
   if ( strstr($memory_limit,'M') ) {
      $memory_limit = substr($memory_limit,0,strlen($memory_limit)-1);
      $memory_limit = $memory_limit * 1024 * 1024;
   } elseif ( strstr($memory_limit,'K') ) {
      $memory_limit = substr($memory_limit,0,strlen($memory_limit)-1);
      $memory_limit = $memory_limit * 1024;
   }
}
if ( $memory_limit < $memory_limit2 ) {
   ini_set('memory_limit',$memory_limit2);
   $memory_limit3 = ini_get('memory_limit');
   if ( $memory_limit3 != $memory_limit2 ) {
      fwrite($file, 'Waring: Can not set memory limit. Script may stop. Please try 640M in your php.ini.'.LF);
   }
}

// start of execution time
include_once('functions/misc_functions.php');
$time_start = getmicrotime();
$start_time = date('d.m.Y H:i:s');

// setup commsy-environment
include_once('etc/cs_constants.php');
// include_once('etc/cs_config.php');
// include_once('classes/cs_environment.php');
// $environment = new cs_environment();
// $environment->setCacheOff();

$environment = $legacyEnvironment;

$result_array = array();

echo('<h1>CommSy Cron Jobs</h1>'.LF);
#$result_html .= '<h1>CommSy Cron Jobs</h1>'.LF;
fwrite($file, '<h1>CommSy Cron Jobs</h1>'.LF);

// server
$server_item = $environment->getServerItem();
// server cron jobs must be run AFTER all other portal crons

// portals and rooms
$result_array['portal'] = array();
$portal_id_array = $server_item->getPortalIDArray();

$portal_manager = $environment->getPortalManager();
foreach ( $portal_id_array as $portal_id ) {
   if ( !isset($context_id)
        or $context_id == $portal_id
      ) {

      // portal
      $portal = $portal_manager->getItem($portal_id);
      fwrite($file, '<h4>'.$environment->getTextConverter()->text_as_html_short($portal->getTitle()).' - Portal</h4>'.LF);
      displayCronResults($portal->runCron());
      fwrite($file, '<hr/>'.LF);

      // private rooms
      #$id_array = $portal->getPrivateIDArray();
      $id_array = $portal->getActiveUserPrivateIDArray();
      fwrite($file, '<h4>Private Rooms ('.count($id_array).')</h4>'.LF);
      performRoomIDArray($id_array,$portal->getTitle(),true);
      unset($id_array);
      fwrite($file, '<hr/>'.LF);

      // community rooms
      #$id_array = $portal->getCommunityIDArray();
      $id_array = $portal->getActiveCommunityIDArray();
      fwrite($file, '<h4>Community Rooms ('.count($id_array).')</h4>'.LF);
      performRoomIDArray($id_array,$portal->getTitle());
      unset($id_array);
      fwrite($file, '<hr/>'.LF);

      // project rooms
      #$id_array = $portal->getProjectIDArray();
      $id_array = $portal->getActiveProjectIDArray();
      fwrite($file, '<h4>Project Rooms ('.count($id_array).')</h4>'.LF);
      performRoomIDArray($id_array,$portal->getTitle());
      unset($id_array);
      fwrite($file, '<hr/>'.LF);

      // group rooms
      #$id_array = $portal->getGroupIDArray();
      $id_array = $portal->getActiveGroupIDArray();
      fwrite($file, '<h4>Group Rooms ('.count($id_array).')</h4>'.LF);
      performRoomIDArray($id_array,$portal->getTitle());
      unset($id_array);
      fwrite($file, '<hr/>'.LF);
      
      fwrite($file, '<h4>Workflow</h4>'.LF);
      cron_workflow($file, $portal);
      fwrite($file, '<hr/>'.LF);

      // unset
      unset($portal);
   }
}
unset($portal_manager);

// server cron jobs must be run AFTER all other portal crons
if ( !isset($context_id)
     or ($context_id == $environment->getServerID())
   ) {
   fwrite($file, '<h4>'.$environment->getTextConverter()->text_as_html_short($server_item->getTitle()).' - Server</h4>'.LF);
   displayCronResults($server_item->runCron());
   fwrite($file, '<hr/>'.BRLF);
}
unset($server_item);
unset($environment);

$time_end = getmicrotime();
$end_time = date('d.m.Y H:i:s');
$time = round($time_end - $time_start,0);
echo('<hr/>'.LF);
fwrite($file, '<hr/>'.LF);
echo('<h1>CRON END</h1>'.LF);
fwrite($file, '<h1>CRON END</h1>'.LF);
echo('<h2>Time</h2>'.LF);
fwrite($file, '<h2>Time</h2>'.LF);
echo('Start: '.$start_time.BRLF);
fwrite($file, 'Start: '.$start_time.BRLF);
echo('End: '.$end_time.BRLF);
fwrite($file, 'End: '.$end_time.BRLF);
if ( $time < 60 ) {
   echo('Total execution time: '.$time.' seconds'.LF);
   fwrite($file, 'Total execution time: '.$time.' seconds'.LF);
} elseif ( $time < 3600 ) {
   $time2 = floor($time / 60);
   $sec2 = $time % 60;
   echo('Total execution time: '.$time2.' minutes '.$sec2.' seconds'.LF);
   fwrite($file, 'Total execution time: '.$time2.' minutes '.$sec2.' seconds'.LF);
} else {
   $hour = floor($time / 3600);
   $sec = $time % 3660;
   if ( $sec > 60 ) {
      $minutes = floor($sec / 60);
      $sec = $sec % 60;
   }
   echo('Total execution time: '.$hour.' hours '.$minutes.' minutes '.$sec.' seconds'.LF);
   fwrite($file, 'Total execution time: '.$hour.' hours '.$minutes.' minutes '.$sec.' seconds'.LF);
}
echo('<h2>Memory</h2>'.LF);
fwrite($file, '<h2>Memory</h2>'.LF);
echo('Peak of memory allocated: '.memory_get_peak_usage().BRLF);
fwrite($file, 'Peak of memory allocated: '.memory_get_peak_usage().BRLF);
echo('Current of memory allocated: '.memory_get_usage().BRLF);
fwrite($file, 'Current of memory allocated: '.memory_get_usage().BRLF);
fwrite($file, '-----CRON-OK-----');
fwrite($file,'</body></html>');
fclose($file);
?>