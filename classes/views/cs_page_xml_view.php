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

/** upper class of the detail view
 */
$this->includeClass(PAGE_VIEW);

/** language_functions are needed for language specific display
 */
include_once('functions/language_functions.php');

/** curl_functions are needed for actions
 */
include_once('functions/curl_functions.php');

/** date_functions are needed for language specific display
 */
include_once('functions/date_functions.php');

/** misc_functions are needed for display the commsy version
 */
include_once('functions/misc_functions.php');
include_once('functions/text_functions.php');

/** class for a page view of commsy
 * this class implements a page view of commsy
 */
class cs_page_xml_view extends cs_page_view {

   private $_xml_array = array();

   /** constructor
    * the only available constructor, initial values for internal variables
    *
    * @param array params parameters in an array of this class
    */
   public function __construct ($params) {
      $this->cs_page_view($params);
   }

   /** adds a XML fragment
    * this method adds a xml fragment to the page
    *
    * @param string $value a XML fragment
    */
   public function add ( $value ) {
      $this->_xml_array[] = $value;
   }

   public function getContent () {
      header('Content-Type: text/xml; charset=utf-8');
      header('Pragma: no-cache');
      header('Expires: 0');

      $retour  = '';
      $retour .= '<?xml version="1.0" encoding="utf-8"?>'.LF;
      $retour .= '<commsy>'.LF;
      $retour .= '   <session_id><![CDATA['.$this->_environment->getSessionID().']]></session_id>'.LF;

      foreach ( $this->_xml_array as $value ) {
         $retour .= $value;
      }

      $retour .= '</commsy>';
      return $retour;
   }
}
?>