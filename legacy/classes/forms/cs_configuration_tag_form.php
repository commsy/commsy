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

/** class for commsy forms
 * this class implements an interface for the creation of forms in the commsy style
 */
class cs_configuration_tag_form extends cs_rubric_form {


var $_with_buzzwords = false;
var $_with_tags = false;

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
      $current_context = $this->_environment->getCurrentContextItem();
      if (isset($this->_item)) {
         $this->_with_buzzwords = !$this->_item->withBuzzwords();
         $this->_with_tags = !$this->_item->withTags();
      }else{
         if (!isset($this->_form_post['buzzword']) or empty($this->_form_post['buzzword']) ) {
            $this->_with_buzzwords = true;
         }
         if (!isset($this->_form_post['tags']) or empty($this->_form_post['tags']) ) {
            $this->_with_tags = true;
         }
      }
   }

   /** create the form, INTERNAL
    * this methods creates the form with the form definitions
    */
   function _createForm () {

      $this->_form->addCheckbox('buzzword','yes','',$this->_translator->getMessage('PREFERENCES_CONFIGURATION_BUZZWORD'),$this->_translator->getMessage('PREFERENCES_CONFIGURATION_BUZZWORD_VALUE'),'','','','onclick="cs_toggle()"');
      $radio_values = array();
      $radio_values[0]['text'] = $this->_translator->getMessage('CONFIGURATION_BUZZWORDS_NOT_MANDATORY');
      $radio_values[0]['value'] = '1';
      $radio_values[1]['text'] = $this->_translator->getMessage('CONFIGURATION_BUZZWORDS_MANDATORY');
      $radio_values[1]['value'] = '2';


      $this->_form->addRadioGroup('buzzword_mandatory',$this->_translator->getMessage('CONFIGURATION_TAG_STATUS'),'',$radio_values,'',true,false,'','',$this->_with_buzzwords);

      $this->_form->addEmptyLine();
      $this->_form->addCheckbox('tags','yes','',$this->_translator->getMessage('PREFERENCES_CONFIGURATION_TAGS'),$this->_translator->getMessage('PREFERENCES_CONFIGURATION_TAGS_VALUE'),'','','','onclick="cs_toggle2()"');

      $radio_values = array();
      $radio_values[0]['text'] = $this->_translator->getMessage('CONFIGURATION_TAG_NOT_MANDATORY');
      $radio_values[0]['value'] = '1';
      $radio_values[1]['text'] = $this->_translator->getMessage('CONFIGURATION_TAG_MANDATORY');
      $radio_values[1]['value'] = '2';
      $this->_form->addRadioGroup('tag_mandatory',$this->_translator->getMessage('CONFIGURATION_TAG_STATUS'),'',$radio_values,'',true,false,'','',$this->_with_tags);

      if ( $this->_environment->inPrivateRoom() ) {
         $this->_form->addHidden('tag_edit','1');
      } else {
         $radio_values = array();
         $radio_values[0]['text'] = $this->_translator->getMessage('CONFIGURATION_TAG_EDIT_BY_ALL');
         $radio_values[0]['value'] = '1';
         $radio_values[1]['text'] = $this->_translator->getMessage('CONFIGURATION_TAG_EDIT_BY_MODERATOR');
         $radio_values[1]['value'] = '2';
         $this->_form->addRadioGroup('tag_edit',$this->_translator->getMessage('CONFIGURATION_TAG_EDIT'),'',$radio_values,'',true,false,'','',$this->_with_tags);
      }

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
         if ($room->withTags()){
            $this->_values['tags'] = 'yes';
         }
         if ($room->withBuzzwords()){
            $this->_values['buzzword'] = 'yes';
         }
         $tag_mandatory = $room->isTagMandatory();
         if ($tag_mandatory){
      $this->_values['tag_mandatory'] ='2';
         }else{
      $this->_values['tag_mandatory'] ='1';
         }
         $tag_edited_by_all = $room->isTagEditedByAll();
         if ($tag_edited_by_all){
      $this->_values['tag_edit'] ='1';
         }else{
      $this->_values['tag_edit'] ='2';
         }
         $buzzword_mandatory = $room->isBuzzwordMandatory();
         if ($buzzword_mandatory){
      $this->_values['buzzword_mandatory'] ='2';
         }else{
      $this->_values['buzzword_mandatory'] ='1';
         }
/*         $buzzword_edited_by_all = $room->isBuzzwordEditedByAll();
         if ($buzzword_edited_by_all){
      $this->_values['buzzword_edit'] ='1';
         }else{
      $this->_values['buzzword_edit'] ='2';
         }   */
      }
   }

   function getInfoForHeaderAsHTML () {
      $retour  = '';
      $retour .= '         function cs_toggle() {'.LF;
      $retour .= '            if (document.f.buzzword.checked) {'.LF;
      $retour .= '               cs_enable_buzzword();'.LF;
      $retour .= '            } else {'.LF;
      $retour .= '               cs_disable_buzzword();'.LF;
      $retour .= '            }'.LF;
      $retour .= '         }'.LF;
      $retour .= '         function cs_toggle2() {'.LF;
      $retour .= '            if (document.f.tags.checked) {'.LF;
      $retour .= '               cs_enable_tags();'.LF;
      $retour .= '            } else {'.LF;
      $retour .= '               cs_disable_tags();'.LF;
      $retour .= '            }'.LF;
      $retour .= '         }'.LF;
      $retour .= '         function cs_disable_buzzword() {'.LF;
      $retour .= '            document.f.buzzword_mandatory.item(0).disabled = true;'.LF;
      $retour .= '            document.f.buzzword_mandatory.item(1).disabled = true;'.LF;
      $retour .= '         }'.LF;
      $retour .= '         function cs_enable_buzzword() {'.LF;
      $retour .= '            document.f.buzzword_mandatory.item(0).disabled = false;'.LF;
      $retour .= '            document.f.buzzword_mandatory.item(1).disabled = false;'.LF;
      $retour .= '         }'.LF;
      $retour .= '         function cs_enable_tags() {'.LF;
      $retour .= '            document.f.tag_mandatory.item(0).disabled = false;'.LF;
      $retour .= '            document.f.tag_mandatory.item(1).disabled = false;'.LF;
      $retour .= '            document.f.tag_edit.item(0).disabled = false;'.LF;
      $retour .= '            document.f.tag_edit.item(1).disabled = false;'.LF;
      $retour .= '         }'.LF;
      $retour .= '         function cs_disable_tags() {'.LF;
      $retour .= '            document.f.tag_mandatory.item(0).disabled = true;'.LF;
      $retour .= '            document.f.tag_mandatory.item(1).disabled = true;'.LF;
      $retour .= '            document.f.tag_edit.item(0).disabled = true;'.LF;
      $retour .= '            document.f.tag_edit.item(1).disabled = true;'.LF;
      $retour .= '         }'.LF;
      $retour .= '         function disable_code() {'.LF;
      $retour .= '            document.f.code.disabled = true;'.LF;
      $retour .= '         }'.LF;
      $retour .= '         function enable_code() {'.LF;
      $retour .= '            document.f.code.disabled = false;'.LF;
      $retour .= '         }'.LF;
      return $retour;
   }


}
?>