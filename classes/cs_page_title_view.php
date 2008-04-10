<?php
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

include_once('classes/cs_view.php');

/**
 *  generic upper class for CommSy homepage-views
 */
class cs_page_title_view extends cs_view {
	
	var $_title = '';
	var $_with_help = true;
	var $_show_title = true;

   /** constructor
    * the only available constructor, initial values for internal variables
    *
    * @param object  environment            the CommSy environment
    * @param boolean with_modifying_actions true: display with modifying functions
    *                                       false: display without modifying functions
    */
   function cs_page_title_view ($environment, $with_modifying_actions) {
      $this->cs_view( $environment,
                      $with_modifying_actions);
   }

   function asHTML () {

      if ($this->_show_title) {
		  $html = '<div class="actions">'.LF;
		  if ($this->_with_help) {
		      // Always show context sensitive help
		      $params = array();
		      $params['module'] = $this->_module;
		      $params['function'] = $this->_function;
		      $html .= ahref_curl($this->_environment->getCurrentContextID(), 'help', 'context',
		                  $params,
		                  $this->_translator->getMessage('HELP_LINK'), '', '_help', '', '',
		                  'onclick="window.open(href, target, \'toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=yes, resizable=yes, copyhistory=yes, width=600, height=400\');"').LF;
		      unset($params);
		  }
	      $html .= '</div>'.LF;
	      $html .= '<div><h2 style="margin-bottom:10px; margin-top:0px;">'.$this->_title.'</h2></div>';
	      return $html;
      }
   }
   
	function setTitle ($value) {
		$this->_title = $value;
	}
	
	function getTitle () {
		$this->_show_title = false;
		return $this->_title;
	}
	
	function setWithoutHelp () {
		$this->_with_help = false;
	}
}
?>