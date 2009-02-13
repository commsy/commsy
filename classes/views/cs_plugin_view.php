<?PHP
// $Id$
//
// Release $Name$
//
// Copyright (c)2009 Iver Jackewitz
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

/** upper class of the text view
 */
$this->includeClass(VIEW);

/** class for a text view in commsy-style
 * this class implements a text view
 */
class cs_plugin_view extends cs_view {

   /**
    * string - containing the title of the text view
    */
   var $_name = NULL;

   /**
    * string - containing the description of the text view
    */
   var $_content = array();

   /**
    * string - containing the data (text) of the text view
    */
   var $_head = array();

   /** constructor: cs_text_view
    * the only available constructor, initial values for internal variables
    *
    * @param array params parameters in an array of this class
    */
   function cs_plugin_view ($params) {
      $this->cs_view($params);
   }

   /** set name of plugin
    * this method sets the name of the plugin
    *
    * @param string value name of the plugin
    */
   function setName ($value) {
      $this->_name = (string)$value;
   }

   /** set content of plugin
    * this method sets the content of the plugin
    *
    * @param string value content of the plugin
    */
   function addContent ($value) {
      $this->_content[] = (string)$value;
   }

   /** add things of the plugin integrate in HTML-header
    * this method adds things like javascript to be integrated in HTML-header
    *
    * @param string value text of the text view
    */
   function addForHead ($value) {
      $this->_head[] = (string)$value;
   }

   /** get content of plugin as HTML
    * this method returns the content of the plugin in HTML-Code
    *
    * @return string content as HMTL
    */
   function asHTML () {
      $html  = LF;
      $html .= '<!-- BEGIN OF PLUGIN '.$this->_name.' -->'.LF;
      if ( !empty($this->_content) ) {
         foreach ( $this->_content as $value ) {
            $html .= $value.LF;
         }
      }
      $html .= '<!-- END OF PLUGIN '.$this->_name.' -->'.LF.LF;
      return $html;
   }

   /** get things for HTML-header
    * this method returns things to integrate in HTML-header
    *
    * @return string things for HTML-header
    */
   function getInfoForHeaderAsHTML () {
      $html  = LF;
      $html .= '<!-- BEGIN OF PLUGIN '.$this->_name.' -->'.LF;
      if ( !empty($this->_head) ) {
         foreach ( $this->_head as $value ) {
            $html .= $value.LF;
         }
      }
      $html .= '<!-- END OF PLUGIN '.$this->_name.' -->'.LF.LF;
      return $html;
   }

}
?>