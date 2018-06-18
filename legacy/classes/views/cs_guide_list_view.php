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

$this->includeClass(LIST_PLAIN_VIEW);
include_once('functions/curl_functions.php');

/**
 *  class for CommSy list view: commsys
 */
class cs_guide_list_view extends cs_list_view_plain {

   var $_max_activity = 0;

   var $_selected_room = 0;

   var $_selected_archive_room = 0;

   var $_selected_time = 0;

   var $_selected_context = 0;

   var $_selected_iid = NULL;

   /**
    * int - begin of list
    */
   var $_from = NULL;

   /**
    * int - length of shown list
    */
   var $_interval = NULL;

   /**
    * int - length of whole list
    */
   var $_count_all = NULL;
   var $_count_all_shown = NULL;

   var $_activity_modus = NULL;

    /** constructor
    * the only available constructor, initial values for internal variables
    *
    * @param array params parameters in an array of this class
    */
   function __construct($params) {
      $params['viewname'] = 'guide_list_view';
      $this->cs_plain_list_view($params);
      if ( $this->_environment->inServer() ) {
         $manager = $this->_environment->getPortalManager();
         $this->_max_activity = $manager->getMaxActivityPoints();
      } elseif ( $this->_environment->inPortal() ) {
         #$manager = $this->_environment->getRoomManager();
         #$this->_max_activity = $manager->getMaxActivityPoints();
         // maybe get max activity out of portal item?
         $portal = $this->_environment->getCurrentContextItem();
         $this->_max_activity = $portal->getMaxRoomActivityPoints();
         unset($portal);
      } else {
         $manager = $this->_environment->getRoomManager();
         $this->_max_activity = $manager->getMaxActivityPoints();
      }
   }

   /** set special activity mode by day
    * this method sets the special activity modus: days of ppage impressions
    *
    * @param int  $value          days [1,99]
    */
   public function setActivityModus ( $value ) {
      $this->_activity_modus = (int)$value;
   }

    /** set the content of the list view
    * this method sets the whole entries of the list view
    *
    * @param list  $this->_list          content of the list view
    */
    function setList ($list) {
       $this->_list = $list;
       if ( !empty($this->_activity_modus) ) {
          $item = $this->_list->getFirst();
          while ($item) {
             if ( $this->_max_activity < $item->getPageImpressions($this->_activity_modus) ) {
                $this->_max_activity = $item->getPageImpressions($this->_activity_modus);
             }
             $item = $this->_list->getNext();
          }
          $this->_list->sortbyPageImpressions($this->_activity_modus);
       }
    }

   /** set from counter of the list view
    * this method sets the counter of the beginning of the list view
    *
    * @param int  $this->_from          beginning counter of the list
    *
    * @author CommSy Development Group
    */
    function setFrom ($from) {
       $this->_from = (int)$from;
    }

   /** get from counter of the list view
    * this method gets the counter of the beginning of the list view
    *
    * @param int  $this->_from          beginning counter of the list
    *
    * @author CommSy Development Group
    */
    function getFrom (){
       return $this->_from;
    }

   /** set interval counter of the list view
    * this method sets the shown interval of the list view
    *
    * @param int  $this->_interval          lenght of the shown list
    *
    * @author CommSy Development Group
    */
    function setInterval ($interval) {
       $this->_interval = (int)$interval;
    }

   /** get interval counter of the list view
    * this method gets the shown interval of the list view
    *
    * @param int  $this->_interval          lenght of the shown list
    *
    * @author CommSy Development Group
    */
    function getInterval () {
       return $this->_interval;
    }

   /** set count_all counter of the list view
    * this method sets the whole entries of the list view
    *
    * @param int  $this->_count_all          lenght of the whole list
    *
    * @author CommSy Development Group
    */
    function setCountAll ($count_all) {
       $this->_count_all = (int)$count_all;
    }

   /** get count_all counter of the list view
    * this method gets the whole entries of the list view
    *
    * @param int  $this->_count_all          lenght of the whole list
    *
    * @author CommSy Development Group
    */
    function getCountAll () {
       return $this->_count_all;
    }

   /** set count_all counter of the list view
    * this method sets the whole entries of the list view
    *
    * @param int  $this->_count_all          lenght of the whole shown list
    *
    * @author CommSy Development Group
    */
    function setCountAllShown ($count_all) {
       $this->_count_all_shown = (int)$count_all;
    }

   /** get count_all counter of the list view
    * this method gets the whole entries of the list view
    *
    * @param int  $this->_count_all          lenght of the whole shown list
    *
    * @author CommSy Development Group
    */
    function getCountAllShown () {
       return $this->_count_all_shown;
    }

    function getSelectedRoom () {
       return $this->_selected_room;
    }

    function setSelectedRoom ($value) {
       $this->_selected_room = $value;
    }
    function setSelectedArchiveRoom ($value) {
       $this->_selected_archive_room = $value;
    }

    function getSelectedArchiveRoom () {
      return $this->_selected_archive_room;
    }

    function setSelectedID ($value) {
       $this->_selected_iid = $value;
    }

    function getSelectedContext () {
       return $this->_selected_context;
    }

    function setSelectedContext ($value) {
       $this->_selected_context = $value;
    }

    function getSelectedTime () {
       return $this->_selected_time;
    }

    function setSelectedTime ($value) {
       $this->_selected_time = $value;
    }

  /** get the description of the list view title as HTML
    * this method returns the description in HTML-Code
    *
    * @return string $this->_description as HMTL
    *
    * @author CommSy Development Group
    */
   function _getDescriptionAsHTML() {

      // short names for easy reading
      $from      = $this->_from;
      $interval  = $this->_interval;
      $count_all = $this->_count_all;
      $count_all_shown = $this->_count_all_shown;
      if ( $count_all > $count_all_shown ) {
         if ( $count_all_shown == 0 ) {
            $description = $this->_translator->getMessage('COMMON_NO_ENTRIES_FROM_ALL', $count_all);
         } elseif ( $count_all_shown == 1 ) {
            $description = $this->_translator->getMessage('COMMON_ONE_ENTRY_FROM_ALL', $count_all);
         } elseif ( $interval == 0 || $count_all_shown <= $interval ) {
            $description = $this->_translator->getMessage('COMMON_X_ENTRIES_FROM_ALL', $count_all_shown, $count_all);
         } elseif ( $from == $count_all_shown){
            $description = $this->_translator->getMessage('COMMON_X_FROM_Z_FROM_ALL', $count_all_shown, $count_all);
         } else {
            if ( $from + $interval -1 <= $count_all_shown ) {
               $to = $from + $interval - 1;
            } else {
               $to = $count_all_shown;
            }
            $description = $this->_translator->getMessage('COMMON_X_TO_Y_FROM_Z_FROM_ALL', $from, $to, $count_all_shown, $count_all);
         }
      } else {
         if ( $count_all_shown == 0 ) {
            $description = $this->_translator->getMessage('COMMON_NO_ENTRIES');
         } elseif ( $count_all_shown == 1 ) {
            $description = $this->_translator->getMessage('COMMON_ONE_SHOWN_ENTRY');
         } elseif ( $interval == 0 || $count_all_shown <= $interval ) {
            $description = $this->_translator->getMessage('COMMON_X_SHOWN_ENTRIES', $count_all_shown);
         } elseif ( $from == $count_all_shown){
            $description = $this->_translator->getMessage('COMMON_X_FROM_Z_SHOWN', $count_all_shown);
         } else {
            if ( $from + $interval -1 <= $count_all ) {
               $to = $from + $interval - 1;
            } else {
               $to = $count_all_shown;
            }
            $description = $this->_translator->getMessage('COMMON_X_TO_Y_FROM_Z_SHOWN', $from, $to, $count_all_shown);
         }
      }
      $html ='';

      if ( !empty($description) ) {
         $html .= '<span class="portal_description">'.$description.'</span>';
      }

      return $html;
   }

   /** get the item of the list view as HTML
    * this method returns the single item in HTML-Code
    *
    * overwritten method form the upper class
    *
    * @return string item as HMTL
    */
   function _getItemAsHTML($item, $i=0) {
      $shown_entry_number = $i;
      if ($shown_entry_number%2 == 0){
         $style='class="portal-even"';
      }else{
         $style='class="portal-odd"';
      }
      $html = '';
      $html .= '   <tr class="list">'.LF;
      if($this->_environment->inServer()){
         $html .= '      <td '.$style.'>'.LF;
         $html .= '         '.$this->_getLogo($item).LF;
         $html .= '      </td>'.LF;
      } else {
         $html .= '      <td style="width:1%;" '.$style.'>'.LF;
         $current_user = $this->_environment->getCurrentUserItem();
         $may_enter = $item->mayEnter($current_user);
         if ($may_enter) {

            global $symfonyContainer;
            $router = $symfonyContainer->get('router');

            $url = $router->generate(
                'commsy_room_home',
                array('roomId' => $item->getItemID())
            );

            $html .= '<a href="' . $url . '"><img src="images/door_open_small.gif" alt="door open" style="vertical-align: middle;"/></a>';
         } else {
            $html .= '<img src="images/door_closed_small.gif" alt="door closed" style="vertical-align: middle;"/>';
         }
         $html .= '      </td>'.LF;
      }
      $html .= '      <td '.$style.'>'.LF;
      $html .= '         '.$this->_getTitle($item).LF;
      $html .= '      </td>'.LF;
      if ($this->_environment->inPortal()) {
         $html .= '      <td '.$style.'>'.LF;
         $html .= $this->_getContactModerators($item).LF;
         $html .= '      </td>'.LF;
      }
      $html .= '      <td '.$style.'>'.LF;
      $html .= '         '.$this->_getActivity($item).LF;
      $html .= '      </td>'.LF;
      $html .= '   </tr>'.LF;

      return $html;
   }

   /** get the title of the item
    * this method returns the item title in the right formatted style
    *
    * @return string title
    */
   function _getTitle ($item) {
      $params = $this->_environment->getCurrentParameterArray();
      if ($this->_environment->inServer()) {
         $cid = $item->getItemID();
      } else {
         $cid = $this->_environment->getCurrentContextID();
         $params['room_id'] = $item->getItemID();
      }
      if ( empty($params['selroom']) and $this->getSelectedRoom() == 5 ) {
         $params['selroom'] = 5;
      }
      unset($params['account']);
      if ($item->getItemID() == $this->getSelectedContext()) {
         $title = '*'.$item->getTitle().'*';
      } else {
         $title = $this->_compareWithSearchText($item->getTitle());
      }
      $title = ahref_curl( $cid,
                           'home',
                           'index',
                           $params,
                           $this->_text_as_html_short($title));
      if ( $item->isPortal() ) {
         $title .= BRLF.'<span class="disabled" style="font-size: smaller;"> ('.$this->_translator->getMessage('PORTAL_COUNT_ROOMS',$item->getCountRooms(),$item->getCountMembers()).')</span>'.LF;
      }
      if ($item->isDeleted()) {
         $title .= ' ('.$this->_translator->getMessage('ROOM_STATUS_DELETED').')';
      } elseif ($item->isLocked()) {
         $title .= ' ('.$this->_translator->getMessage('ROOM_STATUS_BARRICADE').')';
      } elseif ($item->isProjectroom() and $item->isTemplate()) {
         $title .= ' ('.$this->_translator->getMessage('ROOM_STATUS_TEMPLATE').')';
      } elseif ($item->isClosed()) {
         $title .= ' ('.$this->_translator->getMessage('ROOM_STATUS_CLOSE').')';
      }
      return $title;
   }

   /** get the logo of the item
    * this method returns the item logo in the right formatted style
    *
    * @return string title
    */
   function _getLogo ($item) {
      $logo_description = '';
      $logo = $item->getLogoFileName();
      $disc_manager = $this->_environment->getDiscManager();
      if ( $item->isProjectRoom() or $item->isCommunityRoom() ) {
         $disc_manager->setPortalID($item->getContextID());
         $disc_manager->setContextID($item->getItemID());
      } elseif ($item->isPortal()) {
         $disc_manager->setPortalID($item->getItemID());
         $disc_manager->setContextID($item->getItemID());
      }
      if (!empty($logo) and $disc_manager->existsFile($logo)) {
         $params = array();
         $params['picture'] = $item->getLogoFilename();
         $curl = curl($item->getItemID(), 'picture', 'getfile', $params);
         unset($params);
         $logo_description = '<img src="'.$curl.'" alt="'.$item->getTitle().' '.$this->_translator->getMessage('LOGO').'" style="height: 2.4em;"/>'.LF;
         $logo_description = ahref_curl( $item->getItemID(),
                                         'home',
                                         'index',
                                         '',
                                         $logo_description);
      } else {
         $logo_description = '&nbsp;';
      }
      if ( $this->_environment->inServer() ) {
         $disc_manager->setServerID($this->_environment->getServerID());
      } elseif ($this->_environment->inPortal()) {
         $disc_manager->setPortalID($this->_environment->getCurrentPortalID());
         $disc_manager->setContextID($this->_environment->getCurrentPortalID());
      } else {
         $disc_manager->setPortalID($this->_environment->getCurrentPortalID());
         $disc_manager->setContextID($this->_environment->getCurrentContextID());
      }
      return $logo_description;
   }

   /** get the activity of the item
    * this method returns the item activity in the right formatted style
    *
    * @return string title
    */
   function _getActivity ($item) {
      if ( $this->_max_activity != 0 ) {
         if ( empty($this->_activity_modus) ) {
            $percentage = $item->getActivityPoints();
         } else {
            $percentage = $item->getPageImpressions($this->_activity_modus);
         }
         if ( empty($percentage) ) {
            $percentage = 0;
         } else {
            $teiler = $this->_max_activity/20;
            $percentage = log(($percentage/$teiler)+1);
            if ($percentage < 0) {
               $percentage = 0;
            }
            $max_activity = log(($this->_max_activity/$teiler)+1);
            $percentage = round(($percentage / $max_activity) * 100,2);
         }
      } else {
         $percentage = 0;
      }
      $display_percentage = $percentage;
      $html  = '         <div class="gauge">'.LF;
      $html .= '            <div class="gauge-bar" style="width:'.$display_percentage.'%;">&nbsp;</div>'.LF;
      $html .= '         </div>'.LF;
      return $html;
   }

   /** get the contact moderators of the item
    * this method returns the contact moderators of the item
    * in the right formatted style.
    *
    * @return string contact moderators
    */
   function _getContactModerators ($item) {
      $html = '';
      $mod_string = trim($item->getContactPersonString());
      if ( empty($mod_string) ) {
         $mod_list = $item->getContactModeratorList();

         $moderator = $mod_list->getFirst();
         if ($moderator) {
            while ($moderator) {
               if ( !empty($mod_string) ) {
                  $mod_string .= ', ';
               }
               $mod_string .= $moderator->getFullname();
               $moderator = $mod_list->getNext();
            }
         }
      }
      if ( !empty($mod_string) ) {
         $mod_string = $this->_text_as_html_short($this->_compareWithSearchText($mod_string));
         $html = '<span class="small_font">'.$mod_string.'</span>';
      }

      return $html;
   }

    function _getListActionsAsHTML()
    {
        $html = '';
        $html .= '<div class="search_link">' . LF;
        if ($this->_environment->inPortal()) {
            $current_user = $this->_environment->getCurrentUserItem();
            $portal_item = $this->_environment->getCurrentContextItem();
            $room_opening_status = $portal_item->getProjectRoomCreationStatus();

            $isAllowedToCreateRoom = true;
            if (!$current_user->isGuest()) {
                $isAllowedToCreateRoom = $current_user->isAllowedToCreateContext();
            }

            if ($this->_with_modifying_actions && $isAllowedToCreateRoom) {

                $showLink = false;
                if ($current_user->isUser() && $room_opening_status == 'portal') {
                    $showLink = true;
                } else {
                    $community_room_opening = $portal_item->getCommunityRoomCreationStatus();
                    if (($community_room_opening == 'all' && $current_user->isUser()) || $current_user->isModerator()) {
                        $showLink = true;
                    }
                }

                if ($showLink) {
                    global $symfonyContainer;
                    $router = $symfonyContainer->get('router');

                    $ownRoom = $current_user->getOwnRoom();
                    if (isset($ownRoom)) {
                        $url = $router->generate(
                            'commsy_room_create', [
                                'roomId' => $ownRoom->getItemId(),
                            ]
                        );

                        $html .= '> <a href="' . $url . '">' . $this->_translator->getMessage('PORTAL_ENTER_ROOM') . '</a>';
                    }
                }
            }
        }

        $html .= '</div>' . LF;
        return $html;
    }

   function _getConfigurationBoxAsHTML () {
      $current_context = $this->_environment->getCurrentContextItem();
      $html  = '';
      $html .= '<div class="search_link">'.LF;
      $html .= '> '.ahref_curl($this->_environment->getCurrentContextID(),'configuration','index','',$this->_translator->getMessage('PORTAL_CONFIGURATION_ACTION')).BRLF;
      $html .= '</div>'.LF;

      // tasks
      $manager = $this->_environment->getTaskManager();
      $manager->resetLimits();
      $manager->setContextLimit($this->_environment->getCurrentContextID());
      $manager->setStatusLimit('REQUEST');
      $manager->select();
      $task_list = $manager->get();
      if ( !$task_list->isEmpty() ) {
         $task_item = $task_list->getFirst();
         while ( $task_item ) {
            $ref_item = $task_item->getLinkedItem();
            $html .= '<div class="search_link" style="margin-top:10px;">'.LF;
            $html .= $this->_translator->getMessage('PORTAL_ROOM_MOVE_REQUESTED').': '.$ref_item->getTitle().LF;
            $html .= '</div>'.LF;
            $params = array();
            $params['iid'] = $ref_item->getItemID();
            $params['tid'] = $task_item->getItemID();
            $params['modus'] = 'agree';
            $html .= '<div class="search_link">'.LF;
            $html .= '> '.ahref_curl($this->_environment->getCurrentContextID(),'configuration','move2',$params,$this->_translator->getMessage('COMMON_ACCEPT')).BRLF;
            $html .= '</div>'.LF;
            $params['modus'] = 'reject';
            $html .= '<div class="search_link">'.LF;
            $html .= '> '.ahref_curl($this->_environment->getCurrentContextID(),'configuration','move2',$params,$this->_translator->getMessage('COMMON_REJECT'));
            $html .= '</div>'.LF;
            unset($params);

            $task_item = $task_list->getNext();
         }
         $html .='</div>'.LF;
      }


      return $html;
   }

   function _getListSelectionsAsHTML () {
      $current_context = $this->_environment->getCurrentContextItem();
      $show_rooms = $current_context->getShowRoomsOnHome();
      $html  = '';
      // Search / select form
      $html .= '<form style="width:100%; padding:0px; margin:0px;" action="'.curl($this->_environment->getCurrentContextID(), $this->_module, $this->_function,'').'" method="get" name="indexform">'.LF;
      $html .= '   <input type="hidden" name="cid" value="'.$this->_text_as_form($this->_environment->getCurrentContextID()).'"/>'.LF;
      $html .= '   <input type="hidden" name="mod" value="'.$this->_text_as_form($this->_module).'"/>'.LF;
      $html .= '   <input type="hidden" name="fct" value="'.$this->_text_as_form($this->_function).'"/>'.LF;
      $html .= '   <input type="hidden" name="sort" value="'.$this->_text_as_form($this->getSortKey()).'"/>'.LF;
      $session = $this->_environment->getSession();
      if ( !$session->issetValue('cookie')
           or $session->getValue('cookie') == '0' ) {
         $html .= '   <input type="hidden" name="SID" value="'.$this->_text_as_form($session->getSessionID()).'"/>'.LF;
      }
      if ( !empty($this->_activity_modus) ) {
         $html .= '   <input type="hidden" name="activitymodus" value="'.$this->_text_as_form($this->_activity_modus).'"/>'.LF;
      }
      $html .= '<div style="padding-left:3px; padding-top:5px;">'.LF;
      $html .= '<span class="search_title" style="">'.$this->_translator->getMessage('COMMON_ROOM_SEARCH').'</span>'.BRLF;
      $html .= '<span class="portal_description">'.$this->_translator->getMessage('COMMON_ROOM_SEARCH_DESCRIPTION').'</span>'.BRLF;
      $html .= '<div class="search_box">'.LF;
      $session = $this->_environment->getSession();
      $left_menue_status = $session->getValue('left_menue_status');
      if ($left_menue_status !='disapear'){
         $width = '90%';
      } else {
         $width = '90%';
      }
      $html .= '<div style="text-align:left; font-size: 10pt;">'.$this->_translator->getMessage('PORTAL_SEARCH_FIELD').':'.BRLF;
      $html .= '<input style="width:'.$width.'; font-size:8pt; margin-bottom:10px;" name="search" type="text" size="20" value="'.$this->_text_as_form($this->getSearchText()).'"/>'.LF;

      $current_context = $this->_environment->getCurrentContextItem();
      $session = $this->_environment->getSession();
      $left_menue_status = $session->getValue('left_menue_status');
      if ($left_menue_status !='disapear') {
         $width = '90%';
      } else {
         $width = '90%';
      }

      $selroom = $this->getSelectedRoom();
      if ( empty($selroom)
           and !empty($show_rooms)
           and $show_rooms == 'preselectmyrooms'
           and $this->_environment->getCurrentUserItem()->isUser()
         ) {
         $selroom = 5;
      }
      $sel_archive_room = $this->getSelectedArchiveRoom();
      if (!empty($this->_selected_iid)) {
         $html .= '   <input type="hidden" name="iid" value="'.$this->_text_as_form($this->_selected_iid).'"/>'.LF;
      }
      if (!empty($this->_selected_context)) {
         $html .= '   <input type="hidden" name="room_id" value="'.$this->_text_as_form($this->_selected_context).'"/>'.LF;
      }

      $html .= '<div style="text-align:left; font-size: 10pt; padding-bottom:0px; margin-bottom:0px;">'.$this->_translator->getMessage('PORTAL_ROOM_LIST_ROOMS').':'.BRLF;
      
      // jQuery
      //$html .= '   <select style="width: '.$width.'; font-size:8pt; margin-bottom:0px;" name="selroom" size="1" onChange="javascript:document.indexform.submit()">'.LF;
      $html .= '   <select style="width: '.$width.'; font-size:8pt; margin-bottom:0px;" name="selroom" size="1" id="submit_form">'.LF;
      // jQuery

      $html .= '      <option value="1"';
      if ( !isset($selroom) || ($selroom == 1 or $selroom == 2) ) {
         $html .= ' selected="selected"';
      }
      $html .= '>*'.$this->_translator->getMessage('COMMON_NO_SELECTION').'</option>'.LF;

      // deleted rooms
      $current_user = $this->_environment->getCurrentUserItem();
      if ( $current_user->isRoot() ) {
         $html .= '      <option value="9"';
         if ( !empty($selroom) and $selroom == 9 ) {
            $html .= ' selected="selected"';
         }
      }
      if ( $current_user->isRoot()
           or $current_user->isModerator()
         ) {
         $html .= '>'.$this->_translator->getMessage('PORTAL_DELETED_ROOMS').'</option>'.LF;
         #$html .= '      <option value="8"';
         #if ( !empty($selroom) and $selroom == 8 ) {
         #   $html .= ' selected="selected"';
         #}
         #$html .= '>'.$this->_translator->getMessage('PORTAL_ARCHIVED_ROOMS').'</option>'.LF;
      }

      $current_context = $this->_environment->getCurrentContextItem();
      if ( $show_rooms !='onlycommunityrooms'
           and $show_rooms !='onlyprojectrooms'
         ) {
         $html .= '      <option class="disabled" disabled="disabled" value="-2">------------------------------</option>'.LF;
         $html .= '      <option value="3"';
         if ( !empty($selroom) and $selroom == 3 ) {
            $html .= ' selected="selected"';
         }
         $html .= '>'.$this->_translator->getMessage('COMMON_PROJECT_PL').'</option>'.LF;

         $html .= '      <option value="4"';
         if ( !empty($selroom) and $selroom == 4 ) {
            $html .= ' selected="selected"';
         }
         $html .= '>'.$this->_translator->getMessage('COMMON_COMMUNITY_PL').'</option>'.LF;
      }

      if ( $this->_environment->inPortal() ) {
         $current_context = $this->_environment->getCurrentContextItem();
         $current_user = $this->_environment->getCurrentUser();
         if ( $current_user->isModerator()
              and $current_context->withGroupRoomFunctions()
            ) {
            $html .= '      <option class="disabled" disabled="disabled" value="-2">------------------------------</option>'.LF;
            $html .= '      <option value="6"';
            if ( !empty($selroom) and $selroom == 6 ) {
               $html .= ' selected="selected"';
            }
            $html .= '>'.$this->_translator->getMessage('GROUPROOM_PORTAL_SELECT_TITLE').'</option>'.LF;
         }
      }

      if ( $this->_environment->inPortal() ) {
         $current_user = $this->_environment->getCurrentUser();
         if ( $current_user->isUser() ) {
            $html .= '      <option class="disabled" disabled="disabled" value="-2">------------------------------</option>'.LF;
            $html .= '      <option value="5"';
            if ( !empty($selroom) and $selroom == 5 ) {
               $html .= ' selected="selected"';
            }
            if ($show_rooms =='onlycommunityrooms'){
               $html .= '>'.$this->_translator->getMessage('PORTAL_MY_COMMUNITY_ROOMS').'</option>'.LF;
            }elseif ($show_rooms =='onlyprojectrooms'){
               $html .= '>'.$this->_translator->getMessage('PORTAL_MY_PROJECT_ROOMS').'</option>'.LF;
            }else{
               $html .= '>'.$this->_translator->getMessage('PORTAL_MY_ROOMS').'</option>'.LF;
            }
         }
      }
      $html .= '   </select>'.LF;
      $html .= '</div>'.LF;
      
      // archive
      if ( !empty($sel_archive_room) and $sel_archive_room == 1 ) {
        $text1 = '';
        $text2 = ' checked="checked"';
      } else {
         $text1 = ' checked="checked"';
        $text2 = '';
      }
      $html .= '<div style="text-align:left; font-size: 8pt; font-weight:normal; margin-bottom:10px;">'.LF;
      $html .= '<input type="radio" name="sel_archive_room" value="2"'.$text1.'>'.lcfirst($this->_translator->getMessage('PORTAL_NORMAL_ROOMS')).'</input>'.BRLF;
      $html .= '<input type="radio" name="sel_archive_room" value="1"'.$text2.'>'.lcfirst($this->_translator->getMessage('PORTAL_ARCHIVED_ROOMS')).'</input>'.LF;
      $html .= '</div>'.LF;  
      
      $current_context = $this->_environment->getCurrentContextItem();
      if ( $this->_environment->inPortal()
           and $current_context->showTime()
         ) {
         $seltime = $this->getSelectedTime();
         $portal_item = $this->_environment->getCurrentContextItem();
         $time_list = $portal_item->getTimeListRev();

         $html .= '';
         $html .= '<div style="text-align: left; font-size: 10pt;">'.$this->_translator->getMessage('COMMON_TIME_NAME').':'.BRLF;
         // jQuery
         //$html .= '   <select style="width: '.$width.'; font-size: 8pt; margin-bottom:10px;" name="seltime" size="1" onChange="javascript:document.indexform.submit()">'.LF;
         $html .= '   <select style="width: '.$width.'; font-size: 8pt; margin-bottom:10px;" name="seltime" size="1" id="submit_form">'.LF;
         // jQuery
         $html .= '      <option value="-3"';
         if ( !isset($seltime) or $seltime == 0 or $seltime == -3) {
            $html .= ' selected="selected"';
         }
         $html .= '>*'.$this->_translator->getMessage('COMMON_NO_SELECTION').'</option>'.LF;
         $html .= '      <option class="disabled" disabled="disabled" value="-2">------------------------------</option>'.LF;
         if ($time_list->isNotEmpty()) {
            $time_item = $time_list->getFirst();
            while ($time_item) {
               $html .= '      <option value="'.$time_item->getItemID().'"';
               if ( !empty($seltime) and $seltime == $time_item->getItemID() ) {
                  $html .= ' selected="selected"';
               }
               $html .= '>'.$this->_translator->getTimeMessage($time_item->getTitle()).'</option>'.LF;
               $time_item = $time_list->getNext();
            }
         }

         $html .= '      <option class="disabled" disabled="disabled" value="-2">------------------------------</option>'.LF;
         $html .= '      <option value="-1"';
         if ( isset($seltime) and $seltime == -1) {
            $html .= ' selected="selected"';
         }
         $html .= '>*'.$this->_translator->getMessage('COMMON_NOT_LINKED').'</option>'.LF;
         $html .= '   </select>'.LF;
         $html .= '</div>'.LF;
      }

      $html .= '<div><input style="margin-top:5px; font-size:8pt;" name="option" value="'.$this->_translator->getMessage('COMMON_SHOW_BUTTON').'" type="submit"/></div>'.LF;
      $html .= '</div>'.LF;
      $html .= '</div>'.LF;
      $html .= '</div>'.LF;
      $html .= '</form>'.LF;

      return $html;
   }


   /** compare the item text and the search criteria
    * this method returns the item text bold if it fits to the search criteria
    *
    * @return string value
    */
   function _compareWithSearchText ($value) {
      if ( !empty($this->_search_array) ){
         foreach ($this->_search_array as $search_text) {
            if ( mb_stristr($value,$search_text) ) {
               $value = preg_replace('~'.preg_quote($search_text,'/').'~iu','*$0*',$value);
            }
         }
      }
      return $value;
   }

   function _getTableFootAsHTML() {
      if ( !$this->_environment->inServer() ) {
         $current_portal = $this->_environment->getCurrentPortalItem();
         $html = '<tr class="portal-head"><td class="portal-head" colspan="3" style="border-top:4px solid white;"><table style="width:100%;" summary="Layout"><tr><td>'.$this->_getIntervalLinksAsHTML().'</td><td style="width:5%;white-space:nowrap;">'.$this->_getForwardLinkAsHTML().'</td></tr></table></td></tr>'.LF;
      } else {
         $html = '';
      }
      return $html;
   }


   function _getIntervalLinksFirstLineAsHTML() {
      $params = $this->_environment->getCurrentParameterArray();
      $html = $this->_translator->getMessage('COMMON_PAGE_ENTRIES').':';
      return $html;
   }

   function _getIntervalLinksSecondLineAsHTML() {
      $params = $this->_environment->getCurrentParameterArray();
      $html  = '';
      if ( $this->_interval == 10 ) {
         $html  = '<span class="bold">10</span>';
      } else {
         $params['interval'] = 10;
         $html  .= ahref_curl($this->_environment->getCurrentContextID(), $this->_module, $this->_function, $params, '10', '', '', '');
      }

      if ( $this->_interval == 20 ) {
         $html .= ' | <span class="bold">20</span>';
      } else {
         $params['interval'] = 20;
         $html .= ' | '.ahref_curl($this->_environment->getCurrentContextID(), $this->_module, $this->_function, $params, '20', '', '', '');
      }

      if ( $this->_interval == 50 ) {
         $html .= ' | <span class="bold">50</span>';
      } else {
         $params['interval'] = 50;
         $html .= ' | '.ahref_curl($this->_environment->getCurrentContextID(), $this->_module, $this->_function, $params, '50', '', '', '');
      }

      if ( $this->_interval == 0 ) {
         $html .= ' | <span class="bold">'.$this->_translator->getMessage('COMMON_PAGE_ENTRIES_ALL').'</span>';
      } else {
         $params['interval'] = 0;
         $html .= ' | '.ahref_curl($this->_environment->getCurrentContextID(), $this->_module, $this->_function, $params, $this->_translator->getMessage('COMMON_PAGE_ENTRIES_ALL'), '', '', '');
      }

      return $html;
   }

   function _getIntervalLinksAsHTML() {
      $params = $this->_environment->getCurrentParameterArray();
      $html = $this->_translator->getMessage('COMMON_PAGE_ENTRIES').': ';
      if ( $this->_interval == 10 ) {
         $html  .= '10';
      } else {
         $params['interval'] = 10;
         $html  .= ahref_curl($this->_environment->getCurrentContextID(), $this->_module, $this->_function, $params, '10', '', '', '');
      }

      if ( $this->_interval == 20 ) {
         $html .= ' | 20';
      } else {
         $params['interval'] = 20;
         $html .= ' | '.ahref_curl($this->_environment->getCurrentContextID(), $this->_module, $this->_function, $params, '20', '', '', '');
      }

      if ( $this->_interval == 50 ) {
         $html .= ' | 50';
      } else {
         $params['interval'] = 50;
         $html .= ' | '.ahref_curl($this->_environment->getCurrentContextID(), $this->_module, $this->_function, $params, '50', '', '', '');
      }

      if ( $this->_interval == 0 ) {
         $html .= ' | '.$this->_translator->getMessage('COMMON_PAGE_ENTRIES_ALL');
      } else {
         $params['interval'] = 0;
         $html .= ' | '.ahref_curl($this->_environment->getCurrentContextID(), $this->_module, $this->_function, $params, $this->_translator->getMessage('COMMON_PAGE_ENTRIES_ALL'), '', '', '');
      }

      return $html;
   }

   function _getTableheadAsHTML() {
      include_once('functions/misc_functions.php');
      $html = '';
      $current_portal = $this->_environment->getCurrentPortalItem();
      if ($this->_environment->inPortal()) {
         $params = $this->_environment->getCurrentParameterArray();
         $html .= '   <tr class="list">'.LF;
         $html .= '      <td colspan="2" class="portal-head" style="width:50%">'.LF;
         if ( $this->getSortKey() == 'title' ) {
            $params['sort'] = 'title_rev';
            $text = $this->_translator->getMessage('COMMON_TITLE');
         } elseif ( $this->getSortKey() == 'title_rev' ) {
            $params['sort'] = 'title';
            $text = $this->_translator->getMessage('COMMON_TITLE');
         } else {
            $params['sort'] = 'title';
            $text = $this->_translator->getMessage('COMMON_TITLE');
         }
         if ( empty($this->_activity_modus) ) {
            $html .= ahref_curl($this->_environment->getCurrentContextID(), $this->_module, $this->_function,
                                $params, $text, '', '', '','','','','class="head"').LF;
         } else {
            $html .= $text;
         }
         if ( $this->getSortKey() == 'title' ) {
            $html .= ' <img src="' . getSortImage('up') . '" alt="&lt;" border="0"/>';
         } elseif ( $this->getSortKey() == 'title_rev' ) {
            $html .= ' <img src="' . getSortImage('down') . '" alt="&lt;" border="0"/>';
         }
         $html .= '      </td>'.LF;
         if ($this->_environment->inPortal()) {
            $html .= '      <td class="portal-head" style="width:35%">'.LF;
            $html .= '<span class="portal_link">'.$this->_translator->getMessage('CONTEXT_MODERATOR').'</span>'.LF;
            $html .= '      </td>'.LF;
         }

         $html .= '      <td class="portal-head" style="width:15%">'.LF;
         if ( $this->getSortKey() == 'activity_rev' ) {
            $params['sort'] = 'activity';
            $text = $this->_translator->getMessage('CONTEXT_ACTIVITY');
         } elseif ( $this->getSortKey() == 'activity' ) {
            $params['sort'] = 'activity_rev';
            $text = $this->_translator->getMessage('CONTEXT_ACTIVITY');
         } else {
            $params['sort'] = 'activity_rev';
            $text = $this->_translator->getMessage('CONTEXT_ACTIVITY');
         }
         if ( empty($this->_activity_modus) ) {
            $html .= ahref_curl($this->_environment->getCurrentContextID(), $this->_module, $this->_function,
                                $params, $text, '', '', '','','','','class="head"').LF;
         } else {
            $html .= $text;
         }
         if ( $this->getSortKey() == 'activity_rev' ) {
            $html .= ' <img src="' . getSortImage('down') . '" alt="&lt;" border="0"/>';
         } elseif ( $this->getSortKey() == 'activity' ) {
           $html .= ' <img src="' . getSortImage('up') . '" alt="&lt;" border="0"/>';
         }
         $html .= '      </td>'.LF;

         $html .= '   </tr>'.LF;
      }
      return $html;
   }

   function _getForwardLinkAsHTML () {
      // short names for easy reading
      $from      = $this->_from;
      $interval  = $this->_interval;
      $count_all_shown = $this->_count_all_shown;
      $params = $this->_environment->getCurrentParameterArray();;
      if ($interval > 0) {
         if ($count_all_shown != 0) {
            $num_pages = ceil($count_all_shown / $interval);
         } else {
            $num_pages = 1;
         }
         $act_page  = ceil(($from + $interval - 1) / $interval);
      } else {
         $num_pages = 1;
         $act_page  = 1;
      }

      // prepare browsing
      if ( $from > 1 ) {        // can I browse to the left / start?
         $browse_left = $from - $interval;
         if ($browse_left < 1) {
            $browse_left = 1;
         }
         $browse_start = 1;
      } else {
         $browse_left = 0;      // 0 means: do not browse
         $browse_start = 0;     // 0 means: do not browse
      }
      if ( $from + $interval <= $count_all_shown ) {  // can I browse to the right / end?
         $browse_right = $from + $interval;
         $browse_end = $count_all_shown - $interval + 1;
      } else {
         $browse_right = 0;     // 0 means: do not browse
         $browse_end = 0;       // 0 means: do not browse
      }

      // create HTML for browsing icons
      $html = '';
      if ( $browse_start > 0 ) {
         $params['from'] = $browse_start;
         $image = '<span class="bold">&lt;&lt;</span>';
         $html .= '         '.ahref_curl($this->_environment->getCurrentContextID(), $this->_module, $this->_function, $params, $image, $this->_translator->getMessage('COMMON_BROWSE_START_DESC'),'','','','','','class="portal_system_link"').LF;
      } else {
         $html .= '         <span class="bold_disabled">&lt;&lt;</span>'.LF;
      }
      $html .= '|';
      if ( $browse_left > 0 ) {
         $params['from'] = $browse_left;
         $image = '<span class="bold">&lt;</span>';
         $html .= '         '.ahref_curl($this->_environment->getCurrentContextID(), $this->_module, $this->_function, $params, $image, $this->_translator->getMessage('COMMON_BROWSE_LEFT_DESC'),'','','','','','class="portal_system_link"').LF;
      } else {
         $html .= '         <span class="bold_disabled">&lt;</span>'.LF;
      }
      $html .= '|';
      $html .= '<span class="bold">&nbsp;'.$this->_translator->getMessage('COMMON_PAGE').' '.$act_page.' / '.$num_pages.'&nbsp;</span>'.LF;
      $html .= '|';
      if ( $browse_right > 0 ) {
         $params['from'] = $browse_right;
         $image = '<span class="bold">&gt;</span>';
         $html .= '         '.ahref_curl($this->_environment->getCurrentContextID(), $this->_module, $this->_function, $params, $image, $this->_translator->getMessage('COMMON_BROWSE_RIGHT_DESC'),'','','','','','class="portal_system_link"').LF;
      } else {
         $html .= '         <span class="bold_disabled">&gt;</span>'.LF;
      }
      $html .= '|';
      if ( $browse_end > 0 ) {
         $params['from'] = $browse_end;
         $image = '<span class="bold">&gt;&gt;</span>';
         $html .= '         '.ahref_curl($this->_environment->getCurrentContextID(), $this->_module, $this->_function, $params, $image, $this->_translator->getMessage('COMMON_BROWSE_END_DESC'),'','','','','','class="portal_system_link"').LF;
      } else {
         $html .= '         <span class="bold_disabled">&gt;&gt;</span>'.LF;
      }

      return $html;
   }

   /** get list view as HTML
    * this method returns the list view in HTML-Code
    *
    * @return string list view as HMTL
    */
   function asHTML () {
      $html  = LF.'<!-- BEGIN OF PLAIN LIST VIEW -->'.LF;
      $html .= '<a name="'.$this->_view_name.'"></a>'.LF;

      if ($this->_environment->inPortal()) {
         $current_context = $this->_environment->getCurrentPortalItem();
      } else {
         $current_context = $this->_environment->getServerItem();
      }

      $html .='<table style="width:100%;" summary="Layout">'.LF;
      $html .='<tr>'.LF;
      $html .='<td class="room_list_head" colspan="2" style="width:85%; padding-top:5px; vertical-align:bottom; white-space:nowrap;">'.LF;
      $html .='<div>'.LF;
      $html .='<div>'.LF;
      if ($this->_environment->inServer()) {
         $html .= '<span class="portal_section_title">'.$this->_translator->getMessage('SERVER_PORTAL_OVERVIEW').'</span>'.LF;
      } else {
         $html .= '<span class="portal_section_title">'.$this->_translator->getMessage('PORTAL_ROOM_OVERVIEW').'</span>'.LF;
      }
      if (!$this->_environment->inServer()) {
         $html .= BR.$this->_getDescriptionAsHTML().LF;
      }
      $html .='</div>'.LF;
      $html .='</div>'.LF;
      $html .='</td>'.LF;
      $html .='<td class="room_list_head" style="width:15%; padding-top:5px; vertical-align:top; text-align:right; white-space:nowrap;">'.LF;
      if (!$this->_environment->inServer()) {
         $html .='<div style="float:right;text-align:right;">'.LF;
 #        $html .= '<span class="portal_description">'.$this->_getIntervalLinksFirstLineAsHTML().'</span>'.BRLF;
 #        $html .= '<span class="portal_description">'.$this->_getIntervalLinksSecondLineAsHTML().'</span>'.LF;
         $html .= '&nbsp;&nbsp;<span class="portal_forward_links">'.$this->_getForwardLinkAsHTML().'</span>'.BRLF;

         $html .='</div>'.LF;
      }
      $html .='</td>'.LF;
      $html .='</tr>'.LF;
      $html .='<tr>'.LF;
      $html .='<td colspan="3" style="padding-top:0px; vertical-align:top; ">'.LF;


      $html .= '<table style="width: 100%; border-collapse: collapse; border: 0px; padding:0px;" summary="Layout">'.LF;
      $html .= $this->_getTableheadAsHTML();
      $current_user = $this->_environment->getCurrentUserItem();
      if ( !empty($this->_list) ) {
         $html .= $this->_getContentAsHTML();
      } elseif ( !$current_user->isUser() ) {
      	$html .= '<tr><td colspan="3">'.$this->_translator->getMessage('PORTAL_LOGIN_TO_SEE_ROOMS').'</td></tr>';
      }
      unset($current_user);
      $html .= '</table>'.LF;
      $html .='</td>'.LF;
      $html .='</tr>'.LF;
      $html .= '</table>'.LF;
      $html .= '<!-- END OF PLAIN LIST VIEW -->'.LF.LF;
      return $html;
   }


   function getSearchBoxAsHTML(){
      if ($this->_environment->inPortal()) {
         $current_context = $this->_environment->getCurrentPortalItem();
      } else {
         $current_context = $this->_environment->getServerItem();
      }
      $html ='<table style="width:100%;" summary="Layout"><tr><td style="width:100%;">'.LF;
      $html .= LF.'<!-- BEGIN OF SEARCH BOX VIEW -->'.LF;
      $html .='<div style="margin-bottom:10px;">'.LF;
      $html .= $this->_getListSelectionsAsHTML();
      $html .='</div>'.LF;
      $html .='<div style="margin-bottom:15px;">'.LF;
      $html .= $this->_getListActionsAsHTML();
      $user = $this->_environment->getCurrentUser();
      if ( $user->isModerator() ) {
         $html .= $this->_getConfigurationBoxAsHTML();
      } else {
         $html .=BRLF;
      }
      $html .='</div>'.LF;
      $html .= '<!-- END OF SEARCH BOX VIEW -->'.LF.LF;
      $html .='</td></tr></table>'.LF;
      return $html;
   }

   /** get the content of the list view as HTML
    * this method returns the content in HTML-Code
    *
    * @return string $this->_list as HMTL
    */
   function _getContentAsHTML() {
      $i = 1;
      $html = '';
      if ( isset($this->_list)
           and !empty($this->_list) 
           and !$this->_list->isEmpty()
         ) {
         $list = $this->_list;
         $current_item = $list->getFirst();
         $html = '';
         while ( $current_item ) {
            $item_text = '';
            if ( empty($this->_activity_modus)
                 or empty($this->_interval)
               ) {
               $item_text = $this->_getItemAsHTML($current_item, $i);
            } else {
               $from = $this->_from;
               if ( empty($from) ) {
                  $from = 1;
               }
               if ( $from <= $i
                    and $i < $from + $this->_interval
                  ) {
                  $item_text = $this->_getItemAsHTML($current_item, $i);
               }
            }
            $i++;
            $html .= $item_text;
            $current_item = $list->getNext();
         }
      } else {
         $html .= '<tr class="list"><td style="border-bottom: 0px; font-weight: normal;">'.$this->_translator->getMessage('COMMON_NO_ENTRIES').'</td></tr>';
      }
      return $html;
   }
}
?>