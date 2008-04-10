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
include_once('classes/cs_text_view.php');

/** class for an errorbox in commsy-style
 * this class implements an errorbox, it is a special text_view
 */
class cs_successbox_view extends cs_text_view {


   var $_width = false;

   /** constructor: cs_errorbox_view
    * the only available constructor, initial values for internal variables
    *
    * @param object  environment            commsy environment
    * @param boolean with_modifying_actions true: display with modifying functions
    *                                       false: display without modifying functions
    */
   function cs_successbox_view ($environment, $with_modifying_actions, $width=false) {
      if ($width) {
         $this->_width = $width;
      }
      $this->cs_text_view($environment, $with_modifying_actions);
      $this->setTitle($this->_translator->getMessage('COMMON_SUCCESSBOX_TITLE'));
   }


   /** get errorbox as HTML
    * this method returns the errorbox in HTML-Code
    *
    * @return string errorbox view as HMTL
    *
    * @author CommSy Development Group
    */
   function asHTML () {
      if ($this->_width) {
         $text = ' width: '.$this->_width.'px;';
      } else {
         $text = '';
      }

      $room_item = $this->_environment->getCurrentContextItem();
      $color = $room_item->getCSColor();

      $html  = '';
      $html .= '<!-- BEGIN OF SUCCESSBOX -->'.LF;
      $html .= '<center>'.LF;
      $html .= '<table style=" border: 2px solid '.$color.'; margin: 5px;'.$text.'" summary="Layout">'.LF;
      if (!empty($this->_title)) {
         $html .= '   <tr><td style="text-align: left;">'.LF;
         $html .= '<span style="font-size: large;">'.$this->_title.'</span>'.LF;
         if (!empty($this->_description)) {
            $html .= '      <span>('.$this->_description.')</span>'.LF;
         }
         $html .= '   </td></tr>'.LF;
      }
      $html .= '   <tr>'."\n";
      $html .= '      <td style="text-align: left;">'.$this->_text.'</td>'.LF;
      $html .= '   </tr>'.LF;
      $html .= '</table>'.LF;
      $html .= '</center>'.LF;
      $html .= '<!-- END OF SUCCESSBOX -->'.LF.LF;
      return $html;
   }
}
?>