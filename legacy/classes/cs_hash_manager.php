<?php
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

class cs_hash_manager extends cs_manager {

   private $_context_item;

   private $_cached_rss_array = array();
   private $_cached_ical_array = array();

   public function __construct($environment) {
      $this->_db_table = 'hash';
      cs_manager::__construct($environment);
   }

   ###################
   # RSS HASH
   ###################

   public function getRSSHashForUser ( $user_item_id ) {
      $retour = '';
      if ( !empty($user_item_id) ) {
         if ( !$this->_issetRSSHashForUser($user_item_id) ) {
            $this->_saveHashesForUser($user_item_id);
         }
         $retour = $this->_getRSSHashForUser($user_item_id);
      }
      return $retour;
   }

   private function _createRSSHashForUser ( $user_item_id ) {
      $retour = '';
      if ( !empty($user_item_id) ) {
         include_once('functions/date_functions.php');
         $retour = md5($user_item_id*rand(1,99).getCurrentDateTimeInMySQL());
      }
      return $retour;
   }

   private function _issetRSSHashForUser ( $user_item_id ) {
      $retour = false;
      if ( !empty($user_item_id) ) {
         $hash = $this->_getRSSHashForUser($user_item_id);
         if ( isset($hash) and $hash != false ) {
            $retour = true;
         }
      }
      return $retour;
   }

   private function _getRSSHashForUser ( $user_item_id ) {
      $retour = false;
      if ( !empty($user_item_id) ) {
         if ( !isset($this->_cached_rss_array[$user_item_id]) or empty($this->_cached_rss_array[$user_item_id]) ) {
            $query = "SELECT rss FROM ".$this->addDatabasePrefix($this->_db_table)." WHERE user_item_id = '".$user_item_id."' LIMIT 1";
            $result = $this->_db_connector->performQuery($query);
            if ( isset($result[0]['rss']) and !empty($result[0]['rss']) ) {
               $retour = $result[0]['rss'];
               if ( $this->_cache_on ) {
                  $this->_cached_rss_array[$user_item_id] = $retour;
               }
            }
         } elseif ( !empty($this->_cached_rss_array[$user_item_id]) ) {
            $retour = $this->_cached_rss_array[$user_item_id];
         }
      }
      return $retour;
   }

   public function isRSSHashValid ( $rss_hash, $context_item ) {
      $retour = false;
      $query = "SELECT user_item_id FROM ".$this->addDatabasePrefix($this->_db_table)." WHERE rss = '".$rss_hash."' LIMIT 1";
      $result = $this->_db_connector->performQuery($query);
      if (!empty($result) ) {
         $retour = $context_item->mayEnterByUserItemID($result[0]['user_item_id']);
         if ( !$retour ) {
            $this->deleteHashesForUser($result[0]['user_item_id']);
         }
      }
      return $retour;
   }

   public function isAjaxHashValid ( $rss_hash, $context_item ) {
      $retour = false;
      $query = "SELECT user_item_id FROM ".$this->addDatabasePrefix($this->_db_table)." WHERE rss = '".$rss_hash."' LIMIT 1";
      $result = $this->_db_connector->performQuery($query);
      if (!empty($result) ) {
         $retour = $context_item->mayEnterByUserItemID($result[0]['user_item_id']);
         if ( !$retour ) {
            $this->deleteHashesForUser($result[0]['user_item_id']);
         }
      }
      return $retour;
   }
   
   public function deleteHashesForUser ( $user_item_id ) {
      if ( !empty($user_item_id) ) {
         $delete = "DELETE FROM ".$this->addDatabasePrefix($this->_db_table)." WHERE user_item_id = '".$user_item_id."'";
         $result = $this->_db_connector->performQuery($delete);
      }
   }

   private function _getUserItemIDForRSSHash ( $rss_hash ) {
      $retour = '';
      $query = "SELECT user_item_id FROM ".$this->addDatabasePrefix($this->_db_table)." WHERE rss = '".$rss_hash."' LIMIT 1";
      $result = $this->_db_connector->performQuery($query);
      if (!empty($result[0]['user_item_id'])) {
         $retour = $result[0]['user_item_id'];
      }
      return $retour;
   }

   public function getUserByRSSHash ( $rss_hash ) {
      $retour = NULL;
      $user_item_id = $this->_getUserItemIDForRSSHash($rss_hash);
      $user_manager = $this->_environment->getUserManager();
      $retour = $user_manager->getItem($user_item_id);
      return $retour;
   }

   ###################
   # ICAL HASH
   ###################

   public function getICalHashForUser ( $user_item_id ) {
      $retour = '';
      if ( !empty($user_item_id) ) {
         if ( !$this->_issetICalHashForUser($user_item_id) ) {
            $retour = $this->_saveHashesForUser($user_item_id);
         }
         $retour = $this->_getICalHashForUser($user_item_id);
      }
      return $retour;
   }

   private function _createICalHashForUser ( $user_item_id ) {
      $retour = '';
      if ( !empty($user_item_id) ) {
         include_once('functions/date_functions.php');
         $retour = md5($user_item_id * rand(1,99) . getCurrentDateTimeInMySQL() . time() * rand(100,200));
      }
      return $retour;
   }

   private function _issetICalHashForUser ( $user_item_id ) {
      $retour = false;
      if ( !empty($user_item_id) ) {
         $hash = $this->_getICalHashForUser($user_item_id);
         if ( isset($hash) and !empty($hash) ) {
            $retour = true;
         }
      }
      return $retour;
   }

   private function _getICalHashForUser ( $user_item_id ) {
      $retour = false;
      if ( !empty($user_item_id) ) {
         if ( !isset($this->_cached_ical_array[$user_item_id]) or empty($this->_cached_ical_array[$user_item_id]) ) {
            $query = "SELECT ical FROM ".$this->addDatabasePrefix($this->_db_table)." WHERE user_item_id = '".$user_item_id."' LIMIT 1";
            $result = $this->_db_connector->performQuery($query);
            if ( isset($result[0]['ical']) and !empty($result[0]['ical']) ) {
               $retour = $result[0]['ical'];
               if ( $this->_cache_on ) {
                  $this->_cached_ical_array[$user_item_id] = $retour;
               }
            }
         } elseif ( !empty($this->_cached_ical_array[$user_item_id]) ) {
            $retour = $this->_cached_ical_array[$user_item_id];
         }
      }
      return $retour;
   }

   public function isICalHashValid ( $ical_hash, $context_item ) {
      $retour = false;
      $query = "SELECT user_item_id FROM ".$this->addDatabasePrefix($this->_db_table)." WHERE ical = '".$ical_hash."' LIMIT 1";
      $result = $this->_db_connector->performQuery($query);
      if (!empty($result) ) {
         $retour = $context_item->mayEnterByUserItemID($result[0]['user_item_id']);
         if ( !$retour ) {
            $this->deleteHashesForUser($result[0]['user_item_id']);
         }
      }
      return $retour;
   }

   private function _getUserItemIDForICalHash ( $ical_hash ) {
      $retour = '';
     $query = "SELECT user_item_id FROM ".$this->addDatabasePrefix($this->_db_table)." WHERE ical = '".$ical_hash."' LIMIT 1";
     $result = $this->_db_connector->performQuery($query);
     if(!empty($result))
     {
           $retour = $result[0]['user_item_id'];
     }
      return $retour;
   }

   public function getUserByICalHash ( $ical_hash ) {
      $retour = NULL;
      $user_item_id = $this->_getUserItemIDForICalHash($ical_hash);
      $user_manager = $this->_environment->getUserManager();
      $retour = $user_manager->getItem($user_item_id);
      return $retour;
   }

   ###################
   # HASH COMMON
   ###################

   private function _saveHashesForUser ( $user_item_id ) {
      if ( !empty($user_item_id) ) {
         $update = false;

         $query = "SELECT * FROM ".$this->addDatabasePrefix($this->_db_table)." WHERE user_item_id = '".$user_item_id."';";
         $result = $this->_db_connector->performQuery($query);
         if ( isset($result[0]['rss']) and !empty($result[0]['rss'])  ) {
            $rss_hash = $result[0]['rss'];
            $update = true;
         } else {
            $rss_hash = $this->_createRSSHashForUser($user_item_id);
         }
         if ( isset($result[0]['ical']) and !empty($result[0]['ical'])  ) {
            $rss_hash = $result[0]['ical'];
            $update = true;
         } else {
            $ical_hash = $this->_createICalHashForUser($user_item_id);
         }

         if ( $update ) {
            $query = "UPDATE ".$this->addDatabasePrefix($this->_db_table)." SET rss = '".$rss_hash."', ical = '".$ical_hash."'
                      WHERE user_item_id ='".$user_item_id."';";
         } else {
            $query = "INSERT INTO ".$this->addDatabasePrefix($this->_db_table)." (`user_item_id`,`rss`,`ical`)
                      VALUES ('".$user_item_id."', '".$rss_hash."', '".$ical_hash."')";
         }
         $result = $this->_db_connector->performQuery($query);
      }
   }

    function moveFromDbToBackup($context_id)
    {
        $id_array = array();
        $user_manager = $this->_environment->getUserManager();
        $user_manager->setContextLimit($context_id);
        $user_manager->select();
        $user_list = $user_manager->get();
        $temp_user = $user_list->getFirst();
        while ($temp_user) {
            $id_array[] = $temp_user->getItemID();
            $temp_user = $user_list->getNext();
        }

        global $symfonyContainer;
        $c_db_backup_prefix = $symfonyContainer->getParameter('commsy.db.backup_prefix');

        if (!empty($id_array)) {
            if (!empty($context_id)) {
                $query = 'INSERT INTO ' . $c_db_backup_prefix . '_' . $this->_db_table . ' SELECT * FROM ' . $this->_db_table . ' WHERE ' . $this->_db_table . '.user_item_id IN (' . implode(",", $id_array) . ')';
                $this->_db_connector->performQuery($query);

                $this->deleteFromDb($context_id);
            }
        }
    }

    function moveFromBackupToDb($context_id)
    {
        $id_array = array();
        $zzz_user_manager = $this->_environment->getZzzUserManager();
        $zzz_user_manager->setContextLimit($context_id);
        $zzz_user_manager->select();
        $user_list = $zzz_user_manager->get();
        $temp_user = $user_list->getFirst();
        while ($temp_user) {
            $id_array[] = $temp_user->getItemID();
            $temp_user = $user_list->getNext();
        }

        global $symfonyContainer;
        $c_db_backup_prefix = $symfonyContainer->getParameter('commsy.db.backup_prefix');

        if (!empty($id_array)) {
            if (!empty($context_id)) {
                // archive
                if ($this->_environment->isArchiveMode()) {
                    $this->setWithoutDatabasePrefix();
                }
                $query = 'INSERT INTO ' . $this->_db_table . ' SELECT * FROM ' . $c_db_backup_prefix . '_' . $this->_db_table . ' WHERE ' . $c_db_backup_prefix . '_' . $this->_db_table . '.user_item_id IN (' . implode(",", $id_array) . ')';
                // archive
                if ($this->_environment->isArchiveMode()) {
                    $this->setWithDatabasePrefix();
                }
                $this->_db_connector->performQuery($query);

                $this->deleteFromDb($context_id, true);
            }
        }
    }
   
   function deleteFromDb($context_id, $from_backup = false){
       global $symfonyContainer;
       $c_db_backup_prefix = $symfonyContainer->getParameter('commsy.db.backup_prefix');

       $db_prefix = '';
       $id_array = array();
       if (!$from_backup) {
           $user_manager = $this->_environment->getUserManager();
           $user_manager->setContextLimit($context_id);
           $user_manager->select();
           $user_list = $user_manager->get();
           $temp_user = $user_list->getFirst();
           while ($temp_user) {
               $id_array[] = $temp_user->getItemID();
               $temp_user = $user_list->getNext();
           }
       } else {
           $db_prefix .= $c_db_backup_prefix . '_';
           $zzz_user_manager = $this->_environment->getZzzUserManager();
           $zzz_user_manager->setContextLimit($context_id);
           $zzz_user_manager->select();
           $user_list = $zzz_user_manager->get();
           $temp_user = $user_list->getFirst();
           while ($temp_user) {
               $id_array[] = $temp_user->getItemID();
               $temp_user = $user_list->getNext();
           }
       }

       if (!empty($id_array)) {
           $query = 'DELETE FROM ' . $db_prefix . $this->_db_table . ' WHERE ' . $db_prefix . $this->_db_table . '.user_item_id IN (' . implode(",", $id_array) . ')';
           $this->_db_connector->performQuery($query);
       }
   }
}
?>