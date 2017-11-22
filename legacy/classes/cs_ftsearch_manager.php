<?PHP
// $Id$
//
// Release $Name$
//
// Copyright (c)2007 Iver Jackewitz, Michael Kempe
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

/** class for ft search handling
 * this class implements a ftsearch manager
 */
class cs_ftsearch_manager extends cs_manager {

   /**
       * boolean - is ftsearch enabled
       */
   var $_ftsearch_enabled = NULL;

   /**
       * boolean - search in action
       */
   var $_search_status = false;

   /**
       * string - containing the words we are searching for
       */
   var $_words = NULL;

   /**
       * array - containing the ftearch result
       */
   var $_ft_file_ids = NULL;

   var $_incremental = false;

   private $_portal_id = NULL;
   private $_room_id = NULL;

   /** constructor: cs_ftsearch_manager
    * the only available constructor, initial values for internal variables
    *
    * @param object cs_environment the environment
    */
   function __construct($environment) {
      cs_manager::__construct($environment);
   }

   function setIncremental () {
      $this->_incremental = true;
   }

   /** set context for search
     * this method sets the search context
     *
     * @param are we in search context
     */
   function setSearchStatus($search_action) {
      $this->_search_status = $search_action;
   }

   /** set words for ft search
     * this method modifies and sets the words we are ft-searching for
     *
     * @param string words we are ft-searching for
     */
   function setWords($ftsearch) {
      $ftsearch_word = '';
      $ftsearch_words = '';
      foreach ($ftsearch as $xe) {
         $ftsearch_word = mb_strtolower($xe);
         // convert charset to UTF-8 for swish-e ;-)
         $ftsearch_word = iconv("ISO-8859-1", "UTF-8", $ftsearch_word);
         $ftsearch_words .= $ftsearch_word . '* ';
      }
      $ftsearch_words = trim($ftsearch_words);
      $this->_words = '"' . rtrim(str_replace('\(', '(', str_replace('\)', ')', str_replace('\*', '*', escapeshellcmd($ftsearch_words) . ' ')))) . '"';
   }

   /** get search context
   * this method returns search context
   *
   * @return boolean search or not
   */
   function getSearchStatus() {
      return $this->_search_status;
   }

   /** get list of ft results
   * this method returns a item list of ft results
   *
   * @return object cs_list
   */
   function getFTResultList($fileIdArray) {
      if (!empty($fileIdArray)) {
         $query = "SELECT item_iid FROM " . $this->addDatabasePrefix("item_link_file") . " WHERE deletion_date IS NULL AND (";

         foreach($fileIdArray as $fileId) {
            $query .= "file_id = " . encode(AS_DB, $fileId);

            if ($fileId != end($fileIdArray)) {
               $query .= " OR ";
            }
         }
         $query .= ")";

         $results = $this->_db_connector->performQuery($query);
         if (!isset($results)) {
            include_once('functions/error_functions.php');
            trigger_error("FT-Search: Problems loading material file links: " . $query, E_USER_WARNING);
         } else {
            $itemIds = array();
            foreach ($results as $result) {
               $itemIds[] = $result['item_iid'];
            }

            return $itemIds;
         }
      }

      return null;
   }

   /** get fulltext search result
   *
   * @param
   *
   * @return array of file_ids
   */
   function getFTResult() {
      // there are php swish-e functions, maybe use theses ???
      //send shell command
      $ft_results = array();
      $return = '';
      exec($this->_cmdline, $ft_results, $return);

      if(!empty($ft_results)){
         // Remark: problems with trailing german umlaute in search word
         if ( strstr($ft_results[0],'err: Index file error') and $ft_results[1] == '.') {
            if ( strstr($ft_results[0],'has an unknown format') ) {
               $pos1 = mb_strpos($ft_results[0],'"');
               $pos2 = mb_strrpos($ft_results[0],'"');
               if ( $pos1 != $pos2 ) {
                  $file_name = mb_substr($ft_results[0],$pos1+1,$pos2-$pos1-1);
                  if ( !empty($file_name)
                       and file_exists($file_name)
                     ) {
                     unlink($file_name);
                  }
               }
            } elseif ( !strstr($ft_results[0],'No such file or directory') ) {
               include_once('functions/error_functions.php');
               trigger_error("FT-Search: ". $ft_results[0], E_USER_WARNING);
            }
            unset ($ft_results);
            return array();
         } elseif ($ft_results[3] == 'err: no results' or $ft_results[4] == '.') {
            // error handling by '$ft_results[4] == '.' -> array id 4 as begin of result section
            //trigger_error("FT-Search: Nothing found or error performing search! " . $query, E_USER_WARNING);
            unset ($ft_results);
            return array();
         } else {
            for ($i = 0; $i < (count($ft_results) - 1); $i++) {
               $r_entry = trim($ft_results[$i]);
               if (mb_substr($r_entry, 0, 1) == "#") {
                  // e-swish result header - version info...
               } else {
                  // *** split string
                  // get file rank
                  $f_rank = mb_substr($r_entry, 0, mb_strpos($r_entry, " "));
                  $r_entry = mb_substr($r_entry, mb_strpos($r_entry, " ") + 1, mb_strlen($r_entry));

                  // get file size
                  $f_size = number_format(intval(mb_substr($r_entry, mb_strrpos($r_entry, " ") + 1, mb_strlen($r_entry))) / 1024, 0, ',', '.');
                  $r_entry = mb_substr($r_entry, 0, mb_strrpos($r_entry, " "));
                  // get file url
                  $f_url = mb_substr($r_entry, 0, mb_strpos($r_entry, " \""));
                  $r_entry = mb_substr($r_entry, mb_strpos($r_entry, " \"") + 1, mb_strlen($r_entry));

                  // get file id
                  if ( !strstr($r_entry,'cid')  ) {
                     $f_name = mb_substr($r_entry, 1, mb_strlen($r_entry) - 2);
                     $f_name = substr($f_name,0,strpos($f_name,'.'));
                  } else {
                     $f_name = substr($r_entry, 1, strlen($r_entry) - 2);
                     $f_name = substr($f_name, strpos($f_name, "_") + 1, strlen($f_name));
                     $f_name = substr($f_name, 0, strpos($f_name, "_"));
                  }
                  if ( !empty($f_name) ) {
                     $ft_fid = $f_name;
                  }
                  // append iid
                  if ( isset($ft_fid)
                       and !empty($ft_fid)
                       and is_numeric($ft_fid) ) {
                     $ft_fids[] = $ft_fid;
                  }
               }
            }
            // set file item ids for cs_file_item (file icon with border)
            if ( !empty($ft_fids) ) {
               $ft_fids = array_unique($ft_fids);
            } else {
               $ft_fids = array();
            }
            $this->_ft_file_ids = $ft_fids;
            return $ft_fids;
         }
      }
   }

   /** get result of file ids
   * this method returns file ids
   *
   * @return array file item_iid
   *
   */
   function getFileIDs() {
      return $this->_ft_file_ids;
   }

   function performFTSearch() {
      // search configuration in etc/cs_config.php
      // check if ftsearch is enabled
      global $ftsearch_enabled;
      if ( isset($ftsearch_enabled) ) {
         $this->_ftsearch_enabled = $ftsearch_enabled;
      } else {
         $this->_ftsearch_enabled = false;
      }

      if ($this->_ftsearch_enabled) {
         $ft_cids = NULL;
         global $search_engine;
         global $maxhits_offset;
         $disc_manager = $this->_environment->getDiscManager();
         $disc_manager->setPortalID($this->_getPortalID());
         $disc_manager->setContextID($this->_getRoomID());
         $searchsite_index = $disc_manager->getFilePath().'ft.index';
         $disc_manager->setPortalID($this->_environment->getCurrentPortalID());
         $disc_manager->setContextID($this->_environment->getCurrentContextID());
         unset($disc_manager);
         $this->_cmdline = $search_engine . escapeshellcmd(" -m $maxhits_offset -f $searchsite_index -w ") . $this->_words;

         // perform search
         $ft_fids = $this->getFTResult();
         if ( isset($ft_fids[0]) and ($ft_fids[0] != '') ) {
            $ft_cids = $this->getFTResultList($ft_fids);
            return $ft_cids;
         } else {
            unset ($ft_cids);
            return array();
         }
      }
   }

   public function rebuildFTIndex () {  	
      $disc_manager = $this->_environment->getDiscManager();
      $disc_manager->setPortalID($this->_getPortalID());
      $disc_manager->setContextID($this->_getRoomID());
      $index_base = $disc_manager->getFilePath();
      unset($disc_manager);
      
      $file2del = $index_base.DIRECTORY_SEPARATOR.'ft_idx.log';
      if ( file_exists($file2del) ) {
         unlink($file2del);      	
      }
      $file2del = $index_base.DIRECTORY_SEPARATOR.'ft.index.prop';
      if ( file_exists($file2del) ) {
      	unlink($file2del);
      }
      $file2del = $index_base.DIRECTORY_SEPARATOR.'ft.index';
      if ( file_exists($file2del) ) {
      	unlink($file2del);
         $this->buildFTIndexForIndexBase($index_base);
      }
   }

   function buildFTIndex() {

      // create new or update existing fulltext index
      global $ftsearch_enabled;
      if ( !isset($ftsearch_enabled) ) {
         $ftsearch_enabled = false;
      }
      if ($ftsearch_enabled) {

         $folder_string = '/tmp/swish-e';
         $folder = @opendir($folder_string);
         if (!$folder) {
            mkdir($folder_string);
         }
         $disc_manager = $this->_environment->getDiscManager();
         $disc_manager->setPortalID($this->_getPortalID());
         $disc_manager->setContextID($this->_getRoomID());
         $index_base = $disc_manager->getFilePath();
         $disc_manager->setPortalID($this->_environment->getCurrentPortalID());
         $disc_manager->setContextID($this->_environment->getCurrentContextID());
         unset($disc_manager);
         $index_result = array();
         $return = '';
         $cmdline = $this->_getCMDLine2($index_base);
         exec($cmdline, $index_result, $return);
      }
   }

   function buildFTIndexForIndexBase ( $index_base ) {

      // create new or update existing fulltext index
      global $ftsearch_enabled;
      if ( !isset($ftsearch_enabled) ) {
         $ftsearch_enabled = false;
      }
      if ($ftsearch_enabled) {

         $folder_string = '/tmp/swish-e';
         $folder = @opendir($folder_string);
         if (!$folder) {
            #mkdir($folder_string);
         }

         $index_result = array();
         $return = '';
         $cmdline = $this->_getCMDLine2($index_base);
         exec($cmdline, $index_result, $return);
      }
   }

   private function _getCMDLine ( $index_opt, $index_base ) {
      global $search_engine;
      global $background_mode;
      global $bgm_appendtolog;
      global $bgm_log;

      $retour = '';
      if ($background_mode and $bgm_log) {
         if ($bgm_appendtolog) {
            $retour = $search_engine . escapeshellcmd("$index_opt") . ' >> ' . $index_base . 'ft_idx.log &';
         } else {
            $retour = $search_engine . escapeshellcmd("$index_opt") . ' > '. $index_base . 'ft_idx.log &';
         }
      } else {
          $retour = $search_engine . escapeshellcmd("$index_opt");
      }
      return $retour;
   }

   private function _getCMDLine2 ( $index_base ) {
      global $search_engine;
      global $searchsite_base;
      global $background_mode;
      global $bgm_appendtolog;
      global $bgm_log;

      $retour = 'scripts/indexing/auto-indexer.sh '.$search_engine.' '.$searchsite_base.' '.$index_base;
      if ($background_mode and $bgm_log) {
         if ($bgm_appendtolog) {
            $retour .= ' >> ' . $index_base . 'ft_idx.log &';
         } else {
            $retour .= ' > '. $index_base . 'ft_idx.log &';
         }
      } else {
          $retour .= ' &';
      }
      return $retour;
   }

   private function _getPortalID () {
       if ( !isset($this->_portal_id) ) {
            $this->_portal_id = $this->_environment->getCurrentPortalID();
       }
       return $this->_portal_id;
   }

   public function setPortalID ( $value ) {
      $this->_portal_id = (int)$value;
   }

   private function _getRoomID () {
       if ( !isset($this->_room_id) ) {
            $this->_room_id = $this->_environment->getCurrentContextID();
       }
       return $this->_room_id;
   }

   public function setRoomID ( $value ) {
      $this->_room_id = (int)$value;
   }
}
?>