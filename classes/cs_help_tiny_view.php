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

include_once('classes/cs_tiny_view.php');
include_once('functions/curl_functions.php');

/**
 *  class for CommSy tiny view: help
 */
class cs_help_tiny_view extends cs_tiny_view {


var $_description;
   /** constructor
    * the only available constructor, initial values for internal variables
    *
    * @param object  environment            commsy environment
    * @param boolean with_modifying_actions true: display with modifying functions
    *                                       false: display without modifying functions
    */
   function cs_help_tiny_view ($environment, $with_modifying_actions) {
      $this->cs_tiny_view($environment, $with_modifying_actions);
      $this->setTitle($this->_translator->getMessage('HELP_TINY_HEADER'));

      $description = '<ul style=" margin-left:17px; margin-top:0px; spacing-left:0px; spacing-top:0px; padding-top:0px; padding-left:0px;">'.LF;
      $description .= '<li><a href="http://www.commsy.net/uploads/Software/commsy_kurzbeschreibung.pdf" target="_blank">'.$this->_translator->getMessage('CAMPUS_COMMSY_DESCRIPTION_HELP').'</a></li>'.LF;
      $description .= '<li><a href="http://www.commsy.net/uploads/Software/commsy_nutzungshandbuch.pdf" target="_blank">'.$this->_translator->getMessage('CAMPUS_HANDBOOK_DESCRIPTION_HELP').'</a></li>'.LF;
      $description .= '<li><a href="http://www.commsy.net/uploads/Software/commsy_moderationshandbuch.pdf" target="_blank">'.$this->_translator->getMessage('CAMPUS_MODERATION_DESCRIPTION_HELP').'</a></li>'.LF;
      $description .= '<li><a href="http://www.commsy.net/Software/FAQ" target="_blank">'.$this->_translator->getMessage('CAMPUS_QUESTIONS_DESCRIPTION_HELP').'</a></li>'.LF;
      $description .= '<li><a href="http://www.commsy.net" target="_blank">'.$this->_translator->getMessage('COMMSY_WEBSITE').'</a></li>'.LF;
      $description .= '</ul>'.LF;
     $this->_description = $description;
   }

   /** get the description of the list view title as HTML
    * this method returns the description in HTML-Code
    *
    * @return string $this->_description as HMTL
    */
   function _getDescriptionAsHTML() {
      return $this->_description;
   }
}
?>