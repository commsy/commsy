<?php

// http://www.linuxscope.net/articles/mailAttachmentsPHP.html

function get_mime_type(&$structure) {
   $primary_mime_type = array("TEXT", "MULTIPART","MESSAGE", "APPLICATION", "AUDIO","IMAGE", "VIDEO", "OTHER");
   if($structure->subtype) {
   	return $primary_mime_type[(int) $structure->type] . '/' .$structure->subtype;
   }
   return "TEXT/PLAIN";
}

function get_part($stream, $msg_number, $mime_type, $structure = false, $part_number = false) {
   $prefix = null;
   if(!$structure) {
   	$structure = imap_fetchstructure($stream, $msg_number);
   }
   if($structure) {
   	if($mime_type == get_mime_type($structure)) {
   		if(!$part_number) {
   			$part_number = "1";
   		}
   		$text = imap_fetchbody($stream, $msg_number, $part_number);
   		if($structure->encoding == 3) {
   			return imap_base64($text);
   		} else if($structure->encoding == 4) {
   			return imap_qprint($text);
   		} else {
   		return $text;
   	   }
	  	}
	   
	  	// multipart message
		if($structure->type == 1) {
	  		while(list($index, $sub_structure) = each($structure->parts)) {
	  			if($part_number) {
	  				$prefix = $part_number . '.';
	  			}
	  			$data = get_part($stream, $msg_number, $mime_type, $sub_structure,$prefix .    ($index + 1));
	  			if($data) {
	  				return $data;
	  			}
	  		}
	  	}
  	}
  	return false;
}

function getFile($strFileType,$strFileName,$fileContent) {
  	$ContentType = "application/octet-stream";
   
  	if ($strFileType == ".asf") 
  		$ContentType = "video/x-ms-asf";
  	if ($strFileType == ".avi")
  		$ContentType = "video/avi";
  	if ($strFileType == ".doc")
  		$ContentType = "application/msword";
  	if ($strFileType == ".zip")
  		$ContentType = "application/zip";
  	if ($strFileType == ".xls")
  		$ContentType = "application/vnd.ms-excel";
  	if ($strFileType == ".gif")
  		$ContentType = "image/gif";
  	if ($strFileType == ".jpg" || $strFileType == "jpeg")
  		$ContentType = "image/jpeg";
  	if ($strFileType == ".wav")
  		$ContentType = "audio/wav";
  	if ($strFileType == ".mp3")
  		$ContentType = "audio/mpeg3";
  	if ($strFileType == ".mpg" || $strFileType == "mpeg")
  		$ContentType = "video/mpeg";
  	if ($strFileType == ".rtf")
  		$ContentType = "application/rtf";
  	if ($strFileType == ".htm" || $strFileType == "html")
  		$ContentType = "text/html";
  	if ($strFileType == ".xml") 
  		$ContentType = "text/xml";
  	if ($strFileType == ".xsl") 
  		$ContentType = "text/xsl";
  	if ($strFileType == ".css") 
  		$ContentType = "text/css";
  	if ($strFileType == ".php") 
  		$ContentType = "text/php";
  	if ($strFileType == ".asp") 
  		$ContentType = "text/asp";
  	if ($strFileType == ".pdf")
  		$ContentType = "application/pdf";
   
	if (substr($ContentType,0,4) == "text") {
	   return imap_qprint($fileContent);
	} else {
		return imap_base64($fileContent);
	}
}

function email_to_commsy($mbox,$msgno){
	global $environment;
	global $portal_id_array;
	global $c_email_upload_email_account;
	
	$translator = $environment->getTranslationObject();
	
   $struct = imap_fetchstructure($mbox,$msgno);

   $header = imap_headerinfo($mbox,$msgno);
   $sender = $header->from[0]->mailbox.'@'.$header->from[0]->host;
   $subject = $header->subject;
   #$body = imap_fetchbody($mbox,$msgno,1);
	
   // just use the plain part of the e-mail
   $body_plain = get_part($mbox, $msgno, "TEXT/PLAIN");
   $body_html = get_part($mbox, $msgno, "TEXT/HTML");
   $body_is_plain = true;
   
   if(!empty($body_plain)){
   	$body = $body_plain;
   } else {
   	$body_is_plain = false;
   	$body = $body_html;
   }
   
   // get additional Information from e-mail body
   $translator->setSelectedLanguage('de');
   $translation['de']['password'] = $translator->getMessage('EMAIL_TO_COMMSY_PASSWORD');
   $translation['de']['account'] = $translator->getMessage('EMAIL_TO_COMMSY_ACCOUNT');
   $translator->setSelectedLanguage('en');
   $translation['en']['password'] = $translator->getMessage('EMAIL_TO_COMMSY_PASSWORD');
   $translation['en']['account'] = $translator->getMessage('EMAIL_TO_COMMSY_ACCOUNT');
   
   $account = '';
   $secret = '';
   
   $body = preg_replace('/\r\n|\r/', "\n", $body);
   $body_array = explode("\n", $body);
   $temp_body = array();
   
   $with_footer = false;
   $footer_line = 0;
   $index = 0;
   foreach($body_array as $body_line){
   	if(strip_tags($body_line) == '-- '){ // start of e-mail signature
   		$with_footer = true;
   		$footer_line = $index;
   	}
   	$index++;
   }
   
   $index = 0;
   $secret_found = false;
   $account_found = false;
   foreach($body_array as $body_line){
   	if($with_footer and $index == $footer_line){
   		break;
   	}
   	if(!empty($body_line)){
   		$body_line = strip_tags($body_line);
	   	if(stristr($body_line, $translation['de']['account']) and !$account_found){
	   		$temp_body_line = str_ireplace($translation['de']['account'].':', '', $body_line);
	   		$temp_body_line_array = explode(' ', trim($temp_body_line));
	   		$account = $temp_body_line_array[0];
	   		$account_found = true;
	   	} else if(stristr($body_line, $translation['en']['account']) and !$account_found){
	   		$temp_body_line = str_ireplace($translation['en']['account'].':', '', $body_line);
	   		$temp_body_line_array = explode(' ', trim($temp_body_line));
	   		$account = $temp_body_line_array[0];
	   		$account_found = true;
	   	} else if(stristr($body_line, $translation['de']['password']) and !$secret_found){
	   		$temp_body_line = str_ireplace($translation['de']['password'].':', '', $body_line);
	   		$temp_body_line_array = explode(' ', trim($temp_body_line));
	   		$secret = $temp_body_line_array[0];
	   		$secret_found = true;
	   	} else if(stristr($body_line, $translation['en']['password']) and !$secret_found){
	   		$temp_body_line = str_ireplace($translation['en']['password'].':', '', $body_line);
	   		$temp_body_line_array = explode(' ', trim($temp_body_line));
	   		$secret = $temp_body_line_array[0];
	   		$secret_found = true;
	   	} else {
	   		$temp_body[] = $body_line;
	   	}
   	} else {
   		$temp_body[] = $body_line;
   	}
   	$index++;
   }
   $body = implode("\n", $temp_body);
   
	foreach($portal_id_array as $portal_id){
		$environment->setCurrentPortalID($portal_id);
		$user_manager = $environment->getUserManager();
		$user_manager->setContextArrayLimit($portal_id);
		$user_manager->setEMailLimit($sender);
		$user_manager->select();
		$user_list = $user_manager->get();
		$user = $user_list->getfirst();
		$found_users = array();
		while($user){
			if($account != ''){
				if($account == $user->getUserID()){
			   	$found_users[] = $user;
				}
			} else {
				$found_users[] = $user;
			}
			$user = $user_list->getnext();
		}
		
		foreach($found_users as $found_user){
			$private_room_user = $found_user->getRelatedPrivateRoomUserItem();
			$private_room = $private_room_user->getOwnRoom();
			$translator->setSelectedLanguage($private_room->getLanguage());
			
			if($private_room->getEmailToCommSy()){
			   $email_to_commsy_secret = $private_room->getEmailToCommSySecret();
			   
			   $result_mail = new cs_mail();
            $result_mail->set_to($sender);
            $result_mail->set_from_name('CommSy');
				// $result_mail->set_from_email('commsy@commsy.net');
				
				$errors = array();
				
			   if($secret == $email_to_commsy_secret){
			   	$private_room_id = $private_room->getItemID();
			   	
			   	$files = array();
			   	
				   if($struct->subtype == 'PLAIN'){
				   } else if ($struct->subtype == 'MIXED') {
				      // with attachment 
					   $contentParts = count($struct->parts);
					   if ($contentParts >= 2) {
						   for ($i=2;$i<=$contentParts;$i++) {
					   	   $att[$i-2] = imap_bodystruct($mbox,$msgno,$i);
					   	}
					   	for ($k=0;$k<sizeof($att);$k++) {
					   		$strFileName = $att[$k]->dparameters[0]->value;
					   		$strFileType = strrev(substr(strrev($strFileName),0,4));
					   		$fileContent = imap_fetchbody($mbox,$msgno,$k+2);
					   		$file = getFile($strFileType, $strFileName, $fileContent);
					   		
					   		// copy file to temp
					   		$temp_file = 'var/temp/'.$strFileName.'_'.getCurrentDateTimeInMySQL();
					   		file_put_contents($temp_file, $file);
					   		
					   		$temp_array = array();
			         		$temp_array['name'] = utf8_encode($strFileName);
			         		$temp_array['tmp_name'] = $temp_file;
			         		$temp_array['file_id'] = $temp_array['name'].'_'.getCurrentDateTimeInMySQL();
			         		$temp_array['file_size'] = filesize($temp_file);
			         		$files[] = $temp_array;
					   	}
					   }
				   }
				   
				   $environment->setCurrentContextID($private_room_id);
				   $environment->setCurrentUser($private_room_user);
				   $environment->unsetLinkModifierItemManager();
				   $material_manager = $environment->getMaterialManager();
				   $material_item = $material_manager->getNewItem();
				   $material_item->setTitle(trim(str_replace($email_to_commsy_secret.':', '', $subject)));

				   $material_item->setDescription($body);
				   
			      // attach files to the material
				   $file_manager = $environment->getFileManager();
			      $file_manager->setContextLimit($private_room_id);
			      
			      $portal_item = $environment->getCurrentPortalItem();
			      $portal_max_file_size = $portal_item->getMaxUploadSizeInBytes();
			      
			      $file_id_array = array();
			      $error['files_to_large'] = array();
			      foreach($files as $file){
			      	if($file["file_size"] <= $portal_max_file_size){
						   $file_item = $file_manager->getNewItem();
					      $file_item->setTempKey($file["file_id"]);
				         $file_item->setPostFile($file);
					      $file_item->save();
					      $file_id_array[] = $file_item->getFileID();
			      	} else {
			      		$error['files_to_large'][] = array('name' => $file['name'], 'size' => $file["file_size"]);
			      	}
			      }
			      
				   $material_item->setFileIDArray($file_id_array);			
				   $material_item->save();
				   
				   // send e-mail with 'material created in your private room' back to sender
				   $file = $_SERVER['PHP_SELF'];
               		$file = str_replace('cron_email_upload','commsy',$file);
               		$curl_text = 'http://'.$c_commsy_domain.$file.'?cid=';
				   
				   #$params['iid'] = $material_item->getItemID();
				   #$link_to_new_material = curl($private_room_id, 'material', 'detail', $params);
				   
               		//$link_to_new_material = '<a href="'.$curl_text.$private_room_id.'&amp;mod=material&amp;fct=detail&amp;iid='.$material_item->getItemID().'">'.$material_item->getTitle().'</a>';
               
				   $result_body = $translator->getMessage('EMAIL_TO_COMMSY_RESULT_SUCCESS', $private_room_user->getFullName())."\n\n";
				   
				   if(!empty($error['files_to_large'])){
				   	$files_to_large = '';
				   	foreach($error['files_to_large'] as $file_to_large){
				   		$files_to_large .= '- '.$file_to_large['name'].' ('.round($file_to_large['size'] / (1024*1024), 2).' MB)'."\n";
				   	}
				   	$result_body .= $translator->getMessage('EMAIL_TO_COMMSY_RESULT_FILES_TO_LARGE', $portal_max_file_size / (1024*1024), $files_to_large)."\n\n";
				   }
				   
				   $result_body .= $translator->getMessage('EMAIL_TO_COMMSY_RESULT_REGARDS');
				   
				   $result_mail->set_subject('Upload2CommSy - erfolgreich');
               		$result_mail->set_message($result_body);
			   } else {
			   	// send e-mail with 'password or subject not correct' back to sender
			   	$result_body = $translator->getMessage('EMAIL_TO_COMMSY_RESULT_FAILURE', $private_room_user->getFullName(), $translator->getMessage('EMAIL_TO_COMMSY_PASSWORD'));
			   	$result_mail->set_subject('Upload2CommSy - fehlgeschlagen');
               	$result_mail->set_message($result_body);
			   }
			   
			   #$result_mail->setSendAsHTML();
			   $result_mail->send();
			}
		}
	}
   
	// mark e-mail for deletion
	imap_delete($mbox,$msgno);
}

chdir('..');

include_once('etc/commsy/development.php');
include_once('classes/cs_mail.php');
include_once('functions/curl_functions.php');

// setup commsy-environment
include_once('etc/cs_constants.php');
include_once('etc/cs_config.php');
include_once('classes/cs_environment.php');
$environment = new cs_environment();
$environment->setCacheOff();
$server_item = $environment->getServerItem();
$portal_id_array = $server_item->getPortalIDArray();

// open connection
$options = $environment->getConfiguration('c_email_upload_server_options');
if ( !isset($options) ) {
	$options = '';
}
$mbox = imap_open('{'.$c_email_upload_server.':'.$c_email_upload_server_port.$options.'}', $c_email_upload_email_account, $c_email_upload_email_password);

if ( !empty($mbox) ) {
	// get and process e-mails
	$message_count = imap_num_msg($mbox);
	for ($msgno = 1; $msgno <= $message_count; ++$msgno) {
		email_to_commsy($mbox,$msgno);
	}
	#echo('email: '.$message_count.LF);

	// remove deleted e-mails
	imap_expunge($mbox);

	// close connection
	imap_close($mbox);
} else {
	$error_array = imap_errors();
	if ( !empty($error_array) ) {
		echo(implode(LF,$error_array).LF);
	}
}
?>