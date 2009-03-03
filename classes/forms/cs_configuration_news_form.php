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
   function cs_configuration_news_form($params) {
      $this->cs_rubric_form($params);
   }
   /** init data for form, INTERNAL
    * this methods init the data for the form, for example groups
    *
    * @author CommSy Development Group
    */
   function _initForm () {
      $this->_headline = getMessage('SERVER_NEWS_LINK');

      $show_array = array();
      $temp_array['text']  = getMessage('COMMON_YES');
      $temp_array['value'] = 1;
      $show_array[] = $temp_array;
      $temp_array['text']  = getMessage('COMMON_NO');
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

      $link = ahref_curl($this->_environment->getCurrentContextID(), 'help', 'context',
                  array('module'=>$this->_environment->getCurrentModule(),'function'=>$this->_environment->getCurrentFunction(),'context'=>'HELP_COMMON_FORMAT'),
                  getMessage('HELP_COMMON_FORMAT_TITLE'), '', '_help', '', '',
                  'onclick="window.open(href, target, \'toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=yes, resizable=yes, copyhistory=yes, width=600, height=400\');"');
      $this->setHeadline($this->_headline);
      $this->_form->addHidden('iid','');
      $this->_form->addRadioGroup('show',getMessage('SERVER_CONFIGURATION_NEWS_SHOW'),'',$this->_show_array,'',true,true);
      $this->_form->addTextfield('title','',getMessage('COMMON_TITLE'),'',200,'66',true);
      $this->_form->addTextArea('text','',getMessage('SERVER_CONFIGURATION_NEWS_TEXT'),getMessage('COMMON_CONTENT_DESC',$link));
      $this->_form->addTextfield('link','',getMessage('SERVER_CONFIGURATION_NEWS_LINK'),getMessage('SERVER_CONFIGURATION_NEWS_LINK_DESC'),200,'66',false);

      // buttons
      $this->_form->addButtonBar('option',getMessage('PREFERENCES_SAVE_BUTTON'),'');
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
      }
   }
}
?>