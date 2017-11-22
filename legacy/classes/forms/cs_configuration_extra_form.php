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

$this->includeClass(RUBRIC_FORM);

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

  /**
   * array - containing the portals to choose
   */
   var $_array_portal = NULL;

   var $_show_checkboxes = false;

  /** constructor
    * the only available constructor
    *
    * @param array params array of parameter
    */
   function __construct($params) {
      cs_rubric_form::__construct($params);
   }

   /** init data for form, INTERNAL
    * this methods init the data for the form
    */
   function _initForm () {
      // headline
      $this->_headline = $this->_translator->getMessage('CONFIGURATION_EXTRA_FORM_HEADLINE');

      // portal option choice
      $this->_array_portal[0]['text']  = '*'.$this->_translator->getMessage('CONFIGURATION_EXTRA_CHOOSE_NO_PORTAL');
      $this->_array_portal[0]['value'] = -1;

      $server_item = $this->_environment->getServerItem();
      $portal_list = $server_item->getPortalList();
      if ( $portal_list->isNotEmpty() ) {
         $this->_array_portal[1]['text']  = '----------------------';
         $this->_array_portal[1]['value'] = 'disabled';
         $portal_item = $portal_list->getFirst();
         while ( $portal_item ) {
            $temp_array = array();
            $temp_array['text']  = $portal_item->getTitle();
            $temp_array['value'] = $portal_item->getItemID();
            $this->_array_portal[] = $temp_array;

            $portal_item = $portal_list->getNext();
         }
      }

      // extra option choice
      $this->_array_extra[0]['text']  = '*'.$this->_translator->getMessage('CONFIGURATION_EXTRA_CHOOSE_TEXT');
      $this->_array_extra[0]['value'] = -1;

      // extra options
      $this->_array_extra[1]['text']  = '----------------------';
      $this->_array_extra[1]['value'] = 'disabled';
      $this->_array_extra[5]['text']  = $this->_translator->getMessage('CONFIGURATION_EXTRA_MATERIALIMPORT');
      $this->_array_extra[5]['value'] = 'CONFIGURATION_EXTRA_MATERIALIMPORT';
      $this->_array_extra[6]['text']  = $this->_translator->getMessage('CONFIGURATION_EXTRA_ACTIVATING_CONTENT');
      $this->_array_extra[6]['value'] = 'CONFIGURATION_EXTRA_ACTIVATING_CONTENT';
      #$this->_array_extra[7]['text']  = $this->_translator->getMessage('CONFIGURATION_EXTRA_GROUPROOM');
      #$this->_array_extra[7]['value'] = 'CONFIGURATION_EXTRA_GROUPROOM';
      $this->_array_extra[8]['text']  = $this->_translator->getMessage('CONFIGURATION_EXTRA_LOGARCHIVE');
      $this->_array_extra[8]['value'] = 'CONFIGURATION_EXTRA_LOGARCHIVE';
//       $this->_array_extra[9]['text']  = $this->_translator->getMessage('CONFIGURATION_EXTRA_LOG_IP');
//       $this->_array_extra[9]['value'] = 'CONFIGURATION_EXTRA_LOG_IP';
      $this->_array_extra[20]['text']  = '----------------------';
      $this->_array_extra[20]['value'] = 'disabled';

      global $c_etchat_enable;
      if ( isset($c_etchat_enable) and  $c_etchat_enable ) {
         $this->_array_extra[21]['text']  = $this->_translator->getMessage('CHAT_CONFIGURATION_EXTRA_CHAT');
         $this->_array_extra[21]['value'] = 'CHAT_CONFIGURATION_EXTRA_CHAT';
      }
      
      global $c_pmwiki;
      if ( isset($c_pmwiki) and  $c_pmwiki ) {
         $this->_array_extra[23]['text']  = $this->_translator->getMessage('CONFIGURATION_EXTRA_WIKI');
         $this->_array_extra[23]['value'] = 'CONFIGURATION_EXTRA_WIKI';
      }
      
      /*
       * commsywordpress
       */
      global $symfonyContainer;
      $c_wordpress = $symfonyContainer->getParameter('commsy.wordpress.enabled');
      if ( isset($c_wordpress) and  $c_wordpress ) {
         $this->_array_extra[24]['text']  = $this->_translator->getMessage('CONFIGURATION_EXTRA_WORDPRESS');
         $this->_array_extra[24]['value'] = 'CONFIGURATION_EXTRA_WORDPRESS';
      }

      /*
       * workflow
       */
      global $c_workflow;
      if ( isset($c_workflow) and  $c_workflow ) {
         $this->_array_extra[25]['text']  = $this->_translator->getMessage('CONFIGURATION_EXTRA_WORKFLOW');
         $this->_array_extra[25]['value'] = 'CONFIGURATION_EXTRA_WORKFLOW';
      }
      
      global $c_plugin_array;
      if (isset($c_plugin_array) and !empty($c_plugin_array)) {
         $this->_array_extra[50]['text']  = '----------------------';
         $this->_array_extra[50]['value'] = 'disabled';
         foreach ($c_plugin_array as $plugin) {
            $plugin_class = $this->_environment->getPluginClass($plugin);
            if (method_exists($plugin_class,'getArrayForExtraConfiguration')) {
               $temp_array = $plugin_class->getArrayForExtraConfiguration();
               if (isset($temp_array)) {
                  $this->_array_extra[] = $temp_array;
               }
            }
         }
      }

      // show checkboxes
      if ( !empty($this->_form_post['portal'])
           and $this->_form_post['portal'] > 99
         ) {
         $this->_show_checkboxes = $this->_form_post['portal'];
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
                               'option',
                               '',
                               '',
                               '',
                               true);

      $this->_form->addSelect( 'portal',
                               $this->_array_portal,
                               '',
                               $translator->getMessage('COMMON_PORTAL'),
                               '',
                               '',
                               '',
                               '',
                               true,
                               $translator->getMessage('COMMON_CHOOSE_BUTTON'),
                               'option',
                               '',
                               '',
                               '',
                               true);

      // description text
      $this->_form->addText('description',$translator->getMessage('COMMON_DESCRIPTION'),'');
      $this->_form->addHidden('description_hidden','');

      // generate checkboxes for rooms
      if ( $this->_show_checkboxes ) {
         $portal_manager = $this->_environment->getPortalManager();
         $portal = $portal_manager->getItem($this->_show_checkboxes);
         unset($portal_manager);
         $this->_form->addSubHeadline('headline',$portal->getTitle());
         $this->_form->addCheckbox('ROOM_'.$portal->getItemID(),$portal->getItemID(),'','',$portal->getTitle().' ('.$translator->getMessage('ROOM_TYPE_PORTAL').')','','',$disabled);
         $room_list = $portal->getRoomList();
         if ( !$room_list->isEmpty() ) {
            $room = $room_list->getFirst();
            while ($room) {
               // skip entry if room is grouproom
               if( $room->isGroupRoom() ) {
                  $room = $room_list->getNext();
                  continue;
               }
               
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
         if ( mb_strlen($this->_values['extra']) == 2 and $this->_values['extra'] != -1) {
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
      if (mb_strlen($this->_form_post['extra']) == 2 and $this->_form_post['extra'] != -1) {
         $this->_error_array[] = $this->_translator->getMessage('CONFIGURATION_EXTRA_CHOICE_ERROR');
         $this->_form->setFailure('extra','');
      }
      if ( mb_strlen($this->_form_post['extra']) == 2 and
           $this->_form_post['extra'] == -1 and
           isset($this->_form_post['option']) and
           isOption($this->_form_post['option'], $this->_translator->getMessage('COMMON_SAVE_BUTTON'))
         ) {
         $this->_error_array[] = $this->_translator->getMessage('CONFIGURATION_EXTRA_CHOICE_ERROR');
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