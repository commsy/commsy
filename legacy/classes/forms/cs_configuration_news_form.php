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
class cs_configuration_news_form extends cs_rubric_form {

  /**
   * string - containing the headline of the form
   */
  var $_headline = NULL;

  var $_show_array = array();

  var $_show_hint = false;

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
    *
    * @author CommSy Development Group
    */
   function _initForm () {
      global $environment;
      if ( $environment->getCurrentContextItem()->isPortal() ) {
         $this->_headline = $this->_translator->getMessage('PORTAL_NEWS_LINK');
      } else {
         $this->_headline = $this->_translator->getMessage('SERVER_NEWS_LINK');
      }
      $show_array = array();
      $temp_array['text']  = $this->_translator->getMessage('COMMON_YES');
      $temp_array['value'] = 1;
      $show_array[] = $temp_array;
      $temp_array['text']  = $this->_translator->getMessage('COMMON_NO');
      $temp_array['value'] = -1;
      $show_array[] = $temp_array;
      $this->_show_array = $show_array;
   }

   /** create the form, INTERNAL
    * this methods creates the form with the form definitions
    *
    * @author CommSy Development Group
    */
   function _createForm () {

      $this->setHeadline($this->_headline);
      $this->_form->addHidden('iid','');
      $this->_form->addRadioGroup('show',$this->_translator->getMessage('SERVER_CONFIGURATION_NEWS_SHOW'),'',$this->_show_array,'',true,true);
      $this->_form->addTextfield('title','',$this->_translator->getMessage('COMMON_TITLE').'<span class="required">*</span>','',200,'62',false);
      $this->_form->addTextArea('text','',$this->_translator->getMessage('SERVER_CONFIGURATION_NEWS_TEXT'),'');
      $this->_form->addTextfield('link','',$this->_translator->getMessage('SERVER_CONFIGURATION_NEWS_LINK'),$this->_translator->getMessage('SERVER_CONFIGURATION_NEWS_LINK_DESC'),200,'62',false);

      if ( $this->_environment->inPortal() ) {
         $this->_form->addEmptyline();
         $this->_form->addRadioGroup('show_server',$this->_translator->getMessage('PORTAL_CONFIGURATION_NEWS_SHOW_SERVER'),'',$this->_show_array,'',false,true);
      }

      // buttons
      $this->_form->addButtonBar('option',$this->_translator->getMessage('PREFERENCES_SAVE_BUTTON'),'');
   }

   /** loads the selected and given values to the form
    * this methods loads the selected and given values to the form from the material item or the form_post data
    *
    * @author CommSy Development Group
    */
   function _prepareValues () {
      $this->_values = array();

      if ( !empty($this->_form_post) ) {
         $this->_values = $this->_form_post;
      } elseif ( isset($this->_item) ) {
         $this->_values['iid']   = $this->_item->getItemID();
         $this->_values['title'] = $this->_item->getServerNewsTitle();
         $this->_values['text']  = $this->_item->getServerNewsText();
         $this->_values['link']  = $this->_item->getServerNewsLink();
         if ($this->_item->showServerNews()) {
            $this->_values['show'] = 1;
         } else {
            $this->_values['show'] = -1;
         }
         if ( $this->_item->isPortal() ) {
            if ($this->_item->showNewsFromServer()) {
               $this->_values['show_server'] = 1;
            } else {
               $this->_values['show_server'] = -1;
            }
         }
      }
   }
   
   function _checkValues(){
      if($_POST['show'] == '1'){
         if(empty($_POST['title'])){
            $this->_error_array[] = $this->_translator->getMessage('COMMON_ERROR_FIELD', $this->_translator->getMessage('COMMON_TITLE'));
            $this->_form->setFailure('title','');
         }
      }
   }
}
?>