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
include_once('functions/curl_functions.php');

// Get the translator object
$translator = $environment->getTranslationObject();

if (!empty($_POST['option'])) {
   $command = $_POST['option'];
} else {
   $command = '';
}
$is_saved = false;

$context_item = $environment->getCurrentContextItem();

// Check access rights
if ($current_user->isGuest()) {
   if (!$context_item->isOpenForGuests()) {
      redirect($environment->getCurrentPortalId(),'home','index','');
   } else {
      $params = array() ;
      $params['cid'] = $context_item->getItemId();
      redirect($environment->getCurrentPortalId(),'home','index',$params);
   }
} elseif ( $context_item->isProjectRoom() and !$context_item->isOpen() ) {
   $params = array();
   $params['environment'] = $environment;
   $params['with_modifying_actions'] = true;
   $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
   unset($params);
   $errorbox->setText($translator->getMessage('PROJECT_ROOM_IS_CLOSED', $context_item->getTitle()));
   $page->add($errorbox);
   $command = 'error';
} elseif (!$current_user->isModerator()) {
   $params = array();
   $params['environment'] = $environment;
   $params['with_modifying_actions'] = true;
   $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
   unset($params);
   $errorbox->setText($translator->getMessage('ACCESS_NOT_GRANTED'));
   $page->add($errorbox);
   $command = 'error';
}

if ($command != 'error') { // only if user is allowed to edit colors

   // include form
   $class_params= array();
   $class_params['environment'] = $environment;
   $form = $class_factory->getClass(INTERNAL_COLOR_FORM,$class_params);
   unset($class_params);
   $form->setItem($context_item);
   // display form
   $params = array();
   $params['environment'] = $environment;
   $form_view = $class_factory->getClass(CONFIGURATION_COLOR_FORM_VIEW,$params);
   unset($params);

   // Save item

   if ( !empty($command) and isOption($command, $translator->getMessage('COMMON_SAVE_BUTTON')) ) {
      $color = $context_item->getColorArray();
      if ( isset($_POST['color_choice'])) {
         global $cs_color;
         if ($_POST['color_choice']=='COMMON_COLOR_SCHEMA_1'){
            $color = $cs_color['SCHEMA_1'];
         }elseif ($_POST['color_choice']=='COMMON_COLOR_SCHEMA_2'){
            $color = $cs_color['SCHEMA_2'];
         }elseif ($_POST['color_choice']=='COMMON_COLOR_DEFAULT'){
            $color = $cs_color['DEFAULT'];
         }elseif ($_POST['color_choice']=='COMMON_COLOR_SCHEMA_3'){
            $color = $cs_color['SCHEMA_3'];
         }elseif ($_POST['color_choice']=='COMMON_COLOR_SCHEMA_4'){
            $color = $cs_color['SCHEMA_4'];
         }elseif ($_POST['color_choice']=='COMMON_COLOR_SCHEMA_5'){
            $color = $cs_color['SCHEMA_5'];
         }elseif ($_POST['color_choice']=='COMMON_COLOR_SCHEMA_6'){
            $color = $cs_color['SCHEMA_6'];
         }elseif ($_POST['color_choice']=='COMMON_COLOR_SCHEMA_7'){
            $color = $cs_color['SCHEMA_7'];
         }elseif ($_POST['color_choice']=='COMMON_COLOR_SCHEMA_8'){
            $color = $cs_color['SCHEMA_8'];
         }elseif ($_POST['color_choice']=='COMMON_COLOR_SCHEMA_9'){
            $color = $cs_color['SCHEMA_9'];
         }elseif ($_POST['color_choice']=='COMMON_COLOR_SCHEMA_10'){
            $color = $cs_color['SCHEMA_10'];
         }elseif ($_POST['color_choice']=='COMMON_COLOR_SCHEMA_11'){
            $color = $cs_color['SCHEMA_11'];
         }elseif ($_POST['color_choice']=='COMMON_COLOR_SCHEMA_OWN'){
            if (!empty($_POST['color_1'])){
               $color['tabs_background'] = $_POST['color_1'];
            }
            if (!empty($_POST['color_2'])){
               $color['tabs_focus'] = $_POST['color_2'];
            }
            if (!empty($_POST['color_3'])){
               $color['table_background'] = $_POST['color_3'];
            }
            if (!empty($_POST['color_4'])){
               $color['tabs_title'] = $_POST['color_4'];
            }
            if (!empty($_POST['color_5'])){
               $color['headline_text'] = $_POST['color_5'];
            }
            if (!empty($_POST['color_6'])){
               $color['hyperlink'] = $_POST['color_6'];
            }
            if (!empty($_POST['color_7'])){
               $color['help_background'] = $_POST['color_7'];
            }
            if (!empty($_POST['color_8'])){
               $color['boxes_background'] = $_POST['color_8'];
            }
            if (!empty($_POST['color_9'])){
               $color['content_background'] = $_POST['color_9'];
            }
            if (!empty($_POST['color_10'])){
               $color['list_entry_odd'] = $_POST['color_10'];
            }
            if (!empty($_POST['color_11'])){
               $color['list_entry_even'] = $_POST['color_11'];
            }
             if (!empty($_POST['color_12'])){
               $color['index_td_head_title'] = $_POST['color_12'];
            }
             if (!empty($_POST['color_13'])){
               $color['date_title'] = $_POST['color_13'];
            }
             if (!empty($_POST['color_14'])){
               $color['info_color'] = $_POST['color_14'];
            }
             if (!empty($_POST['color_15'])){
               $color['disabled'] = $_POST['color_15'];
            }
             if (!empty($_POST['color_16'])){
               $color['warning'] = $_POST['color_16'];
            }
             if (!empty($_POST['color_17'])){
               $color['welcome_text'] = $_POST['color_17'];
            }
            $color['schema']='SCHEMA_OWN';
         }
      }

      $context_item->setColorArray($color);
      $context_item->generateLayoutImages();

      // save room_item
      $context_item->save();

      $form_view->setItemIsSaved();
      $is_saved = true;
      if ( !empty($_POST)) {
         if ($_POST['color_choice']=='-1'){
            $_POST['color_choice'] = '';
         }
         $form->setFormPost($_POST);
      } elseif ( isset($context_item) ) {
         $form->setItem($context_item);
      }
   } else{
      // init form, create form and loadValues
      // Load form data from postvars
      if ( !empty($_POST)) {
         if ($_POST['color_choice']=='-1'){
            $_POST['color_choice'] = '';
         }
         $form->setFormPost($_POST);
      } elseif ( isset($context_item) ) {
         $form->setItem($context_item);
      }
   }
   $form->prepareForm();
   $form->loadValues();

   $form_view->setAction(curl($environment->getCurrentContextID(),$environment->getCurrentModule(),$environment->getCurrentFunction(),''));
   $form_view->setForm($form);
   if ( $environment->inPortal() or $environment->inServer() ){
      $page->addForm($form_view);
   }else{
      $page->add($form_view);
   }
}
?>