<?PHP
//
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
include_once('functions/text_functions.php');

/** class for commsy forms
 * this class implements an interface for the creation of forms in the commsy style
 */
class cs_configuration_structure_options_form extends cs_rubric_form {


var $_with_buzzwords = false;
var $_with_tags = false;

   /** constructor
    * the only available constructor
    *
    * @param object environment the environment object
    */
   function __construct($params) {
      cs_rubric_form::__construct($params);
   }

   /** init data for form, INTERNAL
    * this methods init the data for the form, for example groups
    */
   function _initForm () {
      $current_context = $this->_environment->getCurrentContextItem();
   }

   /** create the form, INTERNAL
    * this methods creates the form with the form definitions
    */
   function _createForm () {

      //buzzwords
      $this->_form->addCheckbox('buzzword','yes','',$this->_translator->getMessage('PREFERENCES_CONFIGURATION_BUZZWORD'),$this->_translator->getMessage('PREFERENCES_CONFIGURATION_BUZZWORD_VALUE'),'','','','onclick="cs_toggleBuzzwords()"');
      $this->_form->combine();
      $this->_form->addCheckbox('buzzword_show','yes','',$this->_translator->getMessage('CONFIGURATION_SHOW_EXPANDED'),$this->_translator->getMessage('CONFIGURATION_SHOW_EXPANDED'));
      $this->_form->combine();
      $this->_form->addCheckbox('buzzword_mandatory','yes','',$this->_translator->getMessage('CONFIGURATION_BUZZWORDS_MANDATORY'),$this->_translator->getMessage('CONFIGURATION_BUZZWORDS_MANDATORY'));

      //tags
      $this->_form->addCheckbox('tags','yes','',$this->_translator->getMessage('PREFERENCES_CONFIGURATION_TAGS'),$this->_translator->getMessage('PREFERENCES_CONFIGURATION_TAGS_VALUE'),'','','','onclick="cs_toggleTags()"');
      $this->_form->combine();
      $this->_form->addCheckbox('tags_show','yes','',$this->_translator->getMessage('CONFIGURATION_SHOW_EXPANDED'),$this->_translator->getMessage('CONFIGURATION_SHOW_EXPANDED'));
      $this->_form->combine();
      $this->_form->addCheckbox('tags_mandatory','yes','',$this->_translator->getMessage('CONFIGURATION_BUZZWORDS_MANDATORY'),$this->_translator->getMessage('CONFIGURATION_TAG_MANDATORY'));
      if ( $this->_environment->inPrivateRoom() ) {
         $this->_form->addHidden('tags_edit','1');
      } else {
      $this->_form->combine();
         $this->_form->addCheckbox('tags_edit','yes','',$this->_translator->getMessage('CONFIGURATION_TAG_EDIT'),$this->_translator->getMessage('CONFIGURATION_TAG_EDIT_BY_MODERATOR'));
      }

      //netnavigation
      $this->_form->addCheckbox('netnavigation','yes','',$this->_translator->getMessage('PREFERENCES_CONFIGURATION_NETNAVIGATION'),$this->_translator->getMessage('PREFERENCES_CONFIGURATION_NETNAVIGATION_VALUE'),'','','','onclick="cs_toggleNetnavigation()"');
      $this->_form->combine();
      $this->_form->addCheckbox('netnavigation_show','yes','',$this->_translator->getMessage('CONFIGURATION_SHOW_EXPANDED'),$this->_translator->getMessage('CONFIGURATION_SHOW_EXPANDED'));
      $this->_form->combine();
      $this->_form->addCheckbox('path','yes','',$this->_translator->getMessage('TOPIC_INDEX'),$this->_translator->getMessage('PREFERENCES_CONFIGURATION_PATH_VALUE'),'');

      // buttons
      $this->_form->addButtonBar('option',$this->_translator->getMessage('PREFERENCES_SAVE_BUTTON'),'');
   }

   /** loads the selected and given values to the form
    * this methods loads the selected and given values to the form from the material item or the form_post data
    */
   function _prepareValues () {
      if (isset($this->_form_post)) {
         $this->_values = $this->_form_post;
      }else{
         $room = $this->_environment->getCurrentContextItem();

         if ($room->withPath()){
            $this->_values['path'] = 'yes';
         }
         //buzzwords
         if ($room->withBuzzwords()){
            $this->_values['buzzword'] = 'yes';
         }
         if ($room->isBuzzwordShowExpanded()){
            $this->_values['buzzword_show'] = 'yes';
         }
         if ($room->isBuzzwordMandatory()){
            $this->_values['buzzword_mandatory'] = 'yes';
         }

         //tags
         if ($room->withTags()){
            $this->_values['tags'] = 'yes';
         }
         if ($room->isTagsShowExpanded()){
            $this->_values['tags_show'] = 'yes';
         }
         if ($room->isTagMandatory()){
            $this->_values['tags_mandatory'] = 'yes';
         }
         if (!$room->isTagEditedByAll()){
            $this->_values['tags_edit'] = 'yes';
         }

         //Netnavigation
         if ($room->withNetnavigation()){
            $this->_values['netnavigation'] = 'yes';
         }
         if ($room->isNetnavigationShowExpanded()){
            $this->_values['netnavigation_show'] = 'yes';
         }

      }
   }

   function getInfoForHeaderAsHTML () {
      $retour  = '';
      //$retour .= '        window.addEvent(\'domready\', function(){'.LF;
      $retour .= '			jQuery(document).ready( function() {' . LF;
      $retour .= '           if (document.edit.buzzword.checked) {'.LF;
      $retour .= '               cs_enable_buzzword();'.LF;
      $retour .= '            } else {'.LF;
      $retour .= '               cs_disable_buzzword();'.LF;
      $retour .= '            }'.LF;
      $retour .= '           if (document.edit.tags.checked) {'.LF;
      $retour .= '               cs_enable_tags();'.LF;
      $retour .= '            } else {'.LF;
      $retour .= '               cs_disable_tags();'.LF;
      $retour .= '            }'.LF;
      $retour .= '           if (document.edit.netnavigation.checked) {'.LF;
      $retour .= '               cs_enable_netnavigation();'.LF;
      $retour .= '            } else {'.LF;
      $retour .= '               cs_disable_netnavigation();'.LF;
      $retour .= '            }'.LF;
      $retour .= '         });'.LF;
      $retour .= '         function cs_toggleBuzzwords() {'.LF;
      $retour .= '            if (document.edit.buzzword.checked) {'.LF;
      $retour .= '               cs_enable_buzzword();'.LF;
      $retour .= '            } else {'.LF;
      $retour .= '               cs_disable_buzzword();'.LF;
      $retour .= '            }'.LF;
      $retour .= '         }'.LF;
      $retour .= '         function cs_toggleNetnavigation() {'.LF;
      $retour .= '            if (document.edit.netnavigation.checked) {'.LF;
      $retour .= '               cs_enable_netnavigation();'.LF;
      $retour .= '            } else {'.LF;
      $retour .= '               cs_disable_netnavigation();'.LF;
      $retour .= '            }'.LF;
      $retour .= '         }'.LF;
      $retour .= '         function cs_toggleTags() {'.LF;
      $retour .= '            if (document.edit.tags.checked) {'.LF;
      $retour .= '               cs_enable_tags();'.LF;
      $retour .= '            } else {'.LF;
      $retour .= '               cs_disable_tags();'.LF;
      $retour .= '            }'.LF;
      $retour .= '         }'.LF;
      $retour .= '         function cs_disable_buzzword() {'.LF;
      $retour .= '            document.edit.buzzword_mandatory.disabled = true;'.LF;
      $retour .= '            document.edit.buzzword_show.disabled = true;'.LF;
      $retour .= '         }'.LF;
      $retour .= '         function cs_enable_buzzword() {'.LF;
      $retour .= '            document.edit.buzzword_mandatory.disabled = false;'.LF;
      $retour .= '            document.edit.buzzword_show.disabled = false;'.LF;
      $retour .= '         }'.LF;
      $retour .= '         function cs_enable_tags() {'.LF;
      $retour .= '            document.edit.tags_mandatory.disabled = false;'.LF;
      $retour .= '            document.edit.tags_show.disabled = false;'.LF;
      $retour .= '            document.edit.tags_edit.disabled = false;'.LF;
      $retour .= '         }'.LF;
      $retour .= '         function cs_disable_tags() {'.LF;
      $retour .= '            document.edit.tags_mandatory.disabled = true;'.LF;
      $retour .= '            document.edit.tags_show.disabled = true;'.LF;
      $retour .= '            document.edit.tags_edit.disabled = true;'.LF;
      $retour .= '         }'.LF;
      $retour .= '         function cs_enable_netnavigation() {'.LF;
      $retour .= '            document.edit.netnavigation_show.disabled = false;'.LF;
      $retour .= '            document.edit.path.disabled = false;'.LF;
      $retour .= '         }'.LF;
      $retour .= '         function cs_disable_netnavigation() {'.LF;
      $retour .= '            document.edit.netnavigation_show.disabled = true;'.LF;
      $retour .= '            document.edit.path.disabled = true;'.LF;
      $retour .= '         }'.LF;
      return $retour;
   }


}
?>