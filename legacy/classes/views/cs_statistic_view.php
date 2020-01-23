<?php
// $Id$
//
// Release $Name$
//
// Copyright (c)2002-2007 Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
// Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Frithjof Meyer, Timo Nolte, Bernd Pape,
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

$this->includeClass(VIEW);

/**
 *  generic upper class for CommSy views
 */
class cs_statistic_view extends cs_view {

   /**
    * string - begin of statistic date interval
    */
   var $_start_date = NULL;

   /**
    * string - end of statistic date interval
    */
   var $_end_date = NULL;

   /**
    * list - containing the content of the statistic view
    */
   var $_list = NULL;

   /**
    * string - title of this view
    */
   var $_title = '';

   var $_day1 = '';
   var $_month1 = '';
   var $_year1 = '';
   var $_day2 = '';
   var $_month2 = '';
   var $_year2 = '';
   var $_room_status = '';

   var $_pr_used = 0;
   var $_pr_used_closed = 0;
   var $_pr_closed = 0;
   var $_pr_open = 0;
   var $_pr_active = 0;
   var $_pr_all = 0;
   var $_pr_all_cr = 0;
   var $_pr_all_pr = 0;
   var $_pr_all_gr = 0;
   var $_pr_active_cr = 0;
   var $_pr_active_pr = 0;
   var $_pr_active_gr = 0;
   var $_pr_used_cr = 0;
   var $_pr_used_pr = 0;
   var $_pr_used_gr = 0;

   var $_ac_used = 0;
   var $_ac_open = 0;
   var $_ac_all  = 0;

   private $_plugin_active = array();

   var $_statistic_matrix = array();
   private $_community_statistic_matrix = array();

   var $_sort_by = 'ac_used';

   var $_show_title = true;

   /** constructor
    * the only available constructor, initial values for internal variables
    *
    * @param array params parameters in an array of this class
    */
   function __construct($params) {
      cs_view::__construct( $params );
   }

   /** set title of the statistic view
    * this method sets the title of the statistic view
    *
    * @param string  $this->_title          title of the statistic view
    */
    function setTitle ($value) {
       $this->_title = (string)$value;
    }

   /** set first month of the statistic view
    * this method sets the first month of the statistic view
    *
    * @param int  $this->_month1          first month of the statistic
    */
    function setMonth1 ($value) {
       $this->_month1 = $value;
    }

    function setDay1 ($value) {
       $this->_day1 = $value;
    }

    function setYear1 ($value) {
       $this->_year1 = $value;
    }

    function setMonth2 ($value) {
       $this->_month2 = $value;
    }

    function setDay2 ($value) {
       $this->_day2 = $value;
    }

    function setYear2 ($value) {
       $this->_year2 = $value;
    }

    function setRoomStatus ($value) {
       $this->_room_status = $value;
    }

   /** set interval counter of the statistic view
    * this method sets the shown interval of the statistic view
    *
    * @param int  $this->_start_date        starting date of statistic in datetime
    * @param int  $this->_end_date          ending date of statistic in datetime
    */
    function setInterval ($start,$end) {
       $this->_start_date = $start;
       $this->_end_date   = $end;
    }

   /** set description of the statistic view
    * this method sets the shown description of the statistic view
    *
    * @param int  $this->_description          description of the shown list
    */
    function setDescription ($description) {
       $this->_description = (string)$description;
    }

   /** set the content of the statistic view
    * this method sets the whole entries of the statistic view
    *
    * @param list  $this->_list          content of the statistic view
    */
    function setList ($list){
       $this->_list = $list;
    }

   function _execute () {
      if ( empty($this->_statistic_matrix) ) {
         $list = $this->_list;
         $current_item = $list->getFirst();
         while ( $current_item ) {
            $row = $this->_getRow($current_item);
            $this->_statistic_matrix[] = $row;
            $current_item = $list->getNext();
         }

         // sort by active user
         $sort_by = $this->_sort_by;
         usort($this->_statistic_matrix,create_function('$a,$b','return (-1)*strnatcasecmp($a[\''.$sort_by.'\'],$b[\''.$sort_by.'\']);'));
      }
   }

   function _getRow ($room) {
      $retour = array();
      $retour['item_id']       = $room->getItemID();
      $retour['title']         = $room->getTitle();
      $retour['creation_date'] = $room->getCreationDate();
      $retour['is_open']       = $room->isOpen();
      $retour['active']        = $room->getCountActiveTypeRooms('',$this->_start_date,$this->_end_date);
      $retour['active_gr']     = $room->getCountActiveTypeRooms(CS_GROUPROOM_TYPE,$this->_start_date,$this->_end_date);
      $retour['active_pr']     = $room->getCountActiveTypeRooms(CS_PROJECT_TYPE,$this->_start_date,$this->_end_date);
      $retour['active_cr']     = $room->getCountActiveTypeRooms(CS_COMMUNITY_TYPE,$this->_start_date,$this->_end_date);
      $retour['used']          = $room->getCountUsedTypeRooms('',$this->_start_date,$this->_end_date);
      $retour['used_gr']       = $room->getCountUsedTypeRooms(CS_GROUPROOM_TYPE,$this->_start_date,$this->_end_date);
      $retour['used_pr']       = $room->getCountUsedTypeRooms(CS_PROJECT_TYPE,$this->_start_date,$this->_end_date);
      $retour['used_cr']       = $room->getCountUsedTypeRooms(CS_COMMUNITY_TYPE,$this->_start_date,$this->_end_date);
      $retour['all']           = $room->getCountAllTypeRooms('',$this->_start_date,$this->_end_date);
      $retour['all_gr']        = $room->getCountAllTypeRooms(CS_GROUPROOM_TYPE,$this->_start_date,$this->_end_date);
      $retour['all_pr']        = $room->getCountAllTypeRooms(CS_PROJECT_TYPE,$this->_start_date,$this->_end_date);
      $retour['all_cr']        = $room->getCountAllTypeRooms(CS_COMMUNITY_TYPE,$this->_start_date,$this->_end_date);
      $retour['ac_used']       = $room->getCountUsedAccounts($this->_start_date,$this->_end_date);
      $retour['ac_open']       = $room->getCountOpenAccounts($this->_start_date,$this->_end_date);
      $retour['ac_all']        = $room->getCountAllAccounts($this->_start_date,$this->_end_date);

      ########################################################################
      # plugins - BEGIN
      ########################################################################
      global $c_etchat_enable;
      if ( !empty($c_etchat_enable)
           and $c_etchat_enable
         ) {
         $retour['chat']      = $room->getCountPlugin('etchat',$this->_start_date,$this->_end_date);
      }
      global $c_pmwiki;
      if ( !empty($c_pmwiki)
           and $c_pmwiki
         ) {
         $retour['wiki']      = $room->getCountPlugin('pmwiki',$this->_start_date,$this->_end_date);
      }

      global $c_plugin_array;
      if ( isset($c_plugin_array)
           and !empty($c_plugin_array)
           and $room->isPortal()
         ) {
         foreach ($c_plugin_array as $plugin) {
            if ( isset($room) ) {
               $plugin_class = $this->_environment->getPluginClass($plugin);
               if ( method_exists($plugin_class,'inStatistics') ) {
                  if ( $plugin_class->inStatistics() ) {
                     if ( $room->isPluginOn($plugin) ) {
                        $retour[$plugin] = $room->getCountPlugin($plugin,$this->_start_date,$this->_end_date);
                     } else {
                        $retour[$plugin] = 'n.a.';
                     }
                  }
               }
            }
         }
      }


      ########################################################################
      # plugins - END
      ########################################################################

      if ($this->_room_status != 'none') {
         $temp_array = array();
         if ( $this->_room_status == 'used'
              or $this->_room_status == 'community'
            ) {
            $room_list = $room->getUsedRoomList($this->_start_date,$this->_end_date);
         } else {
            $room_list = $room->getActiveRoomList($this->_start_date,$this->_end_date);
         }
         if ($room_list->isNotEmpty()) {
            $temp_array2 = array();
            $sub_room_item = $room_list->getFirst();
            while ($sub_room_item) {
               if ( $this->_room_status == 'community' ) {
                  if ( $sub_room_item->isCommunityRoom() ) {
                     $temp_array2['is_open']       = $room->isOpen();
                     $temp_array2['creation_date'] = $sub_room_item->getCreationDate();
                     $temp_array2['item_id']       = $sub_room_item->getItemID();
                     $temp_array2['type']          = $sub_room_item->getItemType();
                     $temp_array2['title']         = $sub_room_item->getTitle();
                     $temp_array2['active']        = 0;
                     if ( $sub_room_item->isActive($this->_start_date,$this->_end_date) ) {
                        $temp_array2['active']++;
                        $temp_array2['active_cr']  = 1;
                     }
                     $temp_array2['used']          = 1;
                     $temp_array2['used_cr']       = 1;
                     $temp_array2['all']           = 1;
                     $temp_array2['all_cr']        = 1;
                     $temp_array2['ac_used']       = $sub_room_item->getCountUsedAccounts($this->_start_date,$this->_end_date);
                     $temp_array2['ac_open']       = $sub_room_item->getCountOpenAccounts($this->_start_date,$this->_end_date);
                     $temp_array2['ac_all']        = $sub_room_item->getCountAllAccounts($this->_start_date,$this->_end_date);

                     ########################################################################
                     # plugins - BEGIN
                     ########################################################################
                     global $c_etchat_enable;
                     if ( !empty($c_etchat_enable)
                          and $c_etchat_enable
                        ) {
                        $temp_array2['chat']      = $sub_room_item->getCountPluginWithLinkedRooms('etchat',$this->_start_date,$this->_end_date);
                     }
                     global $c_pmwiki;
                     if ( !empty($c_pmwiki)
                          and $c_pmwiki
                        ) {
                        $temp_array2['wiki']      = $sub_room_item->getCountPluginWithLinkedRooms('pmwiki',$this->_start_date,$this->_end_date);
                     }
                     global $c_plugin_array;
                     if ( isset($c_plugin_array)
                          and !empty($c_plugin_array)
                          and $room->isPortal()
                        ) {
                        foreach ($c_plugin_array as $plugin) {
                           if ( isset($room) ) {
                              $plugin_class = $this->_environment->getPluginClass($plugin);
                              if ( method_exists($plugin_class,'inStatistics') ) {
                                 if ( $plugin_class->inStatistics() ) {
                                    if ( $room->isPluginOn($plugin) ) {
                                       $temp_array2[$plugin] = $sub_room_item->getCountPluginWithLinkedRooms($plugin,$this->_start_date,$this->_end_date);
                                    } else {
                                       $temp_array2[$plugin] = 'n.a.';
                                    }
                                 }
                              }
                           }
                        }
                     }
                     ########################################################################
                     # plugins - END
                     ########################################################################

                     $this->_community_statistic_matrix[$sub_room_item->getItemID()] = $temp_array2;
                  } elseif ( $sub_room_item->isProjectRoom() ) {
                     $group_room_list = $sub_room_item->getGroupRoomList();
                     $active = $sub_room_item->isActive($this->_start_date,$this->_end_date);
                     $temp_group_room = array();
                     if ( !empty($group_room_list)
                          and $group_room_list->isNotEmpty()
                        ) {
                        $grouproom_item = $group_room_list->getFirst();
                        while ($grouproom_item) {
                           $active_gr = $grouproom_item->isActive($this->_start_date,$this->_end_date);
                           if ($active_gr) {
                              $used_gr = true;
                           } else {
                              $used_gr = $grouproom_item->isUsed($this->_start_date,$this->_end_date);
                           }
                           if ( isset($temp_group_room['all']) ) {
                              $temp_group_room['all']++;
                           } else {
                              $temp_group_room['all'] = 1;
                           }
                           if ( isset($temp_group_room['all_gr']) ) {
                              $temp_group_room['all_gr']++;
                           } else {
                              $temp_group_room['all_gr'] = 1;
                           }
                           if ($active_gr) {
                              if ( isset($temp_group_room['active']) ) {
                                 $temp_group_room['active']++;
                              } else {
                                 $temp_group_room['active'] = 1;
                              }
                              if ( isset($temp_group_room['active_gr']) ) {
                                 $temp_group_room['active_gr']++;
                              } else {
                                 $temp_group_room['active_gr'] = 1;
                              }
                           }
                           if ($used_gr) {
                              if ( isset($temp_group_room['used']) ) {
                                 $temp_group_room['used']++;
                              } else {
                                 $temp_group_room['used'] = 1;
                              }
                              if ( isset($temp_group_room['used_gr']) ) {
                                 $temp_group_room['used_gr']++;
                              } else {
                                 $temp_group_room['used_gr'] = 1;
                              }
                           }
                           $grouproom_item = $group_room_list->getNext();
                        }
                     }
                     $community_list = $sub_room_item->getCommunityList();
                     if ( isset($community_list)
                          and $community_list->isNotEmpty()
                        ) {
                        $community_room = $community_list->getFirst();
                        while ($community_room) {
                           if ( !isset($this->_community_statistic_matrix[$community_room->getItemID()]['all_gr']) ) {
                              $this->_community_statistic_matrix[$community_room->getItemID()]['all_gr'] = 0;
                           }
                           if ( !isset($this->_community_statistic_matrix[$community_room->getItemID()]['active_gr']) ) {
                              $this->_community_statistic_matrix[$community_room->getItemID()]['active_gr'] = 0;
                           }
                           if ( !isset($this->_community_statistic_matrix[$community_room->getItemID()]['used_gr']) ) {
                              $this->_community_statistic_matrix[$community_room->getItemID()]['used_gr'] = 0;
                           }
                           if ( isset($this->_community_statistic_matrix[$community_room->getItemID()]['all']) ) {
                              $this->_community_statistic_matrix[$community_room->getItemID()]['all']++;
                           } else {
                              $this->_community_statistic_matrix[$community_room->getItemID()]['all'] = 1;
                           }
                           if ( isset($this->_community_statistic_matrix[$community_room->getItemID()]['all_pr']) ) {
                              $this->_community_statistic_matrix[$community_room->getItemID()]['all_pr']++;
                           } else {
                              $this->_community_statistic_matrix[$community_room->getItemID()]['all_pr'] = 1;
                           }
                           if ( isset($this->_community_statistic_matrix[$community_room->getItemID()]['used']) ) {
                              $this->_community_statistic_matrix[$community_room->getItemID()]['used']++;
                           } else {
                              $this->_community_statistic_matrix[$community_room->getItemID()]['used'] = 1;
                           }
                           if ( isset($this->_community_statistic_matrix[$community_room->getItemID()]['used_pr']) ) {
                              $this->_community_statistic_matrix[$community_room->getItemID()]['used_pr']++;
                           } else {
                              $this->_community_statistic_matrix[$community_room->getItemID()]['used_pr'] = 1;
                           }
                           if ($active) {
                              if ( isset($this->_community_statistic_matrix[$community_room->getItemID()]['active']) ) {
                                 $this->_community_statistic_matrix[$community_room->getItemID()]['active']++;
                              } else {
                                 $this->_community_statistic_matrix[$community_room->getItemID()]['active'] = 1;
                              }
                              if ( isset($this->_community_statistic_matrix[$community_room->getItemID()]['active_pr']) ) {
                                 $this->_community_statistic_matrix[$community_room->getItemID()]['active_pr']++;
                              } else {
                                 $this->_community_statistic_matrix[$community_room->getItemID()]['active_pr'] = 1;
                              }
                           }

                           // grouproom
                           if ( !empty($temp_group_room['all']) ) {
                              $this->_community_statistic_matrix[$community_room->getItemID()]['all'] += $temp_group_room['all'];
                           }
                           if ( !empty($temp_group_room['all_gr']) ) {
                              $this->_community_statistic_matrix[$community_room->getItemID()]['all_gr'] += $temp_group_room['all_gr'];
                           }
                           if ( !empty($temp_group_room['active']) ) {
                              $this->_community_statistic_matrix[$community_room->getItemID()]['active'] += $temp_group_room['active'];
                           }
                           if ( !empty($temp_group_room['active_gr']) ) {
                              $this->_community_statistic_matrix[$community_room->getItemID()]['active_gr'] += $temp_group_room['active_gr'];
                           }
                           if ( !empty($temp_group_room['used']) ) {
                              $this->_community_statistic_matrix[$community_room->getItemID()]['used'] += $temp_group_room['used'];
                           }
                           if ( !empty($temp_group_room['used_gr']) ) {
                              $this->_community_statistic_matrix[$community_room->getItemID()]['used_gr'] += $temp_group_room['used_gr'];
                           }

                           $community_room = $community_list->getNext();
                        }
                     }
                     unset($temp_group_room);
                  }
               } else {
                  $temp_array2['type'] = $sub_room_item->getItemType();
                  $temp_array2['title'] = $sub_room_item->getTitle();
                  $temp_array2['is_open'] = $sub_room_item->isOpen();
                  $temp_array2['home_conf'] = $sub_room_item->getHomeConf();
                  if ($sub_room_item->withRubric(CS_ANNOUNCEMENT_TYPE)) {
                     $temp_array2[CS_ANNOUNCEMENT_TYPE] = $sub_room_item->getCountAnnouncements($this->_start_date,$this->_end_date);
                  }
                  if ($sub_room_item->withRubric(CS_DATE_TYPE)) {
                     $temp_array2[CS_DATE_TYPE] = $sub_room_item->getCountDates($this->_start_date,$this->_end_date);
                  }
                  if ($sub_room_item->withRubric(CS_DISCUSSION_TYPE)) {
                     $temp_array2[CS_DISCUSSION_TYPE] = $sub_room_item->getCountDiscussions($this->_start_date,$this->_end_date);
                  }
                  if ($sub_room_item->withRubric(CS_MATERIAL_TYPE)) {
                     $temp_array2[CS_MATERIAL_TYPE] = $sub_room_item->getCountMaterials($this->_start_date,$this->_end_date);
                  }
                  if ($sub_room_item->withRubric(CS_TOPIC_TYPE)) {
                     $temp_array2[CS_TOPIC_TYPE] = $sub_room_item->getCountTopics($this->_start_date,$this->_end_date);
                  }
                  if ($sub_room_item->withRubric(CS_GROUP_TYPE)) {
                     $temp_array2[CS_GROUP_TYPE] = $sub_room_item->getCountGroups($this->_start_date,$this->_end_date);
                  }
                  if ($sub_room_item->withRubric(CS_USER_TYPE) ) {
                     $temp_array2[CS_USER_TYPE] = $sub_room_item->getCountUsers($this->_start_date,$this->_end_date);
                  }
                  if ($sub_room_item->withRubric(CS_PROJECT_TYPE)) {
                     $temp_array2[CS_PROJECT_TYPE] = $sub_room_item->getCountProjects($this->_start_date,$this->_end_date);
                  }
                  $temp_array2['moderators'] = $this->_getContactModerators($sub_room_item);

                  ########################################################################
                  # plugins - BEGIN
                  ########################################################################
                  global $c_etchat_enable;
                  if ( !empty($c_etchat_enable)
                       and $c_etchat_enable
                     ) {
                     $temp_array2['chat']      = $sub_room_item->getCountPlugin('etchat',$this->_start_date,$this->_end_date);
                  }
                  global $c_pmwiki;
                  if ( !empty($c_pmwiki)
                       and $c_pmwiki
                     ) {
                     $temp_array2['wiki']      = $sub_room_item->getCountPlugin('pmwiki',$this->_start_date,$this->_end_date);
                  }
                  global $c_plugin_array;
                  if ( isset($c_plugin_array)
                       and !empty($c_plugin_array)
                       and $room->isPortal()
                     ) {
                     foreach ($c_plugin_array as $plugin) {
                        if ( isset($room) ) {
                           $plugin_class = $this->_environment->getPluginClass($plugin);
                           if ( method_exists($plugin_class,'inStatistics') ) {
                              if ( $plugin_class->inStatistics() ) {
                                 if ( $room->isPluginOn($plugin) ) {
                                    $temp_array2[$plugin] = $sub_room_item->getCountPlugin($plugin,$this->_start_date,$this->_end_date);
                                 } else {
                                    $temp_array2[$plugin] = 'n.a.';
                                 }
                              }
                           }
                        }
                     }
                  }
                  ########################################################################
                  # plugins - END
                  ########################################################################

                  $temp_array[] = $temp_array2;
               }
               $sub_room_item = $room_list->getNext();
            }
         }
         $retour['rooms'] = $temp_array;
      }

      $this->_pr_used = $this->_pr_used + $retour['used'];
      $this->_pr_used_gr = $this->_pr_used_gr + $retour['used_gr'];
      $this->_pr_used_pr = $this->_pr_used_pr + $retour['used_pr'];
      $this->_pr_used_cr = $this->_pr_used_cr + $retour['used_cr'];
      $this->_pr_all = $this->_pr_all + $retour['all'];
      $this->_pr_all_gr = $this->_pr_all_gr + $retour['all_gr'];
      $this->_pr_all_pr = $this->_pr_all_pr + $retour['all_pr'];
      $this->_pr_all_cr = $this->_pr_all_cr + $retour['all_cr'];
      $this->_pr_active = $this->_pr_active + $retour['active'];
      if ( !empty($retour['active_gr']) ) {
         $this->_pr_active_gr = $this->_pr_active_gr + $retour['active_gr'];
      }
      if ( !empty($retour['active_pr']) ) {
         $this->_pr_active_pr = $this->_pr_active_pr + $retour['active_pr'];
      }
      if ( !empty($retour['active_cr']) ) {
         $this->_pr_active_cr = $this->_pr_active_cr + $retour['active_cr'];
      }
      $this->_ac_used = $this->_ac_used + $retour['ac_used'];
      $this->_ac_open = $this->_ac_open + $retour['ac_open'];
      $this->_ac_all  = $this->_ac_all  + $retour['ac_all'];

      ########################################################################
      # plugins - BEGIN
      ########################################################################
      global $c_etchat_enable;
      if ( !empty($c_etchat_enable)
           and $c_etchat_enable
         ) {
         if ( !isset($this->_plugin_active['chat']) ) {
            $this->_plugin_active['chat'] = 0;
         }
         $this->_plugin_active['chat'] = $this->_plugin_active['chat'] + $retour['chat'];
      }
      global $c_pmwiki;
      if ( !empty($c_pmwiki)
           and $c_pmwiki
         ) {
         if ( !isset($this->_plugin_active['wiki']) ) {
            $this->_plugin_active['wiki'] = 0;
         }
         $this->_plugin_active['wiki'] = $this->_plugin_active['wiki'] + $retour['wiki'];
      }
      global $c_plugin_array;
      if ( isset($c_plugin_array)
           and !empty($c_plugin_array)
         ) {
         foreach ($c_plugin_array as $plugin) {
            $plugin_class = $this->_environment->getPluginClass($plugin);
            if ( method_exists($plugin_class,'inStatistics') ) {
               if ( $plugin_class->inStatistics() ) {
                  if ( !isset($this->_plugin_active[$plugin]) ) {
                     $this->_plugin_active[$plugin] = 0;
                  }
                  if ( !empty($retour[$plugin]) ) {
                     $this->_plugin_active[$plugin] = $this->_plugin_active[$plugin] + $retour[$plugin];
                  }
               }
            }
         }
      }
      ########################################################################
      # plugins - END
      ########################################################################

      return $retour;
   }

   function _getContactModerators ($room) {
      $retour = array();
      $mod_list = $room->getContactModeratorList();
       if (!$mod_list->isEmpty()) {
          $moderator = $mod_list->getFirst();
          while ($moderator) {
             $retour[] = $moderator->getFullname().' ['.$moderator->getEMail().']';
             $moderator = $mod_list->getNext();
          }
       }
      return $retour;
   }

   function asPrintableHTML () {
     return $this->asHTML();
   }

   /** get statistic view as HTML
    * this method returns the statistic view in HTML-Code
    *
    * @return string statistic view as HMTL
    */
   function asHTML () {

      $html  = LF.'<!-- BEGIN OF STATISTIC VIEW -->'.LF;
      $html .= LF.'<div class="indexform" style="padding:3px; font-size:10pt;">'.LF;

      $month_array = array($this->_translator->getMessage('DATES_JANUARY_LONG'),
      $this->_translator->getMessage('DATES_FEBRUARY_LONG'),
      $this->_translator->getMessage('DATES_MARCH_LONG'),
      $this->_translator->getMessage('DATES_APRIL_LONG'),
      $this->_translator->getMessage('DATES_MAY_LONG'),
      $this->_translator->getMessage('DATES_JUNE_LONG'),
      $this->_translator->getMessage('DATES_JULY_LONG'),
      $this->_translator->getMessage('DATES_AUGUST_LONG'),
      $this->_translator->getMessage('DATES_SEPTEMBER_LONG'),
      $this->_translator->getMessage('DATES_OCTOBER_LONG'),
      $this->_translator->getMessage('DATES_NOVEMBER_LONG'),
      $this->_translator->getMessage('DATES_DECEMBER_LONG'));

      $date = date("Y-m-d");
      $date_array = explode('-',$date);
      $current_time = localtime();
      $month = $current_time[4];
      $year = $current_time[5]+1900;
      $html .='<div>'.LF;
      $html .= '<div class="indexdate">'.$date_array[2].'. '.$month_array[$month].' '.$date_array[0].'</div>';
      // Heading
      if ($this->_show_title) {
         $html .= LF.'<h2 style="margin-bottom:10px; margin-top:0px;">'.LF.
                  '   '.$this->_translator->getMessage('SERVER_STATISTIC_TITLE').LF.
                  '</h2>'.LF;
      }
      $html .='<div class="infoborder" style="padding-top:10px; padding-bottom:0px; vertical-align:top; width:100%; font-size:2pt;">&nbsp;</div>'.LF;

      // select form
      if (!isset($_GET['mode']) or !$_GET['mode']=='print') {
         $html .= '<form action="'.curl($this->_environment->getCurrentContextID(), $this->_module, $this->_function,'').'" method="get" name="indexform">'.LF;
         $html .= '   <input type="hidden" name="cid" value="'.$this->_text_as_form($this->_environment->getCurrentContextID()).'"/>'.LF;
         $html .= '   <input type="hidden" name="mod" value="'.$this->_text_as_form($this->_module).'"/>'.LF;
         $html .= '   <input type="hidden" name="fct" value="'.$this->_text_as_form($this->_function).'"/>'.LF;
         $session = $this->_environment->getSession();
         if ( !$session->issetValue('cookie')
              or $session->getValue('cookie') == '0' ) {
            $html .= '   <input type="hidden" name="SID" value="'.$this->_text_as_form($session->getSessionID()).'"/>'.LF;
         }
         $html .= '<table summary="Layout"><tr>'.LF;
         $html .= $this->_getAdditionalFormFieldsAsHTML();
         $html .= '<td><br /><input name="option" value="'.$this->_translator->getMessage('COMMON_SHOW_BUTTON').'" type="submit"/></td>'.LF;
         $html .= '</tr>'.LF;
         $html .= '</table></form>'.LF;
      } else {
         $html .= '<table summary="Layout"><tr>'.LF;
         $html .= $this->_getAdditionalFormFieldsAsPrintableHTML();
         $html .= '</tr>'.LF;
         $html .= '</table>'.LF;
     }

      $html .= '</div>'.LF; // end of index form

      // Content
      $html .= '<table class="list" summary="Layout">'.LF;
      $html .= $this->_getTableheadAsHTML();
      $html .= $this->_getContentAsHTML();
      $html .= $this->_getTablefootAsHTML();
      $html .= '</table>'.LF;
      if ($this->_room_status != 'none') {
         $html .= $this->_getSubContentAsHTML();
      }
      $html .= $this->_getLegendAsHTML();
      $html .= '</div>'.LF; // end of index form
      $html .= '<!-- END OF STATISTIC VIEW -->'."\n\n";
      return $html;
   }

   /** get the description of the statistic view title as HTML
    * this method returns the description in HTML-Code
    *
    * @return string $this->_description as HMTL
    */
   function _getDescriptionAsHTML() {
   }

   function _getTableheadAsHTML () {

      $html  = '   <tr class="head">'.LF;

      $html .= '      <td style="width: 30%; border-bottom: 1px solid;" class="head" colspan="3">';
      $html .= '&nbsp;';
      $html .= '</td>'.LF;

      $html .= '      <td style="width: 2%;  border-bottom: 1px solid;" class="head">';
      $html .= '&nbsp;';
      $html .= '</td>'.LF;

      $html .= '      <td style="width: 25%; border-bottom: 1px solid; border-left: 1px solid;" class="head" colspan="12">';
      $html .= $this->_translator->getMessage('SERVER_STATISTIC_ROOMS');
      $html .= '</td>'.LF;

      $html .= '      <td style="width: 2%;  border-bottom: 1px solid;" class="head">';
      $html .= '&nbsp;';
      $html .= '</td>'.LF;

      $html .= '      <td style="width: 25%; border-bottom: 1px solid; border-left: 1px solid;" class="head" colspan="3">';
      $html .= $this->_translator->getMessage('SERVER_STATISTIC_ACCOUNTS');
      $html .= '</td>'.LF;

      #################################################################
      # plugins - BEGIN
      #################################################################
      global $c_etchat_enable;
      global $c_pmwiki;
      if ( ( !empty($c_etchat_enable)
             and $c_etchat_enable
           )
           or
           ( !empty($c_pmwiki)
             and $c_pmwiki
           )
         ) {
         $html .= '      <td style="width: 2%;  border-bottom: 1px solid;" class="head">';
         $html .= '&nbsp;';
         $html .= '</td>'.LF;

         $colspan = 0;
         if ( !empty($c_etchat_enable)
              and $c_etchat_enable
            ) {
            $colspan++;
         }
         if ( !empty($c_pmwiki)
              and $c_pmwiki
            ) {
            $colspan++;
         }
         global $c_plugin_array;
         if ( isset($c_plugin_array)
              and !empty($c_plugin_array)
            ) {
            foreach ($c_plugin_array as $plugin) {
               $plugin_class = $this->_environment->getPluginClass($plugin);
               if ( method_exists($plugin_class,'inStatistics') ) {
                  if ( $plugin_class->inStatistics() ) {
                     $colspan++;
                  }
               }
            }
         }
         $html .= '      <td style="width: 25%; border-bottom: 1px solid; border-left: 1px solid;" class="head" colspan="'.$colspan.'">';
         $html .= $this->_translator->getMessage('CONFIGURATION_PLUGIN_LINK');
         $html .= '</td>'.LF;
      }
      #################################################################
      # plugins - END
      #################################################################

      $html .= '   </tr>'.LF;


      $html  .= '   <tr class="head">'.LF;

      $html .= '      <td style="width: 1%;" class="head">';
      $html .= $this->_translator->getMessage('SERVER_STATISTIC_NO');
      $html .= '</td>'.LF;

      $html .= '      <td style="width: 20%;" class="head">';
      $html .= $this->_translator->getMessage('SERVER_STATISTIC_NAME');
      $html .= '</td>'.LF;

      $html .= '      <td style="width: 9%;" class="head">';
      $html .= $this->_translator->getMessage('SERVER_STATISTIC_START');
      $html .= '</td>'.LF;

      $html .= '      <td style="width: 2%;" class="head">';
      $html .= '&nbsp;';
      $html .= '</td>'.LF;

      $html .= '      <td style="width:6%;text-align:right;  border-left: 1px solid;" class="head">';
      $html .= $this->_translator->getMessage('SERVER_STATISTIC_PROJECTROOMS_ALL');
      $html .= '</td>'.LF;
      $html .= '      <td style="width:1%;text-align:right;" class="head">';
      $html .= 'cr';
      $html .= '</td>'.LF;
      $html .= '      <td style="width:1%;text-align:right;" class="head">';
      $html .= 'pr';
      $html .= '</td>'.LF;
      $html .= '      <td style="width:1%;text-align:right;" class="head">';
      $html .= 'gr';
      $html .= '</td>'.LF;
      $html .= '      <td style="width:6%;text-align:right; border-left: 1px solid;" class="head">';
      $html .= $this->_translator->getMessage('SERVER_STATISTIC_PROJECTROOMS_USED');
      $html .= '</td>'.LF;
      $html .= '      <td style="width:1%;text-align:right;" class="head">';
      $html .= 'cr';
      $html .= '</td>'.LF;
      $html .= '      <td style="width:1%;text-align:right;" class="head">';
      $html .= 'pr';
      $html .= '</td>'.LF;
      $html .= '      <td style="width:1%;text-align:right;" class="head">';
      $html .= 'gr';
      $html .= '</td>'.LF;
      $html .= '      <td style="width:6%;text-align:right; border-left: 1px solid;" class="head">';
      $html .= $this->_translator->getMessage('SERVER_STATISTIC_PROJECTROOMS_ACTIVE');
      $html .= '</td>'.LF;
      $html .= '      <td style="width:1%;text-align:right;" class="head">';
      $html .= 'cr';
      $html .= '</td>'.LF;
      $html .= '      <td style="width:1%;text-align:right;" class="head">';
      $html .= 'pr';
      $html .= '</td>'.LF;
      $html .= '      <td style="width:1%;text-align:right;" class="head">';
      $html .= 'gr';
      $html .= '</td>'.LF;
      $html .= '      <td style="width: 2%;" class="head">';
      $html .= '&nbsp;';
      $html .= '</td>'.LF;

      $html .= '      <td style="width: 9%;text-align:right;  border-left: 1px solid;" class="head">';
      $html .= $this->_translator->getMessage('SERVER_STATISTIC_ACCOUNTS_ALL');
      $html .= '</td>'.LF;
      $html .= '      <td style="width: 9%;text-align:right;" class="head">';
      $html .= $this->_translator->getMessage('SERVER_STATISTIC_ACCOUNTS_USED');
      $html .= '</td>'.LF;
      $html .= '      <td style="width: 9%;text-align:right;" class="head">';
      $html .= $this->_translator->getMessage('SERVER_STATISTIC_ACCOUNTS_OPEN');
      $html .= '</td>'.LF;

      #################################################################
      # plugins - BEGIN
      #################################################################

      global $c_etchat_enable;
      global $c_plugin_array;
      if ( ( !empty($c_etchat_enable)
             and $c_etchat_enable
           )
           or
           ( !empty($c_pmwiki)
             and $c_pmwiki
           )
           or !empty($c_plugin_array)
         ) {
         $html .= '      <td style="width: 2%;" class="head">';
         $html .= '&nbsp;';
         $html .= '</td>'.LF;
         if ( !empty($c_etchat_enable)
              and $c_etchat_enable
            ) {
            $html .= '      <td style="text-align:right; border-left: 1px solid;" class="head">';
            $html .= $this->_translator->getMessage('CONFIGURATION_STATISTIC_CHAT');
            $html .= '</td>'.LF;
         }
         if ( !empty($c_pmwiki)
              and $c_pmwiki
            ) {
            $html .= '      <td style="text-align:right;" class="head">';
            $html .= $this->_translator->getMessage('COMMON_WIKI_LINK');
            $html .= '</td>'.LF;
         }
         if ( isset($c_plugin_array)
              and !empty($c_plugin_array)
            ) {
            foreach ($c_plugin_array as $plugin) {
               $plugin_class = $this->_environment->getPluginClass($plugin);
               if ( method_exists($plugin_class,'inStatistics') ) {
                  if ( $plugin_class->inStatistics() ) {
                     $html .= '      <td style="text-align:right;" class="head">';
                     $html .= $plugin_class->getTitle();
                     $html .= '</td>'.LF;
                  }
               }
            }
         }
      }

      #################################################################
      # plugins - END
      #################################################################

      $html .= '   </tr>'.LF;

      return $html;
   }

    function _getAdditionalFormFieldsAsHTML () {
      $html  = '';

      // day1
      $html .= '<td class="key">&nbsp;'.$this->_translator->getMessage('SERVER_STATISTIC_CHOICE_DAY').BRLF;
      $html .= '   <select name="day1" size="1" style="width:50px;">'.LF;

      $selday = $this->_day1;
      for ($i=1; $i<=31; $i++) {
         $html .= '      <option value="'.$i.'"';
         if ( isset($selday) and $selday == $i ) {
            $html .= ' selected="selected"';
         }
         $html .= '>'.$i.'</option>'.LF;
      }

      $html .= '   </select>'.LF;
      $html .= '</td>'.LF;

      // month1
      $html .= '<td class="key">&nbsp;'.$this->_translator->getMessage('SERVER_STATISTIC_CHOICE_MONTH').BRLF;
      $html .= '   <select name="month1" size="1" style="width:50px;">'.LF;

      $selmonth = $this->_month1;
      for ($i=1; $i<=12; $i++) {
         $html .= '      <option value="'.$i.'"';
         if ( isset($selmonth) and $selmonth == $i ) {
            $html .= ' selected="selected"';
         }
         $html .= '>'.$i.'</option>'.LF;
      }

      $html .= '   </select>'.LF;
      $html .= '</td>'.LF;

      // year1
      $start_year = date('Y')-1;

      $html .= '<td class="key">&nbsp;'.$this->_translator->getMessage('SERVER_STATISTIC_CHOICE_YEAR').BRLF;
      $html .= '   <select name="year1" size="1" style="width:60px;">'.LF;

      $selyear = $this->_year1;
      for ($i=$start_year; $i<=date('Y'); $i++) {
         $html .= '      <option value="'.$i.'"';
         if ( isset($selyear) and $selyear == $i ) {
            $html .= ' selected="selected"';
         }
         $html .= '>'.$i.'</option>'.LF;
      }

      $html .= '   </select>'.LF;
      $html .= '</td>'.LF;

      // space
      $html .= '<td class="key">';
      $html .= BR.'&nbsp;'.$this->_translator->getMessage('COMMON_TO').'&nbsp;';
      $html .= '</td>'.LF;

      // day2
      $html .= '<td class="key">&nbsp;'.$this->_translator->getMessage('SERVER_STATISTIC_CHOICE_DAY').BRLF;
      $html .= '   <select name="day2" size="1" style="width:50px;">'.LF;

      $selday = $this->_day2;
      for ($i=1; $i<=31; $i++) {
         $html .= '      <option value="'.$i.'"';
         if ( isset($selday) and $selday == $i ) {
            $html .= ' selected="selected"';
         }
         $html .= '>'.$i.'</option>'.LF;
      }

      $html .= '   </select>'.LF;
      $html .= '</td>'.LF;

      // month2
      $html .= '<td class="key">&nbsp;'.$this->_translator->getMessage('SERVER_STATISTIC_CHOICE_MONTH').BRLF;
      $html .= '   <select name="month2" size="1" style="width:50px;">'.LF;

      $selmonth = $this->_month2;
      for ($i=1; $i<=12; $i++) {
         $html .= '      <option value="'.$i.'"';
         if ( isset($selmonth) and $selmonth == $i ) {
            $html .= ' selected="selected"';
         }
         $html .= '>'.$i.'</option>'.LF;
      }

      $html .= '   </select>'.LF;
      $html .= '</td>'.LF;

      // year2
      $html .= '<td class="key">&nbsp;'.$this->_translator->getMessage('SERVER_STATISTIC_CHOICE_YEAR').BRLF;
      $html .= '   <select name="year2" size="1" style="width:60px;">'.LF;

      $selyear = $this->_year2;
      for ($i=$start_year; $i<=date('Y'); $i++) {
         $html .= '      <option value="'.$i.'"';
         if ( isset($selyear) and $selyear == $i ) {
            $html .= ' selected="selected"';
         }
         $html .= '>'.$i.'</option>'.LF;
      }

      $html .= '   </select>'.LF;
      $html .= '</td>'.LF;

      // space
      $html .= '<td class="key">';
      $html .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
      $html .= '</td>'.LF;


      // show used or active rooms
      $html .= '<td class="key">&nbsp;'.$this->_translator->getMessage('SERVER_STATISTIC_CHOICE_ROOM').BRLF;
      $html .= '   <select name="room_status" size="1" style="width:150px;">'.LF;

      $selroomstatus = $this->_room_status;
      $html .= '      <option value="none"';
      if ( isset($selroomstatus) and $selroomstatus == "none" ) {
         $html .= ' selected="selected"';
      }
      $html .= '>'.$this->_translator->getMessage('SERVER_STATISTIC_CHOICE_ROOM_NONE').'</option>'.LF;

      $selroomstatus = $this->_room_status;
      $html .= '      <option value="community"';
      if ( isset($selroomstatus) and $selroomstatus == "community" ) {
         $html .= ' selected="selected"';
      }
      $html .= '>'.$this->_translator->getMessage('SERVER_STATISTIC_CHOICE_ROOM_COMMUNITY').'</option>'.LF;

      $html .= '      <option value="active"';
      if ( isset($selroomstatus) and $selroomstatus == "active" ) {
         $html .= ' selected="selected"';
      }
      $html .= '>'.$this->_translator->getMessage('SERVER_STATISTIC_CHOICE_ROOM_ACTIVE').'</option>'.LF;

      $html .= '      <option value="used"';
      if ( isset($selroomstatus) and $selroomstatus == "used" ) {
         $html .= ' selected="selected"';
      }
      $html .= '>'.$this->_translator->getMessage('SERVER_STATISTIC_CHOICE_ROOM_USED').'</option>'.LF;

      $html .= '   </select>'.LF;
      $html .= '</td>'.LF;

      return $html;
   }

    function _getAdditionalFormFieldsAsPrintableHTML () {
      $html  = '';
      $html .= '<td>'.LF;
      $html .= $this->_day1;
      $html .= '.';
      $html .= $this->_month1;
      $html .= '.';
      $html .= $this->_year1.LF;
      $html .= '&nbsp;'.$this->_translator->getMessage('COMMON_TO').'&nbsp;'.LF;
      $html .= $this->_day2;
      $html .= '.';
      $html .= $this->_month2;
      $html .= '.';
      $html .= $this->_year2.LF;
      $html .= '&nbsp;&nbsp;-&nbsp;&nbsp;';
      $selroomstatus = $this->_room_status;
      if ( isset($selroomstatus) and $selroomstatus == "none" ) {
         $html .= $this->_translator->getMessage('SERVER_STATISTIC_CHOICE_ROOM_NONE').LF;
      } elseif ( isset($selroomstatus) and $selroomstatus == "active" ) {
         $html .= $this->_translator->getMessage('SERVER_STATISTIC_CHOICE_ROOM_ACTIVE').LF;
      } elseif ( isset($selroomstatus) and $selroomstatus == "used" ) {
         $html .= $this->_translator->getMessage('SERVER_STATISTIC_CHOICE_ROOM_USED').LF;
      }
      $html .= '</td>'.LF;

      return $html;
   }

   function _getTablefootAsHTML () {
   }

   /** get the content of the statistic view as HTML
    * this method returns the content in HTML-Code
    *
    * @return string $this->_list as HMTL
    */
   function _getContentAsHTML( $type = '', $portal_id = 0 ) {
      $this->_execute();
      if ( empty($type) ) {
         $value_array = $this->_statistic_matrix;
      } elseif ( $type == 'community' ) {
         $value_array = $this->_community_statistic_matrix;
      }
      if ( !empty($portal_id) ) {
         $portal_manager = $this->_environment->getPortalManager();
         $portal_item = $portal_manager->getItem($portal_id);
         $community_id_array = $portal_item->getCommunityIDArray();
      }

      $colspan = 20;
      $plugin_show = false;
      global $c_etchat_enable;
      if ( !empty($c_etchat_enable)
           and $c_etchat_enable
         ) {
         $plugin_show = true;
         $colspan++;
      }
      global $c_pmwiki;
      if ( !empty($c_pmwiki)
           and $c_pmwiki
         ) {
         $plugin_show = true;
         $colspan++;
      }
      global $c_plugin_array;
      if ( isset($c_plugin_array)
           and !empty($c_plugin_array)
         ) {
         foreach ($c_plugin_array as $plugin) {
            $plugin_class = $this->_environment->getPluginClass($plugin);
            if ( method_exists($plugin_class,'inStatistics') ) {
               if ( $plugin_class->inStatistics() ) {
                  $plugin_show = true;
                  $colspan++;
               }
            }
         }
      }
      if ( $plugin_show ) {
         $colspan++;
      }

      $html = '';
      $count = count($value_array);
      if ( $count == 0 ) {
         $html .= '<tr class="list"><td class="even" style="border-bottom: 0px;" colspan="'.$colspan.'">'.$this->_translator->getMessage('COMMON_NO_ENTRIES').'</td></tr>';
      } else {
         $i = 1;
         $row_show = false;
         foreach ($value_array as $row) {
            if ( empty($type)
                 or ( $type == 'community'
                      and !empty($row['item_id'])
                      and in_array($row['item_id'],$community_id_array)
                    )
               ) {
               $html .= $this->_getRowAsHTML($row, $i++);
               $row_show = true;
            }
         }
         if ( !$row_show ) {
            $html .= '<tr class="list"><td class="even" style="border-bottom: 0px;" colspan="'.$colspan.'">'.$this->_translator->getMessage('COMMON_NO_ENTRIES').'</td></tr>';
         }
         if ( empty($type) ) {
            $html .= $this->_getSumAsHTML();
         }
      }
      return $html;
   }

   /** get the single entry of the statistic view as HTML
    * this method returns the single entry in HTML-Code
    *
    * @returns string $item as HMTL
    *
    * @param object item     the single list entry
    * @param int    count    count list entry
    */
   function _getRowAsHTML ($row, $count) {
      $shown_entry_number = $count;
      if ($shown_entry_number%2 == 0){
         $style='class="odd"';
      }else{
         $style='class="even"';
      }
      $html  = '   <tr class="list">'.LF;
      $html .= '      <td '.$style.'>'.$shown_entry_number.'</td>'.LF;
      $html .= '      <td '.$style.'>'.$this->_getName($row).'</td>'.LF;
      $html .= '      <td '.$style.'>'.$this->_getStart($row).'</td>'.LF;
      $html .= '      <td '.$style.'>&nbsp;</td>'.LF;
      $html .= '      '.$this->_getRooms($row,$style).''.LF;
      $html .= '      <td '.$style.'>&nbsp;</td>'.LF;
      $html .= '      '.$this->_getAccounts($row,$style).''.LF;

      #################################################################
      # plugins - BEGIN
      #################################################################
      global $c_etchat_enable;
      global $c_pmwiki;
      global $c_plugin_array;
      if ( ( !empty($c_etchat_enable)
             and $c_etchat_enable
           )
           or
           ( !empty($c_pmwiki)
             and $c_pmwiki
           )
           or !empty($c_plugin_array)
         ) {
         $html .= '      <td '.$style.'>&nbsp;</td>'.LF;
         $html .= '      '.$this->_getPlugins($row,$style).''.LF;
      }
      #################################################################
      # plugins - END
      #################################################################

      $html .= '   </tr>'.LF;

      return $html;
   }

   function _getSumAsHTML () {
      $html  = '   <tr class="list">'.LF;
      $html .= '      <td class="head">&nbsp;</td>'.LF;
      $html .= '      <td class="head">'.$this->_translator->getMessage('SERVER_STATISTIC_SUM').'</td>'.LF;
      $html .= '      <td  class="head">&nbsp;</td>'.LF;
      $html .= '      <td  class="head">&nbsp;</td>'.LF;
      $html .= '      <td  class="head" style="text-align:right; border-left: 1px solid;">'.$this->_pr_all.'</td>'.LF;
      $html .= '      <td  class="head" style="text-align:right;">'.$this->_pr_all_cr.'</td>'.LF;
      $html .= '      <td  class="head" style="text-align:right;">'.$this->_pr_all_pr.'</td>'.LF;
      $html .= '      <td  class="head" style="text-align:right;">'.$this->_pr_all_gr.'</td>'.LF;
      $html .= '      <td  class="head" style="text-align:right; border-left: 1px solid;">'.$this->_pr_used.'</td>'.LF;
      $html .= '      <td  class="head" style="text-align:right;">'.$this->_pr_used_cr.'</td>'.LF;
      $html .= '      <td  class="head" style="text-align:right;">'.$this->_pr_used_pr.'</td>'.LF;
      $html .= '      <td  class="head" style="text-align:right;">'.$this->_pr_used_gr.'</td>'.LF;
      $html .= '      <td  class="head" style="text-align:right; border-left: 1px solid;">'.$this->_pr_active.'</td>'.LF;
      $html .= '      <td  class="head" style="text-align:right;">'.$this->_pr_active_cr.'</td>'.LF;
      $html .= '      <td  class="head" style="text-align:right;">'.$this->_pr_active_pr.'</td>'.LF;
      $html .= '      <td  class="head" style="text-align:right;">'.$this->_pr_active_gr.'</td>'.LF;
      $html .= '      <td  class="head">&nbsp;</td>'.LF;
      $html .= '      <td  class="head" style="text-align:right; border-left: 1px solid;">'.$this->_ac_all.'</td>'.LF;
      $html .= '      <td  class="head" style="text-align:right;">'.$this->_ac_used.'</td>'.LF;
      $html .= '      <td  class="head" style="text-align:right;">'.$this->_ac_open.'</td>'.LF;

      ########################################################################
      # plugins - BEGIN
      ########################################################################
      global $c_etchat_enable;
      global $c_pmwiki;
      global $c_plugin_array;
      if ( ( !empty($c_etchat_enable)
             and $c_etchat_enable
           )
           or
           ( !empty($c_pmwiki)
             and $c_pmwiki
           )
           or !empty($c_plugin_array)
         ) {
         $html .= '      <td  class="head">&nbsp;</td>'.LF;
         if ( isset($this->_plugin_active['chat']) ) {
            $html .= '      <td  class="head" style="text-align:right; border-left: 1px solid;">'.$this->_plugin_active['chat'].'</td>'.LF;
         }
         if ( isset($this->_plugin_active['wiki']) ) {
            $html .= '      <td  class="head" style="text-align:right;">'.$this->_plugin_active['wiki'].'</td>'.LF;
         }
         if ( isset($c_plugin_array)
              and !empty($c_plugin_array)
            ) {
            foreach ($c_plugin_array as $plugin) {
               $plugin_class = $this->_environment->getPluginClass($plugin);
               if ( method_exists($plugin_class,'inStatistics') ) {
                  if ( isset($this->_plugin_active[$plugin]) ) {
                     $html .= '      <td  class="head" style="text-align:right;">'.$this->_plugin_active[$plugin].'</td>'.LF;
                  }
               }
            }
         }
      }
      ########################################################################
      # plugins - END
      ########################################################################

      $html .= '   </tr>'.LF;

      return $html;
   }

   /** get the name of the item
    * this method returns the item name in the right formatted style
    *
    * @param object item     the single list entry
    *
    * @return string name
    */
   function _getName ($item) {
      $name = $this->_text_as_html_short($item['title']);
      if ($this->_room_status != 'none' and !$this->isPrintableView()) {
         $name = '<a href="#'.$item['item_id'].'">'.$name.'</a>';
      }
     if ($item['is_open'] == 0) {
       $name = '<span class="disabled">'.$name.'</span>';
     }
      return $name;
   }

   /** get the start date of the item
    * this method returns the item start date in the right formatted style
    *
    * @param object item     the single list entry
    *
    * @return string start date
    */
   function _getStart($item){
      $date = $item['creation_date'];
      $date = $this->_translator->getDateInLang($date);
      return $this->_text_as_html_short($date);
   }

   /** get the name of the item
    * this method returns the item name in the right formatted style
    *
    * @param object item     the single list entry
    *
    * @return string name
    */
   function _getRooms ($item, $style) {

      if ( !isset($item['all_cr']) ) {
         $item['all_cr'] = 0;
      }
      if ( !isset($item['all_pr']) ) {
         $item['all_pr'] = 0;
      }
      if ( !isset($item['all_gr']) ) {
         $item['all_gr'] = 0;
      }

      if ( !isset($item['used_cr']) ) {
         $item['used_cr'] = 0;
      }
      if ( !isset($item['used_pr']) ) {
         $item['used_pr'] = 0;
      }
      if ( !isset($item['used_gr']) ) {
         $item['used_gr'] = 0;
      }

      if ( !isset($item['active_cr']) ) {
         $item['active_cr'] = 0;
      }
      if ( !isset($item['active_pr']) ) {
         $item['active_pr'] = 0;
      }
      if ( !isset($item['active_gr']) ) {
         $item['active_gr'] = 0;
      }

      $retour  = ''.LF;
      $retour .= '      <td '.$style.' style="border-left:1px solid black; text-align:right;">'.LF;
      $retour .= $item['all'].LF;
      $retour .= '      </td>'.LF;
      $retour .= '      <td '.$style.' style="text-align:right;">'.LF;
      $retour .= $item['all_cr'].LF;
      $retour .= '      </td>'.LF;
      $retour .= '      <td '.$style.' style="text-align:right;">'.LF;
      $retour .= $item['all_pr'].LF;
      $retour .= '      </td>'.LF;
      $retour .= '      <td '.$style.' style="text-align:right;">'.LF;
      $retour .= $item['all_gr'].LF;
      $retour .= '      </td>'.LF;
      $retour .= '      <td '.$style.' style="border-left:1px solid black; text-align:right;">'.LF;
      $retour .= $item['used'].LF;
      $retour .= '      </td>'.LF;
      $retour .= '      <td '.$style.' style="text-align:right;">'.LF;
      $retour .= $item['used_cr'].LF;
      $retour .= '      </td>'.LF;
      $retour .= '      <td '.$style.' style="text-align:right;">'.LF;
      $retour .= $item['used_pr'].LF;
      $retour .= '      </td>'.LF;
      $retour .= '      <td '.$style.' style="text-align:right;">'.LF;
      $retour .= $item['used_gr'].LF;
      $retour .= '      </td>'.LF;
      $retour .= '      <td '.$style.' style="border-left:1px solid black; text-align:right;">'.LF;
      $retour .= $item['active'].LF;
      $retour .= '      </td>'.LF;
      $retour .= '      <td '.$style.' style="text-align:right;">'.LF;
      $retour .= $item['active_cr'].LF;
      $retour .= '      </td>'.LF;
      $retour .= '      <td '.$style.' style="text-align:right;">'.LF;
      $retour .= $item['active_pr'].LF;
      $retour .= '      </td>'.LF;
      $retour .= '      <td '.$style.' style="text-align:right;">'.LF;
      $retour .= $item['active_gr'].LF;
      $retour .= '      </td>'.LF;
      return $retour;
   }

   /** get the name of the item
    * this method returns the item name in the right formatted style
    *
    * @param object item     the single list entry
    *
    * @return string name
    */
   function _getAccounts ($item, $style) {
      $retour  = ''.LF;
      $retour .= '      <td  '.$style.' style="border-left:1px solid black; text-align:right;">'.LF;
      $retour .= $item['ac_all'].LF;
      $retour .= '      </td>'.LF;
      $retour .= '      <td  '.$style.' style="text-align:right;">'.LF;
      $retour .= $item['ac_used'].LF;
      $retour .= '      </td>'.LF;
      $retour .= '      <td  '.$style.' style="text-align:right;">'.LF;
      $retour .= $item['ac_open'].LF;
      $retour .= '      </td>'.LF;

      return $retour;
   }


   ###############################################
   # Sub Content: List of Rooms
   ###############################################

   function _getSubContentAsHTML () {
      $retour = '';
      foreach ($this->_statistic_matrix as $row) {
         $retour .= '<a name="'.$row['item_id'].'"></a>'.LF;
         $retour .= '<br /><hr/><br />'.LF;
         $title_text = $this->_text_as_html_short($row['title']);
         if ($row['is_open'] != 1) {
            $title_text .= ' <span class="changed">['.$this->_translator->getMessage('COMMON_CLOSED').']</span>';
         }
         $retour .= '<h2 style="margin-bottom:10px; margin-top:0px;">'.$title_text.'</h2>'.LF;
         if ( $this->_room_status == 'community' ) {
            $retour .= '<table class="list" summary="Layout">'.LF;
            $retour .= $this->_getTableheadAsHTML();
            $retour .= $this->_getContentAsHTML('community',$row['item_id']);
            $retour .= '</table>'.LF;
         } else {
            $retour .= '<table class="list" summary="Layout">'.LF;
            $retour .= $this->_getSubTableHeadAsHTML();
            $retour .= $this->_getRoomsOfPortalAsHTML($row['rooms']);
            $retour .= '</table>'.LF;
         }
      }
      return $retour;
   }

   function _getRoomsOfPortalAsHTML ($room_array) {
      $retour = '';
      if (!empty($room_array)) {
         $i = 1;
        foreach ($room_array as $room) {
            $retour .= '   <tr>'.LF;
            $retour .= $this->_getRoomAsHTML($room,$i);
            $retour .= '   </tr>'.LF;
            $i++;
         }
      } else {
        $retour .= '   <tr class="list">'.LF;
        $retour .= '      <td class="even" colspan="6">'.LF;
        $retour .= $this->_translator->getMessage('SERVER_STATISTIC_NO_ACTIVE_PROJECTROOMS');
        $retour .= '      </td>'.LF;
        $retour .= '   </tr>'.LF;
      }
      return $retour;
   }

   function _getRoomAsHTML ($room, $count) {
      $retour = '';
      $shown_entry_number = $count;
      if ($shown_entry_number%2 == 0){
         $style='class="odd"';
      }else{
         $style='class="even"';
      }

      $retour .= '      <td '.$style.' style="vertical-align:top;">';
      $retour .= $count;
      $retour .= '</td>'.LF;

      $title_room = $room['title'];
      if (!empty($title_room)) {
         $name_text  = '<span style="font-weight: bold;">'.$title_room.'</span>';
         if ($room['is_open'] != 1) {
            $name_text .= ' <span class="changed">['.$this->_translator->getMessage('COMMON_CLOSED').']</span>';
         }
         $temp_roomtype = mb_strtoupper($room['type'], 'UTF-8');
         // ---> Remark for testing: Login as root, configure server, server statistics, choose active/used rooms <---
         switch( $temp_roomtype )
         {
            case 'COMMUNITY':
               $name_text .= ' ('.$this->_translator->getMessage('SERVER_STATISTIC_COMMUNITYROOM').')';
               break;
            case 'PROJECT':
               $name_text .= ' ('.$this->_translator->getMessage('SERVER_STATISTIC_PROJECTROOM').')';
               break;
            default:                     // "Bitte Messagetag-Fehler melden"
               $name_text .= $this->_translator->getMessage('COMMON_MESSAGETAG_ERROR')." cs_statistic_view Zl.846";
               break;
         }
      }

      if (!empty($room['moderators'])) {
         $name_text .= '<br />'.implode(BRLF,$room['moderators']);
      }
      $retour .= '      <td  '.$style.' style="vertical-align:top;">';
      $retour .= $name_text;
      $retour .= '</td>'.LF;

      $retour .= '      <td  '.$style.' style="vertical-align:top;">';
      $home_conf_array = explode(',',$room['home_conf']);
      $temp_text = '';
      $first = true;
      foreach ($home_conf_array as $rubric) {
         $rubric_array = explode('_',$rubric);
         if (isset($room[$rubric_array[0]])) {
            if ($first) {
               $first = false;
            } else {
               $temp_text .= BRLF;
            }
            $temp_rubric = mb_strtoupper($rubric_array[0], 'UTF-8');
            // ---> Remark for testing: Login as root, configure server, server statistics, choose active/used rooms, look at "Rubric activity" <---
            switch( $temp_rubric )
            {
                case 'ANNOUNCEMENT':
                   $temp_text .= $this->_translator->getMessage('SERVER_STATISTIC_RUBRIC_ANNOUNCEMENT');
                   break;
                case 'DATE':
                   $temp_text .= $this->_translator->getMessage('SERVER_STATISTIC_RUBRIC_DATE');
                   break;
                case 'DISCUSSION':
                   $temp_text .= $this->_translator->getMessage('SERVER_STATISTIC_RUBRIC_DISCUSSION');
                   break;
                case 'GROUP':
                   $temp_text .= $this->_translator->getMessage('SERVER_STATISTIC_RUBRIC_GROUP');
                   break;
                case 'INSTITUTION':
                   $temp_text .= $this->_translator->getMessage('SERVER_STATISTIC_RUBRIC_INSTITUTION');
                   break;
                case 'MATERIAL':
                   $temp_text .= $this->_translator->getMessage('SERVER_STATISTIC_RUBRIC_MATERIAL');
                   break;
                case 'PROJECT':
                   $temp_text .= $this->_translator->getMessage('SERVER_STATISTIC_RUBRIC_PROJECT');
                   break;
                case 'TODO':
                   $temp_text .= $this->_translator->getMessage('SERVER_STATISTIC_RUBRIC_TODO');
                   break;
                case 'TOPIC':
                   $temp_text .= $this->_translator->getMessage('SERVER_STATISTIC_RUBRIC_TOPIC');
                   break;
                case 'USER':
                   $temp_text .= $this->_translator->getMessage('SERVER_STATISTIC_RUBRIC_USER');
                   break;
                default:                     // "Bitte Messagetag-Fehler melden"
                   $temp_text .= $this->_translator->getMessage('COMMON_MESSAGETAG_ERROR')." cs_statistic_view Zl.892";
                   break;
            }
            $temp_text .= ': ' . $room[$rubric_array[0]];
         }
      }
      $retour .= $temp_text;

      ########################################################################
      # plugins - BEGIN
      ########################################################################
      global $c_etchat_enable;
      global $c_pmwiki;
      global $c_plugin_array;
      if ( ( !empty($c_etchat_enable)
             and $c_etchat_enable
           )
           or
           ( !empty($c_pmwiki)
             and $c_pmwiki
           )
           or !empty($c_plugin_array)
         ) {
         if ( isset($room['chat']) ) {
            $retour .= BRLF.$this->_translator->getMessage('CHAT_CHAT').': '.$room['chat'].LF;
         }
         if ( isset($room['wiki']) ) {
            $retour .= BRLF.$this->_translator->getMessage('COMMON_WIKI_LINK').': '.$room['wiki'].LF;
         }
         if ( isset($c_plugin_array)
              and !empty($c_plugin_array)
            ) {
            foreach ($c_plugin_array as $plugin) {
               $plugin_class = $this->_environment->getPluginClass($plugin);
               if ( method_exists($plugin_class,'inStatistics') ) {
                  if ( isset($room[$plugin]) ) {
                     $retour .= BRLF.$plugin_class->getTitle().': '.$room[$plugin].LF;
                  }
               }
            }
         }
      }
      ########################################################################
      # plugins - END
      ########################################################################

      $retour .= '</td>'.LF;

      return $retour;
   }

   function _getSubTableheadAsHTML () {
      $html  = '';
      $html .= '   <tr class="list">'.LF;

      $html .= '      <td class="head" style="width: 1%;">';
      $html .= $this->_translator->getMessage('SERVER_STATISTIC_NO');
      $html .= '</td>'.LF;

      $html .= '      <td class="head" style="width: 59%; border-right:1px solid;">';
      $html .= $this->_translator->getMessage('SERVER_STATISTIC_NAME');
      $html .= '</td>'.LF;

      $html .= '      <td style="width: 40%;" class="head">';
      $html .= $this->_translator->getMessage('SERVER_STATISTIC_RUBRICS');
      $html .= '</td>'.LF;
      $html .= '   </tr>'.LF;

      return $html;
   }

   function _getLegendAsHTML () {
      $html  = '';
      $html .= '<div class="detailsubitem">';
      $html .= $this->_translator->getMessage('SERVER_STATISTIC_LEGEND');
      $html .= '</div>'.LF;
      return $html;
   }

   function getTitle ($not_with_actions = false) {
      $this->_show_title = false;
      $retour = '';
      if ($this->_environment->inServer() and !$not_with_actions) {
         $retour .= '<div class="actions" style="font-size: small; font-weight: normal;">'.LF;
         $retour .= ahref_curl($this->_environment->getCurrentContextID(), 'configuration', 'index',
                                '', $this->_translator->getMessage('ADMIN_INDEX')).LF;
         $retour .= '</div>'.LF;
      }
      $retour .= $this->_translator->getMessage('SERVER_STATISTIC_TITLE');
      return $retour;
   }

   private function _getPlugins ($item, $style) {
      $retour  = ''.LF;
      if ( isset($item['chat']) ) {
         $retour .= '      <td  '.$style.' style="border-left:1px solid black; text-align:right;">'.LF;
         $retour .= $item['chat'].LF;
         $retour .= '      </td>'.LF;
      }
      if ( isset($item['wiki']) ) {
         $retour .= '      <td  '.$style.' style="text-align:right;">'.LF;
         $retour .= $item['wiki'].LF;
         $retour .= '      </td>'.LF;
      }
      global $c_plugin_array;
      if ( isset($c_plugin_array)
           and !empty($c_plugin_array)
         ) {
         foreach ($c_plugin_array as $plugin) {
            $plugin_class = $this->_environment->getPluginClass($plugin);
            if ( method_exists($plugin_class,'inStatistics') ) {
               if ( isset($item[$plugin]) ) {
                  $retour .= '      <td  '.$style.' style="text-align:right;">'.LF;
                  $retour .= $item[$plugin].LF;
                  $retour .= '      </td>'.LF;
               }
            }
         }
      }

      return $retour;
   }
}
?>