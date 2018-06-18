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

/** upper class of the text view
 */
$this->includeClass(VIEW);

/** class for a text view in commsy-style
 * this class implements a text view
 */
class cs_text_view extends cs_view {

   /**
    * string - containing the title of the text view
    */
   var $_title = NULL;

   /**
    * string - containing the description of the text view
    */
   var $_description = NULL;

   /**
    * string - containing the data (text) of the text view
    */
   var $_text = NULL;

   /** constructor: cs_text_view
    * the only available constructor, initial values for internal variables
    *
    * @param array params parameters in an array of this class
    */
   function __construct($params) {
      cs_view::__construct($params);
   }

   /** set title of text view
    * this method sets the title of the text view
    *
    * @param string value title of the text view
    */
   function setTitle ($value) {
      $this->_title = (string)$value;
   }

   /** set description of text view
    * this method sets the text of the detail view
    *
    * @param string value description of the text view
    */
   function setDescription ($value) {
      $this->_description = (string)$value;
   }

   /** set text of text view
    * this method sets the data (text) of the text view
    *
    * @param string value text of the text view
    */
   function setText ($value) {
      $this->_text = (string)$value;
   }

   /** get text view as HTML
    * this method returns the text view in HTML-Code
    *
    * @return string text view as HMTL
    */
   function asHTML () {
      $html  = '';
      $html .= '<!-- BEGIN OF TEXTVIEW -->'.LF;
      if (!empty($this->_anchor)) {
         $html .= '<a name="'.$this->_anchor.'"></a>'.LF;
      }
      $html .= '<table border="0" cellspacing="1" cellpadding="3" width="100%" summary="Layout">'.LF;
      if (!empty($this->_title)) {
         $html .= '   <tr><td>'.LF;
         $html .= '      <b>'.$this->_text_as_html_short($this->_title).'</b>'.LF;
         if (!empty($this->_description)) {
            $html .= '      <span class="small">('.$this->_text_as_html_short($this->_description).')</span>'.LF;
         }
         $html .= '   </td></tr>'.LF;
      }
      $html .= '   <tr>'.LF;
      $html .= '      <td>'.$this->_text_as_html_long($this->_text).'</td>'.LF;
      $html .= '   </tr>'.LF;
      $html .= '</table>'.LF;
      $html .= '<!-- END OF TEXTVIEW -->'.LF.LF;
      return $html;
   }
}
?>