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

include_once('classes/cs_rubric_form.php');

/** class for commsy forms
 * this class implements an interface for the creation of forms in the commsy style
 */
class cs_configuration_extra_form extends cs_rubric_form {

  /**
   * string - containing the headline of the form
   */
   var $_headline = NULL;

  /**
   * array - containing the extras to choose
   */
   var $_array_extra = NULL;

  /** constructor
    * the only available constructor
    *
    * @param object environment the environment object
    */
   function cs_configuration_extra_form ($environment) {
      $this->cs_rubric_form($environment);
   }

   /** init data for form, INTERNAL
    * this methods init the data for the form
    */
   function _initForm () {

      // headline
      $this->_headline = getMessage('CONFIGURATION_EXTRA_FORM_HEADLINE');

      // extra option choice
      $this->_array_extra[0]['text']  = '*'.getMessage('CONFIGURATION_EXTRA_CHOOSE_TEXT');
      $this->_array_extra[0]['value'] = -1;

      // extra options
      $this->_array_extra[1]['text']  = '----------------------';
      $this->_array_extra[1]['value'] = 'disabled';
      $this->_array_extra[5]['text']  = getMessage('CONFIGURATION_EXTRA_MATERIALIMPORT');
      $this->_array_extra[5]['value'] = 'CONFIGURATION_EXTRA_MATERIALIMPORT';
      $this->_array_extra[6]['text']  = getMessage('CONFIGURATION_EXTRA_ACTIVATING_CONTENT');
      $this->_array_extra[6]['value'] = 'CONFIGURATION_EXTRA_ACTIVATING_CONTENT';
      $this->_array_extra[7]['text']  = getMessage('CONFIGURATION_EXTRA_GROUPROOM');
      $this->_array_extra[7]['value'] = 'CONFIGURATION_EXTRA_GROUPROOM';
      $this->_array_extra[8]['text']  = getMessage('CONFIGURATION_EXTRA_LOGARCHIVE');
      $this->_array_extra[8]['value'] = 'CONFIGURATION_EXTRA_LOGARCHIVE';
      $this->_array_extra[20]['text']  = '----------------------';
      $this->_array_extra[20]['value'] = 'disabled';

      global $c_etchat_enable;
      if ( isset($c_etchat_enable) and  $c_etchat_enable ) {
         $this->_array_extra[21]['text']  = getMessage('CHAT_CONFIGURATION_EXTRA_CHAT');
         $this->_array_extra[21]['value'] = 'CHAT_CONFIGURATION_EXTRA_CHAT';
      }

      $this->_array_extra[22]['text']  = getMessage('HOMEPAGE_CONFIGURATION_EXTRA_HOMEPAGE');
      $this->_array_extra[22]['value'] = 'HOMEPAGE_CONFIGURATION_EXTRA_HOMEPAGE';
      #$this->_array_extra[23]['text']  = getMessage('CONFIGURATION_EXTRA_PDA');
      #$this->_array_extra[23]['value'] = 'CONFIGURATION_EXTRA_PDA';
      global $c_pmwiki;
      if ( isset($c_pmwiki) and  $c_pmwiki ) {
         $this->_array_extra[23]['text']  = getMessage('CONFIGURATION_EXTRA_WIKI');
         $this->_array_extra[23]['value'] = 'CONFIGURATION_EXTRA_WIKI';
      }

      global $c_plugin_array;
      if (isset($c_plugin_array) and !empty($c_plugin_array)) {
         $this->_array_extra[50]['text']  = '----------------------';
         $this->_array_extra[50]['value'] = 'disabled';
         foreach ($c_plugin_array as $plugin) {
            $plugin_class = $this->_environment->getPluginClass($c_plugin_array['HTML']);
            if (method_exists($plugin_class,'getArrayForExtraConfiguration')) {
               $temp_array = $plugin_class->getArrayForExtraConfiguration();
               if (isset($temp_array)) {
                  $this->_array_extra[] = $temp_array;
               }
            }
         }
      }
   }

   /** create the form, INTERNAL
    * this methods creates the form with the form definitions
    */
   function _createForm () {
      $translator = $this->_environment->getTranslationObject();
      if (isset($this->_form_post['extra']) and $this->_form_post['extra'] != -1) {
         $disabled = false;
      } else {
         $disabled = true;
      }

      $this->setHeadline($this->_headline);

      $this->_form->addSelect( 'extra',
                               $this->_array_extra,
                               '',
                               $translator->getMessage('CONFIGURATION_EXTRA_FORM_CHOOSE_EXTRA'),
                               $translator->getMessage('CONFIGURATION_EXTRA_FORM_CHOOSE_EXTRA_DESC'),
                               '',
                               '',
                               '',
                               true,
                               $translator->getMessage('COMMON_CHOOSE_BUTTON'),
                               'option');

      // description text
      $this->_form->addText('description',$translator->getMessage('COMMON_DESCRIPTION'),'');
      $this->_form->addHidden('description_hidden','');

      // generate checkboxes for rooms
      $server_item = $this->_environment->getServerItem();
      $portal_list = $server_item->getPortalList();
      unset($server_item);
      if ( !$portal_list->isEmpty() ) {
         $portal = $portal_list->getFirst();
         while ($portal) {
            $this->_form->addSubHeadline('headline',$portal->getTitle());
            $this->_form->addCheckbox('ROOM_'.$portal->getItemID(),$portal->getItemID(),'','',$portal->getTitle().' ('.$translator->getMessage('ROOM_TYPE_PORTAL').')','','',$disabled);
            $room_list = $portal->getRoomList();
            if ( !$portal_list->isEmpty() ) {
               $room = $room_list->getFirst();
               while ($room) {
                  $type = '';
                  if ( $room->isProjectRoom() ) {
                     $type = ' ('.$translator->getMessage('ROOM_TYPE_PROJECT').')';
                  } elseif ( $room->isCommunityRoom() ) {
                     $type = ' ('.$translator->getMessage('ROOM_TYPE_COMMUNITY').')';
                  }
                  $this->_form->combine();
                  $this->_form->addCheckbox('ROOM_'.$room->getItemID(),$room->getItemID(),'','',$room->getTitle().$type,'','',$disabled);
                  unset($type);
                  unset($room);
                  $room = $room_list->getNext();
               }
               unset($room_list);
            }
            unset($portal);
            $portal = $portal_list->getNext();
         }
         unset($portal_list);
      }

      // buttons
      $this->_form->addButtonBar('option',$translator->getMessage('PREFERENCES_SAVE_BUTTON'),'','','','','',$disabled);
   }

   /** loads the selected and given values to the form
    * this methods loads the selected and given values to the form from the material item or the form_post data
    */
   function _prepareValues () {
      $this->_values = array();
      if ( !empty($this->_form_post) ) {
         $this->_values = $this->_form_post;
         if ( strlen($this->_values['extra']) == 2 and $this->_values['extra'] != -1) {
            $this->_values['extra'] = -1;
         }
         if ( !empty($this->_form_post['description_hidden']) ) {
            $this->_values['description'] = $this->_values['description_hidden'];
         }
      } else {
         $this->_values['extra'] = -1;
      }
   }

   /** specific check the values of the form
    * this methods check the entered values
    */
   function _checkValues () {
      // check choosen mail text
      if (strlen($this->_form_post['extra']) == 2 and $this->_form_post['extra'] != -1) {
         $this->_error_array[] = getMessage('CONFIGURATION_EXTRA_CHOICE_ERROR');
         $this->_form->setFailure('extra','');
      }
      if ( strlen($this->_form_post['extra']) == 2 and
           $this->_form_post['extra'] == -1 and
           isset($this->_form_post['option']) and
           isOption($this->_form_post['option'], getMessage('COMMON_SAVE_BUTTON'))
         ) {
         $this->_error_array[] = getMessage('CONFIGURATION_EXTRA_CHOICE_ERROR');
         $this->_form->setFailure('extra','');
      }
   }

   /** reset rubric form
    *  reset this rubric form (item, values, postvars and the form [elements])
    */
   function reset () {
      parent::reset();
      unset($this->_array_extra);
   }
}
?>