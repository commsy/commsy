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

$this->includeClass(RUBRIC_FORM);
/** class for commsy forms
 * this class implements an interface for the creation of forms in the commsy style
 */
class cs_material_form extends cs_rubric_form {

  /**
   * string - containing the headline of the form
   */
   var $_headline = NULL;

   var $_public_arry = array();

  /**
   * array - containing an array of existing buzzwords in the context
   */
   var $_buzzword_array = array();

   var $_tag_array = array();
  /**
   * array - containing an array of shown buzzwords in the context
   */
   var $_shown_buzzword_array = array();

   var $_shown_tag_array = array();

   var $_session_tag_array = array();

   var $_public_array = array();

   var $_bib_kind = 'none';            // string holding the kind of bib data to show

   var $_workflow_array = array();

   var $_workflow_resubmission_array = array();

   var $_workflow_validity_array = array();

  /** constructor: cs_material_form
    * the only available constructor
    *
    * @param object environment the environment object
    *
    * @author CommSy Development Group
    */
   function __construct($params) {
      cs_rubric_form::__construct($params);
   }

   /** set buzzwords from session
    * set an array with the buzzwords from the session
    *
    * @param array array of buzzwords out of session
    */
   function setSessionBuzzwordArray ($value) {
      $this->_session_buzzword_array = (array)$value;
   }

   /** set tags from session
    * set an array with the tags from the session
    *
    * @param array array of tags out of session
    */
   function setSessionTagArray ($value) {
      $this->_session_tag_array = (array)$value;
   }



   function _initTagArray($item = NULL, $ebene = 0) {
      if ( isset($item) ) {
         $list = $item->getChildrenList();
         if ( isset($list) and !$list->isEmpty() ) {
            $current_item = $list->getFirst();
            while ( $current_item ) {
               $temp_array = array();
               $text = '';
               $i = 0;
               while($i < $ebene){
                  $text .='>  ';
                  $i++;
               }
               $text .= $current_item->getTitle();
               $temp_array['text']  = $text;
               $temp_array['value'] = $current_item->getItemID();
               $this->_tag_array[] = $temp_array;
               $this->_initTagArray($current_item, $ebene+1);
               $current_item = $list->getNext();
            }
         }
      }
   }

   /** init data for form, INTERNAL
    * this methods init the data for the form, for example groups
    */
   function _initForm () {
      // headline
      if (!empty($this->_item)) {
         $this->_headline = $this->_translator->getMessage('MATERIAL_EDIT');
      } elseif (!empty($this->_form_post)) {
         if (!empty($this->_form_post['iid'])) {
            $this->_headline = $this->_translator->getMessage('MATERIAL_EDIT');
         } else {
            $this->_headline = $this->_translator->getMessage('MATERIAL_ENTER_NEW');
         }
      } else {
         $this->_headline = $this->_translator->getMessage('MATERIAL_ENTER_NEW');
      }

      // files
      $file_array = array();
      if (!empty($this->_session_file_array)) {
         foreach ( $this->_session_file_array as $file ) {
            $temp_array['text'] = $file['name'];
            $temp_array['value'] = $file['file_id'];
            $file_array[] = $temp_array;
         }
      } elseif (isset($this->_item)) {
         $file_list = $this->_item->getFileList();
         if ($file_list->getCount() > 0) {
            $file_item = $file_list->getFirst();
            while ($file_item) {
               $temp_array['text'] = $file_item->getDisplayname();
               $temp_array['value'] = $file_item->getFileID();
               $file_array[] = $temp_array;
               $file_item = $file_list->getNext();
            }
         }
      }
      $this->_file_array = $file_array;

      $this->setHeadline($this->_headline);

      // bib kind
      if ( isset($this->_item) ) {
         $this->_bib_kind = $this->_item->getBibKind();
      } elseif (!empty($this->_form_post['bib_kind'])) {
         $this->_bib_kind = $this->_form_post['bib_kind'];
      }
      if (isset($this->_bib_kind) and (empty($this->_bib_kind)) ) {
         if ( isset($this->_item) ) {
            $text = $this->_item->getBibliographicValues();
            if (!empty($text)){
               $this->_bib_kind = 'common';
            }
         }
      }
      if (empty($this->_bib_kind)){
         $this->_bib_kind = 'none';
      }

      // public
      if ( isset($this->_item) ) {
         $creator_item = $this->_item->getCreatorItem();
         $fullname = $creator_item->getFullname();
      } elseif ( !empty($this->_form_post['iid'])
                 and mb_strtolower($this->_form_post['iid'], 'UTF-8') != 'new'
               ) {
         $manager = $this->_environment->getManager(CS_MATERIAL_TYPE);
         $item = $manager->getItem($this->_form_post['iid']);
         if ( isset($item) ) {
            $creator_item = $item->getCreatorItem();
            $fullname = $creator_item->getFullname();
         } else {
            $current_user = $this->_environment->getCurrentUser();
            $fullname = $current_user->getFullname();
         }
      } else {
         $current_user = $this->_environment->getCurrentUser();
         $fullname = $current_user->getFullname();
      }
      $public_array = array();
      $temp_array['text']  = $this->_translator->getMessage('RUBRIC_PUBLIC_YES');
      $temp_array['value'] = 1;
      $public_array[] = $temp_array;
      $temp_array['text']  = $this->_translator->getMessage('RUBRIC_PUBLIC_NO', $fullname);
      $temp_array['value'] = 0;
      $public_array[] = $temp_array;
      $this->_public_array = $public_array;

      $context_item = $this->_environment->getCurrentContextItem();
      $workflow_array = array();
      $temp_array['text']  = $this->_translator->getMessage('COMMON_WORKFLOW_TRAFFIC_LIGHT_TEXT_NONE');;
      $temp_array['value'] = '3_none';
      $workflow_array[] = $temp_array;
      $description = $this->_translator->getMessage('COMMON_WORKFLOW_TRAFFIC_LIGHT_TEXT_GREEN_DEFAULT');
      if($context_item->getWorkflowTrafficLightTextGreen() != ''){
         $description = $context_item->getWorkflowTrafficLightTextGreen();
      }
      $temp_array['text']  = '<img src="images/commsyicons/workflow_traffic_light_green.png" alt="'.$description.'" title="'.$description.'" style="height:10px;"> ('.$description.')';
      $temp_array['value'] = '0_green';
      $workflow_array[] = $temp_array;
      $description = $this->_translator->getMessage('COMMON_WORKFLOW_TRAFFIC_LIGHT_TEXT_YELLOW_DEFAULT');
      if($context_item->getWorkflowTrafficLightTextYellow() != ''){
         $description = $context_item->getWorkflowTrafficLightTextYellow();
      }
      $temp_array['text']  = '<img src="images/commsyicons/workflow_traffic_light_yellow.png" alt="'.$description.'" title="'.$description.'" style="height:10px;"> ('.$description.')';
      $temp_array['value'] = '1_yellow';
      $workflow_array[] = $temp_array;
      $description = $this->_translator->getMessage('COMMON_WORKFLOW_TRAFFIC_LIGHT_TEXT_RED_DEFAULT');
      if($context_item->getWorkflowTrafficLightTextRed() != ''){
         $description = $context_item->getWorkflowTrafficLightTextRed();
      }
      $temp_array['text']  = '<img src="images/commsyicons/workflow_traffic_light_red.png" alt="'.$description.'" title="'.$description.'" style="height:10px;"> ('.$description.')';
      $temp_array['value'] = '2_red';
      $workflow_array[] = $temp_array;
      $this->_workflow_array = $workflow_array;

      $validity_array = array();
      $temp_array['text']  = $this->_translator->getMessage('COMMON_WORKFLOW_TRAFFIC_LIGHT_TEXT_NONE');;
      $temp_array['value'] = '3_none';
      $validity_array[] = $temp_array;
      $description = $this->_translator->getMessage('COMMON_WORKFLOW_TRAFFIC_LIGHT_TEXT_GREEN_DEFAULT');
      if($context_item->getWorkflowTrafficLightTextGreen() != ''){
         $description = $context_item->getWorkflowTrafficLightTextGreen();
      }
      $temp_array['text']  = '<img src="images/commsyicons/workflow_traffic_light_green.png" alt="'.$description.'" title="'.$description.'" style="height:10px;"> ('.$description.')';
      $temp_array['value'] = '0_green';
      $validity_array[] = $temp_array;
      $description = $this->_translator->getMessage('COMMON_WORKFLOW_TRAFFIC_LIGHT_TEXT_YELLOW_DEFAULT');
      if($context_item->getWorkflowTrafficLightTextYellow() != ''){
         $description = $context_item->getWorkflowTrafficLightTextYellow();
      }
      $temp_array['text']  = '<img src="images/commsyicons/workflow_traffic_light_yellow.png" alt="'.$description.'" title="'.$description.'" style="height:10px;"> ('.$description.')';
      $temp_array['value'] = '1_yellow';
      $validity_array[] = $temp_array;
      $description = $this->_translator->getMessage('COMMON_WORKFLOW_TRAFFIC_LIGHT_TEXT_RED_DEFAULT');
      if($context_item->getWorkflowTrafficLightTextRed() != ''){
         $description = $context_item->getWorkflowTrafficLightTextRed();
      }
      $temp_array['text']  = '<img src="images/commsyicons/workflow_traffic_light_red.png" alt="'.$description.'" title="'.$description.'" style="height:10px;"> ('.$description.')';
      $temp_array['value'] = '2_red';
      $validity_array[] = $temp_array;
      $this->_validity_array = $validity_array;

      // Workflow resubmission

      // All users who ever edited this item

      $modifier_array = array();

      if ( !empty($_GET['iid']) and mb_strtolower($_GET['iid'], 'UTF-8') != 'new') {
         $manager = $this->_environment->getManager(CS_MATERIAL_TYPE);
         $item = $manager->getItem($_GET['iid']);
         if ( isset($item) ) {
            $creator_item = $item->getCreatorItem();
            $fullname = $creator_item->getFullname();
         } else {
            $current_user = $this->_environment->getCurrentUser();
            $fullname = $current_user->getFullname();
         }

         $link_modifier_item_manager = $this->_environment->getLinkModifierItemManager();
         $user_manager = $this->_environment->getUserManager();
         $modifiers = $link_modifier_item_manager->getModifiersOfItem($item->getItemID());

         foreach($modifiers as $modifier_id) {
            $modificator = $user_manager->getItem($modifier_id);
            //Links only at accessible contact pages
            if ( isset($modificator) and $modificator->isRoot() ) {
               $temp_text = $modificator->getFullname();
               $modifier_array[] = $temp_text;
            } elseif ( $modificator->getContextID() == $item->getContextID() ) {
               $user = $this->_environment->getCurrentUserItem();
               if ( $this->_environment->inProjectRoom() ) {
                  $params = array();
                  if (isset($modificator) and !empty($modificator) and $modificator->isUser() and !$modificator->isDeleted() and $modificator->maySee($user)){
                     $params['iid'] = $modificator->getItemID();
                     $temp_text = ahref_curl($this->_environment->getCurrentContextID(),
                                        'user',
                                        'detail',
                                        $params,
                                        $modificator->getFullname());
                  }elseif(isset($modificator) and  !$modificator->isDeleted()){
                      $temp_text = '<span class="disabled">'.$modificator->getFullname().'</span>';
                  }else{
                      $temp_text = '<span class="disabled">'.$this->_translator->getMessage('COMMON_DELETED_USER').'</span>';
                  }
                  $modifier_array[] = $temp_text;
               } elseif ( ($user->isUser() and isset($modificator) and  $modificator->isVisibleForLoggedIn())
                             || (!$user->isUser() and isset($modificator) and $modificator->isVisibleForAll())
                             || (isset($modificator) and $this->_environment->getCurrentUserID() == $modificator->getItemID()) ) {
                  $params = array();
                  $params['iid'] = $modificator->getItemID();
                  if(!$modificator->isDeleted() and $modificator->maySee($user)){
                     if ( !$this->_environment->inPortal() ){
                        $text_converter = $this->_environment->getTextConverter();
                        $modifier_array[] = ahref_curl($this->_environment->getCurrentContextID(),
                                           'user',
                                           'detail',
                                           $params,
                                           $text_converter->encode(AS_HTML_SHORT,$modificator->getFullname()));
                     }else{
                        $modifier_array[] = '<span class="disabled">'.$modificator->getFullname().'</span>';
                     }
                  }else{
                     $modifier_array[] = '<span class="disabled">'.$this->_translator->getMessage('COMMON_DELETED_USER').'</span>';
                  }
                  unset($params);
               } elseif ( $item->mayExternalSee($this->_environment->getCurrentUserItem())) {
                  $modifier_array[] = $modificator->getFullname();
               } else {
                  if(isset($modificator) and !$modificator->isDeleted()){
                     $current_user_item = $this->_environment->getCurrentUserItem();
                     if ( $current_user_item->isGuest() ) {
                        $modifier_array[] = $this->_translator->getMessage('COMMON_USER_NOT_VISIBLE');
                     } else {
                        $modifier_array[] = $modificator->getFullname();
                     }
                     unset($current_user_item);
                  }else{
                     $modifier_array[] = '<span class="disabled">'.$this->_translator->getMessage('COMMON_DELETED_USER').'</span>';
                  }
               }
            }
         }
         $modifier_array = array_unique($modifier_array);
      }

      $workflow_resubmission_array = array();
      $workflow_validity_array = array();
      $current_user = $this->_environment->getCurrentUserItem();
      $params['iid'] = $current_user->getItemID();
      $creator_link = ahref_curl($this->_environment->getCurrentContextID(),
                                 'user',
                                 'detail',
                                 $params,
                                 $current_user->getFullname());
      $temp_array['text']  = $this->_translator->getMessage('COMMON_WORKFLOW_RESUBMISSION_CREATOR').' ('.$creator_link.')';
      $temp_array['value'] = 'creator';
      $workflow_resubmission_array[] = $temp_array;
      $workflow_validity_array[] = $temp_array;
      $temp_array['text']  = $this->_translator->getMessage('COMMON_WORKFLOW_RESUBMISSION_MODIFIER');
      if(!empty($modifier_array)){
         $temp_array['text'] .= ' ('.implode(', ',$modifier_array).')';
      }
      $temp_array['value'] = 'modifier';
      $workflow_resubmission_array[] = $temp_array;
      $workflow_validity_array[] = $temp_array;
      $this->_workflow_resubmission_array = $workflow_resubmission_array;
      $this->_workflow_validity_array = $workflow_validity_array;
   }

   /** create the form, INTERNAL
    * this methods creates the form with the form definitions
    */
   function _createForm () {
      // material
      $this->_form->addHidden('iid','');
      $this->_form->addHidden('vid','');
      $this->_form->addHidden('modification_date','');
      $this->_form->addTitleField('title','',$this->_translator->getMessage('COMMON_TITLE'),$this->_translator->getMessage('COMMON_TITLE_DESC'),200,58,true);
#      if ( $this->_bib_kind=='common' ) {
#      }
#      elseif ( $this->_bib_kind=='none' ) {
#         $this->_form->addTextField('author','',$this->_translator->getMessage('MATERIAL_AUTHORS'),$this->_translator->getMessage('MATERIAL_AUTHORS_DESC'),200,35,false);
#      }
      $context_item = $this->_environment->getCurrentContextItem();
      if (isset($this->_item)) {
         $iid = $this->_item->getItemID();
      }else{
         $iid ='NEW';
      }


         $bib_kinds = array();
         $bib_kinds[] = array('text'  => '* '.$this->_translator->getMessage('MATERIAL_BIB_NOTHING'),
                              'value' => 'none');
         $bib_kinds[] = array('text'  => '* '.$this->_translator->getMessage('MATERIAL_BIB_NONE'),
                              'value' => 'common');
         $bib_kinds[] = array('text'  => $this->_translator->getMessage('MATERIAL_BIB_BOOK'),
                              'value' => 'book');
         $bib_kinds[] = array('text'  => $this->_translator->getMessage('MATERIAL_BIB_COLLECTION'),
                              'value' => 'collection');
         $bib_kinds[] = array('text'  => $this->_translator->getMessage('MATERIAL_BIB_INCOLLECTION'),
                              'value' => 'incollection');
         $bib_kinds[] = array('text'  => $this->_translator->getMessage('MATERIAL_BIB_ARTICLE'),
                              'value' => 'article');
         $bib_kinds[] = array('text'  => $this->_translator->getMessage('MATERIAL_BIB_CHAPTER'),
                              'value' => 'chapter');
         $bib_kinds[] = array('text'  => $this->_translator->getMessage('MATERIAL_BIB_INPAPER'),
                              'value' => 'inpaper');
         $bib_kinds[] = array('text'  => $this->_translator->getMessage('MATERIAL_BIB_THESIS'),
                              'value' => 'thesis');
         $bib_kinds[] = array('text'  => $this->_translator->getMessage('MATERIAL_BIB_MANUSCRIPT'),
                              'value' => 'manuscript');
         $bib_kinds[] = array('text'  => $this->_translator->getMessage('MATERIAL_BIB_WEBSITE'),
                              'value' => 'website');
      	 /** Start Dokumentenverwaltung **/
         $bib_kinds[] = array('text'  => $this->_translator->getMessage('MATERIAL_BIB_DOCUMENT'),
                              'value' => 'document');
      	 /** Ende Dokumentenverwaltung **/
         $this->_form->addSelect('bib_kind',$bib_kinds,'',$this->_translator->getMessage('MATERIAL_BIBLIOGRAPHIC'),'', 1, false,false,true,$this->_translator->getMessage('MATERIAL_BIB_KIND_BUTTON'),'option','','',18.3,true);
         switch ( $this->_bib_kind ) {
            case 'book':
               $this->_form->addTextField('author','',$this->_translator->getMessage('MATERIAL_AUTHORS'),$this->_translator->getMessage('MATERIAL_AUTHORS_DESC'),200,35,true);
               $this->_form->addTextField('publishing_date','',$this->_translator->getMessage('MATERIAL_YEAR'),$this->_translator->getMessage('MATERIAL_YEAR'),4,4,true);
               $this->_form->addTextField('publisher','',$this->_translator->getMessage('MATERIAL_PUBLISHER'),$this->_translator->getMessage('MATERIAL_PUBLISHER_DESC'),200,35,true);
               $this->_form->addTextField('address','',$this->_translator->getMessage('MATERIAL_ADDRESS'),$this->_translator->getMessage('MATERIAL_ADDRESS_DESC'),200,35,true);
               $this->_form->addTextField('edition','',$this->_translator->getMessage('MATERIAL_EDITION'),$this->_translator->getMessage('MATERIAL_EDITION_DESC'),200,3);
               $this->_form->addTextField('series','',$this->_translator->getMessage('MATERIAL_SERIES'),$this->_translator->getMessage('MATERIAL_SERIES_DESC'),200,35);
               $this->_form->addTextField('volume','',$this->_translator->getMessage('MATERIAL_VOLUME'),$this->_translator->getMessage('MATERIAL_VOLUME_DESC'),200,4);
               $this->_form->addTextField('isbn','',$this->_translator->getMessage('MATERIAL_ISBN'),$this->_translator->getMessage('MATERIAL_ISBN_DESC'),30,20);
               $this->_form->addTextField('url','',$this->_translator->getMessage('MATERIAL_URL'),'',200,35);
               $this->_form->addTextField('url_date','',$this->_translator->getMessage('MATERIAL_URL_DATE'),'',10,20);
               break;
            case 'collection':
               $this->_form->addTextField('author','',$this->_translator->getMessage('MATERIAL_EDITOR'),$this->_translator->getMessage('MATERIAL_EDITOR_DESC'),200,35,true);
               $this->_form->addTextField('publishing_date','',$this->_translator->getMessage('MATERIAL_YEAR'),$this->_translator->getMessage('MATERIAL_YEAR'),4,4,true);
               $this->_form->addTextField('publisher','',$this->_translator->getMessage('MATERIAL_PUBLISHER'),$this->_translator->getMessage('MATERIAL_PUBLISHER_DESC'),200,35,true);
               $this->_form->addTextField('address','',$this->_translator->getMessage('MATERIAL_ADDRESS'),$this->_translator->getMessage('MATERIAL_ADDRESS_DESC'),200,35,true);
               $this->_form->addTextField('edition','',$this->_translator->getMessage('MATERIAL_EDITION'),$this->_translator->getMessage('MATERIAL_EDITION_DESC'),200,3);
               $this->_form->addTextField('series','',$this->_translator->getMessage('MATERIAL_SERIES'),$this->_translator->getMessage('MATERIAL_SERIES_DESC'),200,35);
               $this->_form->addTextField('volume','',$this->_translator->getMessage('MATERIAL_VOLUME'),$this->_translator->getMessage('MATERIAL_VOLUME_DESC'),200,4);
               $this->_form->addTextField('isbn','',$this->_translator->getMessage('MATERIAL_ISBN'),$this->_translator->getMessage('MATERIAL_ISBN_DESC'),30,20);
            $this->_form->addTextField('url','',$this->_translator->getMessage('MATERIAL_URL'),'',200,35);
            $this->_form->addTextField('url_date','',$this->_translator->getMessage('MATERIAL_URL_DATE'),'',10,20);
               break;
            case 'incollection':
               $this->_form->addTextField('author','',$this->_translator->getMessage('MATERIAL_AUTHORS'),$this->_translator->getMessage('MATERIAL_AUTHORS_DESC'),200,35,true);
               $this->_form->addTextField('publishing_date','',$this->_translator->getMessage('MATERIAL_YEAR'),$this->_translator->getMessage('MATERIAL_YEAR'),4,4,true);
               $this->_form->addTextField('editor','',$this->_translator->getMessage('MATERIAL_EDITOR'),$this->_translator->getMessage('MATERIAL_EDITOR_DESC'),200,35,true);
               $this->_form->addTextField('booktitle','',$this->_translator->getMessage('MATERIAL_BOOKTITLE'),$this->_translator->getMessage('MATERIAL_BOOKTITLE_DESC'),200,35,true);
               $this->_form->addTextField('address','',$this->_translator->getMessage('MATERIAL_ADDRESS'),$this->_translator->getMessage('MATERIAL_ADDRESS_DESC'),200,35,true);
               $this->_form->addTextField('publisher','',$this->_translator->getMessage('MATERIAL_PUBLISHER'),$this->_translator->getMessage('MATERIAL_PUBLISHER_DESC'),200,35,true);
               $this->_form->addTextField('edition','',$this->_translator->getMessage('MATERIAL_EDITION'),$this->_translator->getMessage('MATERIAL_EDITION_DESC'),200,3);
               $this->_form->addTextField('series','',$this->_translator->getMessage('MATERIAL_SERIES'),$this->_translator->getMessage('MATERIAL_SERIES_DESC'),200,35);
               $this->_form->addTextField('volume','',$this->_translator->getMessage('MATERIAL_VOLUME'),$this->_translator->getMessage('MATERIAL_VOLUME_DESC'),200,4);
               $this->_form->addTextField('isbn','',$this->_translator->getMessage('MATERIAL_ISBN'),$this->_translator->getMessage('MATERIAL_ISBN_DESC'),30,20);
               $this->_form->addTextField('pages','',$this->_translator->getMessage('MATERIAL_PAGES'),$this->_translator->getMessage('MATERIAL_PAGES_DESC'),20,15,true);
            $this->_form->addTextField('url','',$this->_translator->getMessage('MATERIAL_URL'),'',200,35);
            $this->_form->addTextField('url_date','',$this->_translator->getMessage('MATERIAL_URL_DATE'),'',10,20);
               break;
            case 'article':
               $this->_form->addTextField('author','',$this->_translator->getMessage('MATERIAL_AUTHORS'),$this->_translator->getMessage('MATERIAL_AUTHORS_DESC'),200,35,true);
               $this->_form->addTextField('publishing_date','',$this->_translator->getMessage('MATERIAL_YEAR'),$this->_translator->getMessage('MATERIAL_YEAR'),4,4,true);
               $this->_form->addTextField('journal','',$this->_translator->getMessage('MATERIAL_JOURNAL'),$this->_translator->getMessage('MATERIAL_JOURNAL_DESC'),200,35,true);
               $this->_form->addTextField('volume','',$this->_translator->getMessage('MATERIAL_VOLUME_J'),$this->_translator->getMessage('MATERIAL_VOLUME_J_DESC'),200,4);
               $this->_form->addTextField('issue','',$this->_translator->getMessage('MATERIAL_ISSUE'),$this->_translator->getMessage('MATERIAL_ISSUE_DESC'),200,3);
               $this->_form->addTextField('pages','',$this->_translator->getMessage('MATERIAL_PAGES'),$this->_translator->getMessage('MATERIAL_PAGES_DESC'),20,15,true);
               $this->_form->addTextField('address','',$this->_translator->getMessage('MATERIAL_ADDRESS'),$this->_translator->getMessage('MATERIAL_ADDRESS_DESC'),200,35,false);
               $this->_form->addTextField('publisher','',$this->_translator->getMessage('MATERIAL_PUBLISHER'),$this->_translator->getMessage('MATERIAL_PUBLISHER_DESC'),200,35,false);
               $this->_form->addTextField('issn','',$this->_translator->getMessage('MATERIAL_ISSN'),$this->_translator->getMessage('MATERIAL_ISSN_DESC'),30,20);
            $this->_form->addTextField('url','',$this->_translator->getMessage('MATERIAL_URL'),'',200,35);
            $this->_form->addTextField('url_date','',$this->_translator->getMessage('MATERIAL_URL_DATE'),'',10,20);
               break;
            case 'chapter':
               $this->_form->addTextField('author','',$this->_translator->getMessage('MATERIAL_EDITOR'),$this->_translator->getMessage('MATERIAL_EDITOR_DESC'),200,35,true);
               $this->_form->addTextField('publishing_date','',$this->_translator->getMessage('MATERIAL_YEAR'),$this->_translator->getMessage('MATERIAL_YEAR'),4,4,true);
               $this->_form->addTextField('address','',$this->_translator->getMessage('MATERIAL_ADDRESS'),$this->_translator->getMessage('MATERIAL_ADDRESS_DESC'),200,35,true);
               $this->_form->addTextField('edition','',$this->_translator->getMessage('MATERIAL_EDITION'),$this->_translator->getMessage('MATERIAL_EDITION_DESC'),200,3);
               $this->_form->addTextField('series','',$this->_translator->getMessage('MATERIAL_SERIES'),$this->_translator->getMessage('MATERIAL_SERIES_DESC'),200,35);
               $this->_form->addTextField('volume','',$this->_translator->getMessage('MATERIAL_VOLUME'),$this->_translator->getMessage('MATERIAL_VOLUME_DESC'),200,4);
               $this->_form->addTextField('isbn','',$this->_translator->getMessage('MATERIAL_ISBN'),$this->_translator->getMessage('MATERIAL_ISBN_DESC'),30,20);
            $this->_form->addTextField('url','',$this->_translator->getMessage('MATERIAL_URL'),'',200,35);
            $this->_form->addTextField('url_date','',$this->_translator->getMessage('MATERIAL_URL_DATE'),'',10,20);
               break;
            case 'inpaper':
               $this->_form->addTextField('author','',$this->_translator->getMessage('MATERIAL_AUTHORS'),$this->_translator->getMessage('MATERIAL_AUTHORS_DESC'),200,35,true);
               $this->_form->addTextField('publishing_date','',$this->_translator->getMessage('MATERIAL_YEAR'),$this->_translator->getMessage('MATERIAL_YEAR'),4,5,true);
               $this->_form->addTextField('journal','',$this->_translator->getMessage('MATERIAL_PAPER'),$this->_translator->getMessage('MATERIAL_PAPER_DESC'),200,35,true);
               $this->_form->addTextField('issue','',$this->_translator->getMessage('MATERIAL_ISSUE'),$this->_translator->getMessage('MATERIAL_ISSUE_DESC'),200,3);
               $this->_form->addTextField('pages','',$this->_translator->getMessage('MATERIAL_PAGES'),$this->_translator->getMessage('MATERIAL_PAGES_DESC'),200,15,true);
               $this->_form->addTextField('address','',$this->_translator->getMessage('MATERIAL_ADDRESS'),$this->_translator->getMessage('MATERIAL_ADDRESS_DESC'),200,35,false);
               $this->_form->addTextField('publisher','',$this->_translator->getMessage('MATERIAL_PUBLISHER'),$this->_translator->getMessage('MATERIAL_PUBLISHER_DESC'),200,'',false);
            $this->_form->addTextField('url','',$this->_translator->getMessage('MATERIAL_URL'),'',200,35);
            $this->_form->addTextField('url_date','',$this->_translator->getMessage('MATERIAL_URL_DATE'),'',10,20);
               break;
            case 'thesis':
               $thesis_kinds = array();
               $thesis_kinds[] = array('text'  => $this->_translator->getMessage('MATERIAL_THESIS_TERM'),
                                       'value' => 'term');
               $thesis_kinds[] = array('text'  => $this->_translator->getMessage('MATERIAL_THESIS_BACHELOR'),
                                       'value' => 'bachelor');
               $thesis_kinds[] = array('text'  => $this->_translator->getMessage('MATERIAL_THESIS_MASTER'),
                                       'value' => 'master');
               $thesis_kinds[] = array('text'  => $this->_translator->getMessage('MATERIAL_THESIS_EXAM'),
                                       'value' => 'exam');
               $thesis_kinds[] = array('text'  => $this->_translator->getMessage('MATERIAL_THESIS_DIPLOMA'),
                                       'value' => 'diploma');
               $thesis_kinds[] = array('text'  => $this->_translator->getMessage('MATERIAL_THESIS_DISSERTATION'),
                                       'value' => 'dissertation');
               $thesis_kinds[] = array('text'  => $this->_translator->getMessage('MATERIAL_THESIS_POSTDOC'),
                                       'value' => 'postdoc');
               $this->_form->addTextField('author','',$this->_translator->getMessage('MATERIAL_AUTHORS'),$this->_translator->getMessage('MATERIAL_AUTHORS_DESC'),200,35,true);
               $this->_form->addTextField('publishing_date','',$this->_translator->getMessage('MATERIAL_YEAR'),$this->_translator->getMessage('MATERIAL_YEAR'),4,5,true);
               $this->_form->addSelect('thesis_kind',$thesis_kinds,'',$this->_translator->getMessage('MATERIAL_THESIS_KIND'),$this->_translator->getMessage('MATERIAL_THESIS_KIND_DESC'), 1, false,true,false,'','','','',24.8);
               $this->_form->addTextField('address','',$this->_translator->getMessage('MATERIAL_ADDRESS'),$this->_translator->getMessage('MATERIAL_ADDRESS_DESC'),200,35,true);
               $this->_form->addTextField('university','',$this->_translator->getMessage('MATERIAL_UNIVERSITY'),$this->_translator->getMessage('MATERIAL_UNIVERSITY_DESC'),200,35,true);
               $this->_form->addTextField('faculty','',$this->_translator->getMessage('MATERIAL_FACULTY'),$this->_translator->getMessage('MATERIAL_FACULTY_DESC'),200,35,false);
            $this->_form->addTextField('url','',$this->_translator->getMessage('MATERIAL_URL'),'',200,35);
            $this->_form->addTextField('url_date','',$this->_translator->getMessage('MATERIAL_URL_DATE'),'',10,20);
               break;
            case 'manuscript':
               $this->_form->addTextField('author','',$this->_translator->getMessage('MATERIAL_AUTHORS'),$this->_translator->getMessage('MATERIAL_AUTHORS_DESC'),200,35,true);
               $this->_form->addTextField('publishing_date','',$this->_translator->getMessage('MATERIAL_YEAR'),$this->_translator->getMessage('MATERIAL_YEAR'),4,5,true);
               $this->_form->addTextField('address','',$this->_translator->getMessage('MATERIAL_ADDRESS'),$this->_translator->getMessage('MATERIAL_ADDRESS_DESC'),200,35,true);
            $this->_form->addTextField('url','',$this->_translator->getMessage('MATERIAL_URL'),'',200,35);
            $this->_form->addTextField('url_date','',$this->_translator->getMessage('MATERIAL_URL_DATE'),'',10,20);
               break;
         case 'website':
            $this->_form->addTextField('author','',$this->_translator->getMessage('MATERIAL_AUTHORS'),$this->_translator->getMessage('MATERIAL_AUTHORS_DESC'),200,35,true);
            $this->_form->addTextField('url','',$this->_translator->getMessage('MATERIAL_URL'),'',200,35,true);
            $this->_form->addTextField('url_date','',$this->_translator->getMessage('MATERIAL_URL_DATE'),'',10,20);
            break;
      		/** Start Dokumentenverwaltung **/
            case 'document':
               $this->_form->addTextField('document_editor','',$this->_translator->getMessage('MATERIAL_BIB_DOCUMENT_EDITOR'),$this->_translator->getMessage('MATERIAL_BIB_DOCUMENT_EDITOR'),200,35,false);
               $this->_form->addTextField('document_maintainer','',$this->_translator->getMessage('MATERIAL_BIB_DOCUMENT_MAINTAINER'),$this->_translator->getMessage('MATERIAL_BIB_DOCUMENT_MAINTAINER'),200,35,false);
               $this->_form->addTextField('document_release_number','',$this->_translator->getMessage('MATERIAL_BIB_DOCUMENT_RELEASE_NUMBER'),$this->_translator->getMessage('MATERIAL_BIB_DOCUMENT_RELEASE_NUMBER'),200,35,false);
               $this->_form->addTextField('document_release_date','',$this->_translator->getMessage('MATERIAL_BIB_DOCUMENT_RELEASE_DATE'),$this->_translator->getMessage('MATERIAL_BIB_DOCUMENT_RELEASE_DATE'),200,35,false);
               break;
      		/** Ende Dokumentenverwaltung **/
            case 'common':
                  $this->_form->addTextField('author','',$this->_translator->getMessage('MATERIAL_AUTHORS'),$this->_translator->getMessage('MATERIAL_AUTHORS_DESC'),200,35,false);
                  $this->_form->addTextField('publishing_date','',$this->_translator->getMessage('MATERIAL_YEAR'),$this->_translator->getMessage('MATERIAL_YEAR'),4,4,false);
                  $this->_form->addTextArea('common','',$this->_translator->getMessage('MATERIAL_BIBLIOGRAPHIC'),$this->_translator->getMessage('MATERIAL_BIBLIOGRAPHIC_DESC'),'',3,'virtual',false,false,true,1,true,false);
               break;
            case 'none':
            default:
               break;
         }

      if ( $this->_bib_kind=='none' ) {
         $this->_form->addTextArea('description','',$this->_translator->getMessage('MATERIAL_ABSTRACT'),'','',20);
      } else {
         $this->_form->addTextArea('description','',$this->_translator->getMessage('MATERIAL_ABSTRACT'),'','',20);
      }
      // files
      $this->_form->addAnchor('fileupload');
      $val = $context_item->getMaxUploadSizeInBytes();
      $meg_val = round($val/1048576);
      if ( !empty($this->_file_array) ) {
         $this->_form->addCheckBoxGroup('filelist',$this->_file_array,array(true),$this->_translator->getMessage('MATERIAL_FILES'),$this->_translator->getMessage('MATERIAL_FILES_DESC', $meg_val),false,false);
         $this->_form->combine('vertical');
      }
      $this->_form->addHidden('MAX_FILE_SIZE', $val);
      $this->_form->addFilefield('upload', $this->_translator->getMessage('MATERIAL_FILES'), $this->_translator->getMessage('MATERIAL_UPLOAD_DESC',$meg_val), 12, false, $this->_translator->getMessage('MATERIAL_UPLOADFILE_BUTTON'),'option',$this->_with_multi_upload);
      $this->_form->combine('vertical');
      //global $c_new_upload;
      $use_new_upload = false;
      $session = $this->_environment->getSession();
      if($session->issetValue('javascript') and $session->issetValue('flash')){
         if(($session->getValue('javascript') == '1') and ($session->getValue('flash') == '1')){
            $use_new_upload = true;
         }
      }
      if ($this->_with_multi_upload or $use_new_upload) {
         // do nothing
      } else {
         #$px = '245';
         $px = '331';
         $browser = $this->_environment->getCurrentBrowser();
         if ($browser == 'MSIE') {
            $px = '361';
         } elseif ($browser == 'OPERA') {
            $px = '321';
         } elseif ($browser == 'KONQUEROR') {
            $px = '361';
         } elseif ($browser == 'SAFARI') {
            $px = '380';
         } elseif ($browser == 'FIREFOX') {
            $operation_system = $this->_environment->getCurrentOperatingSystem();
            if (mb_strtoupper($operation_system, 'UTF-8') == 'LINUX') {
               $px = '370';
            } elseif (mb_strtoupper($operation_system, 'UTF-8') == 'MAC OS') {
               $px = '362';
            }
         } elseif ($browser == 'MOZILLA') {
            $operation_system = $this->_environment->getCurrentOperatingSystem();
            if (mb_strtoupper($operation_system, 'UTF-8') == 'MAC OS') {
               $px = '336'; // camino
            }
         }
         $this->_form->addButton('option',$this->_translator->getMessage('MATERIAL_BUTTON_MULTI_UPLOAD_YES'),'','',$px.'px');
      }
      $this->_form->combine('vertical');
      $this->_form->addText('max_size',$val,$this->_translator->getMessage('MATERIAL_MAX_FILE_SIZE',$meg_val));

      $session = $this->_environment->getSession();
      $new_upload = false;
      if($session->issetValue('javascript') and $session->issetValue('flash')) {
      	if(($session->getValue('javascript') == '1') and ($session->getValue('flash') == '1')) {
      	   $new_upload = true;
      	}
      }
      if(!$new_upload) $this->_form->addText('old_upload', '', $this->_translator->getMessage('COMMON_UPLOAD_OLD'));

      $current_context = $this->_environment->getCurrentContextItem();
      // world public
      if ($current_context->isOpenForGuests() and $this->_environment->inCommunityRoom() ) {
         if (isset($this->_item)) {
            $world_publish = $this->_item->getWorldPublic();
            if ( $world_publish == 0) {
               $this->_form->addCheckBox('world_public','1','',$this->_translator->getMessage('MATERIAL_WORLD_PUBLISH'),$this->_translator->getMessage('MATERIAL_WORLD_PUBLISH_CHANGE_STATUS_0'),$this->_translator->getMessage('MATERIAL_WORLD_PUBLISH_DESC'), false);
            } elseif ( $world_publish == 1) {
               $this->_form->addCheckBox('world_public','1','',$this->_translator->getMessage('MATERIAL_WORLD_PUBLISH'),$this->_translator->getMessage('MATERIAL_WORLD_PUBLISH_CHANGE_STATUS_1'),$this->_translator->getMessage('MATERIAL_WORLD_PUBLISH_DESC'), false);
            } else {
               $this->_form->addCheckBox('world_public','1','',$this->_translator->getMessage('MATERIAL_WORLD_PUBLISH'),$this->_translator->getMessage('MATERIAL_WORLD_PUBLISH_CHANGE_STATUS_2'),$this->_translator->getMessage('MATERIAL_WORLD_PUBLISH_DESC'), false);
            }
         } else {
            $this->_form->addCheckBox('world_public','1','',$this->_translator->getMessage('MATERIAL_WORLD_PUBLISH'),$this->_translator->getMessage('MATERIAL_WORLD_PUBLISH_CHANGE_STATUS_0'),$this->_translator->getMessage('MATERIAL_WORLD_PUBLISH_DESC'), false);
         }
      }

      // rubric connections
      $this->_setFormElementsForConnectedRubrics();

      if ( !$this->_environment->inPrivateRoom() ){

         if ($current_context->withActivatingContent()){
            $this->_form->addCheckbox('private_editing',1,'',$this->_translator->getMessage('COMMON_RIGHTS'),$this->_public_array[1]['text'],$this->_translator->getMessage('COMMON_RIGHTS_DESCRIPTION'),false,false,'','',true,false);
            $this->_form->combine();
            $this->_form->addCheckbox('hide',1,'',$this->_translator->getMessage('COMMON_HIDE'),$this->_translator->getMessage('COMMON_HIDE'),'');
            $this->_form->combine('horizontal');
            $this->_form->addDateTimeField('start_date_time','','dayStart','timeStart',9,4,$this->_translator->getMessage('DATES_HIDING_DAY'),'('.$this->_translator->getMessage('DATES_HIDING_DAY'),$this->_translator->getMessage('DATES_HIDING_TIME'),$this->_translator->getMessage('DATES_TIME_DAY_START_DESC'),FALSE,FALSE,100,100,true,'left','',FALSE);
            $this->_form->combine('horizontal');
            $this->_form->addText('hide_end2','',')');
         }else{
             // public radio-buttons
             if ( !isset($this->_item) ) {
                $this->_form->addRadioGroup('public',$this->_translator->getMessage('RUBRIC_PUBLIC'),$this->_translator->getMessage('RUBRIC_PUBLIC_DESC'),$this->_public_array);
             } else {
                $current_user = $this->_environment->getCurrentUser();
                $creator = $this->_item->getCreatorItem();
                if ($current_user->getItemID() == $creator->getItemID() or $current_user->isModerator()) {
                   $this->_form->addRadioGroup('public',$this->_translator->getMessage('RUBRIC_PUBLIC'),$this->_translator->getMessage('RUBRIC_PUBLIC_DESC'),$this->_public_array);
                } else {
                   $this->_form->addHidden('public','');
                }
             }
         }

         if ($current_context->withWorkflow()){
            if($current_context->withWorkflowTrafficLight() or $current_context->withWorkflowResubmission()){
               $this->_form->addText('',$this->_translator->getMessage('COMMON_WORKFLOW'),'','',false,'','','left','','',true,false,$this->_translator->getMessage('COMMON_WORKFLOW_DESCRIPTION'));
               $this->_form->combine();
            }
            if($current_context->withWorkflowTrafficLight()){
               $this->_form->addText('','',$this->_translator->getMessage('COMMON_WORKFLOW_TRAFFIC_LIGHT'),'',false,'','','left','','',true);
               $this->_form->combine();
               $this->_form->addRadioGroup('workflow_traffic_light',$this->_translator->getMessage('COMMON_WORKFLOW'),$this->_translator->getMessage('COMMON_WORKFLOW_DESCRIPTION'),$this->_workflow_array,'',false,false,'','',false,'',true);
            }
            if($current_context->withWorkflowTrafficLight() and ($current_context->withWorkflowResubmission() or $current_context->withWorkflowValidity())){
               $this->_form->combine();
               $this->_form->addText('', '', '<br/><br/><hr/>');
               $this->_form->combine();
            }
            if($current_context->withWorkflowResubmission()){
               $this->_form->addText('','',$this->_translator->getMessage('COMMON_WORKFLOW_RESUBMISSION'),'',false,'','','left','','',true);
               $this->_form->combine();
               $this->_form->addCheckbox('workflow_resubmission',1,'',$this->_translator->getMessage('COMMON_WORKFLOW_RESUBMISSION'),'','');
               $this->_form->combine('horizontal');
               $this->_form->addDateTimeField('workflow_resubmission_date','','workflow_resubmission_date','',9,4,'','','','',FALSE,FALSE,100,100,true,'left','',FALSE,TRUE);
               $this->_form->combine();
               $this->_form->addText('', '', '&nbsp;');
               $this->_form->combine();
               $this->_form->addText('workflow_resubmission_who_text','',$this->_translator->getMessage('COMMON_WORKFLOW_RESUBMISSION_WHO').':');
               $this->_form->combine();
               $this->_form->addRadioGroup('workflow_resubmission_who',$this->_translator->getMessage('COMMON_WORKFLOW_RESUBMISSION'),$this->_translator->getMessage('COMMON_WORKFLOW_RESUBMISSION'),$this->_workflow_resubmission_array,'',false,false,'','',false,'',true);
               $this->_form->combine();
               $this->_form->addTextField('workflow_resubmission_who_additional', '', '', '', 255, 50, false, '', '', '', 'left', $this->_translator->getMessage('COMMON_WORKFLOW_RESUBMISSION_ADDITIONAL').':', '', false, '('.$this->_translator->getMessage('COMMON_WORKFLOW_RESUBMISSION_ADDITIONAL_SEPERATOR').')');
               $this->_form->combine();
               $this->_form->addText('', '', '&nbsp;');
               $this->_form->combine();
               $this->_form->addText('workflow_resubmission_traffic_light_text','',$this->_translator->getMessage('COMMON_WORKFLOW_RESUBMISSION_TRAFFIC_LIGHT').':');
               $this->_form->combine();
               $this->_form->addRadioGroup('workflow_resubmission_traffic_light',$this->_translator->getMessage('COMMON_WORKFLOW'),$this->_translator->getMessage('COMMON_WORKFLOW_DESCRIPTION'),$this->_workflow_array,'',false,false,'','',false,'',true);
            } else {
               if (isset($this->_item)) {
                  #$this->_form->addHidden('workflow_resubmission', $this->_item->getWorkflowResubmission());
                  #$this->_form->addHidden('workflow_resubmission_date', $this->_item->getWorkflowResubmissionDate());
                  #$this->_form->addHidden('workflow_resubmission_who', $this->_item->getWorkflowResubmissionWho());
                  #$this->_form->addHidden('workflow_resubmission_traffic_light', $this->_item->getWorkflowResubmissionTrafficLight());
               }
            }
            if($current_context->withWorkflowResubmission() and $current_context->withWorkflowValidity()){
               $this->_form->combine();
               $this->_form->addText('', '', '<br/><br/><hr/>');
               $this->_form->combine();
            }
            if($current_context->withWorkflowValidity()){
               $this->_form->addText('','',$this->_translator->getMessage('COMMON_WORKFLOW_VALIDITY'),'',false,'','','left','','',true);
               $this->_form->combine();
               $this->_form->addCheckbox('workflow_validity',1,'',$this->_translator->getMessage('COMMON_WORKFLOW_VALIDITY'),'','');
               $this->_form->combine('horizontal');
               $this->_form->addDateTimeField('workflow_validity_date','','workflow_validity_date','',9,4,'','','','',FALSE,FALSE,100,100,true,'left','',FALSE,TRUE);
               $this->_form->combine();
               $this->_form->addText('', '', '&nbsp;');
               $this->_form->combine();
               $this->_form->addText('workflow_validity_who_text','',$this->_translator->getMessage('COMMON_WORKFLOW_RESUBMISSION_WHO').':');
               $this->_form->combine();
               $this->_form->addRadioGroup('workflow_validity_who',$this->_translator->getMessage('COMMON_WORKFLOW_RESUBMISSION'),$this->_translator->getMessage('COMMON_WORKFLOW_RESUBMISSION'),$this->_workflow_validity_array,'',false,false,'','',false,'',true);
               $this->_form->combine();
               $this->_form->addTextField('workflow_validity_who_additional', '', '', '', 255, 50, false, '', '', '', 'left', $this->_translator->getMessage('COMMON_WORKFLOW_RESUBMISSION_ADDITIONAL').':', '', false, '('.$this->_translator->getMessage('COMMON_WORKFLOW_RESUBMISSION_ADDITIONAL_SEPERATOR').')');
               $this->_form->combine();
               $this->_form->addText('', '', '&nbsp;');
               $this->_form->combine();
               $this->_form->addText('workflow_validity_traffic_light_text','',$this->_translator->getMessage('COMMON_WORKFLOW_VALIDITY_TRAFFIC_LIGHT').':');
               $this->_form->combine();
               $this->_form->addRadioGroup('workflow_validity_traffic_light',$this->_translator->getMessage('COMMON_WORKFLOW'),$this->_translator->getMessage('COMMON_WORKFLOW_DESCRIPTION'),$this->_validity_array,'',false,false,'','',false,'',true);
            } else {
               if (isset($this->_item)) {
                  #$this->_form->addHidden('workflow_resubmission', $this->_item->getWorkflowResubmission());
                  #$this->_form->addHidden('workflow_resubmission_date', $this->_item->getWorkflowResubmissionDate());
                  #$this->_form->addHidden('workflow_resubmission_who', $this->_item->getWorkflowResubmissionWho());
                  #$this->_form->addHidden('workflow_resubmission_traffic_light', $this->_item->getWorkflowResubmissionTrafficLight());
               }
            }
         }

      } else {
         $this->_form->addHidden('public','');
         $this->_form->addCheckbox('external_viewer',1,'',$this->_translator->getMessage('COMMON_RIGHTS'),$this->_translator->getMessage('EXTERNAL_VIEWER_DESCRIPTION'),$this->_translator->getMessage('COMMON_RIGHTS_DESCRIPTION'),false,false,'','',true,false);
         $this->_form->combine();
         $this->_form->addTextField('external_viewer_accounts','',$this->_translator->getMessage('EXTERNAL_VIEWER'),$this->_translator->getMessage('EXTERNAL_VIEWER_DESC'),200,35,false);
      }

      // buttons
      $id = 0;
      if (isset($this->_item)) {
         $id = $this->_item->getItemID();
      } elseif (isset($this->_form_post)) {
         if (isset($this->_form_post['iid'])) {
            $id = $this->_form_post['iid'];
         }
      }
      if ( $id == 0 )  {
         $this->_form->addButtonBar('option',$this->_translator->getMessage('MATERIAL_SAVE_BUTTON'),$this->_translator->getMessage('COMMON_CANCEL_BUTTON'));
      } else {
         $this->_form->addButtonBar('option',$this->_translator->getMessage('MATERIAL_CHANGE_BUTTON'),$this->_translator->getMessage('COMMON_CANCEL_BUTTON'),'','','',$this->_translator->getMessage('MATERIAL_VERSION_BUTTON'));
      }
   }

   /** loads the selected and given values to the form
    * this methods loads the selected and given values to the form from the material item or the form_post data
    */
   function _prepareValues () {
      $current_context = $this->_environment->getCurrentContextItem();
      if (isset($this->_form_post)) {
         $this->_values = $this->_form_post;
         $tmp_array = array();
         if (isset($this->_form_post['dayStart'])){
            $tmp_array['dayStart'] = $this->_form_post['dayStart'];
         }else{
            $tmp_array['dayStart'] = '';
         }
         if (isset($this->_form_post['timeStart'])){
            $tmp_array['timeStart'] = $this->_form_post['timeStart'];
         }else{
            $tmp_array['timeStart'] = '';
         }
         $this->_values['start_date_time'] = $tmp_array;

         if ($current_context->withWorkflow()){
            $this->_values['workflow_traffic_light'] = $this->_form_post['workflow_traffic_light'];

            $this->_values['workflow_resubmission_date'] = array('workflow_resubmission_date' => $this->_form_post['workflow_resubmission_date']);
            $this->_values['workflow_resubmission_who'] = $this->_form_post['workflow_resubmission_who'];
            $this->_values['workflow_resubmission_who_additional'] = $this->_form_post['workflow_resubmission_who_additional'];
            $this->_values['workflow_resubmission_traffic_light'] = $this->_form_post['workflow_resubmission_traffic_light'];

            $this->_values['workflow_validity_date'] = array('workflow_validity_date' => $this->_form_post['workflow_validity_date']);
            $this->_values['workflow_validity_who'] = $this->_form_post['workflow_validity_who'];
            $this->_values['workflow_validity_who_additional'] = $this->_form_post['workflow_validity_who_additional'];
            $this->_values['workflow_validity_traffic_light'] = $this->_form_post['workflow_validity_traffic_light'];
         }

      }elseif (isset($this->_item)) {
         $this->_values['modification_date'] = $this->_item->getModificationDate();
         $this->_values['iid'] = $this->_item->getItemID();
         $this->_values['vid'] = $this->_item->getVersionID();
         $this->_values['publishing_date'] = $this->_item->getPublishingDate();
         $this->_values['title'] = $this->_item->getTitle();
         $this->_values['description'] = $this->_item->getDescription();

         $this->_values['external_viewer'] = $this->_item->issetExternalViewerStatus();
         $this->_values['external_viewer_accounts'] = $this->_item->getExternalViewerString();
         // file
         $file_array = array();
         $file_list = $this->_item->getFileList();
         if ($file_list->getCount() > 0) {
            $file_item = $file_list->getFirst();
            while ($file_item) {
               $file_array[] = $file_item->getFileID();
               $file_item = $file_list->getNext();
            }
         }
         if (isset($this->_form_post['filelist'])) {
            $this->_values['filelist'] = $this->_form_post['filelist'];
         } else {
            $this->_values['filelist'] = $file_array;
         }

         if ($current_context->withActivatingContent()){
            if ($this->_item->isPrivateEditing()){
               $this->_values['private_editing'] = 1;
            }else{
               $this->_values['private_editing'] = $this->_item->isPrivateEditing();
            }
         }else{
            $this->_values['public'] = $this->_item->isPublic();
         }

         if ($current_context->withWorkflow()){
            $this->_values['workflow_traffic_light'] = $this->_item->getWorkflowTrafficLight();
            $this->_values['workflow_resubmission'] = $this->_item->getWorkflowResubmission();
            if($this->_item->getWorkflowResubmissionDate() != '' and $this->_item->getWorkflowResubmissionDate() != '0000-00-00 00:00:00'){
               $this->_values['workflow_resubmission_date']['workflow_resubmission_date'] = getDateInLang($this->_item->getWorkflowResubmissionDate());
            } else {
               $this->_values['workflow_resubmission_date']['workflow_resubmission_date'] = '';
            }
            $this->_values['workflow_resubmission_who'] = $this->_item->getWorkflowResubmissionWho();
            $this->_values['workflow_resubmission_who_additional'] = $this->_item->getWorkflowResubmissionWhoAdditional();
            $this->_values['workflow_resubmission_traffic_light'] = $this->_item->getWorkflowResubmissionTrafficLight();

            $this->_values['workflow_validity'] = $this->_item->getWorkflowValidity();
            if($this->_item->getWorkflowValidityDate() != '' and $this->_item->getWorkflowValidityDate() != '0000-00-00 00:00:00'){
               $this->_values['workflow_validity_date']['workflow_validity_date'] = getDateInLang($this->_item->getWorkflowValidityDate());
            } else {
               $this->_values['workflow_validity_date']['workflow_validity_date'] = '';
            }
            $this->_values['workflow_validity_who'] = $this->_item->getWorkflowValidityWho();
            $this->_values['workflow_validity_who_additional'] = $this->_item->getWorkflowValidityWhoAdditional();
            $this->_values['workflow_validity_traffic_light'] = $this->_item->getWorkflowValidityTrafficLight();
         }

         // rubric connections
         $this->_setValuesForRubricConnections();

         $this->_values['author'] = $this->_item->getAuthor(); // no encode here - encode in form-views
         $this->_values['bib_kind'] = $this->_item->getBibKind();
         $this->_values['publisher'] = $this->_item->getPublisher();
         $this->_values['address'] = $this->_item->getAddress();
         $this->_values['edition'] = $this->_item->getEdition();
         $this->_values['series'] = $this->_item->getSeries();
         $this->_values['volume'] = $this->_item->getVolume();
         $this->_values['isbn'] = $this->_item->getISBN();
         $this->_values['issn'] = $this->_item->getISSN();
         $this->_values['editor'] = $this->_item->getEditor();
         $this->_values['booktitle'] = $this->_item->getBooktitle();
         $this->_values['pages'] = $this->_item->getPages();
         $this->_values['journal'] = $this->_item->getJournal();
         $this->_values['issue'] = $this->_item->getIssue();
         $this->_values['thesis_kind'] = $this->_item->getThesisKind();
         $this->_values['university'] = $this->_item->getUniversity();
         $this->_values['faculty'] = $this->_item->getFaculty();
         $this->_values['common'] = $this->_item->getBibliographicValues();
         $this->_values['url'] = $this->_item->getURL();
         $this->_values['url_date'] = $this->_item->getURLDate();

         /** Start Dokumentenverwaltung **/
         $this->_values['document_editor'] = $this->_item->getDocumentEditor();
         $this->_values['document_maintainer'] = $this->_item->getDocumentMaintainer();
         $this->_values['document_release_number'] = $this->_item->getDocumentReleaseNumber();
         $this->_values['document_release_date'] = $this->_item->getDocumentReleaseDate();
      	 /** Ende Dokumentenverwaltung **/

         if ( empty($this->_values['bib_kind']) and !empty($this->_values['common']) ) {
            $this->_values['bib_kind'] ='common';
         }
         $this->_values['hide'] = $this->_item->isNotActivated()?'1':'0';
         if ($this->_item->isNotActivated()){
            $activating_date = $this->_item->getActivatingDate();
            if (!strstr($activating_date,'9999-00-00')){
               $array = array();
               $array['dayStart'] = getDateInLang($activating_date);
               $array['timeStart'] = getTimeInLang($activating_date);
               $this->_values['start_date_time'] = $array;
            }
         }

      } else {
         if ($current_context->withActivatingContent()){
            if ( !isset($this->_values['private_editing']) ) {
               $this->_values['private_editing'] = ($this->_environment->inProjectRoom() OR $this->_environment->inGroupRoom())?'0':'1'; //In projectrooms everybody can edit the item by default, else default is creator only
            }
         }else{
            if ( !isset($this->_values['public']) ) {
               $this->_values['public'] = ($this->_environment->inProjectRoom() OR $this->_environment->inGroupRoom())?'1':'0'; //In projectrooms everybody can edit the item by default, else default is creator only
            }
         }
         if ($current_context->withWorkflow()){
            $this->_values['workflow_traffic_light'] = $current_context->getWorkflowTrafficLightDefault();
            $this->_values['workflow_resubmission'] = false;
            $this->_values['workflow_resubmission_date']['workflow_resubmission_date'] = '';
            $this->_values['workflow_resubmission_who'] = 'creator';
            $this->_values['workflow_resubmission_traffic_light'] = '3_none';
            $this->_values['workflow_validity'] = false;
            $this->_values['workflow_validity_date']['workflow_validity_date'] = '';
            $this->_values['workflow_validity_who'] = 'creator';
            $this->_values['workflow_validity_traffic_light'] = '3_none';
         }
      }
   }

   /** specific check the values of the form
    * this methods check the entered values
    *
    * @author CommSy Development Group
    */
   function _checkValues () {
      $current_context = $this->_environment->getCurrentContextItem();

      if ( $current_context->isTagMandatory() ){
         $session = $this->_environment->getSessionItem();
         $tag_ids = $session->getValue('cid'.$this->_environment->getCurrentContextID().'_'.$this->_environment->getCurrentModule().'_tag_ids');
         if (count($tag_ids) == 0){
            $this->_error_array[] = $this->_translator->getMessage('COMMON_ERROR_TAG_ENTRY',$this->_translator->getMessage('MATERIAL_TAGS'));
         }
      }
      if ( $current_context->isBuzzwordMandatory() ){
         $session = $this->_environment->getSessionItem();
         $buzzword_ids = $session->getValue('cid'.$this->_environment->getCurrentContextID().'_'.$this->_environment->getCurrentModule().'_buzzword_ids');
         if (count($buzzword_ids) == 0){
            $this->_error_array[] = $this->_translator->getMessage('COMMON_ERROR_BUZZWORD_ENTRY',$this->_translator->getMessage('MATERIAL_BUZZWORDS'));
         }
      }

      if ( isset($this->_form_post['external_viewer']) and !empty($this->_form_post['external_viewer']) and !isset($this->_form_post['external_viewer_accounts'])){
         $this->_error_array[] = $this->_translator->getMessage('COMMON_ERROR_EXTERNAL_VIEWER_ACCOUNT_MISSED');
         $this->_form->setFailure('external_viewer_accounts','');
      }
      if ( isset($this->_form_post['external_viewer']) and isset($this->_form_post['external_viewer_accounts'])){
          $user_id_array = explode(' ',$this->_form_post['external_viewer_accounts']);
          $user_manager = $this->_environment->getUserManager();
          foreach($user_id_array as $user_id){
             $user_manager->setUserIDLimit($user_id);
             $user_manager->setContextLimit($this->_environment->getCurrentPortalID());
             $user_manager->select();
             $user_list = $user_manager->get();
             $user_item = $user_list->getFirst();
             if (!is_object($user_item)){
                $this->_error_array[] = $this->_translator->getMessage('COMMON_ERROR_EXTERNAL_VIEWER_ACCOUNT_NOT_EXISTS',$user_id);
                $this->_form->setFailure('external_viewer_accounts','');
             }
          }
      }
      if ($current_context->withActivatingContent() and !empty($this->_form_post['dayStart']) and !empty($this->_form_post['hide'])){
         include_once('functions/date_functions.php');
         if ( !isDatetimeCorrect($this->_environment->getSelectedLanguage(),$this->_form_post['dayStart'],$this->_form_post['timeStart']) ) {
            $this->_error_array[] = $this->_translator->getMessage('DATES_DATE_NOT_VALID');
            $this->_form->setFailure('start_date_time','');
         }
      }
      if ( !empty($this->_form_post['workflow_resubmission']) and empty($this->_form_post['workflow_resubmission_date'])){
         $this->_error_array[] = $this->_translator->getMessage('COMMON_ERROR_WORKFLOW_RESUBMISSION_DATE_MISSED');
         $this->_form->setFailure('workflow_resubmission_date','');
      }
      if ( !empty($this->_form_post['workflow_validity']) and empty($this->_form_post['workflow_validity_date'])){
         $this->_error_array[] = $this->_translator->getMessage('COMMON_ERROR_WORKFLOW_VALIDITY_DATE_MISSED');
         $this->_form->setFailure('workflow_validity_date','');
      }
   }

   function getInfoForHeaderAsHTML () {
   	$ckEditor_file_array = array();
      if (!empty($this->_session_file_array)) {
         foreach ( $this->_session_file_array as $file ) {
            $temp_array['text'] = $file['name'];
            $temp_array['value'] = $file['file_id'];
            $ckEditor_file_array[] = $temp_array;
         }
      } elseif (isset($this->_item)) {
         $file_list = $this->_item->getFileList();
         if ($file_list->getCount() > 0) {
            $file_item = $file_list->getFirst();
            while ($file_item) {
               $temp_array['text'] = $file_item->getDisplayname();
               $temp_array['value'] = $file_item->getFileID();
               $ckEditor_file_array[] = $temp_array;
               $file_item = $file_list->getNext();
            }
         }
      }

   	$retour  = ''.LF;
   	$retour  .= 'var ckeditor_show_icons = true;'.LF;
      $retour  .= 'var ckeditor_commsy_files = new Array(';
      $counter = 0;
      foreach($ckEditor_file_array as $temp_file){
      	$retour  .= '"'.$temp_file['text'].'"';
      	if($counter < sizeof($ckEditor_file_array)-1){
      		$retour  .= ',';
      	}
      	$counter++;
      }
      $retour  .= ');'.LF;

     //------------------------------------------------------------------------------------
     //------------------------------------------------------------------------------------
     //------------------------------------ ACHTUNG! --------------------------------------
     //------------------- Nicht einkommentieren, erst überarbeiten! ----------------------
     //------------------- Aktuell viel zu viele Datenbank-Anfragen  ----------------------
     //------------------------------------------------------------------------------------
     //------------------------------------------------------------------------------------
     //---
     //--- $ckEditor_link_array = array();
     //--- $item_manager = $this->_environment->getItemManager();
     //--- $item_manager->resetLimits();
     //--- $item_manager->setContextLimit($this->_environment->getCurrentContextID());
     //--- $item_manager->setIntervalLimit(50);
     //--- $item_manager->select();
     //--- $item_list = $item_manager->get();
     //--- $item_item = $item_list->getFirst();
     //--- while($item_item){
     //--- 	#if($item_item->getItemType() == 'material'
     //--- 	#   or $item_item->getItemType() == 'announcement'
     //--- 	#   or $item_item->getItemType() == 'date'
     //--- 	#   or $item_item->getItemType() == 'discussion'
     //--- 	#   or $item_item->getItemType() == 'todo'
     //--- 	#   or $item_item->getItemType() == 'topic'
     //--- 	#   or $item_item->getItemType() == 'group'){
	  //---       $temp_manager = $this->_environment->getManager($item_item->getItemType());
	  //---       //$temp_manager->setIDArrayLimit(array($item_item->getItemID()));
	  //---       //$temp_manager->select();
	  //---       //$temp_list = $temp_manager->get();
	  //---       //$temp_item = $temp_list->getFirst();
	  //---       $temp_item = $temp_manager->getItem($item_item->getItemID());
	  //---       if ( !empty($temp_item) ) {
	  //---	      	if($temp_item->getItemType() != 'user'){
	  //---	      		$text = $temp_item->getTitle();
	  //---	      	} else {
	  //---	      		$text = $temp_item->getFullname();
	  //---	      	}
	  //---	         $text = str_replace("'", "\'",$text);
	  //---	         $text = str_replace('"', '\"',$text);
	  //---	         if(strlen($text) > 30){
	  //---	           $text = substr($text, 0, 27).'...';
	  //---	         }
	  //---	         $ckEditor_link_array[] = array($temp_item->getItemID(), $text, $temp_item->getItemType());
     //---       }
     //--- 	#}
     //--- 	$item_item = $item_list->getNext();
     //--- }
     //---
     //--- $retour  .= 'var ckeditor_commsy_links = new Array(';
     //--- $counter = 0;
     //--- foreach($ckEditor_link_array as $temp_array){
     //---    $retour  .= 'new Array("'.$temp_array[0].'","'.$temp_array[1].'","'.$temp_array[2].'")';
     //---    if($counter < sizeof($ckEditor_link_array)-1){
     //---       $retour  .= ',';
     //---    }
     //---    $counter++;
     //--- }
     //--- $retour  .= ');'.LF;
     //---
     //------------------------------------------------------------------------------------
     //------------------------------------------------------------------------------------
     //------------------------------------ ACHTUNG! --------------------------------------
     //------------------------------------------------------------------------------------
     //------------------------------------------------------------------------------------

   	return $retour;
   }
}
?>