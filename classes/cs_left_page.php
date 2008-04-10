<?PHP
// $Id$
//
// Release $Name$
//
// Copyright (c)2002-2003 Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
// Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
// Edouard Simon, Monique Strauss, Jos� Manuel Gonz�lez V�zquez
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
class cs_left_page {

   var $_environment = NULL;
   var $_post_vars = array();
   var $_get_vars = array();
   var $_translator = NULL;
   var $_command = NULL;

   function cs_left_page ($environment) {
      $this->_environment = $environment;
      $this->_translator = $this->_environment->getTranslationObject();
      $this->_get_vars  = $this->_environment->getCurrentParameterArray();
      $this->_post_vars = $this->_environment->getCurrentPostParameterArray();

      // get the command
      if ( !empty($this->_post_vars['option'])) {
	$this->_command = $this->_post_vars['option'];
      } elseif ( !empty($this->_get_vars['option']) ) {
         $this->_command = $this->_get_vars['option'];
      } else {
         $this->_command = 'empty';
      }
   }

   function _redirect_back () {
      $params = array();
      $params = $this->_environment->getCurrentParameterArray();
      unset($params['cs_modus']);
      redirect($this->_environment->getCurrentContextID(),$this->_environment->getCurrentModule(),$this->_environment->getCurrentFunction(),$params);
      unset($params);
   }

   function _show_form ($form,$form_name='') {
      include_once('classes/cs_form_view_left.php');
      $form_view = new cs_form_view_left($this->_environment,'');
      if ( !empty($form_name) ) {
         $form_view->setFormName($form_name);
      }
      include_once('functions/curl_functions.php');
      $params = $this->_environment->getCurrentParameterArray();
      $form_view->setAction(curl($this->_environment->getCurrentContextID(),$this->_environment->getCurrentModule(),$this->_environment->getCurrentFunction(),$this->_environment->getCurrentParameterArray()));
      $form_view->setForm($form);
      return $form_view->asHTML();
   }
}
?>