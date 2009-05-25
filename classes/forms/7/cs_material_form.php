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


  /** constructor: cs_material_form
    * the only available constructor
    *
    * @param object environment the environment object
    *
    * @author CommSy Development Group
    */
   function cs_material_form($params) {
      $this->cs_rubric_form($params);
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
         $this->_headline = getMessage('MATERIAL_EDIT');
      } elseif (!empty($this->_form_post)) {
         if (!empty($this->_form_post['iid'])) {
            $this->_headline = getMessage('MATERIAL_EDIT');
         } else {
            $this->_headline = getMessage('MATERIAL_ENTER_NEW');
         }
      } else {
         $this->_headline = getMessage('MATERIAL_ENTER_NEW');
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
      $temp_array['text']  = getMessage('RUBRIC_PUBLIC_YES');
      $temp_array['value'] = 1;
      $public_array[] = $temp_array;
      $temp_array['text']  = getMessage('RUBRIC_PUBLIC_NO', $fullname);
      $temp_array['value'] = 0;
      $public_array[] = $temp_array;
      $this->_public_array = $public_array;
   }

   /** create the form, INTERNAL
    * this methods creates the form with the form definitions
    */
   function _createForm () {
      // material
      $this->_form->addHidden('iid','');
      $this->_form->addHidden('vid','');
      $this->_form->addHidden('modification_date','');
      $this->_form->addTitleField('title','',getMessage('COMMON_TITLE'),getMessage('COMMON_TITLE_DESC'),200,58,true);
#      if ( $this->_bib_kind=='common' ) {
#      }
#      elseif ( $this->_bib_kind=='none' ) {
#         $this->_form->addTextField('author','',getMessage('MATERIAL_AUTHORS'),getMessage('MATERIAL_AUTHORS_DESC'),200,35,false);
#      }
      $context_item = $this->_environment->getCurrentContextItem();
      if (isset($this->_item)) {
         $iid = $this->_item->getItemID();
      }else{
         $iid ='NEW';
      }


         $bib_kinds = array();
         $bib_kinds[] = array('text'  => '* '.getMessage('MATERIAL_BIB_NOTHING'),
                              'value' => 'none');
         $bib_kinds[] = array('text'  => '* '.getMessage('MATERIAL_BIB_NONE'),
                              'value' => 'common');
         $bib_kinds[] = array('text'  => getMessage('MATERIAL_BIB_BOOK'),
                              'value' => 'book');
         $bib_kinds[] = array('text'  => getMessage('MATERIAL_BIB_COLLECTION'),
                              'value' => 'collection');
         $bib_kinds[] = array('text'  => getMessage('MATERIAL_BIB_INCOLLECTION'),
                              'value' => 'incollection');
         $bib_kinds[] = array('text'  => getMessage('MATERIAL_BIB_ARTICLE'),
                              'value' => 'article');
         $bib_kinds[] = array('text'  => getMessage('MATERIAL_BIB_CHAPTER'),
                              'value' => 'chapter');
         $bib_kinds[] = array('text'  => getMessage('MATERIAL_BIB_INPAPER'),
                              'value' => 'inpaper');
         $bib_kinds[] = array('text'  => getMessage('MATERIAL_BIB_THESIS'),
                              'value' => 'thesis');
         $bib_kinds[] = array('text'  => getMessage('MATERIAL_BIB_MANUSCRIPT'),
                              'value' => 'manuscript');
         $bib_kinds[] = array('text'  => getMessage('MATERIAL_BIB_WEBSITE'),
                              'value' => 'website');
         $this->_form->addSelect('bib_kind',$bib_kinds,'',getMessage('MATERIAL_BIBLIOGRAPHIC'),'', 1, false,false,true,getMessage('MATERIAL_BIB_KIND_BUTTON'),'option','','',18.3,true);
         switch ( $this->_bib_kind ) {
            case 'book':
               $this->_form->addTextField('author','',getMessage('MATERIAL_AUTHORS'),getMessage('MATERIAL_AUTHORS_DESC'),200,35,true);
               $this->_form->addTextField('publishing_date','',getMessage('MATERIAL_YEAR'),getMessage('MATERIAL_YEAR'),4,4,true);
               $this->_form->addTextField('publisher','',getMessage('MATERIAL_PUBLISHER'),getMessage('MATERIAL_PUBLISHER_DESC'),50,35,true);
               $this->_form->addTextField('address','',getMessage('MATERIAL_ADDRESS'),getMessage('MATERIAL_ADDRESS_DESC'),50,35,true);
               $this->_form->addTextField('edition','',getMessage('MATERIAL_EDITION'),getMessage('MATERIAL_EDITION_DESC'),3,3);
               $this->_form->addTextField('series','',getMessage('MATERIAL_SERIES'),getMessage('MATERIAL_SERIES_DESC'),20,35);
               $this->_form->addTextField('volume','',getMessage('MATERIAL_VOLUME'),getMessage('MATERIAL_VOLUME_DESC'),4,4);
               $this->_form->addTextField('isbn','',getMessage('MATERIAL_ISBN'),getMessage('MATERIAL_ISBN_DESC'),20,20);
            $this->_form->addTextField('url','',getMessage('MATERIAL_URL'),'',100,35);
            $this->_form->addTextField('url_date','',getMessage('MATERIAL_URL_DATE'),'',10,20);
               break;
            case 'collection':
               $this->_form->addTextField('author','',getMessage('MATERIAL_EDITOR'),getMessage('MATERIAL_EDITOR_DESC'),200,35,true);
               $this->_form->addTextField('publishing_date','',getMessage('MATERIAL_YEAR'),getMessage('MATERIAL_YEAR'),4,4,true);
               $this->_form->addTextField('publisher','',getMessage('MATERIAL_PUBLISHER'),getMessage('MATERIAL_PUBLISHER_DESC'),50,35,true);
               $this->_form->addTextField('address','',getMessage('MATERIAL_ADDRESS'),getMessage('MATERIAL_ADDRESS_DESC'),50,35,true);
               $this->_form->addTextField('edition','',getMessage('MATERIAL_EDITION'),getMessage('MATERIAL_EDITION_DESC'),3,3);
               $this->_form->addTextField('series','',getMessage('MATERIAL_SERIES'),getMessage('MATERIAL_SERIES_DESC'),20,35);
               $this->_form->addTextField('volume','',getMessage('MATERIAL_VOLUME'),getMessage('MATERIAL_VOLUME_DESC'),4,4);
               $this->_form->addTextField('isbn','',getMessage('MATERIAL_ISBN'),getMessage('MATERIAL_ISBN_DESC'),20,20);
            $this->_form->addTextField('url','',getMessage('MATERIAL_URL'),'',100,35);
            $this->_form->addTextField('url_date','',getMessage('MATERIAL_URL_DATE'),'',10,20);
               break;
            case 'incollection':
               $this->_form->addTextField('author','',getMessage('MATERIAL_AUTHORS'),getMessage('MATERIAL_AUTHORS_DESC'),200,35,true);
               $this->_form->addTextField('publishing_date','',getMessage('MATERIAL_YEAR'),getMessage('MATERIAL_YEAR'),4,4,true);
               $this->_form->addTextField('editor','',getMessage('MATERIAL_EDITOR'),getMessage('MATERIAL_EDITOR_DESC'),200,35,true);
               $this->_form->addTextField('booktitle','',getMessage('MATERIAL_BOOKTITLE'),getMessage('MATERIAL_BOOKTITLE_DESC'),200,35,true);
               $this->_form->addTextField('address','',getMessage('MATERIAL_ADDRESS'),getMessage('MATERIAL_ADDRESS_DESC'),200,35,true);
               $this->_form->addTextField('publisher','',getMessage('MATERIAL_PUBLISHER'),getMessage('MATERIAL_PUBLISHER_DESC'),200,35,true);
               $this->_form->addTextField('edition','',getMessage('MATERIAL_EDITION'),getMessage('MATERIAL_EDITION_DESC'),3,3);
               $this->_form->addTextField('series','',getMessage('MATERIAL_SERIES'),getMessage('MATERIAL_SERIES_DESC'),200,35);
               $this->_form->addTextField('volume','',getMessage('MATERIAL_VOLUME'),getMessage('MATERIAL_VOLUME_DESC'),4,4);
               $this->_form->addTextField('isbn','',getMessage('MATERIAL_ISBN'),getMessage('MATERIAL_ISBN_DESC'),20,20);
               $this->_form->addTextField('pages','',getMessage('MATERIAL_PAGES'),getMessage('MATERIAL_PAGES_DESC'),20,15,true);
            $this->_form->addTextField('url','',getMessage('MATERIAL_URL'),'',100,35);
            $this->_form->addTextField('url_date','',getMessage('MATERIAL_URL_DATE'),'',10,20);
               break;
            case 'article':
               $this->_form->addTextField('author','',getMessage('MATERIAL_AUTHORS'),getMessage('MATERIAL_AUTHORS_DESC'),200,35,true);
               $this->_form->addTextField('publishing_date','',getMessage('MATERIAL_YEAR'),getMessage('MATERIAL_YEAR'),4,4,true);
               $this->_form->addTextField('journal','',getMessage('MATERIAL_JOURNAL'),getMessage('MATERIAL_JOURNAL_DESC'),200,35,true);
               $this->_form->addTextField('volume','',getMessage('MATERIAL_VOLUME_J'),getMessage('MATERIAL_VOLUME_J_DESC'),4,4);
               $this->_form->addTextField('issue','',getMessage('MATERIAL_ISSUE'),getMessage('MATERIAL_ISSUE_DESC'),3,3);
               $this->_form->addTextField('pages','',getMessage('MATERIAL_PAGES'),getMessage('MATERIAL_PAGES_DESC'),20,15,true);
               $this->_form->addTextField('address','',getMessage('MATERIAL_ADDRESS'),getMessage('MATERIAL_ADDRESS_DESC'),200,35,false);
               $this->_form->addTextField('publisher','',getMessage('MATERIAL_PUBLISHER'),getMessage('MATERIAL_PUBLISHER_DESC'),200,35,false);
               $this->_form->addTextField('issn','',getMessage('MATERIAL_ISSN'),getMessage('MATERIAL_ISSN_DESC'),20,20);
            $this->_form->addTextField('url','',getMessage('MATERIAL_URL'),'',100,35);
            $this->_form->addTextField('url_date','',getMessage('MATERIAL_URL_DATE'),'',10,20);
               break;
            case 'chapter':
               $this->_form->addTextField('author','',getMessage('MATERIAL_EDITOR'),getMessage('MATERIAL_EDITOR_DESC'),200,35,true);
               $this->_form->addTextField('publishing_date','',getMessage('MATERIAL_YEAR'),getMessage('MATERIAL_YEAR'),4,4,true);
               $this->_form->addTextField('address','',getMessage('MATERIAL_ADDRESS'),getMessage('MATERIAL_ADDRESS_DESC'),50,35,true);
               $this->_form->addTextField('edition','',getMessage('MATERIAL_EDITION'),getMessage('MATERIAL_EDITION_DESC'),3,3);
               $this->_form->addTextField('series','',getMessage('MATERIAL_SERIES'),getMessage('MATERIAL_SERIES_DESC'),20,35);
               $this->_form->addTextField('volume','',getMessage('MATERIAL_VOLUME'),getMessage('MATERIAL_VOLUME_DESC'),4,4);
               $this->_form->addTextField('isbn','',getMessage('MATERIAL_ISBN'),getMessage('MATERIAL_ISBN_DESC'),20,20);
            $this->_form->addTextField('url','',getMessage('MATERIAL_URL'),'',100,35);
            $this->_form->addTextField('url_date','',getMessage('MATERIAL_URL_DATE'),'',10,20);
               break;
            case 'inpaper':
               $this->_form->addTextField('author','',getMessage('MATERIAL_AUTHORS'),getMessage('MATERIAL_AUTHORS_DESC'),200,35,true);
               $this->_form->addTextField('publishing_date','',getMessage('MATERIAL_YEAR'),getMessage('MATERIAL_YEAR'),4,5,true);
               $this->_form->addTextField('journal','',getMessage('MATERIAL_PAPER'),getMessage('MATERIAL_PAPER_DESC'),200,35,true);
               $this->_form->addTextField('issue','',getMessage('MATERIAL_ISSUE'),getMessage('MATERIAL_ISSUE_DESC'),3,3);
               $this->_form->addTextField('pages','',getMessage('MATERIAL_PAGES'),getMessage('MATERIAL_PAGES_DESC'),20,15,true);
               $this->_form->addTextField('address','',getMessage('MATERIAL_ADDRESS'),getMessage('MATERIAL_ADDRESS_DESC'),200,35,false);
               $this->_form->addTextField('publisher','',getMessage('MATERIAL_PUBLISHER'),getMessage('MATERIAL_PUBLISHER_DESC'),200,'',false);
            $this->_form->addTextField('url','',getMessage('MATERIAL_URL'),'',100,35);
            $this->_form->addTextField('url_date','',getMessage('MATERIAL_URL_DATE'),'',10,20);
               break;
            case 'thesis':
               $thesis_kinds = array();
               $thesis_kinds[] = array('text'  => getMessage('MATERIAL_THESIS_TERM'),
                                       'value' => 'term');
               $thesis_kinds[] = array('text'  => getMessage('MATERIAL_THESIS_BACHELOR'),
                                       'value' => 'bachelor');
               $thesis_kinds[] = array('text'  => getMessage('MATERIAL_THESIS_MASTER'),
                                       'value' => 'master');
               $thesis_kinds[] = array('text'  => getMessage('MATERIAL_THESIS_EXAM'),
                                       'value' => 'exam');
               $thesis_kinds[] = array('text'  => getMessage('MATERIAL_THESIS_DIPLOMA'),
                                       'value' => 'diploma');
               $thesis_kinds[] = array('text'  => getMessage('MATERIAL_THESIS_DISSERTATION'),
                                       'value' => 'dissertation');
               $thesis_kinds[] = array('text'  => getMessage('MATERIAL_THESIS_POSTDOC'),
                                       'value' => 'postdoc');
               $this->_form->addTextField('author','',getMessage('MATERIAL_AUTHORS'),getMessage('MATERIAL_AUTHORS_DESC'),200,35,true);
               $this->_form->addTextField('publishing_date','',getMessage('MATERIAL_YEAR'),getMessage('MATERIAL_YEAR'),4,5,true);
               $this->_form->addSelect('thesis_kind',$thesis_kinds,'',getMessage('MATERIAL_THESIS_KIND'),getMessage('MATERIAL_THESIS_KIND_DESC'), 1, false,true,false,'','','','',24.8);
               $this->_form->addTextField('address','',getMessage('MATERIAL_ADDRESS'),getMessage('MATERIAL_ADDRESS_DESC'),200,35,true);
               $this->_form->addTextField('university','',getMessage('MATERIAL_UNIVERSITY'),getMessage('MATERIAL_UNIVERSITY_DESC'),200,35,true);
               $this->_form->addTextField('faculty','',getMessage('MATERIAL_FACULTY'),getMessage('MATERIAL_FACULTY_DESC'),200,35,false);
            $this->_form->addTextField('url','',getMessage('MATERIAL_URL'),'',100,35);
            $this->_form->addTextField('url_date','',getMessage('MATERIAL_URL_DATE'),'',10,20);
               break;
            case 'manuscript':
               $this->_form->addTextField('author','',getMessage('MATERIAL_AUTHORS'),getMessage('MATERIAL_AUTHORS_DESC'),200,35,true);
               $this->_form->addTextField('publishing_date','',getMessage('MATERIAL_YEAR'),getMessage('MATERIAL_YEAR'),4,5,true);
               $this->_form->addTextField('address','',getMessage('MATERIAL_ADDRESS'),getMessage('MATERIAL_ADDRESS_DESC'),200,35,true);
            $this->_form->addTextField('url','',getMessage('MATERIAL_URL'),'',100,35);
            $this->_form->addTextField('url_date','',getMessage('MATERIAL_URL_DATE'),'',10,20);
               break;
         case 'website':
            $this->_form->addTextField('author','',getMessage('MATERIAL_AUTHORS'),getMessage('MATERIAL_AUTHORS_DESC'),200,35,true);
            $this->_form->addTextField('url','',getMessage('MATERIAL_URL'),'',100,35,true);
            $this->_form->addTextField('url_date','',getMessage('MATERIAL_URL_DATE'),'',10,20);
            break;
            case 'common':
                  $this->_form->addTextField('author','',getMessage('MATERIAL_AUTHORS'),getMessage('MATERIAL_AUTHORS_DESC'),200,35,false);
                  $this->_form->addTextField('publishing_date','',getMessage('MATERIAL_YEAR'),getMessage('MATERIAL_YEAR'),4,4,false);
                  $this->_form->addTextArea('common','',getMessage('MATERIAL_BIBLIOGRAPHIC'),getMessage('MATERIAL_BIBLIOGRAPHIC_DESC'),'',3,'virtual',false,false,true,1,true,false);
               break;
            case 'none':
            default:
               break;
         }

      $format_help_link = ahref_curl($this->_environment->getCurrentContextID(), 'help', 'context',
                  array('module'=>$this->_environment->getCurrentModule(),'function'=>$this->_environment->getCurrentFunction(),'context'=>'HELP_COMMON_FORMAT'),
                  getMessage('HELP_COMMON_FORMAT_TITLE'), '', '_help', '', '',
                  'onclick="window.open(href, target, \'toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=yes, resizable=yes, copyhistory=yes, width=600, height=400\');"');
      if ( $this->_bib_kind=='none' ) {
         $this->_form->addTextArea('description','',getMessage('MATERIAL_ABSTRACT'),getMessage('COMMON_CONTENT_DESC',$format_help_link),'',20);
      } else {
         $this->_form->addTextArea('description','',getMessage('MATERIAL_ABSTRACT'),getMessage('COMMON_CONTENT_DESC',$format_help_link),'',20);
      }
      // files
      $this->_form->addAnchor('fileupload');
      $val = ini_get('upload_max_filesize');
      $val = trim($val);
      $last = $val[mb_strlen($val)-1];
      switch($last) {
         case 'k':
         case 'K':
            $val = $val * 1024;
            break;
         case 'm':
         case 'M':
            $val = $val * 1048576;
            break;
      }
      $meg_val = round($val/1048576);
      if ( !empty($this->_file_array) ) {
         $this->_form->addCheckBoxGroup('filelist',$this->_file_array,'',getMessage('MATERIAL_FILES'),getMessage('MATERIAL_FILES_DESC', $meg_val),false,false);
         $this->_form->combine('vertical');
      }
      $this->_form->addHidden('MAX_FILE_SIZE', $val);
      $this->_form->addFilefield('upload', getMessage('MATERIAL_FILES'), getMessage('MATERIAL_UPLOAD_DESC',$meg_val), 12, false, getMessage('MATERIAL_UPLOADFILE_BUTTON'),'option',$this->_with_multi_upload);
      $this->_form->combine('vertical');
      if ($this->_with_multi_upload) {
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
         $this->_form->addButton('option',getMessage('MATERIAL_BUTTON_MULTI_UPLOAD_YES'),'','',$px.'px');
      }
      $this->_form->combine('vertical');
      $this->_form->addText('max_size',$val,getMessage('MATERIAL_MAX_FILE_SIZE',$meg_val));

      $current_context = $this->_environment->getCurrentContextItem();
      // world public
      if ($current_context->isOpenForGuests() and $this->_environment->inCommunityRoom() ) {
         if (isset($this->_item)) {
            $world_publish = $this->_item->getWorldPublic();
            if ( $world_publish == 0) {
               $this->_form->addCheckBox('world_public','1','',getMessage('MATERIAL_WORLD_PUBLISH'),getMessage('MATERIAL_WORLD_PUBLISH_CHANGE_STATUS_0'),getMessage('MATERIAL_WORLD_PUBLISH_DESC'), false);
            } elseif ( $world_publish == 1) {
               $this->_form->addCheckBox('world_public','1','',getMessage('MATERIAL_WORLD_PUBLISH'),getMessage('MATERIAL_WORLD_PUBLISH_CHANGE_STATUS_1'),getMessage('MATERIAL_WORLD_PUBLISH_DESC'), false);
            } else {
               $this->_form->addCheckBox('world_public','1','',getMessage('MATERIAL_WORLD_PUBLISH'),getMessage('MATERIAL_WORLD_PUBLISH_CHANGE_STATUS_2'),getMessage('MATERIAL_WORLD_PUBLISH_DESC'), false);
            }
         } else {
            $this->_form->addCheckBox('world_public','1','',getMessage('MATERIAL_WORLD_PUBLISH'),getMessage('MATERIAL_WORLD_PUBLISH_CHANGE_STATUS_0'),getMessage('MATERIAL_WORLD_PUBLISH_DESC'), false);
         }
      }

      // rubric connections
      $this->_setFormElementsForConnectedRubrics();

      if ( !$this->_environment->inPrivateRoom() ){

         if ($current_context->withActivatingContent()){
            $this->_form->addCheckbox('private_editing',1,'',getMessage('COMMON_RIGHTS'),$this->_public_array[1]['text'],getMessage('COMMON_RIGHTS_DESCRIPTION'),false,false,'','',true,false);
            $this->_form->combine();
            $this->_form->addCheckbox('hide',1,'',getMessage('COMMON_HIDE'),getMessage('COMMON_HIDE'),'');
            $this->_form->combine('horizontal');
            $this->_form->addDateTimeField('start_date_time','','dayStart','timeStart',9,4,getMessage('DATES_HIDING_DAY'),'('.getMessage('DATES_HIDING_DAY'),getMessage('DATES_HIDING_TIME'),getMessage('DATES_TIME_DAY_START_DESC'),FALSE,FALSE,100,100,true,'left','',FALSE);
            $this->_form->combine('horizontal');
            $this->_form->addText('hide_end2','',')');
         }else{
             // public radio-buttons
             if ( !isset($this->_item) ) {
                $this->_form->addRadioGroup('public',getMessage('RUBRIC_PUBLIC'),getMessage('RUBRIC_PUBLIC_DESC'),$this->_public_array);
             } else {
                $current_user = $this->_environment->getCurrentUser();
                $creator = $this->_item->getCreatorItem();
                if ($current_user->getItemID() == $creator->getItemID() or $current_user->isModerator()) {
                   $this->_form->addRadioGroup('public',getMessage('RUBRIC_PUBLIC'),getMessage('RUBRIC_PUBLIC_DESC'),$this->_public_array);
                } else {
                   $this->_form->addHidden('public','');
                }
             }
         }

      } else {
         $this->_form->addHidden('public','');
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
         $this->_form->addButtonBar('option',getMessage('MATERIAL_SAVE_BUTTON'),getMessage('COMMON_CANCEL_BUTTON'));
      } else {
         $this->_form->addButtonBar('option',getMessage('MATERIAL_CHANGE_BUTTON'),getMessage('COMMON_CANCEL_BUTTON'),'','','',getMessage('MATERIAL_VERSION_BUTTON'));
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
      }elseif (isset($this->_item)) {
         $this->_values['modification_date'] = $this->_item->getModificationDate();
         $this->_values['iid'] = $this->_item->getItemID();
         $this->_values['vid'] = $this->_item->getVersionID();
         $this->_values['publishing_date'] = $this->_item->getPublishingDate();
         $this->_values['title'] = $this->_item->getTitle();
         $this->_values['description'] = $this->_item->getDescription();

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
      }
   }

   /** specific check the values of the form
    * this methods check the entered values
    *
    * @author CommSy Development Group
    */
   function _checkValues () {
      $current_context = $this->_environment->getCurrentContextItem();
/*** Neue Schlagwörter und Tags***/
      if ( $current_context->isTagMandatory() ){
         $session = $this->_environment->getSessionItem();
         $tag_ids = $session->getValue('cid'.$this->_environment->getCurrentContextID().'_'.$this->_environment->getCurrentModule().'_tag_ids');
         if (count($tag_ids) == 0){
            $this->_error_array[] = getMessage('COMMON_ERROR_TAG_ENTRY',getMessage('MATERIAL_TAGS'));
         }
      }
      if ( $current_context->isBuzzwordMandatory() ){
         $session = $this->_environment->getSessionItem();
         $buzzword_ids = $session->getValue('cid'.$this->_environment->getCurrentContextID().'_'.$this->_environment->getCurrentModule().'_buzzword_ids');
         if (count($buzzword_ids) == 0){
            $this->_error_array[] = getMessage('COMMON_ERROR_BUZZWORD_ENTRY',getMessage('MATERIAL_BUZZWORDS'));
         }
      }
/*** Neue Schlagwörter und Tags***/
   }

}
?>