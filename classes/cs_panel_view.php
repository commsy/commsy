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

include_once('classes/cs_view.php');
include_once('functions/text_functions.php');

/**
 *  class for list view: link (internal, preferences)
 */
class cs_panel_view extends cs_view {

   var $_title = '';   // string holding the title

   var $_description = ''; // string holding a description that is displayed next to the title

   var $_panel = array(); // two-dimensional array holding the options
                          // as row X col

   /** constructor
    * the only available constructor, initial values for internal variables
    *
    * @param object  environment            environment of commsy
    * @param boolean with_modifying_actions true: display with modifying functions
    *                                       false: display without modifying functions
    */
   function cs_panel_view ($environment, $with_modifying_actions) {
      $this->cs_view( $environment,$with_modifying_actions);
   }

   function setTitle ($title) {
      $this->_title = (string)$title;
   }

   function getTitle () {
      return $this->_title;
   }

   function setDescription ($description) {
      $this->_description = (string)$description;
   }

   function getDescription () {
      return $this->_description;
   }

   function resetPanel () {
      $this->_panel = array();
   }

   function addPanelItem ($col, $row, $url, $title, $description, $icon_link) {
      $panel_item = array();
      $panel_item['title'] = (string)$title;
      $panel_item['url'] = (string)$url;
      $panel_item['description'] = (string)$description;
      $panel_item['icon_link'] = (string)$icon_link;
      $this->_panel[$row][$col] = $panel_item;
   }

   function getPanelArray () {
      return $this->_panel;
   }

   function asHTML () {
      $html  = LF.'<!-- BEGIN OF PANEL VIEW -->'.LF;

      // Heading
      $html .= '<h2>'.LF.
               '   '.$this->_text_as_html_short($this->getTitle()).LF;
      $desc = $this->getDescription();
      if ( !empty($desc) ) {
         $html .= '    <span style="font-size:10pt;font-weight:normal">('.
                  $this->_text_as_html_short($desc).')</span>'.LF;
      }
      $html .= '</h2>'.LF;

      // Panel
      $panel = $this->getPanelArray();
      if ( !empty($panel) ) {

         // get maximum number of cols
         $num_cols = 0;
         foreach ( $panel as $row ) {

            if ( count($row) > $num_cols ) {
               $num_cols = count($row);
            }
         }

         // print out the panel
         reset($panel);
         $html .= '<table class="panel" summary="Layout">'.LF;
         foreach ( $panel as $row) {
            $html .= '   <tr>'.LF;
            for ( $i=1; $i<=$num_cols; $i++ ) {
               if ( isset($row[$i]) ) {
                  $item = $row[$i];
                  $html .= '      <td>'.LF;
                  if ( !empty($item['icon_link']) ) {
                     $html .= '         <img src="'.$item['icon_link'].'" />'.LF;
                  }
                  $html .= '      </td><td>'.LF;
                  $html .= '         <a href="'.$item['url'].'">'.$this->_text_as_html_short($item['title']).'</a>'.BRLF;
                  $html .= '         '.$this->_text_as_html_short($item['description']).LF;
                  $html .= '      </td>'.LF;
               } else {
                  $html .= '      <td colspan="2">'.LF;
                  $html .= '      </td>'.LF;
               }
            }
         }
         $html .= '</table>'.LF;
      }
      $html  .= '<!-- END OF PANEL VIEW -->'.LF.LF;
      return $html;
   }
}
?>