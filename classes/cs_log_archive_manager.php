<?PHP
// $Id$
//
// Release $Name$
//
// Copyright (c)2002-2003 Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
// Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
// Edouard Simon, Monique Strauss, Jos� Manuel Gonz�lez V�zquez
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
class cs_log_archive_manager extends cs_manager {

   /** constructor: cs_log_manager
     * the only available constructor, initial values for internal variables
     *
     * @param object cs_environment the environment
     */
   function cs_log_archive_manager ( $environment ) {
      $this->cs_manager($environment);
   }

   /** reset limits
    * reset limits of this class: room limit, delete limit
    */
   function resetLimits () {
      $this->_limit_timestamp = NULL;
   }

   function save ($data) {
      if ( !is_array($data) ) {
         include_once('functions/error_functions.php');
         trigger_error('need array',E_USER_ERROR);
         $success = false;
      } else {
         if ( is_array($data[0]) ) {
            $success = true;
            foreach ($data as $key => $value) {
               if ( !isset($data[$key]['uid'])
                    or empty($data[$key]['uid'])
                  ) {
                  $data[$key]['uid'] = '0';
               }
               if ( !isset($data[$key]['iid'])
                    or empty($data[$key]['iid'])
                  ) {
                  $data[$key]['iid'] = '0';
               }
               $query = 'INSERT INTO log_archive SET '.
                        'ip="'.      encode(AS_DB,$data[$key]['ip']).'", '.
                        'agent="'.   encode(AS_DB,$data[$key]['agent']).'", '.
                        'timestamp="'.encode(AS_DB,$data[$key]['timestamp']).'", '.
                        'request="'. encode(AS_DB,$data[$key]['request']).'", '.
                        'method="'.  encode(AS_DB,$data[$key]['method']).'", '.
                        'uid="'.     encode(AS_DB,$data[$key]['uid']).'", '.
                        'ulogin="'.  encode(AS_DB,$data[$key]['ulogin']).'", '.
                        'cid="'.     encode(AS_DB,$data[$key]['cid']).'", '.
                        'module="'.  encode(AS_DB,$data[$key]['module']).'", '.
                        'fct="'.     encode(AS_DB,$data[$key]['fct']).'", '.
                        'param="'.   encode(AS_DB,$data[$key]['param']).'", '.
                        'iid="'.     encode(AS_DB,$data[$key]['iid']).'"';

               // perform query
               $result = $this->_db_connector->performQuery($query);
               if ( !isset($result) ) {
                  include_once('functions/error_functions.php');
                  trigger_error('Problems log_archive from query:<br />"'.$query.'"',E_USER_WARNING);
                  $success = false;
               }
            }
         } else {
            if ( !isset($data['uid'])
                 or empty($data['uid'])
               ) {
               $data['uid'] = '0';
            }
            if ( !isset($data['iid'])
                 or empty($data['iid'])
               ) {
               $data['iid'] = '0';
            }
            $query = 'INSERT INTO log_archive SET '.
                     'ip="'.      encode(AS_DB,$data['ip']).'", '.
                     'agent="'.   encode(AS_DB,$data['agent']).'", '.
                     'timestamp="'.encode(AS_DB,$data['timestamp']).'", '.
                     'request="'. encode(AS_DB,$data['request']).'", '.
                     'method="'.  encode(AS_DB,$data['method']).'", '.
                     'uid="'.     encode(AS_DB,$data['uid']).'", '.
                     'ulogin="'.  encode(AS_DB,$data['ulogin']).'", '.
                     'cid="'.     encode(AS_DB,$data['cid']).'", '.
                     'module="'.  encode(AS_DB,$data['module']).'", '.
                     'fct="'.     encode(AS_DB,$data['fct']).'", '.
                     'param="'.   encode(AS_DB,$data['param']).'", '.
                     'iid="'.     encode(AS_DB,$data['iid']).'"';
            // perform query
            $result = $this->_db_connector->performQuery($query);
            if ( !isset($result) ) {
               include_once('functions/error_functions.php');
               trigger_error('Problems log_archive from query:<br />"'.$query.'"',E_USER_WARNING);
               $success = false;
            } else {
               $success = true;
            }
         }
      }
      return $success;
   }

   function deleteByContextArray ($array) {
      $retour = false;
      if ( !empty($array)
           and count($array) > 0
         ) {
         $id_string = implode(',',$array);
         $query = 'DELETE FROM log_archive WHERE cid NOT IN ('.encode(AS_DB,$id_string).')';

         include_once('functions/date_functions.php');
         $days = 50;
         $datetime = getCurrentDateTimeMinusDaysInMySQL($days);
         $query .= ' AND timestamp < "'.$datetime.'"';

         // perform query
         $result = $this->_db_connector->performQuery($query);
         if ( !isset($result) or !$result ) {
            include_once('functions/error_functions.php');
            trigger_error('Problems at logs from query:<br />"'.$query.'"',E_USER_WARNING);
         } else {
            $retour = $result;
         }
      }
      return $retour;
   }
}
?>