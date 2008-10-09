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

/** upper class of the log manager
 */
include_once('classes/cs_manager.php');

/** class for database connection to the database table "reader"
 * this class implements a database manager for the table "reader". Read items
 */
class cs_log_manager extends cs_manager {

   var $_limit_timestamp_old = NULL;
   var $_limit_from = NULL;
   var $_limit_range = NULL;
   var $_limit_timestamp_not_older = NULL;

   /** constructor: cs_log_manager
     * the only available constructor, initial values for internal variables
     *
     * @param object cs_environment the environment
     */
   function cs_log_manager ( $environment ) {
      $this->cs_manager($environment);
     $this->_db_table = CS_LOG_TYPE;
   }

   /** reset limits
    * reset limits of this class: room limit, delete limit
    */
   function resetLimits () {
      $this->_limit_timestamp_old = NULL;
      $this->_limit_from = NULL;
      $this->_limit_range = NULL;
      $this->_limit_timestamp_not_older = NULL;
   }

   function setTimestampOlderLimit ($data) {
      $this->_limit_timestamp_old = $data;
   }

   /**
    * @param integer days
    */
   function setTimestampNotOlderLimit ($data) {
      $this->_limit_timestamp_not_older = $data;
   }

   function setRangeLimit ($from, $range) {
      $this->_limit_from = $from;
      $this->_limit_range = $range;
   }

   function select () {
      $result = $this->_performQuery('select');
      $array = array();
      foreach ($result as $row) {
         $array[] = $row;
      }
      return $array;
   }

   function count () {
      $retour = 0;
      $result = $this->_performQuery('count');
      $row = $result[0];
      $retour = $row['count'];
      return $retour;
   }

   function delete () {
      return $this->_performQuery('delete');
   }

   function deleteByArray ($array) {
      $id_string = '';
      $first = true;
      foreach ($array as $row) {
         if ( !empty($row['id']) ) {
            if ($first) {
               $first = false;
            } else {
               $id_string .= ',';
            }
            $id_string .= $row['id'];
         }
      }

      $query  = '';
      $query .= 'DELETE FROM log WHERE id IN ('.$id_string.')';

      // perform query
      $result = $this->_db_connector->performQuery($query);
      if ( !isset($result) or !$result ) {
         include_once('functions/error_functions.php');
         trigger_error('Problems at logs from query:<br />"'.$query.'"',E_USER_WARNING);
      } else {
         return $result;
      }
   }

   function _performQuery ( $mode = 'select') {
      if ($mode == 'select') {
         $query = 'SELECT * FROM log';
      } elseif ($mode == 'delete') {
         $query = 'DELETE FROM log';
      } elseif ($mode == 'count') {
         $query = 'SELECT count(id) AS count FROM log';
      } else {
         include_once('functions/error_functions.php');
         trigger_error('lost perform mode',E_USER_ERROR);
      }

      $query .= ' WHERE 1';

      if (isset($this->_room_limit) and $this->_room_limit !=0 ) {
         $query .= ' AND cid = "'.encode(AS_DB,$this->_room_limit).'"';
      }

      if ( isset($this->_limit_timestamp_old) and !empty($this->_limit_timestamp_old) ) {
         $query .= ' AND timestamp < "'.encode(AS_DB,$this->_limit_timestamp_old).'"';
      }

      if ( isset($this->_limit_timestamp_not_older) and !empty($this->_limit_timestamp_not_older) ) {
         $query .= ' AND timestamp >= DATE_SUB(CURRENT_DATE,interval '.encode(AS_DB,$this->_limit_timestamp_not_older).' day)';
      }

      $query .= ' ORDER BY timestamp ASC';

      if (isset($this->_limit_from) and isset($this->_limit_range)) {
         if ( empty($this->_limit_form) ) {
            $query .= ' LIMIT 0,'.encode(AS_DB,$this->_limit_range);
         } else {
            $query .= ' LIMIT '.encode(AS_DB,$this->_limit_from).','.encode(AS_DB,$this->_limit_range);
         }
      }

      // perform query
      $result = $this->_db_connector->performQuery($query);
      if ( !isset($result) ) {
         include_once('functions/error_functions.php');
         trigger_error('Problems log from query: "'.$query.'"',E_USER_WARNING);
      } else {
         return $result;
      }
   }

   public function saveArray ( $array ) {
      $retour = false;
      $text = 'NULL';
      if ( !empty($array['post_content']) ) {
         // warum zwei mal strtoupper ??? (TBD)
         $post_text = strtoupper($array['post_content']);
         $post_content_big = strtoupper($post_text);
         if( !empty($post_content_big) and (stristr($post_content_big,'SELECT') !==false
            or stristr( $post_content_big,'INSERT') !==false
            or stristr($post_content_big, 'UPDATE') !==false)) {
           $text = $array['post_content'];
         }
      }
      if ( empty($array['user_item_id']) ) {
         $array['user_item_id'] = '0';
      }
      if ( empty($array['iid']) or strtoupper($array['iid']) == 'NEW' ) {
         $array['iid'] = '0';
      }
      $query = 'INSERT DELAYED INTO log SET '.
               'ip="'.      encode(AS_DB,$array['remote_addr']).'", '.
               'timestamp=NOW(), '.
               'agent="'.   encode(AS_DB,$array['user_agent']).'", '.
               'request="'. encode(AS_DB,$array['script_name'].'?'.$array['query_string']).'", '.
               'method="'.  encode(AS_DB,$array['request_method']).'", '.
               'post_content="'.encode(AS_DB,$text).'", '.
               'uid='.     encode(AS_DB,$array['user_item_id']).', '.
               'ulogin="'.  encode(AS_DB,$array['user_user_id']).'", '.
               'cid="'.     encode(AS_DB,$array['context_id']).'", '.
               'module="'.  encode(AS_DB,$array['module']).'", '.
               'fct="'.     encode(AS_DB,$array['function']).'", '.
               'param="'.   encode(AS_DB,$array['parameter_string']).'", '.
               'iid='.     encode(AS_DB,$array['iid']).';';
      $result = $this->_db_connector->performQuery($query);
      if ( isset($result) ) {
         $retour = true;
      } else {
         include_once('functions/error_functions.php');
         trigger_error('Problems save log with query: "'.$query.'"',E_USER_WARNING);
      }
      return $retour;
   }
}
?>