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

include_once('classes/cs_list_view.php');
include_once('classes/cs_reader_manager.php');
include_once('functions/text_functions.php');

/**
 *  class for list view: link (internal, preferences)
 */
class cs_link_list_view extends cs_list_view {

   /** constructor
    * the only available constructor, initial values for internal variables
    *
    * @param object  environment            environment of commsy
    * @param string  viewname               e.g. link_list
    * @param boolean with_modifying_actions true: display with modifying functions
    *                                       false: display without modifying functions
    *
    * @author CommSy Development Group
    */
   function cs_link_list_view ($environment, $viewname, $with_modifying_actions) {
      $this->cs_list_view($environment,$viewname,$with_modifying_actions);
      $this->setTitle($this->_translator->getMessage('PREFERENCES_TITLE'));
   }

   /** get the item of the list view as HTML
    * this method returns the single item in HTML-Code
    *
    * overwritten method form the upper class
    *
    * @return string item as HMTL
    */
   function _getItemAsHTML ($item) {

      $context_item = $this->_environment->getCurrentContextItem();
      $html = '                     <tr><td width="30%" style="vertical-align: baseline;">'.LF;
      if ( !$context_item->isClosed()
	  or ( stristr($item->getLink(),'configuration')
	       and stristr($item->getLink(),'preferences')
	     )
	) {
         $html .= '                        '.$item->getLink().LF;
      } else {
         $html .= '                        '.$item->getTitle().LF;
      }
      $html .= '                     </td>'.LF;

      $html .= '                     <td width="70%" style="vertical-align: baseline;">'.LF;
      $html .= '                        '.$item->getDescription().LF;
      $html .= '                     </td></tr>'.LF;
      return $html;
   }

   /** get list view as HTML
    * this method returns the list view in HTML-Code
    *
    * @return string list view as HMTL
    *
    * @author CommSy Development Group
    */
   function asHTML () {
      $html  = LF.'<!-- BEGIN OF LIST VIEW -->'.LF;
      // Actions
      $html .= '<div class="actions">'.LF;
      // Always show context sensitive help
      $params = array();
      $params['module'] = $this->_module;
      $params['function'] = $this->_function;
      $html .= ahref_curl($this->_environment->getCurrentContextID(), 'help', 'context',
                  $params,
                  $this->_translator->getMessage('HELP_LINK'), '', '_help', '', '',
                  'onclick="window.open(href, target, \'toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=yes, resizable=yes, copyhistory=yes, width=600, height=400\');"').LF;
      unset($params);
      $html .= '</div>'.LF;
      $html .= LF.'<div class="indexform">'.LF;
      $html .= '<h2 style="margin-bottom:10px; margin-top:0px;">'."\n";
      $html .= $this->_title;
      if (!empty($this->_description)) {
         $html .= ' ('.$this->_description.')';
      }
      $html .= '</h2>'."\n";
      $html .= '<table class="list" summary="Layout">'."\n";

      if ( $this->hasContent() ) {
         $list = $this->_list;
         if ( isset($list)) {
            $current_item = $list->getFirst();
            while ( $current_item ) {
               $item_text = $this->_getItemAsHTML($current_item);
               $html .= $item_text;
               $current_item = $list->getNext();
            }
         }
      }
      $html .= '</table>'."\n";
      $html .='</div>';
      $html .= '<!-- END OF LIST VIEW -->'."\n\n";
      return $html;
   }




}
?>