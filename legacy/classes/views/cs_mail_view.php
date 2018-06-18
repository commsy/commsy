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

$this->includeClass(TEXT_VIEW);

/**
 *  class for CommSy mail view: group
 */
class cs_mail_view extends cs_text_view {

   /** constructor
    * the only available constructor, initial values for internal variables
    *
    * @param array params parameters in an array of this class
    */
   function __construct($params) {
      cs_text_view::__construct($params);
      $this->setTitle($this->_translator->getMessage('SEND_MAIL_DESC'));
   }

 /** this method sets the formal data that is to be displayed in the text box
   *
   * @param array formal data
   *
   * @author CommSy Development Group
   */
   function setFormalData($data_array) {
      $this->setText($this->_getFormalDataAsHTML($data_array));
   }

   /** this method returns the data set by setFormalData() as HTML
   *  internal - called by asHTML()
   *
   * @return string formal data as HMTL
   *
   * @author CommSy Development Group
   */
   function _getFormalDataAsHTML($data) {
      $html  = '<table class="formal"  summary="Layout">'."\n";
      $html .= '   <tr><td colspan="2">&nbsp;</td></tr>';

      foreach ($data as $value) {
         if ( ($value[0] == $this->_translator->getMessage('MAIL_SUBJECT')) || ($value[0] == $this->_translator->getMessage('COMMON_MAIL_CONTENT').":")) {
      $html .= '   <tr><td colspan="2">&nbsp;</td></tr>';
   }
   $html .= '   <tr>'.LF;
   $html .= '      <td class="formal_key" align="right" valign="top">'.LF;
   $html .= '         '.$this->_text_as_html_short_coding_format($value[0]).'&nbsp;&nbsp;'.LF;
   $html .= '      </td>'.LF;
   $html .= '      <td class="formal_value" align="left" valign="top" >'.LF;
   if ( $value[0] != $this->_translator->getMessage('MAIL_BODY')
              and $value[0] != $this->_translator->getMessage('COMMON_MAIL_CONTENT').':') {
      if ($value[0] == $this->_translator->getMessage('MAIL_TO')) {
         $html .= '         '.str_replace(',',BRLF, $value[1]).'&nbsp;'.LF;
      } else {
         $html .= '         '.$value[1].'&nbsp;'.LF;
      }
   } else {
      $html .= '         '.$this->_text_as_html_long($value[1]).LF;
   }
   $html .= '      </td>'.LF;
   $html .= '   </tr>'.LF;
      }
      $html .= '   <tr><td colspan="2">&nbsp;</td></tr>';
      $html .= '</table>'."\n";

      return $html;
   }

   /** get view as HTML
    * this method returns the group_mail_view in HTML-Code
    *
    * @return string group_mail_view view as HMTL
    *
    * @author CommSy Development Group
    */
   function asHTML () {
      $html  = '';
      $html .= '<!-- BEGIN OF MAIL VIEW -->'."\n";
//      $html .= '<center>';
      if (!empty($this->_title)) {
         $html .= '   <div id="mail_headline">'."\n";
         $html .= '      <h2 style="font-size:14pt; padding:3px;">'.$this->_title.'</h2>'."\n";
         if (!empty($this->_description)) {
            $html .= '      <span>'.$this->_description.':</span>'."\n";
         }
         $html .= '   </div>';
      }
      $html .= '<div id="mail_content" style="background-color:#FFFFFF;" summary="Layout">'."\n";
      $html .= $this->_text;
      $html .= '   </div>'."\n";
//      $html .= '</center>';
      $html .= '<!-- END OF MAIL VIEW -->'."\n\n";
      return $html;
   }
}
?>