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
class cs_configuration_backup_form extends cs_rubric_form {

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
    */
   function _initForm () {
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
      $this->_meg_val = round($val/1048576);
   }

   /** create the form, INTERNAL
    * this methods creates the form with the form definitions
    */
   function _createForm () {

      $this->setHeadline($this->_headline);
      $this->_form->addText('text',$this->_translator->getMessage('COMMON_ATTENTION'),$this->_translator->getMessage('PREFERENCES_BACKUP_DESC',getCommSyVersion()));

      $this->_form->addFilefield('upload', $this->_translator->getMessage('COMMON_FILE'),'');
      // buttons
      $this->_form->addButtonBar('option',$this->_translator->getMessage('PREFERENCES_BACKUP_BUTTON'),'');
   }

   /** loads the selected and given values to the form
    * this methods loads the selected and given values to the form from the material item or the form_post data
    */
   function _prepareValues () {
      $this->_values = array();
      if ( !empty($this->_form_post) ) {
         $this->_values = $this->_form_post;
      }
   }
}
?>