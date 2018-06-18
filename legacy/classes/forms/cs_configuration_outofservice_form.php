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

/** class for CommSy forms
 * this class implements an interface for the creation of forms in the CommSy style
 */
class cs_configuration_outofservice_form extends cs_rubric_form {

   var $_languages = NULL;

   var $_iid = NULL;

   var $_type = NULL;

   var $_with_html_textarea = false;
   var $_with_html_textarea_status = 1;

   var $_description_text = '';
   var $_show_array = array();

   /** constructor
    * the only available constructor
    *
    * @param array params array of parameter
    */
   public function __construct($params) {
      cs_rubric_form::__construct($params);
   }

   /** init data for form, INTERNAL
    * this methods init the data for the form, for example groups
    */
   function _initForm () {

      if ( isset($this->_item) ) {
         $this->_iid = $this->_item->getItemID();
      } elseif (isset($this->_form_post['iid'])) {
         $this->_iid = $this->_form_post['iid'];
      } else {
         $this->_iid = 'NEW';
      }

      if (isset($this->_form_post['oos_text'])) {
         $this->_description_text = $this->_form_post['oos_text'];
      } elseif ( isset($this->_item) ) {
         $this->_description_text = $this->_item->getLanguage();
      } else {
         $current_server = $this->_environment->getServerItem();
         $language = $current_server->getLanguage();
         $this->_description_text = $language;
      }

      if ($this->_description_text =='user'){
          $this->_description_text = 'de';
      }

      if ( isset($this->_item) ) {
         $this->_type = $this->_item->getItemType();
      } elseif (isset($this->_form_post['type'])) {
         $this->_type = $this->_form_post['type'];
      }

      if ( isset($this->_item) ) {
         $this->_with_html_textarea = $this->_item->withHtmlTextArea();
         $this->_with_html_textarea_status = $this->_item->getHtmlTextAreaStatus();
      } elseif (isset($this->_form_post['with_html_textarea'])) {
             $this->_with_html_textarea = $this->_form_post['with_html_textarea'];
             $this->_with_html_textarea_status = $this->_form_post['with_html_textarea_status'];
      }

      $show_array = array();
      $temp_array['text']  = $this->_translator->getMessage('COMMON_YES');
      $temp_array['value'] = 1;
      $show_array[] = $temp_array;
      $temp_array['text']  = $this->_translator->getMessage('COMMON_NO');
      $temp_array['value'] = -1;
      $show_array[] = $temp_array;
      $this->_show_array = $show_array;

      // languages
      $this->_languages = $this->_environment->getAvailableLanguageArray();

  }  // End of function _initForm (ca. line 101)

   /** create the form, INTERNAL
    * this methods creates the form with the form definitions
    */
   function _createForm () {

      // form fields
      $this->_form->addHidden('iid',$this->_iid);
      $this->_form->addHidden('type',$this->_type);
      $this->_form->addHidden('with_html_textarea',$this->_with_html_textarea);
      $this->_form->addHidden('with_html_textarea_status',$this->_with_html_textarea_status);

      $this->_form->addRadioGroup('oos_show',$this->_translator->getMessage('SERVER_CONFIGURATION_NEWS_SHOW'),'',$this->_show_array,'',true,true);

      // description
      $languageArray = array();
      $tmpArray = $this->_environment->getAvailableLanguageArray();
      $zaehler = 0;

      foreach ($tmpArray as $item){
         switch ( mb_strtoupper($item, 'UTF-8') ){
            case 'DE':
               $languageArray[$zaehler]['text'] = $this->_translator->getMessage('DE');
               break;
            case 'EN':
               $languageArray[$zaehler]['text'] = $this->_translator->getMessage('EN');
               break;
            case 'RU':
               $languageArray[$zaehler]['text'] = $this->_translator->getMessage('RU');
               break;
            default:
               break;
         }

         $languageArray[$zaehler]['value']= $item;
         $zaehler++;
      }
      $this->_form->addSelect( 'oos_text',
                               $languageArray,
                               '',
                               $this->_translator->getMessage('CONFIGURATION_CHOOSE_LANGUAGE'),
                               '',
                               '',
                               '',
                               '',
                               true,
                               $this->_translator->getMessage('COMMON_LANGUAGE_CHOOSE_BUTTON'),
                               'option','','','13',true
                             );

      // description
      foreach ($this->_languages as $language) {
         if ($language == $this->_description_text){

            if (isset ($this->_item) ) {
               $html_status = $this->_item->getHtmlTextAreaStatus();
            } else {
               $portal = $this->_environment->getServerItem();
               $html_status = $portal->getHtmlTextAreaStatus();
            }

            if ($html_status == '1') {
               $html_status = '2';
            }

            $this->_form->addTextArea('oos_'.$language,
                                      '',
                                      '',
                                      '',
                                      '44',
                                      '15',
                                      'virtual',
                                      false,
                                      false,
                                      true,
                                      $html_status
                                     );

         } else{
            $this->_form->addHidden('oos_'.$language,'');
         }
      }

      // buttons
      $this->_form->addButtonBar( 'option',
                                  $this->_translator->getMessage('PREFERENCES_SAVE_BUTTON'),
                                  $this->_translator->getMessage('COMMON_CANCEL_BUTTON')
                                 );
   } // End of function _createForm (ca. line 194)

   /** loads the selected and given values to the form
    * this methods loads the selected and given values to the form from the material item or the form_post data
    */
   function _prepareValues () {
      $this->_values = array();

      if (isset($this->_item)) {
         $this->_values['iid'] = $this->_item->getItemID();

         $description_array = $this->_item->getOutOfServiceArray();
         $languages = $this->_environment->getAvailableLanguageArray();

         foreach ($languages as $language) {

            if (!empty($description_array[cs_strtoupper($language)])) {
               $this->_values['oos_'.$language] = $description_array[cs_strtoupper($language)];
            } else {
               $this->_values['oos_'.$language] = $this->_translator->getMessageInLang($language,'SERVER_CONFIGURATION_OOS_DEFAULT_TEXT');
            }
         }
         if ( $this->_item->showOutOfService() ) {
            $this->_values['oos_show'] = 1;
         } else {
            $this->_values['oos_show'] = -1;
         }

      } elseif ( isset($this->_form_post) ) {
         $this->_values = $this->_form_post;
      }
   } // End of function _prepareValues (ca. line 569)

   /** specific check the values of the form
    * this methods check the entered values
    */
   function _checkValues () {
   }

   function getInfoForHeaderAsHTML () {
      $retour  = '';
      /*
      if ($this->_with_template_form_element) {
         $retour .= '         function cs_toggle() {'.LF;
         $retour .= '            if (document.f.status.checked) {'.LF;
         $retour .= '               cs_enable1();'.LF;
         $retour .= '            } else {'.LF;
         $retour .= '               cs_disable1();'.LF;
         $retour .= '            }'.LF;
         $retour .= '         }'.LF;
         $retour .= '         function cs_disable1() {'.LF;
         $retour .= '            document.f.template.checked = 0;'.LF;
         $retour .= '            document.f.template.disabled = true;'.LF;
         $retour .= '         }'.LF;
         $retour .= '         function cs_enable1() {'.LF;
         $retour .= '            document.f.template.disabled = false;'.LF;
         $retour .= '         }'.LF;
      }
      */
      return $retour;
   }
}
?>