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

/** upper class of the project manager
 */
include_once('classes/cs_context_manager.php');

/** misc functions are needed for extras field in database table
 */
include_once('functions/misc_functions.php');

/** class for database connection to the database table "project"
 * this class implements a database manager for the table "project"
 */
class cs_server_manager extends cs_context_manager {

   private $_statistic_matrix = NULL;

  /** constructor: cs_server_manager
    * the only available constructor, initial values for internal variables
    *
    * @param object cs_environment the environment
    */
   function __construct($environment) {
      $this->_db_table = 'server';
      $this->_room_type = CS_SERVER_TYPE;
      cs_context_manager::__construct($environment);
   }

   public function getStatistics ($server_item,$date_start,$date_end) {
      $retour = false;

      if ( isset($this->_statistic_matrix) ) {
         $retour = $this->_statistic_matrix;
      } else {
         if ( !empty($date_start) ) {
            $this->_start_date = $date_start;
            if ( !empty($date_end) ) {
               $this->_end_date = $date_end;
            } else {
               $this->_end_date = 'NOW';
            }
            if ( $this->_end_date == 'NOW' ) {
               $this->_end_date = date('Y-m-d').' 23:59:59';
            }
            if ( !empty($server_item) ) {
               $portal_list = $server_item->getPortalList();
               $current_item = $portal_list->getFirst();
               while ( $current_item ) {
                  $row = $this->_getStatisticRow($current_item);
                  $this->_statistic_matrix[] = $row;
                  $current_item = $portal_list->getNext();
               }

               // summary
               $temp_array = array();
               $temp_array['title'] = 'SUMMARY';
               $temp_array['active'] = $this->_pr_active;
               $temp_array['active_gr'] = $this->_pr_active_gr;
               $temp_array['active_pr'] = $this->_pr_active_pr;
               $temp_array['active_cr'] = $this->_pr_active_cr;
               $temp_array['used'] = $this->_pr_used;
               $temp_array['used_gr'] = $this->_pr_used_gr;
               $temp_array['used_pr'] = $this->_pr_used_pr;
               $temp_array['used_cr'] = $this->_pr_used_cr;
               $temp_array['all'] = $this->_pr_all;
               $temp_array['all_gr'] = $this->_pr_all_gr;
               $temp_array['all_pr'] = $this->_pr_all_pr;
               $temp_array['all_cr'] = $this->_pr_all_cr;
               $temp_array['ac_used'] = $this->_ac_used;
               $temp_array['ac_open'] = $this->_ac_open;
               $temp_array['ac_all'] = $this->_ac_all;
               foreach ( $this->_plugin_active as $key => $value ) {
                  $temp_array[$key] = $value;
               }
               $this->_statistic_matrix[] = $temp_array;
               unset($temp_array);
            }
         }
         $retour = $this->_statistic_matrix;
      }
      return $retour;
   }

   private function _getStatisticRow ($room) {
      set_time_limit(0);
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
}
?>