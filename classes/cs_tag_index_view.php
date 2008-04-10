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
class cs_tag_index_view extends cs_index_view {

   private $_root_item = NULL;

   /** constructor
    * the only available constructor, initial values for internal variables
    *
    * @param object  environment            environment of the page
    * @param boolean with_modifying_actions true: display with modifying functions
    *                                       false: display without modifying functions
    */
   function cs_tag_index_view ($environment, $with_modifying_actions) {
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

   public function setRootItem ( $value ) {
      $this->_root_item = $value;
   }

   /** get the item of the list view as HTML
    * this method returns the single item in HTML-Code
    *
    * overwritten method form the upper class
    *
    * @return string item as HMTL
    */
   function _getItemAsHTML($item, $ebene = 0) {
      $html  = '   <tr style="border:0px;">'.LF;
      $html .= '      <td style="padding-left: 4em;border:0px;">'.$this->_getItemTitle($item).$ebene.'</td>'.LF;
      $html .= '   </tr>'.LF;

      return $html;
   }

   /** get the title of the item
    * this method returns the item title in the right formatted style
    *
    * @return string title
    */
   function _getItemTitle($item){
      $title = $item->getTitle();
      return $title;
   }

   /** get the content of the list view as HTML
    * this method returns the content in HTML-Code
    *
    * @return string $this->_list as HTML
    */
   function _getContentAsHTML($item, $ebene = 0) {
      $html = '';
      if ( isset($item) ) {
         $list = $item->getChildrenList();
         if ( isset($list) and !$list->isEmpty() ) {
            $current_item = $list->getFirst();
            while ( $current_item ) {
               $html .= $this->_getItemAsHTML($current_item,$ebene);
               $html .= $this->_getContentAsHTML($current_item, $ebene+1);
               $current_item = $list->getNext();
            }
         }
      }
      return $html;
   }

   /** get list view as HTML
    * this method returns the list view in HTML-Code
    *
    * @return string list view as HMTL
    */
   function asHTML () {
      $html  = LF.'<!-- BEGIN OF TAG INDEX VIEW -->'.LF;

      // Heading
      $html .= LF.'<div class="indexform">'.LF;
      // Heading
      $html .= LF.'<h2 style="margin-bottom:10px; margin-top:0px;">'.LF.
               '   '.$this->_translator->getMessage('BUZZWORD_HEADER').LF.
               '</h2>'.LF;
      // Search / select form
      $html .= '</div>'.LF;

      $html .= '<table class="list" style="width:50%; border:0px;" summary="Layout" >'.LF;
      $html .= $this->_getContentAsHTML($this->_root_item);
      $html .= '</table>'.LF;
      $html .= '<!-- END OF TAG INDEX VIEW -->'."\n\n";
      return $html;
   }
}
?>