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

/** upper class of the errorbox
 */
$this->includeClass(TEXT_VIEW);

/** class for an errorbox in commsy-style
 * this class implements an errorbox, it is a special text_view
 */
class cs_helpbox_view extends cs_text_view {

   var $_links = array();
   var $rows = array();

   /** constructor: cs_helpbox_view
    * the only available constructor, initial values for internal variables
    *
    * @param array params parameters in an array of this class
    */
   function __construct($params) {
      $width="100%";
      if ( !empty($params['width']) ) {
         $width = $params['width'];
      }
      cs_text_view::__construct($params);
      $this->width = $width;
   }

   /** add an action to the box
    * this method adds an action (hyperlink) to the page view
    *
    * @param string  title        title of the action
    * @param string  explanantion explanation of the action
    * @param string  module       module of the action
    * @param string  function     function in module of the action
    * @param string  parameter    get parameter of the action
    *
    * @author CommSy Development Group
    */

   function addAction ($title, $explanation = '', $module = '', $function = '', $parameter = '') {
      $action['title'] = $title;
      $action['module'] = $module;
      $action['function'] = $function;
      $action['parameter'] = $parameter;
      $action['explanation'] = $explanation;
      $this->_links[] = $action;
   }

   /** get errorbox as HTML
    * this method returns the errorbox in HTML-Code
    *
    * @return string errorbox view as HMTL
    *
    * @author CommSy Development Group
    */
    function addRow($text) {
       $this->rows[] = $text;
    }

   function asHTML () {
      $html  = '';
      $html .= '<!-- BEGIN OF HELPBOX -->'."\n";
      $html .='<table style="width:100%; padding-bottom:30px;" summary="Layout">'.LF;
      $html .='<tr>'.LF;
      $html .='<td style="width:100%; padding-top:5px; vertical-align:bottom;">'.LF;
      $html .='<div>'.LF;

      // back link
      $links = array();
      if ( !empty($this->_links) ) {
         $html .= '<div class="actions" style="float: right;">'.LF;
         foreach ( $this->_links as $link ) {
            $links[] = ahref_curl($this->_environment->getCurrentContextID(), $link['module'],$link['function'],$link['parameter'],$link['title'],$link['explanation'],'_top');
         }
         $html .= implode(' | ', $links);
         $html .= '</div>'.LF;
      }
      if (!empty($this->_title)) {
         $html .= '<h2 class="pagetitle">'.$this->_title;
      }
      $html .= '</h2>'.LF;
      $html .='</div>'.LF;
      $html .='</td>'.LF;
      $html .='<tr>'.LF;
      $html .='<td class="infoborder" style="padding-top:10px; vertical-align:top;">'.LF;

      $html .='<div style="margin-bottom:10px;">'.LF;
      if (!empty($this->rows)) {
         $html .= '<table class="detail" style="text-align: left;" summary="Layout">'."\n";
            foreach($this->rows as $row) {
               $html .= '   <tr>'."\n";
               $html .= '      <td colspan=2 style="padding-top: 10px;">'.$row.'</td>'."\n";
               $html .= '   </tr>'."\n";
            }
         $html .= '</table>'."\n";
      }
      $html .='</div>'.LF;
      $html .='</td>'.LF;
      $html .='</tr>'.LF;
      $html .='</table>'.LF;


         return $html;
   }
}
?>