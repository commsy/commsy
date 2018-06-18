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
    * string - containing the content of the plugin view
    */
   private $_content = array();

   /**
    * string - containing the content of the plugin view
    */
   private $_content_before_body_end = array();

   /**
    * string - containing the data for html-head of the plugin view
    */
   private $_head = array();

   /**
    * string - containing the title of the plugin view
    */
   private $_title = NULL;

   /**
    * string - containing the icon of the plugin view
    */
   private $_icon = NULL;

   /**
    * boolean - display title or not
    */
   private $_display_title = true;

   /** constructor: cs_text_view
    * the only available constructor, initial values for internal variables
    *
    * @param array params parameters in an array of this class
    */
   public function __construct ($params) {
      cs_view::__construct($params);
   }

   /** set name of plugin
    * this method sets the name of the plugin
    *
    * @param string value name of the plugin
    */
   public function setName ($value) {
      $this->_name = (string)$value;
   }

   /** set content of plugin
    * this method sets the content of the plugin
    *
    * @param string value content of the plugin
    */
   public function addContent ($value) {
      $this->_content[] = (string)$value;
   }

   /** add things of the plugin integrate before body end tag
    * this method adds things like javascript to be integrated before body end tag
    *
    * @param string value content to be integrated before body end tag
    */
   public function addBeforeBodyEnd ($value) {
      $this->_content_before_body_end[] = (string)$value;
   }

   /** add things of the plugin integrate in HTML-header
    * this method adds things like javascript to be integrated in HTML-header
    *
    * @param string value text of the plugin view
    */
   public function addForHead ($value) {
      $this->_head[] = (string)$value;
   }

   /** set title of plugin view
    * this method sets the title of the plugin view
    *
    * @param string value title of the plugin view
    */
   public function setTitle ($value) {
      $this->_title = (string)$value;
   }

   /** set title icon of plugin view
    * this method sets the title icon of the plugin view
    *
    * @param string value title icon of the plugin view
    */
   public function setIcon ($value) {
      $this->_icon = (string)$value;
   }

   /** not display title
    * this method sets a flag so the title will not be shown
    */
   public function notDisplayTitle () {
      $this->_display_title = false;
   }

   /** get content of plugin as HTML
    * this method returns the content of the plugin in HTML-Code
    *
    * @return string content as HMTL
    */
   public function asHTML () {
      $html  = LF;
      $html .= '<!-- BEGIN OF PLUGIN '.$this->_name.' asHTML -->'.LF;
      if ($this->_display_title) {
         $html .= $this->_getTitleAsHTML();
      }
      if ( !empty($this->_content) ) {
         foreach ( $this->_content as $value ) {
            $html .= $value.LF;
         }
      }
      $html .= '<!-- END OF PLUGIN '.$this->_name.' asHTML -->'.LF.LF;
      return $html;
   }

   /** get things for HTML-header
    * this method returns things to integrate in HTML-header
    *
    * @return string things for HTML-header
    */
   public function getInfoForHeaderAsHTML () {
      $html  = LF;
      $html .= '<!-- BEGIN OF PLUGIN '.$this->_name.' getInfoForHeaderAsHTML -->'.LF;
      if ( !empty($this->_head) ) {
         foreach ( $this->_head as $value ) {
            $html .= '   '.$value.LF;
         }
      }
      $html .= '<!-- END OF PLUGIN '.$this->_name.' getInfoForHeaderAsHTML -->'.LF.LF;
      return $html;
   }

   /** get things to insert directly before body end tag
    * this method returns things to integrate before body end tag
    *
    * @return string things before body end tag
    */
   public function getContentForBeforeBodyEndAsHTML () {
      $html  = LF;
      $html .= '<!-- BEGIN OF PLUGIN '.$this->_name.' getContentForBeforeBodyEndAsHTML -->'.LF;
      if ( !empty($this->_content_before_body_end) ) {
         foreach ( $this->_content_before_body_end as $value ) {
            $html .= '   '.$value.LF;
         }
      }
      $html .= '<!-- END OF PLUGIN '.$this->_name.' getContentForBeforeBodyEndAsHTML -->'.LF.LF;
      return $html;
   }

   private function _getTitleAsHTML () {
      $retour = '';
      if ( !empty($this->_title) ) {
         $retour .= '<div style="vertical-align:bottom;">'.LF;
         $retour .= '   <h2 class="pagetitle">'.LF;
         if ( !empty($this->_icon) ) {
            $retour .= '      <img src="'.$this->_icon.'" style="vertical-align:bottom;"/>'.LF;
         }
         if ( !empty($this->_title) ) {
            $retour .= '      '.$this->_title.LF;
         }
         $retour .= '   </h2>'.LF;
         $retour .= '</div>'.LF;
      }
      return $retour;
   }
}
?>