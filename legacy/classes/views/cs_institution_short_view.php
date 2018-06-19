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

$this->includeClass(HOME_VIEW);
include_once('functions/text_functions.php');

/**
 *  class for CommSy list view: institution
 */
class cs_institution_short_view extends cs_home_view {

   /** constructor
    * the only available constructor, initial values for internal variables
    *
    * @param array params parameters in an array of this class
    */
   function __construct($params) {
      cs_home_view::__construct($params);
      $title = ahref_curl( $this->_environment->getCurrentContextID(),
                           CS_INSTITUTION_TYPE,
                           'index',
                           '',
                           $this->_translator->getMessage('COMMON_INSTITUTION_INDEX'),'','','','','','','class="head"');
      $this->setViewTitle($title);
   }


   /** get the description of the list view title as HTML
    * this method returns the description in HTML-Code
    *
    * @return string $this->_description as HMTL
    *
    * @author CommSy Development Group
    */
   function _getDescriptionAsHTML() {
      $all = $this->getCountAll();
      if ($all > 0) {
         $list = $this->getList();
         $shown = $list->getCount();
         return ' ('.$this->_translator->getMessage('HOME_INSTITUTION_SHORT_VIEW_DESCRIPTION', $shown).')';
      } else {
         return '';
      }
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
      $list = $this->getList();
      if ( !isset($list) || $list->isEmpty() ) {
         $html .= '<tr class="list"><td colspan="2">'.$this->_translator->getMessage('COMMON_NO_INSTITUTIONS').'</td></tr>';
      } else {

         // Put items into an array representing the
         // future table layout
         $count = $list->getCount();
         $num_cols = 2;
         $num_rows = ceil($count/$num_cols);
         $layout_array = array();
         $item = $list->getFirst();
         for ( $col=1; $col<=$num_cols; $col++ ) {
            for ( $row=1; $row<=$num_rows; $row++ ) {
               if ( $item ) {
                  $layout_array[$row][$col] = $this->_getItemTitle($item);
                  $item = $list->getNext();
               } else {
                  $layout_array[$row][$col] = '';
               }
            }
         }

         // Print out the table
         $r = 0;
         foreach ( $layout_array as $row ) {
            $r++;
            if ($r%2 == 0){
               $style='class="even"';
            }else{
               $style='class="odd"';
            }
            $html .= '<tr class="list">'.LF;
            foreach ( $row as $institution ) {
               if ( $r < $num_rows ) {
                  $html .= '   <td  '.$style.' width="50%">';
               } else {
                  $html .= '   <td  '.$style.' width="50%">';
               }
               $html .= $institution.'</td>'.LF;
            }
            $html .= '</tr>'.LF;
         }
      }
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
      $title = $this->_text_as_html_short($item->getName());
      $params = array();
      $params['iid'] = $item->getItemID();
      $title = ahref_curl( $this->_environment->getCurrentContextID(),
                           CS_INSTITUTION_TYPE,
                           'detail',
                           $params,
                           $title);
      unset($params);
      if ( !$this->_environment->inPrivateRoom() ) {
         $title .= $this->_getItemChangeStatus($item);
         $title .= $this->_getItemAnnotationChangeStatus($item);
      }
      return $title;
   }
}
?>