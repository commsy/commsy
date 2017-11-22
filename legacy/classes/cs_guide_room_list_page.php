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

include_once('classes/cs_page.php');

/** class for commsy forms
 * this class implements an interface for the creation of forms in the commsy style
 */
class cs_guide_room_list_page extends cs_page {

  /** constructor
    * the only available constructor
    *
    * @param object environment the environment object
    */
   function __construct($environment, $with_mod_actions) {
      cs_page::__construct($environment, $with_mod_actions);
   }

   function _generateViewObject () {
      $current_user_item = $this->_environment->getCurrentUserItem();

      $selroom = '';
      $sel_archive_room = '';
      $current_context = $this->_environment->getCurrentContextItem();
      // Find current browsing starting point
     if ( isset($this->_values['from']) ) {
        $from = $_GET['from'];
     }  else {
        $from = 1;
     }

     // Find current browsing interval
     if ( isset($this->_values['interval']) ) {
        $interval = $this->_values['interval'];
     } else {
        if ( $current_context->isPortal() ) {
           $interval = $current_context->getNumberRoomsOnHome();
        }
        if ( empty($interval) ) {
           $interval = 20;
        } elseif ($interval == 'all') {
           $interval = '';
        }
     }

      if ($this->_environment->inPortal()) {
         if (!empty($this->_values['selroom'])) {
            $selroom = $this->_values['selroom'];
            $show_rooms = '';
            if ( $selroom == 3 ) {
               $show_rooms = 'onlyprojectrooms';
            } elseif ( $selroom == 4 ) {
               $show_rooms = 'onlycommunityrooms';
            }
         } else {
            $selroom = '';
            $show_rooms = '';
         }

         if (!empty($this->_values['sel_archive_room'])) {
            $sel_archive_room = $this->_values['sel_archive_room'];
         }else {
            $sel_archive_room = '';
         }



         if (!empty($this->_values['seltime'])) {
            $seltime = $this->_values['seltime'];
            if ($seltime == -2 or $seltime == -3) {
               $seltime = '';
            }
         }

		 // get data
		 $manager = $this->_environment->getRoomManager();
		 $manager->setContextLimit($this->_environment->getCurrentContextID());
		 $show_rooms_save = $current_context->getShowRoomsOnHome();
		 $show_rooms = $show_rooms_save;
		 if ( empty($selroom)
		      and !empty($show_rooms)
		      and $show_rooms_save == 'preselectmyrooms'
		      and $this->_environment->getCurrentUserItem()->isUser()
		    ) {
		    $selroom = 5;
		    $show_rooms = $show_rooms_save;
		 }
		 if ($show_rooms == 'onlycommunityrooms'){
		    $manager->setRoomTypeLimit(CS_COMMUNITY_TYPE);
		 } elseif ($show_rooms == 'onlyprojectrooms'){
		    $manager->setRoomTypeLimit(CS_PROJECT_TYPE);
		 }

		 // archive limit
		 if (!empty($sel_archive_room) && $sel_archive_room == 1) {					// archived rooms
		 	$manager->setArchiveLimit();
		 	$count_all = $current_context->getCountArchivedProjectAndCommunityRooms();	
		 } else {																	// usable rooms or parameter missing or unknown value
		 	$manager->setOpenedLimit();
		 	
		 	// changes for count room redundancy - room count from portal extra
		 	if ($show_rooms == 'onlycommunityrooms') {
		 		$count_all = $current_context->getCountCommunityRooms();
		 	} elseif ($show_rooms == 'onlyprojectrooms') {
		 		$count_all = $current_context->getCountProjectRooms();
		 	} else {
		         if ($current_context->isPortal() && !$current_context->showTemplatesInRoomList()) {
		         	$count_all = $current_context->getCountProjectAndCommunityRoomsWithoutTemplates();
		         } else {
		 		 	$count_all = $current_context->getCountProjectAndCommunityRooms();
		         }
		 	}
		 }
         
      	// template limit -> not show templates
         if ( $current_context->isPortal()
         	  and !$current_context->showTemplatesInRoomList()
         	) {
         	$manager->setNotTemplateLimit();
         }
         
         if (!empty($selroom)) {
            if ($selroom == 3) {
               $manager->setRoomTypeLimit(CS_PROJECT_TYPE);
            } elseif ($selroom == 4) {
               $manager->setRoomTypeLimit(CS_COMMUNITY_TYPE);
            } elseif ($selroom == 5) {
               $current_user = $this->_environment->getCurrentUser();
               $manager->setUserIDLimit($current_user->getUserID());
               $manager->setAuthSourceLimit($current_user->getAuthSource());
            } elseif ($selroom == 6) {
               $manager->setRoomTypeLimit(CS_GROUPROOM_TYPE);
		         if ( !empty($sel_archive_room) 
		              and $sel_archive_room == 1
		            ) {
		            $count_all = $current_context->getCountArchivedGroupRooms();
		         } else {
	               $count_all = $current_context->getCountGroupRooms();
		         }
            } elseif ($selroom == 9) {
               $manager->setDeletedLimit();
               $count_all = $manager->getCountAll();
            }
         }elseif ($show_rooms == 'preselectcommunityrooms'){
            $manager->setRoomTypeLimit(CS_COMMUNITY_TYPE);
         }
         if (!empty($seltime)) {
            $manager->setTimeLimit($seltime);
         }
         if (!empty($this->_values['search'])) {
            $manager->setSearchLimit($this->_values['search']);
         }
         if (!empty($this->_values['sort'])) {
            $manager->setOrder($this->_values['sort']);
         } elseif($current_context->isSortRoomsByTitleOnHome()){
            $manager->setOrder('title');
         }else {
            $manager->setOrder('activity_rev');
         }
         
         $ids = $manager->getIDArray();
         $count_all_shown = count($ids);
         
         if ( empty($interval) ) {
            $interval = count($ids);
         }
         if ( $interval > 0
              and empty($this->_values['activitymodus'])
            ) {
            $manager->setIntervalLimit($from-1,$interval);
            $manager->setQueryWithoutExtra();
            $manager->select();
            $list = $manager->get();
            
            // add archived rooms to list
            if(isset($archive_list) and !empty($archive_list)){
            	$list->addList($archive_list);
            }

         } else {
            # sortby log-table, not by activity points
            # not performant (ij 26.01.2010)
            $list = new cs_list();
            for( $i = $from-1; $i<($interval+$from);$i++){
               if (isset($ids[$i])){
                  $item = $manager->getItem($ids[$i]);
                  $list->add($item);
               }
            }
         }
      } elseif ($this->_environment->inServer()) {
         $context_item = $this->_environment->getCurrentContextItem();
         $list = $context_item->getPortalListByActivity();
      }

      // Prepare view object
      $params = array();
      $params['environment'] = $this->_environment;
      $params['with_modifying_actions'] = $this->_with_mod_actions;
      $this->_view_object = $this->_class_factory->getClass(LIST_GUIDE_VIEW,$params);
      unset($params);
      if ( !empty($this->_values['activitymodus'])
           and is_numeric($this->_values['activitymodus'])
         ) {
         $this->_view_object->setActivityModus($this->_values['activitymodus']);
      }
      $this->_view_object->setList($list);
      if ($this->_environment->inPortal()) {
         $this->_view_object->setCountAllShown($count_all_shown);
         $this->_view_object->setCountAll($count_all);
         $this->_view_object->setFrom($from);
         $this->_view_object->setInterval($interval);
      }
      if (!empty($selroom)) {
         $this->_view_object->setSelectedRoom($selroom);
      }elseif(isset($show_rooms) and $show_rooms == 'preselectcommunityrooms'){
         $this->_view_object->setSelectedRoom('4');
      }
      if (!empty($sel_archive_room)) {
         $this->_view_object->setSelectedArchiveRoom($sel_archive_room);
      }
      if (!empty($seltime)) {
         $this->_view_object->setSelectedTime($seltime);
      }
      if (!empty($this->_values['search'])) {
         $this->_view_object->setSearchText($this->_values['search']);
      }
      if ( !empty($this->_values['activitymodus'])
           and is_numeric($this->_values['activitymodus'])
         ) {
         $this->_view_object->setSortKey('activity_rev');
      } elseif (!empty($this->_values['sort'])) {
         $this->_view_object->setSortKey($this->_values['sort']);
      } elseif($current_context->isPortal() and $current_context->isSortRoomsByTitleOnHome()){
         $this->_view_object->setSortKey('title');
      }elseif ($this->_environment->inPortal()) {
         $this->_view_object->setSortKey('activity_rev');
      }
      if (!empty($this->_values['room_id'])) {
         $this->_view_object->setSelectedContext($this->_values['room_id']);
      }
      if (!empty($this->_values['iid'])) {
         $this->_view_object->setSelectedID($this->_values['iid']);
      }
      
      unset($current_user_item);
 
   }
}
?>