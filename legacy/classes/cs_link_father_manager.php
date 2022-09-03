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

/** upper class of the material manager
 */
include_once('classes/cs_manager.php');

/** class for database connection to the database table "link_material_file"
 * this class implements a database manager for the table "link_material_file",
 * in which we store the links between materials and files
 */
class cs_link_father_manager extends cs_manager {

    var $_db_table = null;

    /**
     * object cs_user_item - containing the current user
     */
    protected $_current_user_id;

    public $_context_limit = null;

    /** constructor
     * the only available constructor, initial values for internal variables
     *
     * @param object cs_environment the environment
     */

    public function __construct(cs_environment $environment)
    {
        parent::__construct($environment);
        $this->_current_user_id = $this->_current_user->getItemID();
    }

   /** reset limits
    * reset limits of this class
    */
   function resetLimits () {
      $this->_context_limit = NULL;
      $this->_output_limit = NULL;
   }

   /** select items limited by limits
   * this method returns a list (cs_list) of items within the database limited by the limits.
   * depends on _performQuery(), which must be overwritten
   */
   function select () {
      $result = $this->_performQuery();
      $this->_id_array = NULL;
      if ( isset($this->_output_limit)
           and !empty($this->_output_limit)
           and $this->_output_limit == 'XML'
         ) {
         $this->_data = '<'.$this->_db_table.'_list>'.LF;
      } else {
      	include_once('classes/cs_list.php');
         $this->_data = new cs_list();
      }
      foreach ($result as $query_result) {
         if ( isset($this->_output_limit)
              and !empty($this->_output_limit)
              and $this->_output_limit == 'XML'
            ) {
            if ( isset($query_result)
                 and !empty($query_result) ) {
               $this->_data .= '<'.$this->_db_table.'_item>'.LF;
               foreach ($query_result as $key => $value) {
                  $value = str_replace('<','lt_commsy_export',$value);
                  $value = str_replace('>','gt_commsy_export',$value);
                  $value = str_replace('&','and_commsy_export',$value);
                  if ( $key == 'extras' ) {
                     $value = serialize($value);
                  }
                  $this->_data .= '<'.$key.'>'.$value.'</'.$key.'>'.LF;
               }
               $this->_data .= '</'.$this->_db_table.'_item>'.LF;
            }
         } else {
            $item = $this->_buildItem($query_result);
            $this->_data->add($item);
         }
         //$this->_id_array[] = $query_result['item_id'];
      }
      if ( isset($this->_output_limit)
           and !empty($this->_output_limit)
           and $this->_output_limit == 'XML'
         ) {
         $this->_data .= '</'.$this->_db_table.'_list>'.LF;
      }
   }

   function setContextLimit ($limit) {
      $this->_context_limit = $limit;
   }

   function backupDataFromXMLObject ($xml_object) {
      $major_success = true;

      if ( isset($xml_object) and !empty($xml_object) ) {
         foreach ($xml_object->children() as $xml_item) {
            $data_array = array();
            foreach ($xml_item->children() as $xml_element) {
               $value = utf8_decode((string)$xml_element);
               if ($xml_element->getName() == 'extras') {
                  include_once('functions/text_functions.php');
                  $value = mb_unserialize($value);
               }
               if ( !empty($value) ) {
                  $value = str_replace('lt_commsy_export','<',$value);
                  $value = str_replace('gt_commsy_export','>',$value);
                  $value = str_replace('and_commsy_export','&',$value);
                  $data_array[$xml_element->getName()] = $value;
               }
            }
            if ( isset($data_array) and !empty($data_array) ) {
               $success = $this->_updateFromBackup($data_array);
               $major_success = $major_success and $success;
            }
         }
      }

      return $major_success;
   }

   function _updateFromBackup2 ( $data_array ) {

      $success = false;
      if ( empty($data_array['item_vid']) ) {
         $data_array['item_vid'] = 0;
      }
      if ( !isset($data_array['deleter_id']) or empty($data_array['deleter_id']) ) {
         $data_array['deleter_id'] = 'NULL';
      }
      if ( !isset($data_array['deletion_date']) or empty($data_array['deletion_date']) ) {
         $data_array['deletion_date'] = 'NULL';
      }

      $query  = '';
      $query .= 'UPDATE '.$this->addDatabasePrefix($this->_db_table).'';

      $query .= ' SET ';
      $first = true;

      foreach ($data_array as $key => $value) {
         if ( $key != 'item_iid'
              and $key != 'item_vid'
              and $key != 'file_id'
            ) {
            if ($first) {
               $first = false;
            } else {
               $query .= ',';
            }
            if ($value == 'NULL') {
               $query .= $key.'= NULL';
            } else {
               $query .= $key.'="'.encode(AS_DB,$value).'"';
            }
         }
      }

      $query .= ' WHERE item_iid="'.encode(AS_DB,$data_array['item_iid']).'"';
      $query .= ' AND item_vid="'.encode(AS_DB,$data_array['item_vid']).'"';
      $query .= ' AND file_id="'.encode(AS_DB,$data_array['file_id']).'"';
      $query .= ';';

      $result = $this->_db_connector->performQuery($query);
      if ( !isset($result) or !$result ) {
         include_once('functions/error_functions.php');trigger_error('Problem backuping item from query: "'.$query.'"',E_USER_ERROR);
      } else {
         $success = true;
      }

      return $success;
   }
}
