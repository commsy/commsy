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

/** upper class of the form view
 */
$this->includeClass(VIEW);

/** class for a form view in commsy-style
 * this class implements a form view
 */
class cs_privateroom_home_released_entries_view extends cs_view {

   /** constructor
    * the only available constructor, initial values for internal variables
    *
    * @param array params parameters in an array of this class
    */
   function cs_privateroom_home_released_entries_view ($params) {
      $this->cs_view($params);
      $this->setViewName('note');
      $user = $this->_environment->getCurrentUserItem();
      $room = $this->_environment->getCurrentContextItem();
      $this->_view_title = $this->_translator->getMessage('COMMON_RELEASED_ENTRIES_PORTLET');
   }

   function asHTML(){
   	$room_id = $this->_environment->getCurrentContextID();
   	$user = $this->_environment->getCurrentUser();
   	$item_manager = $this->_environment->getItemManager();
   	$released_ids = $item_manager->getExternalViewerEntriesForRoom($room_id);
   	$viewable_ids = $item_manager->getExternalViewerEntriesForUser($user->getUserID());
   	
   	$select_ids = array_merge($released_ids, $viewable_ids);
   	
   	$material_manager = $this->_environment->getMaterialManager();
   	$material_list = $material_manager->getItemList($select_ids);
      
      $html = '';
      $html .= '<div id="'.get_class($this).'">'.LF;
      $html .= '<b>'.$this->_translator->getMessage('COMMON_RELEASED_ENTRIES_FOR_OTHER_USERS').':</b><br/>'.LF;
      if(!empty($released_ids)){
         $released_material_item = $material_list->getFirst();
	      while($released_material_item){
	      	if(in_array($released_material_item->getItemID(), $released_ids)){
	      	  $html .= ahref_curl($released_material_item->getContextID(), 'material', 'detail', array('iid' => $released_material_item->getItemID()), $released_material_item->getTitle()).'</a><br/>';
	      	}
	      	$released_material_item = $material_list->getNext();
	      }
      } else {
      	$html .= $this->_translator->getMessage('COMMON_NO_ENTRIES').LF;
      	$html .= '<br/>';
      }
      $html .= '<br/>';
      $html .= '<b>'.$this->_translator->getMessage('COMMON_RELEASED_ENTRIES_FOR_CURRENT_USER').':</b><br/>'.LF;
      if(!empty($viewable_ids)){
	      $viewable_material_item = $material_list->getFirst();
	      while($viewable_material_item){
	      	if(in_array($viewable_material_item->getItemID(), $viewable_ids)){
	            $html .= ahref_curl($viewable_material_item->getContextID(), 'material', 'detail', array('iid' => $viewable_material_item->getItemID()), $viewable_material_item->getTitle()).'</a><br/>';
	      	}
	      	$viewable_material_item = $material_list->getNext();
	      }
      } else {
      	$html .= $this->_translator->getMessage('COMMON_NO_ENTRIES').LF;
      }
      $html .= '</div>'.LF;
      return $html;
   }
}
?>