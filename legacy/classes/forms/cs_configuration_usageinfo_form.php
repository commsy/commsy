<?PHP
// $Id$
//
// Release $Name$
//
// Copyright (c)2002-2007 Dirk Blössl, Matthias Finck, Dirk Fust, Franz Grünig,
// Oliver Hankel, Iver Jackewitz, Michael Janneck, Martti Jeenicke,
// Detlev Krause, Irina L. Marinescu, Frithjof Meyer, Timo Nolte, Bernd Pape,
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
class cs_configuration_usageinfo_form extends cs_rubric_form {

  /**
   * string - containing the headline of the form
   */
   var $_headline = NULL;

  /**
   * array - containing the mail texts to choose
   */
   var $_array_mail_text = NULL;

  /** constructor
    * the only available constructor
    *
    * @param array params array of parameter
    */
   function __construct($params) {
      cs_rubric_form::__construct($params);
   }

   /** init data for form, INTERNAL
    * this methods init the data for the form, for example groups
    */
   function _initForm () {

          $room = $this->_environment->getCurrentContextItem();
          $default_rubrics = $room->getAvailableRubrics();
          $rubric_array = array();
          $temp_array['text']  = $this->_translator->getMessage('HOME_INDEX');
          $temp_array['value'] = 'home';
          $this->_array_info_text[0] = $temp_array;
          $i = 1;
          foreach ($default_rubrics as $rubric) {
                 $temp_array = array();
                  if ($this->_environment->inPrivateRoom() and $rubric =='user' ){
                      $temp_array['text'] = $this->_translator->getMessage('COMMON_MY_USER_DESCRIPTION');
                  } else {
                     switch ( mb_strtoupper($rubric, 'UTF-8') ){
                        case 'ANNOUNCEMENT':
                           $temp_array['text'] = $this->_translator->getMessage('ANNOUNCEMENT_INDEX');
                           break;
                        case 'DATE':
                           $temp_array['text'] = $this->_translator->getMessage('DATE_INDEX');
                           break;
                        case 'DISCUSSION':
                           $temp_array['text'] = $this->_translator->getMessage('DISCUSSION_INDEX');
                           break;
                        case 'INSTITUTION':
                           $temp_array['text'] = $this->_translator->getMessage('INSTITUTION_INDEX');
                           break;
                        case 'GROUP':
                           $temp_array['text'] = $this->_translator->getMessage('GROUP_INDEX');
                           break;
                        case 'MYROOM':
                           $temp_array['text'] = $this->_translator->getMessage('MYROOM_INDEX');
                           break;
                        case 'MATERIAL':
                           $temp_array['text'] = $this->_translator->getMessage('MATERIAL_INDEX');
                           break;
                        case 'PROJECT':
                           $temp_array['text'] = $this->_translator->getMessage('PROJECT_INDEX');
                           break;
                        case 'TODO':
                           $temp_array['text'] = $this->_translator->getMessage('TODO_INDEX');
                           break;
                        case 'TOPIC':
                           $temp_array['text'] = $this->_translator->getMessage('TOPIC_INDEX');
                           break;
                        case 'USER':
                           $temp_array['text'] = $this->_translator->getMessage('USER_INDEX');
                           break;
                        default:
                           $temp_array['text'] = $this->_translator->getMessage('COMMON_MESSAGETAG_ERROR'.' cs_configuration_usageinfo_form(113) ');
                           break;
                     }
                  }
                 $temp_array['value'] = $rubric;
                 $this->_array_info_text[$i] = $temp_array;
                 $i++;
                 unset($temp_array);
          }

    }

   /** create the form, INTERNAL
    * this methods creates the form with the form definitions
    */
   function _createForm () {
      $translator = $this->_environment->getTranslationObject();
      $this->setHeadline($this->_headline);
      $this->_form->addSelect( 'info_text',
                               $this->_array_info_text,
                               '',
                               $this->_translator->getMessage('CONFIGURATION_USAGEINFO_FORM_CHOOSE_TEXT'),
                               '',
                               '',
                               '',
                               '',
                               true,
                               $this->_translator->getMessage('COMMON_CHOOSE_BUTTON'),
                               'option',
                                                           '',
                                                           '',
                                                           '12',true);

                                                           $context_item = $this->_environment->getCurrentContextItem();

         $this->_form->addTextfield('title','',$this->_translator->getMessage('COMMON_TITLE'),'',50,'22','');
     $desc = '<img src="images/configuration_usageinfo.gif" style=" border:1px solid black; vertical-align: middle;"/>'.BRLF;
     if (isset($_POST['info_text']) and $_POST['info_text'] !='home' ){
             $this->_form->addTextArea('text','',$translator->getMessage('COMMON_USAGE_INFO_BODY'),$desc,'60','','','','',false);
#         $this->_form->addCheckbox('show','value',false,$this->_translator->getMessage('CONFIGURATION_USAGEINFO_SHOW_USAGE_INFO'),$translator->getMessage('CONFIGURATION_USAGEINFO_SHOW_USAGE_INFO_DESC'),'');
             $this->_form->addEmptyLine();
         $this->_form->addTextArea('text_form','',$translator->getMessage('COMMON_USAGE_INFO_FORM_BODY'),$desc,'60','','','','',false);
#         $this->_form->addCheckbox('show_form','value',false,$this->_translator->getMessage('CONFIGURATION_USAGEINFO_SHOW_USAGE_INFO'),$translator->getMessage('CONFIGURATION_USAGEINFO_SHOW_USAGE_INFO_DESC'),'');
     }else{
             $this->_form->addTextArea('text','',$translator->getMessage('COMMON_CONTENT'),$desc,'60','','','','',false);
#         $this->_form->addCheckbox('show','value',false,$this->_translator->getMessage('CONFIGURATION_USAGEINFO_SHOW_USAGE_INFO'),$translator->getMessage('CONFIGURATION_USAGEINFO_SHOW_USAGE_INFO_DESC'),'');
     }
#            $this->_form->addCheckbox('show_global','value',false,$this->_translator->getMessage('CONFIGURATION_USAGEINFO_SHOW_USAGE_INFO_GLOBAL'),$translator->getMessage('CONFIGURATION_USAGEINFO_SHOW_USAGE_INFO_DESC_GLOBAL'),'');
      // buttons
        $this->_form->addButtonBar('option',$translator->getMessage('PREFERENCES_SAVE_BUTTON'),'');
   }

   /** loads the selected and given values to the form
    * this methods loads the selected and given values to the form from the material item or the form_post data
    */
   function _prepareValues () {
      $room = $this->_environment->getCurrentContextItem();
      $this->_values = array();
#      $this->_values['show'] = false;

      if ( !empty($this->_form_post) ) {
         $this->_values = $this->_form_post;
         $data = $room->_getExtra('USAGE_INFO_FORM');
#         if ( !empty($data) and in_array($this->_values['info_text'].'_no',$data) ) {
#            $this->_values['show_form'] = false;
#         } else {
#            $this->_values['show_form'] = true;
#         }
      } else {
         $this->_values['info_text'] = 'home';
         $array = $room->_getExtra('USAGE_INFO');
#        if ( !empty($array) and in_array('home_no',$room->_getExtra('USAGE_INFO')) ) {
#            $this->_values['show'] = false;
#         } else {
#            $this->_values['show'] = true;
#         }
         $this->_values['title'] = $room->getUsageInfoHeaderForRubric('home');
#         $this->_values['text'] = $room->getUsageInfoTextForRubric('home'); // war das ein Fehler???
         $this->_values['text'] = $room->getUsageInfoTextForRubricInForm('home');
      }
#      if ( ($room->_getExtra('USAGE_INFO_GLOBAL') == 'false') or (!$room->_issetExtra('USAGE_INFO_GLOBAL'))) {
#         $this->_values['show_global'] = false;
#      } else {
#         $this->_values['show_global'] = true;
#      }
   }
}
?>