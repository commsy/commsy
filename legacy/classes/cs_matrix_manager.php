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

/** upper class of the label manager
 */
include_once('classes/cs_labels_manager.php');

/** class for database connection to the database table "labels"
 * this class implements a database manager for the table "labels". Labels are groups, topics, labels, ...
 */
class cs_matrix_manager extends cs_labels_manager {


   var $_column_limit = NULL;
   var $_row_limit = NULL;

  /** constructor: cs_buzzword_manager
    * the only available constructor, initial values for internal variables
    *
    * @param object cs_environment the environment
    */
  function __construct($environment) {
     cs_labels_manager::__construct($environment);
  }

  /** get an empty buzzword item
    *  get an empty label_item
    *
    *  @return cs_label_item a time label
    */
   function getNewItem() {
      include_once('classes/cs_matrix_item.php');
      $item = new cs_matrix_item($this->_environment);
      return $item;
   }

  function resetLimits () {
     parent::resetLimits();
     $this->_column_limit = false;
     $this->_row_limit = false;
  }

  function setColumnLimit () {
     $this->_column_limit = true;
  }

  function setRowLimit () {
     $this->_row_limit = true;
  }

  /*function getEntriesInPosition($id1,$id2){
     $query  = 'SELECT count( DISTINCT li1.first_item_id) as count';
     $query .= ' FROM '.$this->addDatabasePrefix('link_items').' AS li1';
     $query .= ' INNER JOIN '.$this->addDatabasePrefix('link_items').' AS li2 ON li1.first_item_id = li2.first_item_id';
     $query .= ' WHERE 1';
     $query .= ' AND';
     $query .= ' li1.second_item_id = "'.$id1.'"';
     $query .= ' AND';
     $query .= ' li2.second_item_id = "'.$id2.'" ';
     $result = $this->_db_connector->performQuery($query);
     include_once('functions/development_functions.php');
     #debugToFile($query);
     if ( !isset($result[0]['count']) ) {
        trigger_error('Problems counting matrix entries.', E_USER_WARNING);
     } else {
        return $result[0]['count'];
     }
  }*/

  function getEntriesInPosition($id1,$id2){
     $query  = 'SELECT count( DISTINCT li1.first_item_id) as count';
     $query .= ' FROM '.$this->addDatabasePrefix('link_items').' AS li1';
     $query .= ' INNER JOIN '.$this->addDatabasePrefix('link_items').' AS li2 ON li1.extras = li2.extras';
     $query .= ' WHERE 1';
     $query .= ' AND';
     $query .= ' li1.second_item_id = "'.$id1.'"';
     $query .= ' AND';
     $query .= ' li2.second_item_id = "'.$id2.'" ';
     $query .= ' AND';
     $query .= ' li1.deleter_id IS NULL ';
     $result = $this->_db_connector->performQuery($query);
     include_once('functions/development_functions.php');
     #debugToFile($query);
     if ( !isset($result[0]['count']) ) {
        trigger_error('Problems counting matrix entries.', E_USER_WARNING);
     } else {
        return $result[0]['count'];
     }
  }
  
  /*function getEntriesInPosition($id1,$id2){
  	  $return = 0;
     $link_item_manager = $this->_environment->getLinkItemManager();
     #$link_item_manager->setTypeArrayLimit(CS_LABEL_TYPE);
     $link_item_manager->select();
     $link_item_list = $link_item_manager->get();
     $link_item_item = $link_item_list->getFirst();
     while($link_item_item){
        if($link_item_item->getMatrixCell() == $id1.'_'.$id2){
            pr($link_item_item->getItemID());
            $return++;
        }
        $link_item_item = $link_item_list->getNext();
     }
     return $return;
  }*/
  
  function _performQuery ($mode = 'select') {
     if ($mode == 'count') {
        $query = 'SELECT DISTINCT count('.$this->addDatabasePrefix('labels').'.item_id) as count';
     } else {
        if ($mode == 'id_array') {
           $query = 'SELECT DISTINCT '.$this->addDatabasePrefix('labels').'.item_id';
        } else {
           $query = 'SELECT DISTINCT '.$this->addDatabasePrefix('labels').'.*';
        }
     }
     $query .= ' FROM '.$this->addDatabasePrefix('labels');
     if (!isset($this->_attribute_limit) || (isset($this->_attribute_limit) and ('modificator'== $this->_attribute_limit) )|| (isset($this->_attribute_limit) and ('all'== $this->_attribute_limit))){
        if ( (isset($this->_sort_order) and ($this->_sort_order == 'modificator' or $this->_sort_order == 'modificator_rev')) or (isset($this->_search_array) and !empty($this->_search_array))) {
           $query .= ' LEFT JOIN '.$this->addDatabasePrefix('user').' ON '.$this->addDatabasePrefix('labels').'.creator_id = '.$this->addDatabasePrefix('user').'.item_id';
           $query .= ' LEFT JOIN '.$this->addDatabasePrefix('link_items').' AS l41 ON (l41.deletion_date IS NULL AND ((l41.first_item_id='.$this->addDatabasePrefix('labels').'.item_id AND l41.second_item_type="'.CS_USER_TYPE.'")))';
     	   $query .= ' LEFT JOIN '.$this->addDatabasePrefix('link_items').' AS l42 ON (l42.deletion_date IS NULL AND ((l42.second_item_id='.$this->addDatabasePrefix('labels').'.item_id AND l42.first_item_type="'.CS_USER_TYPE.'")))';
           $query .= ' LEFT JOIN '.$this->addDatabasePrefix('user').' AS user1 ON user1.item_id = l41.second_item_id';
           $query .= ' LEFT JOIN '.$this->addDatabasePrefix('user').' AS user2 ON user2.item_id = l42.first_item_id';
        }elseif ( (isset($this->_order) and $this->_order == 'creator') or (isset($this->_search_array) and !empty($this->_search_array))) {
           $query .= ' LEFT JOIN '.$this->addDatabasePrefix('user').' ON '.$this->addDatabasePrefix('labels').'.creator_id = '.$this->addDatabasePrefix('user').'.item_id';
           $query .= ' LEFT JOIN '.$this->addDatabasePrefix('link_items').' AS l41 ON (l41.deletion_date IS NULL AND ((l41.first_item_id='.$this->addDatabasePrefix('labels').'.item_id AND l41.second_item_type="'.CS_USER_TYPE.'")))';
     	   $query .= ' LEFT JOIN '.$this->addDatabasePrefix('link_items').' AS l42 ON (l42.deletion_date IS NULL AND ((l42.second_item_id='.$this->addDatabasePrefix('labels').'.item_id AND l42.first_item_type="'.CS_USER_TYPE.'")))';
     	   $query .= ' LEFT JOIN '.$this->addDatabasePrefix('user').' AS user1 ON user1.item_id = l41.second_item_id';
     	   $query .= ' LEFT JOIN '.$this->addDatabasePrefix('user').' AS user2 ON user2.item_id = l42.first_item_id';
        }
     }

     if ( isset($this->_topic_limit) ) {
        $query .= ' LEFT JOIN '.$this->addDatabasePrefix('link_items').' AS l21 ON ( l21.deletion_date IS NULL AND ((l21.first_item_id='.$this->addDatabasePrefix('labels').'.item_id AND l21.second_item_type="'.CS_TOPIC_TYPE.'"))) ';
        $query .= ' LEFT JOIN '.$this->addDatabasePrefix('link_items').' AS l22 ON ( l22.deletion_date IS NULL AND ((l22.second_item_id='.$this->addDatabasePrefix('labels').'.item_id AND l22.first_item_type="'.CS_TOPIC_TYPE.'"))) ';
     }
     if ( isset($this->_group_limit) ) {
        $query .= ' LEFT JOIN '.$this->addDatabasePrefix('link_items').' AS l31 ON ( l31.deletion_date IS NULL AND ((l31.first_item_id='.$this->addDatabasePrefix('labels').'.item_id AND l31.second_item_type="'.CS_GROUP_TYPE.'"))) ';
        $query .= ' LEFT JOIN '.$this->addDatabasePrefix('link_items').' AS l32 ON ( l32.deletion_date IS NULL AND ((l32.second_item_id='.$this->addDatabasePrefix('labels').'.item_id AND l32.first_item_type="'.CS_GROUP_TYPE.'"))) ';
     }

     if (!isset($this->_attribute_limit) || (isset($this->_attribute_limit) and ('all'==$this->_attribute_limit))){
        if (!empty($this->_material_limit)) {
           $query .= ' LEFT JOIN '.$this->addDatabasePrefix('links').' ON '.$this->addDatabasePrefix('links').'.to_item_id = '.$this->addDatabasePrefix('labels').'.item_id';
        }
     }
     if (!empty($this->_type_limit)) {
        $query .= ' WHERE '.$this->addDatabasePrefix('labels').'.type="'.encode(AS_DB,$this->_type_limit).'"';
     } else {
        $query .= ' WHERE 1';
     }
     if (!empty($this->_dossier_limit)) {
        $query .= ' AND '.$this->addDatabasePrefix('labels').'.name="'.encode(AS_DB,$this->_dossier_limit).'"';
     }

     if (!empty($this->_material_limit)) {
        $query .= ' AND '.$this->addDatabasePrefix('links').'.link_type = "material_for_'.encode(AS_DB,$this->_type_limit).'" AND '.$this->addDatabasePrefix('links').'.from_item_id = "'.encode(AS_DB,$this->_material_limit).'"';
        if (!empty($this->_version_limit)) {
           $query .= ' AND '.$this->addDatabasePrefix('links').'.from_version_id = "'.encode(AS_DB,$this->_version_limit).'"';
        }
     }

     // insert limits into the select statement
     if (isset($this->_room_limit)) {
        $query .= ' AND '.$this->addDatabasePrefix('labels').'.context_id = "'.encode(AS_DB,$this->_room_limit).'"';
     }
     if ($this->_delete_limit) {
        $query .= ' AND '.$this->addDatabasePrefix('labels').'.deleter_id IS NULL';
     }
     if (isset($this->_name_limit)) {
        $query .= ' AND '.$this->addDatabasePrefix('labels').'.name like "%'.encode(AS_DB,$this->_name_limit).'%"';
     }
     if (isset($this->_exact_name_limit)) {
        $query .= ' AND '.$this->addDatabasePrefix('labels').'.name = "'.encode(AS_DB,$this->_exact_name_limit).'"';
     }
     if (isset($this->_age_limit)) {
        $query .= ' AND '.$this->addDatabasePrefix('labels').'.modification_date >= DATE_SUB(CURRENT_DATE,interval '.encode(AS_DB,$this->_age_limit).' day)';
     }
      if ( isset($this->_existence_limit) ) {
         $query .= ' AND '.$this->addDatabasePrefix('labels').'.creation_date >= DATE_SUB(CURRENT_DATE,interval '.encode(AS_DB,$this->_existence_limit).' day)';
      }

     if ($this->_column_limit) {
        $query .= ' AND '.$this->addDatabasePrefix('labels').'.extras like "%COLUMN%"';
     }
     if ($this->_row_limit) {
        $query .= ' AND '.$this->addDatabasePrefix('labels').'.extras like "%ROW%"';
     }


      if ( isset($this->_topic_limit) ){
         if($this->_topic_limit == -1){
            $query .= ' AND (l21.first_item_id IS NULL AND l21.second_item_id IS NULL)';
            $query .= ' AND (l22.first_item_id IS NULL AND l22.second_item_id IS NULL)';
         }else{
            $query .= ' AND ((l21.first_item_id = "'.encode(AS_DB,$this->_topic_limit).'" OR l21.second_item_id = "'.encode(AS_DB,$this->_topic_limit).'")';
            $query .= ' OR (l22.first_item_id = "'.encode(AS_DB,$this->_topic_limit).'" OR l22.second_item_id = "'.encode(AS_DB,$this->_topic_limit).'"))';
         }
      }
      if ( isset($this->_institution_limit) ){
         if ($this->_institution_limit == -1){
            $query .= ' AND (l11.first_item_id IS NULL AND l11.second_item_id IS NULL)';
            $query .= ' AND (l12.first_item_id IS NULL AND l12.second_item_id IS NULL)';
         }else{
            $query .= ' AND ((l11.first_item_id = "'.encode(AS_DB,$this->_institution_limit).'" OR l11.second_item_id = "'.encode(AS_DB,$this->_institution_limit).'")';
            $query .= ' OR (l12.second_item_id = "'.encode(AS_DB,$this->_institution_limit).'" OR l12.first_item_id = "'.encode(AS_DB,$this->_institution_limit).'"))';
         }
      }
      if ( isset($this->_group_limit) ){
         if($this->_group_limit == -1){
            $query .= ' AND (l31.first_item_id IS NULL AND l31.second_item_id IS NULL)';
            $query .= ' AND (l32.first_item_id IS NULL AND l32.second_item_id IS NULL)';
         }else{
            $query .= ' AND ((l31.first_item_id = "'.encode(AS_DB,$this->_group_limit).'" OR l31.second_item_id = "'.encode(AS_DB,$this->_group_limit).'")';
            $query .= ' OR (l32.first_item_id = "'.encode(AS_DB,$this->_group_limit).'" OR l32.second_item_id = "'.encode(AS_DB,$this->_group_limit).'"))';
         }
      }

      if( !empty($this->_id_array_limit) ) {
         $query .= ' AND '.$this->addDatabasePrefix($this->_db_table).'.item_id IN ('.implode(", ",encode(AS_DB,$this->_id_array_limit)).')';
      }

      // restrict sql-statement by search limit, create wheres
      if (isset($this->_search_array) AND !empty($this->_search_array)) {
         $query .= ' AND ( 1 = 1';
                        if (!isset($this->_attribute_limit) || ('all' == $this->_attribute_limit)){
                           $field_array = array($this->addDatabasePrefix('labels').'.name',
                           						$this->addDatabasePrefix('labels').'.description',
                           						$this->addDatabasePrefix('labels').'.modification_date',
                           						'TRIM(CONCAT('.$this->addDatabasePrefix('user').'.firstname," ",'.$this->addDatabasePrefix('user').'.lastname))',
                           						'TRIM(CONCAT(user1.firstname," ",user1.lastname))',
                           						'TRIM(CONCAT(user2.firstname," ",user2.lastname))');
                           $search_limit_query_code = $this->_generateSearchLimitCode($field_array);
                           $query .= ' AND '.$search_limit_query_code;
                        } else {
            if ( 'title'==$this->_attribute_limit ){
               $query .= $this->_generateSearchLimitCode(array($this->addDatabasePrefix('labels').'.name'));
            }
            if ('description'==$this->_attribute_limit){
               if ('title'==$this->_attribute_limit){
                  $query .= 'OR';
               }
               $query .= $this->_generateSearchLimitCode(array($this->addDatabasePrefix('labels').'.description'));
            }
            if('modificator'== $this->_attribute_limit){
               if ( ('title'==$this->_attribute_limit) || ('description'==$this->_attribute_limit) ){
                  $query .= 'OR';
               }
               $query .= $this->_generateSearchLimitCode(array('TRIM(CONCAT('.$this->addDatabasePrefix('user').'.firstname," ",'.$this->addDatabasePrefix('user').'.lastname))'));
            }
         }
         $query .= ' )';
         $query .= ' GROUP BY '.$this->addDatabasePrefix('labels').'.item_id';
                }

     if ( isset($this->_sort_order) ) {
        if ( $this->_sort_order == 'title' ) {
           $query .= ' ORDER BY '.$this->addDatabasePrefix('labels').'.name ASC';
        } elseif ( $this->_sort_order == 'title_rev' ) {
           $query .= ' ORDER BY '.$this->addDatabasePrefix('labels').'.name DESC';
        } elseif ( $this->_sort_order == 'modificator' ) {
           $query .= ' ORDER BY '.$this->addDatabasePrefix('user').'.lastname ASC';
        } elseif ( $this->_sort_order == 'modificator_rev' ) {
           $query .= ' ORDER BY '.$this->addDatabasePrefix('user').'.lastname DESC';
        }
     }

     elseif (isset($this->_order)) {
        if ($this->_order == 'date') {
           $query .= ' ORDER BY '.$this->addDatabasePrefix('labels').'.modification_date DESC, '.$this->addDatabasePrefix('labels').'.name ASC';
        } elseif ($this->_order == 'creator') {
           $query .= ' ORDER BY '.$this->addDatabasePrefix('user').'.lastname, '.$this->addDatabasePrefix('labels').'.name';
        } else {
           $query .= ' ORDER BY '.$this->addDatabasePrefix('labels').'.name, '.$this->addDatabasePrefix('labels').'.modification_date DESC';
        }
     } else {
        $query .= ' ORDER BY '.$this->addDatabasePrefix('labels').'.name, '.$this->addDatabasePrefix('labels').'.modification_date DESC';
     }
     if ($mode == 'select') {
        if (isset($this->_interval_limit) and isset($this->_from_limit)) {
           $query .= ' LIMIT '.encode(AS_DB,$this->_from_limit).', '.encode(AS_DB,$this->_interval_limit);
        }
     }

     // sixth, perform query
     $result = $this->_db_connector->performQuery($query);
     if ( !isset($result) ) {
        if ($mode == 'count') {
           include_once('functions/error_functions.php');
           trigger_error('Problems counting labels.', E_USER_WARNING);
        } elseif ($mode == 'id_array') {
           include_once('functions/error_functions.php');
           trigger_error('Problems selecting labels ids.', E_USER_WARNING);
        } else {
           include_once('functions/error_functions.php');
           trigger_error('Problems selecting labels.', E_USER_WARNING);
        }
     } else {
        return $result;
     }
  }

   public function insertItem ( $item_id, $column_id, $row_id ) {
      //$retour = 0;
      $retour = $this->getEntriesInPosition($column_id,$row_id);
      if ( !empty($item_id)
           and $item_id > 99
           and !empty($column_id)
           and $column_id > 99
           and !empty($row_id)
           and $row_id > 99
           and !$this->_isInsert($item_id, $column_id, $row_id)
         ) {
         $item_manager = $this->_environment->getItemManager();
         $item_type = $item_manager->getItemType($item_id);
         unset($item_manager);

         $link_item_manager = $this->_environment->getLinkItemManager();
         $link_item = $link_item_manager->getNewItem();
         $link_item->setFirstLinkedItemID($item_id);
         $link_item->setFirstLinkedItemType($item_type);
         $link_item->setSecondLinkedItemID($column_id);
         $link_item->setSecondLinkedItemType(CS_LABEL_TYPE);
         $link_item->setMatrixKey($item_id.'_'.$column_id.'_'.$row_id);
         $link_item->save();
         unset($link_item);
         $link_item = $link_item_manager->getNewItem();
         $link_item->setFirstLinkedItemID($item_id);
         $link_item->setFirstLinkedItemType($item_type);
         $link_item->setSecondLinkedItemID($row_id);
         $link_item->setSecondLinkedItemType(CS_LABEL_TYPE);
         $link_item->setMatrixKey($item_id.'_'.$column_id.'_'.$row_id);
         $link_item->save();
         unset($link_item);
         unset($link_item_manager);

         $retour = $this->getEntriesInPosition($column_id,$row_id);
      }
      return $retour;
   }

   public function removeItem ( $item_id, $column_id, $row_id ) {
      $retour = false;
      if ( !empty($item_id)
           and $item_id > 99
           and !empty($column_id)
           and $column_id > 99
           and !empty($row_id)
           and $row_id > 99
           and $this->_isInsert($item_id, $column_id, $row_id)
         ) {
         /*$item_manager = $this->_environment->getItemManager();
         $item_type = $item_manager->getItemType($item_id);
         unset($item_manager);*/

         $item_manager = $this->_environment->getItemManager();
         $temp_item = $item_manager->getItem($item_id);
         	
         $link_item_manager = $this->_environment->getLinkItemManager();
         $link_item_manager->setLinkedItemLimit($temp_item);
         $link_item_manager->select();
         $link_item_list = $link_item_manager->get();
         $link_item_item = $link_item_list->getFirst();
         while($link_item_item){
         	if($link_item_item->getMatrixKey() == $item_id.'_'.$column_id.'_'.$row_id){
         		$link_item_manager->delete($link_item_item->getItemID());
         		$item_manager->delete($link_item_item->getItemID());
         	}
         	$link_item_item = $link_item_list->getNext();
         }
         
         /*$column_item = $link_item_manager->getItemByFirstAndSecondID($item_id, $column_id);
         pr($column_item->getItemID());
         $link_item_manager->delete($column_item->getItemID());
         
         $row_item = $link_item_manager->getItemByFirstAndSecondID($item_id, $row_id);
         pr($row_item->getItemID());
         $link_item_manager->delete($row_item->getItemID());*/
         
         /*$link_item = $link_item_manager->getNewItem();
         $link_item->setFirstLinkedItemID($item_id);
         $link_item->setFirstLinkedItemType($item_type);
         $link_item->setSecondLinkedItemID($column_id);
         $link_item->setSecondLinkedItemType(CS_LABEL_TYPE);
         $link_item->save();
         unset($link_item);
         $link_item = $link_item_manager->getNewItem();
         $link_item->setFirstLinkedItemID($item_id);
         $link_item->setFirstLinkedItemType($item_type);
         $link_item->setSecondLinkedItemID($row_id);
         $link_item->setSecondLinkedItemType(CS_LABEL_TYPE);
         $link_item->save();
         unset($link_item);
         unset($link_item_manager);

         $retour = $this->getEntriesInPosition($column_id,$row_id);*/
         $retour = true;
      }
      return $retour;
   }
   
   /*private function _isInsert ( $item_id, $column_id, $row_id ) {
      $retour = false;

      if ( !empty($item_id)
           and $item_id > 99
           and !empty($column_id)
           and $column_id > 99
           and !empty($row_id)
           and $row_id > 99
         ) {
         $query  = 'SELECT count( DISTINCT li1.first_item_id) as count';
         $query .= ' FROM '.$this->addDatabasePrefix('link_items').' AS li1';
         $query .= ' INNER JOIN '.$this->addDatabasePrefix('link_items').' AS li2 ON li1.first_item_id = li2.first_item_id';
         $query .= ' WHERE 1';
         $query .= ' AND';
         $query .= ' li1.first_item_id = "'.$item_id.'"';
         $query .= ' AND';
         $query .= ' li1.second_item_id = "'.$column_id.'"';
         $query .= ' AND';
         $query .= ' li2.second_item_id = "'.$row_id.'" ';
         $result = $this->_db_connector->performQuery($query);
         include_once('functions/development_functions.php');
         if ( !isset($result[0]['count']) ) {
            trigger_error('Problems counting matrix entries.', E_USER_WARNING);
         } else {
            if (!empty($result[0]['count'])) {
               $retour = true;
            }
         }
      } else {
      	$retour = NULL;
      }

      return $retour;
   }*/
   
   private function _isInsert ( $item_id, $column_id, $row_id ) {
      $retour = false;

      if ( !empty($item_id)
           and $item_id > 99
           and !empty($column_id)
           and $column_id > 99
           and !empty($row_id)
           and $row_id > 99
         ) {
         $query  = 'SELECT count( DISTINCT li1.first_item_id) as count';
         $query .= ' FROM '.$this->addDatabasePrefix('link_items').' AS li1';
         $query .= ' INNER JOIN '.$this->addDatabasePrefix('link_items').' AS li2 ON li1.first_item_id = li2.first_item_id';
         $query .= ' WHERE 1';
         $query .= ' AND';
         $query .= ' li1.first_item_id = "'.$item_id.'"';
         $query .= ' AND';
         $query .= ' li1.second_item_id = "'.$column_id.'"';
         $query .= ' AND';
         $query .= ' li2.second_item_id = "'.$row_id.'" ';
         $query .= ' AND';
         $query .= ' ((li1.extras LIKE "%'.$column_id.'_'.$row_id.'%") OR (li2.extras LIKE "%'.$item_id.'_'.$column_id.'_'.$row_id.'%")) ';
         $query .= ' AND';
         $query .= ' li1.deleter_id IS NULL ';
         $result = $this->_db_connector->performQuery($query);
         include_once('functions/development_functions.php');
         if ( !isset($result[0]['count']) ) {
            trigger_error('Problems counting matrix entries.', E_USER_WARNING);
         } else {
            if (!empty($result[0]['count'])) {
               $retour = true;
            }
         }
      } else {
         $retour = NULL;
      }

      return $retour;
   }
}
?>