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

include_once('classes/cs_home_view.php');
include_once('functions/text_functions.php');

/**
 *  class for CommSy list view: user
 */
class cs_user_short_view extends cs_home_view {

   /** constructor
    * the only available constructor, initial values for internal variables
    *
    * @param object  environment            environment of the page
    * @param string  viewname               e.g. user_index
    * @param boolean with_modifying_actions true: display with modifying functions
    *                                       false: display without modifying functions
    *
    * @author CommSy Development Group
    */
   function cs_user_short_view ($environment, $with_modifying_actions) {
      $this->cs_home_view($environment, $with_modifying_actions);
      $title = ahref_curl( $this->_environment->getCurrentContextID(),
                           CS_USER_TYPE,
                           'index',
                           '',
                           $this->_translator->getMessage('COMMON_USER_INDEX'),'','','','','','','class="head"');
      $this->setViewTitle($title);
   }

   /** get the description of the list view title as HTML
    * this method returns the description in HTML-Code
    *
    * @return string $this->_description as HMTL
    */
   function _getDescriptionAsHTML() {
      if ($this->_environment->inProjectRoom()){
      global $who_is_online;
	   if (isset($who_is_online) and $who_is_online) {
		 $retour = '';
         $list = $this->getList();
         $shown = $list->getCount();

		 if ($shown > 0) {
			 $context_item = $this->_environment->getCurrentContextItem();
			 if ($context_item->isProjectRoom()) {
				$days = $context_item->getTimeSpread();
			 } else {
				$days = 90;
			 }
			 $item = $list->getFirst();
			 $count_active_now = 0;
			 $this->_user_active_now_array = array();
			 while ($item) {
				 $lastlogin = $item->getLastLogin();
				 if ($lastlogin > getCurrentDateTimeMinusMinutesInMySQL($days)) {
			        $count_active_now++;
					$this->_user_active_now_array[] = $item->getItemID();
				 }
				 $item = $list->getNext();
			 }
		 }

		 $retour = ' ('.$this->_translator->getMessage('HOME_USER_SHORT_VIEW_DESCRIPTION2', $shown, $count_active_now, $this->_count_all,$days).')';
		 return $retour;
	  } else {
         $list = $this->getList();
         $shown = $list->getCount();
         return ' ('.$this->_translator->getMessage('HOME_USER_SHORT_VIEW_DESCRIPTION', $shown).')';
	  }
     }else{
      $all = $this->getCountAll();
      $list = $this->getList();
      $shown = $list->getCount();
      $context = $this->_environment->getCurrentContextItem();
      return ' ('.$this->_translator->getMessage('COMMON_SHORT_CONTACT_VIEW_DESCRIPTION',$shown,$all).')';
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
         $html .= '<tr><td>'.$this->_translator->getMessage('COMMON_NO_CONTENT').'</td></tr>';

      } else {

         // Put items into an array representing the
         // future table layout
         $count = $list->getCount();
         $num_cols = 3;
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
            foreach ( $row as $user ) {
               if ( $r < $num_rows ) {
                  $html .= '   <td  '.$style.' width="33%">';
               } else {
                  $html .= '   <td  '.$style.' width="33%">';
               }
               $html .= $user.'</td>'.LF;
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
	   global $who_is_online;
      $title = $item->getFullname();
	  if (isset($who_is_online) and $who_is_online) {
		  if (in_array($item->getItemID(),$this->_user_active_now_array)) {
			  $title = '<span style="font-weight: bold;">'.$title.'</span>';
		  }
	  }
      $params = array();
      $params['iid'] = $item->getItemID();;
      $title = ahref_curl( $this->_environment->getCurrentContextID(),
                           'user',
                           'detail',
                           $params,
                           $title);
      unset($params);
      if ($this->_environment->inProjectRoom()) {
         $title .= $this->_getItemChangeStatus($item);
      }
      return $title;
   }
}
?>