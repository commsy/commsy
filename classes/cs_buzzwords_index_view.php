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

include_once('classes/cs_index_view.php');

/**
 *  class for CommSy list view: buzzword
 */
class cs_buzzwords_index_view extends cs_index_view {

   /** constructor
    * the only available constructor, initial values for internal variables
    *
    * @param object  environment            environment of the page
    * @param boolean with_modifying_actions true: display with modifying functions
    *                                       false: display without modifying functions
    */
   function cs_buzzwords_index_view ($environment, $with_modifying_actions) {
      $this->cs_index_view($environment, $with_modifying_actions);
      $user = $this->_environment->getCurrentUser();
      $html = '';

      $anAction = ahref_curl( $this->_environment->getCurrentContextID(),
                                 'material',
                                 'index',
                                 '',
                                 $this->_translator->getMessage('COMMON_BACK'));
      $this->addAction($anAction.' | ');

      // Edit and new section
      if ( $user->isModerator() and $with_modifying_actions ) {
         $anAction = ahref_curl( $this->_environment->getCurrentContextID(),
                                 'buzzwords',
                                 'edit',
                                 '',
                                 $this->_translator->getMessage('COMMON_EDIT'));
      } else {
         $anAction = '<span class="disabled">'.$this->_translator->getMessage('COMMON_EDIT').'</span>';
      }
      $this->addAction($anAction.' | ');
   }

   function _getTableheadAsHTML ($sign) {
      $html  = '<tr style="border:0px;">';
      $html .= '      <td style="padding-left: 3em; padding-top:30px; border:0px;"><h3 style="padding-bottom:0px;margin-bottom:0px;spacing-bottom:0px;">'.$this->_text_as_html_short($sign);
      $html .= '</h3></td>'.LF;
      $html .= '   </tr>'.LF;

      return $html;
   }


   /** get the item of the list view as HTML
    * this method returns the single item in HTML-Code
    *
    * overwritten method form the upper class
    *
    * @return string item as HMTL
    *
    * @author CommSy Development Group
    */
   function _getItemAsHTML($item) {
      $html  = '   <tr style="border:0px;">'.LF;
      $html .= '      <td style="padding-left: 4em;border:0px;">'.$this->_getItemTitle($item).'</td>'.LF;
      $html .= '   </tr>'.LF;

      return $html;
   }

   /** get the title of the item
    * this method returns the item title in the right formatted style
    *
    * @return string title
    *
    * @author CommSy Development Group
    */
   function _getItemTitle($item){
      $title = $item->getTitle();
      return $title;
   }

   /** get the content of the list view as HTML
    * this method returns the content in HTML-Code
    *
    * @return string $this->_list as HMTL
    *
    * @author CommSy Development Group
    */
   function _getContentAsHTML() {
      $html = '';
      $list = $this->_list;
      $first = true;
      if ( !isset($list) || $list->isEmpty() ) {
         return '<tr style="border:0px;"><td style="border:0px;">'.$this->_translator->getMessage('COMMON_NO_ENTRIES').'</td></tr>';
      } else {
         $current_item = $list->getFirst();
         $i = 0;
         while ( $current_item ) {
            if ($first){
               $sign = strtoupper(substr($current_item->getTitle(),0,1));
               $html .= $this->_getTableheadAsHTML($sign);
               $html .= $this->_getItemAsHTML($current_item);
               $first = false;
               $old_item = $current_item;
            } else {
               $current_item = $list->getNext();
               if ( $current_item ){
                  if ( strcasecmp(substr($old_item->getTitle(),0,1),substr($current_item->getTitle(),0,1))==0 ){
                     $html .= $this->_getItemAsHTML($current_item);
                  }else{
                     $html .= $this->_getTableheadAsHTML(strtoupper(substr($current_item->getTitle(),0,1)));
                     $html .= $this->_getItemAsHTML($current_item);
                  }
               }
               $old_item = $current_item;
            }
         }
      }
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
      $html  = "\n".'<!-- BEGIN OF LIST VIEW -->'."\n";
      // Actions
      $html .= '<div class="actions">'.LF;
      $actions = $this->_getActionsAsHTML(3);
      if ( !empty($actions) ) {
         $html .= $actions;
      }

      // Always show context sensitive help
      $params = array();
      $params['module'] = $this->_module;
      $params['function'] = $this->_function;
      $html .= ahref_curl($this->_environment->getCurrentContextID(), 'help', 'context',
                  $params,
                  $this->_translator->getMessage('HELP_LINK'), '', '_help', '', '',
                  'onclick="window.open(href, target, \'toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=yes, resizable=yes, copyhistory=yes, width=600, height=400\');"').LF;
      $html .= '</div>'.LF;

      // Heading
      $html .= LF.'<div class="indexform">'.LF;
      // Heading
      $html .= LF.'<h2 style="margin-bottom:10px; margin-top:0px;">'.LF.
               '   '.$this->_translator->getMessage('BUZZWORD_HEADER').LF.
               '</h2>'.LF;
      // Search / select form
      $html .= '</div>'.LF;

      $html .= '<table class="list" style="width:50%; border:0px;" summary="Layout" >'.LF;
      $html .= $this->_getContentAsHTML();
      $html .= '</table>'.LF;
      $html .= '<!-- END OF LIST VIEW -->'."\n\n";
      return $html;
   }
}
?>