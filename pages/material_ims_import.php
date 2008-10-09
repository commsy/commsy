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


include_once('classes/cs_material_ims_import_form.php');


// Get the current user and room
$current_user = $environment->getCurrentUserItem();
$context_item = $environment->getCurrentContextItem();

// Check access rights
if ( !$context_item->withMaterialImportLink() ) {
   include_once('classes/cs_errorbox_view.php');
   $errorbox = new cs_errorbox_view($environment, true);
   $errorbox->setText(getMessage('ACCESS_NOT_GRANTED', $context_item->getTitle()));
   $page->add($errorbox);

}  elseif ( !$current_user->isUser() ) {
   include_once('classes/cs_errorbox_view.php');
   $errorbox = new cs_errorbox_view($environment, true);
   $errorbox->setText(getMessage('LOGIN_NOT_ALLOWED'));
   $page->add($errorbox);
}
// Access granted
else {
   if (isset($c_virus_scan) and $c_virus_scan) {
      include_once('functions/page_edit_functions.php');
   }
   if ( isset($_POST['option']) ) {
      $command = $_POST['option'];
   } else {
      $command = '';
   }
   if ( isOption($command, getMessage('COMMON_CANCEL_BUTTON')) ) {
      redirect($environment->getCurrentContextID(),CS_MATERIAL_TYPE, 'index',$params);
   }elseif(isset($_GET['import_type']) and $_GET['import_type']== 'url'){
      include_once('include/inc_ims_upload.php');
      global $ims_content_connection_temp_folder;
      global $url_for_beluga_upload;
      $session = $environment->getSessionItem();
      if (isset($ims_content_connection_temp_folder)){
         $target_directory = $ims_content_connection_temp_folder.'/'.$environment->getCurrentContextID().'/'.$session->getSessionID().'/';
      }else{
         $target_directory = 'var/temp/ims_import/'.$environment->getCurrentContextID().'/'.$session->getSessionID().'/';
      }
      $file_name = 'ims'.$session->getSessionID().'.zip';
      $file_url = $url_for_beluga_upload.$file_name;
      $destination_dir = $ims_content_connection_temp_folder.$file_name;

      // get file from external ims server
      if ( !empty($c_proxy_ip) ) {
         $out = fopen($destination_dir,'wb');
         if ( $out == false ) {
            include_once('functions/error_functions.php');
            trigger_error('can not open destination file. - '.__FILE__.' - '.__LINE__,E_USER_ERROR);
         }
         if ( function_exists('curl_init') ) {
            $ch = curl_init();
            curl_setopt($ch,CURLOPT_FILE,$out);
            curl_setopt($ch,CURLOPT_HEADER,0);
            curl_setopt($ch,CURLOPT_URL,$file_url);
            $proxy = $c_proxy_ip;
            if ( !empty($c_proxy_port) ) {
               $proxy = $c_proxy_ip.':'.$c_proxy_port;
            }
            curl_setopt($ch,CURLOPT_PROXY,$proxy);
            curl_exec($ch);
            $error = curl_error($ch);
            if ( !empty($error) ) {
               include_once('functions/error_functions.php');
               trigger_error('curl error: '.$error.' - '.$file_url.' - '.__FILE__.' - '.__LINE__,E_USER_ERROR);
            }
            curl_close($ch);
         } else {
            include_once('functions/error_functions.php');
            trigger_error('curl library php5-curl is not installed - '.__FILE__.' - '.__LINE__,E_USER_ERROR);
         }
         fclose($out);
      } else {
         copy($file_url,$destination_dir);
      }

      getMaterialListByIMSZip($file_name,$destination_dir,$target_directory,$environment);
      redirect($environment->getCurrentContextID(),CS_MATERIAL_TYPE, 'index','');
   } else {
      // Initialize the form
      $form = new cs_material_ims_import_form($environment);
      // Load form data from postvars
      if ( !empty($_POST) ) {
         if ( !empty($_FILES) ) {
            if ( !empty($_FILES['ims_upload']['tmp_name']) ) {
               $new_temp_name = $_FILES['ims_upload']['tmp_name'].'_TEMP_'.$_FILES['ims_upload']['name'];
               move_uploaded_file($_FILES['ims_upload']['tmp_name'],$new_temp_name);
               $_FILES['ims_upload']['tmp_name'] = $new_temp_name;
               $session_item = $environment->getSessionItem();
               if ( isset($session_item) ) {
                  $current_iid = $environment->getCurrentContextID();
                  $session_item->setValue($environment->getCurrentContextID().'_material_'.$current_iid.'_ims_temp_name',$new_temp_name);
                  $session_item->setValue($environment->getCurrentContextID().'_material_'.$current_iid.'_ims_name',$_FILES['ims_upload']['name']);
               }
            }
            $values = array_merge($_POST,$_FILES);
         } else {
            $values = $_POST;
         }
         $form->setFormPost($values);
      }

      $form->prepareForm();
      $form->loadValues();

      // Save items
      if ( !empty($command) and isOption($command, getMessage('MATERIAL_IMS_IMPORT_BUTTON')) ) {
         $correct = $form->check();

         if ( $correct
              and empty($_FILES['ims_upload']['tmp_name'])
              and !empty($_POST['hidden_ims_upload_name']))
            {
            $session_item = $environment->getSessionItem();
            if ( isset($session_item) ) {
               $current_iid = $this->_environment->getCurrentContextID();
               $_FILES['ims_upload']['tmp_name'] = $session_item->getValue($environment->getCurrentContextID().'_material_'.$current_iid.'_ims_temp_name');
               $_FILES['ims_upload']['name']     = $session_item->getValue($environment->getCurrentContextID().'_material_'.$current_iid.'_ims_name');
               $session_item->unsetValue($environment->getCurrentContextID().'_material_'.$current_iid.'_ims_temp_name');
               $session_item->unsetValue($environment->getCurrentContextID().'_material_'.$current_iid.'_ims_name');
            }
         }
         if ( $correct
               and ( !isset($c_virus_scan)
               or !$c_virus_scan
               or page_edit_virusscan_isClean($_FILES['ims_upload']['tmp_name'],$_FILES['ims_upload']['name'])))
            {
            include_once('include/inc_ims_upload.php');
            global $ims_content_connection_temp_folder;
            $session = $environment->getSessionItem();
            if (isset($ims_content_connection_temp_folder)){
               $target_directory = $ims_content_connection_temp_folder.'/'.$environment->getCurrentContextID().'/'.$session->getSessionID().'/';
            }else{
               $target_directory = 'var/temp/ims_import/'.$environment->getCurrentContextID().'/'.$session->getSessionID().'/';
            }
            getMaterialListByIMSZip($_FILES['ims_upload']['name'],$_FILES['ims_upload']['tmp_name'],$target_directory,$environment);
            redirect($environment->getCurrentContextID(),CS_MATERIAL_TYPE, 'index','');
         }

      }
      // display form
      include_once('classes/cs_form_view.php');
      $form_view = new cs_form_view($environment,'');
      $form_view->setAction(curl($environment->getCurrentContextID(),CS_MATERIAL_TYPE,'ims_import',''));
      $form_view->setForm($form);
      $page->add($form_view);

   }
}
?>