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
class cs_configuration_ims_form extends cs_rubric_form {

  /**
   * string - containing the headline of the form
   */
  var $_headline = NULL;

  var $_ims_user_id = 'IMS_USER';

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
      $this->_headline = $this->_translator->getMessage('SERVER_IMS_LINK');
   }

   /** create the form, INTERNAL
    * this methods creates the form with the form definitions
    *
    * @author CommSy Development Group
    */
   function _createForm () {

      $this->setHeadline($this->_headline);
      $this->_form->addHidden('user_id',$this->_ims_user_id);
      $this->_form->addCheckbox('exist','1',false,$this->_translator->getMessage('SERVER_CONFIGURATION_IMS_USER_EXIST'),'','','','','onclick="cs_toggle()"');

      $this->_form->addText('user_id_text',$this->_translator->getMessage('SERVER_CONFIGURATION_IMS_USERID'),$this->_ims_user_id,'');
      $this->_form->addPassword('password1', '', $this->_translator->getMessage('SERVER_CONFIGURATION_IMS_PASSWORD'), '', 15, 20,false);
      $this->_form->addPassword('password2', '', $this->_translator->getMessage('SERVER_CONFIGURATION_IMS_PASSWORD_AGAIN'), '', 15, 20,false);
      // buttons
      $this->_form->addButtonBar('option',$this->_translator->getMessage('PREFERENCES_SAVE_BUTTON'),'');


      $this->_form->addEmptyLine();
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
      $this->_form->addFilefield('upload', $this->_translator->getMessage('MATERIAL_FILES'), $this->_translator->getMessage('MATERIAL_UPLOAD_DESC',$meg_val), 12, false, $this->_translator->getMessage('COMMON_UPLOAD'),'option',false);

   }

   /** loads the selected and given values to the form
    * this methods loads the selected and given values to the form from the material item or the form_post data
    *
    * @author CommSy Development Group
    */
   function _prepareValues () {
      $this->_values = array();
      $auth_object = $this->_environment->getAuthenticationObject();
      $auth_manager = $auth_object->getCommSyAuthManager();

      if ($auth_manager->exists($this->_ims_user_id)) {
         $this->_values['exist'] = 1;
      } else {
         $this->_values['exist'] = 0;
      }

      if ( !empty($this->_form_post) ) {
         $this->_values = $this->_form_post;
      }

   }

   /** specific check the values of the form
    * this methods check the entered values
    */
   function _checkValues () {
      if (!empty($this->_form_post['exist'])) {
         if ($this->_form_post['password1'] != $this->_form_post['password2'] OR $this->_form_post['password1'] == '') {
            $this->_error_array[] = $this->_translator->getMessage('IMS_PASSWORD_ERROR');
            $this->_form->setFailure('password1');
            $this->_form->setFailure('password2');
         }
      }
   }

   function getInfoForHeaderAsHTML () {
      $retour =  '         function cs_toggle() {'.LF;
      $retour .= '            if (document.f.exist.checked) {'.LF;
      $retour .= '               cs_enable();'.LF;
      $retour .= '            } else {'.LF;
      $retour .= '               cs_disable();'.LF;
      $retour .= '            }'.LF;
      $retour .= '         }'.LF;
      $retour .= '         function cs_disable() {'.LF;
      $retour .= '            document.f.password1.disabled = true;'.LF;
      $retour .= '            document.f.password2.disabled = true;'.LF;
      $retour .= '         }'.LF;
      $retour .= '         function cs_enable() {'.LF;
      $retour .= '            document.f.password1.disabled = false;'.LF;
      $retour .= '            document.f.password2.disabled = false;'.LF;
      $retour .= '         }'.LF;
      return $retour;
   }

   function getInfoForBodyAsHTML () {
      $retour =  'cs_toggle()'.LF;
      return $retour;
   }
}
?>