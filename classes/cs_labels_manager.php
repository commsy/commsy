<?PHP
// $Id$
//
// Release $Name$
//
// Copyright (c)2002-2007 Dirk Blössl, Matthias Finck, Dirk Fust, Franz Grünig,
// Oliver Hankel, Iver Jackewitz, Michael Janneck, Martti Jeenicke,
// Detlev Krause, Irina L. Marinescu, Frithjof Meyer, Timo Nolte, Bernd Pape,
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

/** cs_list is needed for storage of the commsy items
 */
include_once('classes/cs_list.php');


/** upper class of the label manager
 */
include_once('classes/cs_manager.php');

/** date functions are needed for method _newVersion()
 */
include_once('functions/date_functions.php');

/** language functions are needed to translate labels: e.g. GROUP_ALL
 */
include_once('functions/language_functions.php');
include_once('functions/text_functions.php');

/** class for database connection to the database table "labels"
 * this class implements a database manager for the table "labels". Labels are groups, topics, labels, ...
 */
class cs_labels_manager extends cs_manager {

  /**
   * integer - containing the age of last change as a limit in days
   */
  var $_age_limit = NULL;

  /**
   * string - containing a type as a limit for select labels (e.g. group, topic, ...)
   */
  var $_type_limit = NULL;

  /**
   * string - containing a name as a limit for select labels
   */
  var $_name_limit = NULL;

  /**
   * string - containing a name as a limit for select labels - exact name limit
   */
  var $_exact_name_limit = NULL;

  /**
   * integer - containing a start point for the select statement
   */
  var $_from_limit = NULL;

  /**
   * integer - containing how many labels the select statement should get
   */
  var $_interval_limit = NULL;

  /**
   * integer - containing a id for a material
   */
  var $_material_limit = NULL;

   /**
    * integer - containing a id for a dossier
    */
   var $_dossier_limit = NULL;

   /**
   * integer - containing the id of a institution as a limit for the selected labels
   */
   var $_institution_limit = NULL;

   /**
   * integer - containing the id of a topic as a limit for the selected labels
   */
   var $_topic_limit = NULL;

   /**
   * integer - containing the id of a group as a limit for the selected labels
   */
   var $_group_limit = NULL;

   var $_sort_order = NULL;

  /**
   * string - containing an order limit for the select statement
   */
  var $_order = NULL;

  /**
   * array - containing the data from the database -> cache data
   */
  var $_internal_data = array();

  /**
   * string - containing the context of the CommSy: default = uni
   */
  var $_commsy_context = 'uni';

  var $_count_links = false;

  /** constructor: cs_labels_manager
    * the only available constructor, initial values for internal variables
    *
    * @param object cs_environment the environment
    */
   function cs_labels_manager ($environment) {
      $this->cs_manager($environment);
      $this->_db_table = 'labels';
   }

  /** reset limits
    * reset limits of this class: type limit, from limit, interval limit, order limit and all limits from upper class
    */
  function resetLimits () {
     parent::resetLimits();
     $this->_type_limit = NULL;
     $this->_age_limit = NULL;
     $this->_name_limit = NULL;
     $this->_from_limit = NULL;
     $this->_interval_limit = NULL;
     $this->_institution_limit = NULL;
     $this->_topic_limit = NULL;
     $this->_group_limit = NULL;
     $this->_material_limit = NULL;
     $this->_version_limit = NULL;
     $this->_dossier_limit = NULL;
     $this->_order = NULL;
     $this->_sort_order = NULL;
     $this->_exact_name_limit = NULL;
     $this->_count_links = false;
  }

  public function setGetCountLinks () {
     $this->_count_links = true;
  }

  /** set context of the CommSy
    * this method sets a context of the CommSy: uni or school
    *
    * @param string limit context of the CommSy
    */
  function setCommSyContext ($limit) {
     if ($limit == 'uni') {
        $this->_commsy_context = (string)$limit;
     } elseif ($limit == 'school') {
        $this->_commsy_context = (string)$limit;
     } elseif ($limit == 'none' or $limit == 'project') {
        $this->_commsy_context = 'project';
     } else {
        include_once('functions/error_functions.php');
        trigger_error('Problems setting CommSy context: use "school", "uni" or "project"',E_USER_WARNING);
     }
  }

  /** set age limit
    * this method sets an age limit for the label (modification date)
    *
    * @param integer limit age limit
    */
  function setAgeLimit ($limit) {
     $this->_age_limit = (int)$limit;
  }

  /** set type limit
    * this method sets a type limit
    *
    * @param string limit type limit for labels
    */
  function setTypeLimit ($limit) {
     $this->_type_limit = (string)$limit;
  }

  /** set dossier limit
    * this method sets a dosiier limit
    *
    * @param string limit dossier limit for labels
    */
  function setDossierLimit () {
     $this->_dossier_limit = 'Dossier';
  }

  /** set name limit
    * this method sets a name limit
    *
    * @param string limit name limit for labels
    */
  function setNameLimit ($limit) {
     $this->_name_limit = (string)$limit;
  }

  /** set exact name limit
    * this method sets a name limit - exact
    *
    * @param string limit name limit (exact) for labels
    */
  function setExactNameLimit ($limit) {
     $this->_exact_name_limit = (string)$limit;
  }

  /** set interval limit
    * this method sets a interval limit
    *
    * @param integer from     from limit for selected labels
    * @param integer interval interval limit for selected labels
    */
  function setIntervalLimit ($from, $interval) {
     $this->_interval_limit = (int)$interval;
     $this->_from_limit = (int)$from;
  }

  /** set material limit
    * this method sets a material limit
    *
    * @param integer limit id of the material
    */
  function setMaterialLimit ($limit, $version = '') {
     $this->_material_limit = (int)$limit;
     $this->_version_limit = (int)$version;
  }

   function setInstitutionLimit ($limit) {
      $this->_institution_limit = (int)$limit;
   }

   function setTopicLimit ($limit) {
      $this->_topic_limit = (int)$limit;
   }

   function setGroupLimit ($limit) {
      $this->_group_limit = (int)$limit;
   }

   function setSortOrder ($order) {
      $this->_sort_order = (string)$order;
   }


  /** set order limit
    * this method sets an order limit for the select statement
    *
    * @param string limit order limit for selected labels
    */
  function setOrder ($limit) {
     $this->_order = (string)$limit;
  }

   /** get all ids of the selected items as an array
    * this method returns all ids of the selected items limited by the limits as an array.
    * if no items are loaded, the ids are loaded from the database
    * depends on _performQuery(), which must be overwritten
    *
    * @return array $this->_id_array id array of selected materials
    */
   function getIDArray () {
      if ($this->_isAvailable()) {
         return parent::getIDArray();
      } else {
         return array();
      }
   }

  /** select labels limited by limits
    * this method returns a list (cs_list) of labels within the database limited by the limits. the select statement is a bit tricky, see source code for further information
    */
   function select () {

      if ( isset($this->_output_limit)
           and !empty($this->_output_limit)
           and $this->_output_limit == 'XML'
         ) {
         $this->_data = '<'.$this->_db_table.'_list>';
      } else {
         $this->_data = new cs_list();
      }
      if ($this->_isAvailable()) {
         $result = $this->_performQuery();

         // count links
         $count_array = array();
         if ( $this->_count_links and !empty($this->_type_limit) ) {
            $item_id_array = array();
            foreach ($result as $query_result) {
               $item_id_array[] = $query_result['item_id'];
            }
            $links_manager = $this->_environment->getLinkManager();
            $count_array = $links_manager->getCountLinksFromItemIDArray($item_id_array,$this->_type_limit);
            unset($links_manager);
         }

         foreach ($result as $query_result) {
            if ( isset($this->_output_limit)
                 and !empty($this->_output_limit)
                 and $this->_output_limit == 'XML'
               ) {
               if ( isset($query_result)
                    and !empty($query_result) ) {
                  $this->_data .= '<'.$this->_db_table.'_item>';
                  foreach ($query_result as $key => $value) {
                     $value = str_replace('<','lt_commsy_export',$value);
                     $value = str_replace('>','gt_commsy_export',$value);
                     $value = str_replace('&','and_commsy_export',$value);
                     if ( $key == 'extras' ) {
                        $value = serialize($value);
                     }
                     $this->_data .= '<'.$key.'>'.$value.'</'.$key.'>'.LF;
                  }
                  $this->_data .= '</'.$this->_db_table.'_item>';
               }
            } else {
               $label_item = $this->_buildItem($query_result);
               if ( !empty($count_array) ) {
                  if ( !empty($count_array[$label_item->getItemID()]) ) {
                     $label_item->setCountLinks($count_array[$label_item->getItemID()]);
                  }
               }
               $this->_data->add($label_item);
               unset($label_item);
            }
         }
      }
      if ( isset($this->_output_limit)
           and !empty($this->_output_limit)
           and $this->_output_limit == 'XML'
         ) {
         $this->_data .= '</'.$this->_db_table.'_list>';
      }
   }

  /** perform query for labels: select and count
    * this method perform query for selecting and counting labels
    *
    * @param boolean count true: count labels
    *                      false: select labels
    *
    * @return integer num of labels if count = true
    */
  function _performQuery ($mode = 'select') {
     if ($mode == 'count') {
        $query = 'SELECT DISTINCT count(labels.item_id) as count';
     } else {
        if ($mode == 'id_array') {
           $query = 'SELECT DISTINCT labels.item_id';
        } else {
           $query = 'SELECT DISTINCT labels.*';
        }
     }
     $query .= ' FROM labels';
     if (!isset($this->_attribute_limit) || (isset($this->_attribute_limit) and ('modificator'== $this->_attribute_limit) )|| (isset($this->_attribute_limit) and ('all'== $this->_attribute_limit))){
        if ( (isset($this->_sort_order) and ($this->_sort_order == 'modificator' or $this->_sort_order == 'modificator_rev')) or (isset($this->_search_array) and !empty($this->_search_array))) {
           $query .= ' LEFT JOIN user ON labels.creator_id = user.item_id';
        }elseif ( (isset($this->_order) and $this->_order == 'creator') or (isset($this->_search_array) and !empty($this->_search_array))) {
           $query .= ' LEFT JOIN user ON labels.creator_id = user.item_id';
        }
     }

     if ( isset($this->_institution_limit) ) {
        $query .= ' LEFT JOIN link_items AS l11 ON ( l11.deletion_date IS NULL AND ((l11.first_item_id=labels.item_id AND l11.second_item_type="'.CS_INSTITUTION_TYPE.'"))) ';
        $query .= ' LEFT JOIN link_items AS l12 ON ( l12.deletion_date IS NULL AND ((l12.second_item_id=labels.item_id AND l12.first_item_type="'.CS_INSTITUTION_TYPE.'"))) ';
     }
     if ( isset($this->_topic_limit) ) {
        $query .= ' LEFT JOIN link_items AS l21 ON ( l21.deletion_date IS NULL AND ((l21.first_item_id=labels.item_id AND l21.second_item_type="'.CS_TOPIC_TYPE.'"))) ';
        $query .= ' LEFT JOIN link_items AS l22 ON ( l22.deletion_date IS NULL AND ((l22.second_item_id=labels.item_id AND l22.first_item_type="'.CS_TOPIC_TYPE.'"))) ';
     }
     if ( isset($this->_group_limit) ) {
        $query .= ' LEFT JOIN link_items AS l31 ON ( l31.deletion_date IS NULL AND ((l31.first_item_id=labels.item_id AND l31.second_item_type="'.CS_GROUP_TYPE.'"))) ';
        $query .= ' LEFT JOIN link_items AS l32 ON ( l32.deletion_date IS NULL AND ((l32.second_item_id=labels.item_id AND l32.first_item_type="'.CS_GROUP_TYPE.'"))) ';
     }

     if (!isset($this->_attribute_limit) || (isset($this->_attribute_limit) and ('all'==$this->_attribute_limit))){
        if (!empty($this->_material_limit)) {
           $query .= ' LEFT JOIN links ON links.to_item_id = labels.item_id';
        }
     }
     if (!empty($this->_type_limit)) {
        $query .= ' WHERE labels.type="'.encode(AS_DB,$this->_type_limit).'"';
     } else {
        $query .= ' WHERE 1';
     }
     if (!empty($this->_dossier_limit)) {
        $query .= ' AND labels.name="'.encode(AS_DB,$this->_dossier_limit).'"';
     }

     if (!empty($this->_material_limit)) {
        $query .= ' AND links.link_type = "material_for_'.encode(AS_DB,$this->_type_limit).'" AND links.from_item_id = "'.encode(AS_DB,$this->_material_limit).'"';
        if (!empty($this->_version_limit)) {
           $query .= ' AND links.from_version_id = "'.encode(AS_DB,$this->_version_limit).'"';
        }
     }

     // insert limits into the select statement
     if (isset($this->_room_limit)) {
        $query .= ' AND labels.context_id = "'.encode(AS_DB,$this->_room_limit).'"';
     }
     if ($this->_delete_limit) {
        $query .= ' AND labels.deleter_id IS NULL';
     }
     if (isset($this->_name_limit)) {
        $query .= ' AND labels.name like "%'.encode(AS_DB,$this->_name_limit).'%"';
     }
     if (isset($this->_exact_name_limit)) {
        $query .= ' AND labels.name = "'.encode(AS_DB,$this->_exact_name_limit).'"';
     }
     if (isset($this->_age_limit)) {
        $query .= ' AND labels.modification_date >= DATE_SUB(CURRENT_DATE,interval '.encode(AS_DB,$this->_age_limit).' day)';
     }
      if ( isset($this->_existence_limit) ) {
         $query .= ' AND labels.creation_date >= DATE_SUB(CURRENT_DATE,interval '.encode(AS_DB,$this->_existence_limit).' day)';
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
         $query .= ' AND '.$this->_db_table.'.item_id IN ('.implode(", ",encode(AS_DB,$this->_id_array_limit)).')';
      }

      // restrict sql-statement by search limit, create wheres
      if (isset($this->_search_array) AND !empty($this->_search_array)) {
         $query .= ' AND ( 1 = 1';
                        if (!isset($this->_attribute_limit) || ('all' == $this->_attribute_limit)){
                           $field_array = array('labels.name','labels.description','labels.modification_date','TRIM(CONCAT(user.firstname," ",user.lastname))');
                                $search_limit_query_code = $this->_generateSearchLimitCode($field_array);
                                $query .= ' AND '.$search_limit_query_code;
                        } else {
            if ( 'title'==$this->_attribute_limit ){
               $query .= $this->_generateSearchLimitCode(array('labels.name'));
            }
            if ('description'==$this->_attribute_limit){
               if ('title'==$this->_attribute_limit){
                  $query .= 'OR';
               }
               $query .= $this->_generateSearchLimitCode(array('labels.description'));
            }
            if('modificator'== $this->_attribute_limit){
               if ( ('title'==$this->_attribute_limit) || ('description'==$this->_attribute_limit) ){
                  $query .= 'OR';
               }
               $query .= $this->_generateSearchLimitCode(array('TRIM(CONCAT(user.firstname," ",user.lastname))'));
            }
         }
         $query .= ' )';
         $query .= ' GROUP BY labels.item_id';
                }

     if ( isset($this->_sort_order) ) {
        if ( $this->_sort_order == 'title' ) {
           $query .= ' ORDER BY labels.name ASC';
        } elseif ( $this->_sort_order == 'title_rev' ) {
           $query .= ' ORDER BY labels.name DESC';
        } elseif ( $this->_sort_order == 'modificator' ) {
           $query .= ' ORDER BY user.lastname ASC';
        } elseif ( $this->_sort_order == 'modificator_rev' ) {
           $query .= ' ORDER BY user.lastname DESC';
        }
     }

     elseif (isset($this->_order)) {
        if ($this->_order == 'date') {
           $query .= ' ORDER BY labels.modification_date DESC, labels.name ASC';
        } elseif ($this->_order == 'creator') {
           $query .= ' ORDER BY user.lastname, labels.name';
        } else {
           $query .= ' ORDER BY labels.name, labels.modification_date DESC';
        }
     } else {
        $query .= ' ORDER BY labels.name, labels.modification_date DESC';
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

  /** get all labels and save it - INTERNAL
    * this method get all labels for the context and cache it in this class
    *
    * @param string  type       type of the label
    */
   function _getAllLabels ($type) {
      $data_array = array();
      if (isset($this->_room_limit)) {
         $current_context = $this->_room_limit;
      } else {
         $current_context = $this->_environment->getCurrentContextID();
      }
      if ( $this->_isAvailable() ) {
         $query = 'SELECT * FROM labels';
         $query .= ' WHERE labels.type = "'.encode(AS_DB,$type).'"';
         $query .= ' AND labels.context_id = "'.encode(AS_DB,$current_context).'"';
         $result = $this->_db_connector->performQuery($query);
         if ( !isset($result) ) {
            include_once('functions/error_functions.php');
            trigger_error('Problems selecting all labels.',E_USER_WARNING);
         } else {
            foreach ($result as $query_result) {
               $data_array[] = $query_result;
            }
         }
      }
      $data = $data_array;
      $this->_internal_data[$current_context][$type] = $data_array;
   }

  /** get one label without type information - INTERNAL
    * this method gets one label without type information
    *
    * @param integer  label_id  item id of the label
    */
  function _getLabelWithoutType ($label_id) {
     $label = NULL;
     if ( !empty($label_id) ) {
        $query = 'SELECT * FROM labels';
        $query .= ' WHERE labels.item_id = "'.encode(AS_DB,$label_id).'"';
        $result = $this->_db_connector->performQuery($query);
        if ( !isset($result) ) {
           include_once('functions/error_functions.php');
           trigger_error('Problems selecting one label.',E_USER_WARNING);
        } elseif ( !empty($result[0]) ) {
           $label = $this->_buildItem($result[0]);
        }
     }
     return $label;
  }

   /**
      get empty label_item
      @return cs_label_item a label
   */

   function getNewItem($label_type = '') {
      include_once('classes/cs_label_item.php');
      return new cs_label_item($this->_environment, $label_type);
   }

  /** get a label in newest version
    *
    * @param string  type    type of the label
    * @param integer item_id id of the item
    *
    * @return object cs_item a label
    */
  function getItem ($item_id) {
     if ( $this->_cache_on ) {
        if (isset($this->_room_limit)) {
           $current_context = $this->_room_limit;
        } else {
           $current_context = $this->_environment->getCurrentContextID();
        }
        if (isset($this->_type_limit)) {
           $current_module = $this->_environment->getCurrentModule();
           $current_function = $this->_environment->getCurrentFunction();
           if ( !isset($this->_internal_data[$current_context][$this->_type_limit]) ) {
              $this->_getAllLabels($this->_type_limit);
           }
           reset($this->_internal_data[$current_context][$this->_type_limit]);
           $line = current($this->_internal_data[$current_context][$this->_type_limit]);
           $label = NULL;
           while ($line and empty($label)) {
              if ($line['item_id'] == $item_id) {
                 $label = $this->_buildItem($line);
              }
              $line = next($this->_internal_data[$current_context][$this->_type_limit]);
           }
           if (!isset($label)) {
              $label = $this->_getLabelWithoutType($item_id);
           }
        } else {
           $label = $this->_getLabelWithoutType($item_id);
        }
     } else {
        $label = $this->_getLabelWithoutType($item_id);
     }
     return $label;
  }

  /** get a list of items (newest version)
    * this method returns a list of items
    *
    * @param array id_array ids of the items items
    *
    * @return cs_list list of cs_items
    *
    * @author CommSy Development Group
    */
   function getItemList($id_array) {
      return $this->_getItemList('labels', $id_array);
   }

  function getItemByName ($name) {
     $label = NULL;
     if (isset($this->_room_limit)) {
        $current_context = $this->_room_limit;
     } else {
        $current_context = $this->_environment->getCurrentContextID();
     }
     if (isset($this->_type_limit)) {
        if (!isset($this->_internal_data[$current_context][$this->_type_limit])) {
           $this->_getAllLabels($this->_type_limit);
        }
        reset($this->_internal_data[$current_context][$this->_type_limit]);
        $line = current($this->_internal_data[$current_context][$this->_type_limit]);
        while ($line and is_null($label)) {
           if ($line['name'] == $name) {
              $label = $this->_buildItem($line);
           }
           $line = next($this->_internal_data[$current_context][$this->_type_limit]);
        }
     }
     return $label;
  }

  /** Prepares the db_array for the item
    *
    * @param $db_array Contains the data from the database
    *
    * @return array Contains prepared data ( textfunctions applied etc. )
    */
  function _buildItem($db_array) {
     if ( $db_array['name'] == 'ALL' ) {
        $translator = $this->_environment->getTranslationObject();
        $db_array['name'] = $translator->getMessage('ALL_MEMBERS');
        if ( $db_array['description'] == 'GROUP_ALL_DESC' ) {
           $db_array['description'] = $translator->getMessage('GROUP_ALL_DESC');
        }
     }
     include_once('functions/text_functions.php');
     $db_array['extras'] = mb_unserialize($db_array['extras']);
     $item = parent::_buildItem($db_array);
     return $item;
  }

  /** update a label - internal, do not use -> use method save
    * this method updates a label
    *
    * @param object cs_item label_item the label
    *
    * @author CommSy Development Group
    */
  function _update ($item) {
     parent::_update($item);

     $modificator = $item->getModificatorItem();
     $current_datetime = getCurrentDateTimeInMySQL();

     if ($item->isPublic()) {
        $public = 1;
     } else {
        $public = 0;
     }

     $query =  'UPDATE labels SET '.
               'modifier_id="'.encode(AS_DB,$modificator->getItemID()).'",'.
               'modification_date="'.$current_datetime.'",';
     if ( !($item->getLabelType() == CS_GROUP_TYPE AND $item->isSystemLabel()) ) {
        $query .= 'name="'.encode(AS_DB,$item->getTitle()).'",';
     }
     $query .= 'description="'.encode(AS_DB,$item->getDescription()).'",'.
               'public="'.encode(AS_DB,$public).'",'.
               "extras='".encode(AS_DB,serialize($item->getExtraInformation()))."'".
               ' WHERE item_id="'.encode(AS_DB,$item->getItemID()).'"';
     $result = $this->_db_connector->performQuery($query);
     if ( !isset($result) or !$result ) {
        include_once('functions/error_functions.php');trigger_error('Problems updating label: "'.$this->_dberror.'" from query: "'.$query.'"',E_USER_WARNING);
     }
  }

  /** create a label - internal, do not use -> use method save
    * this method creates a label
    *
    * @param object cs_item label_item the label
    */
  function _create ($item) {
     $query = 'INSERT INTO items SET '.
              'context_id="'.encode(AS_DB,$item->getContextID()).'",'.
              'modification_date="'.getCurrentDateTimeInMySQL().'",'.
              'type="label"';

     $result = $this->_db_connector->performQuery($query);
     if ( !isset($result) ) {
        include_once('functions/error_functions.php');
        trigger_error('Problems creating label.', E_USER_ERROR);
        $this->_create_id = NULL;
     } else {
        $this->_create_id = $result;
        $item->setItemID($this->getCreateID());
        $this->_newLabel($item);
     }
  }

  /** creates a new label - internal, do not use -> use method save
    * this method creates a new version of a label
    *
    * @param object cs_item label_item the label
    *
    * @author CommSy Development Group
    */
  function _newLabel ($item) {
     $user = $item->getCreatorItem();
     $modificator = $item->getModificatorItem();
     $current_datetime = getCurrentDateTimeInMySQL();

     if ($item->isPublic()) {
        $public = 1;
     } else {
        $public = 0;
     }

     $query  = 'INSERT INTO labels SET '.
               'item_id="'.encode(AS_DB,$item->getItemID()).'",'.
               'context_id="'.encode(AS_DB,$item->getContextID()).'",'.
               'creator_id="'.encode(AS_DB,$user->getItemID()).'",'.
               'creation_date="'.$current_datetime.'",'.
               'modifier_id="'.encode(AS_DB,$modificator->getItemID()).'",'.
               'modification_date="'.$current_datetime.'",'.
               'name="'.encode(AS_DB,$item->getTitle()).'",'.
               'public="'.encode(AS_DB,$public).'",'.
               'description="'.encode(AS_DB,$item->getDescription()).'",'.
               'extras="'.encode(AS_DB,serialize($item->getExtraInformation())).'",'.
               'type="'.encode(AS_DB,$item->getLabelType()).'"';
     $result = $this->_db_connector->performQuery($query);
     if ( !isset($result) ) {
        include_once('functions/error_functions.php');
        trigger_error('Problems creating label.',E_USER_WARNING);
     }
  }

  /** save a label
    *
    * @param object cs_item label_item the label
    *
    * @author CommSy Development Group
    */
  function saveItem ($label_item) {
     $item_id = $label_item->getItemID();
     if (!empty($item_id)) {
        $this->_update($label_item);
     } else {
        $this->_create($label_item);
     }

     //Add modifier to all users who ever edited this item
     $link_modifier_item_manager = $this->_environment->getLinkModifierItemManager();
     $link_modifier_item_manager->markEdited($label_item->getItemID());
     unset($link_modifier_item_manager);
     unset($label_item);
  }


  /** update a label, with new informations, e.g. creator and modificator
    * this method updates a label initially
    *
    * @param object cs_item label_item the label
    */
   function saveItemNew ($item) {
      $user = $item->getCreatorItem();
      $modificator = $item->getModificatorItem();
      $current_datetime = getCurrentDateTimeInMySQL();

      if ($item->isPublic()) {
         $public = 1;
      } else {
         $public = 0;
      }

      $query =  'UPDATE labels SET '.
                'context_id="'.encode(AS_DB,$item->getContextID()).'",'.
                'creator_id="'.encode(AS_DB,$user->getItemID()).'",'.
                'creation_date="'.$current_datetime.'",'.
                'modifier_id="'.encode(AS_DB,$modificator->getItemID()).'",'.
                'modification_date="'.$current_datetime.'",';
      if ( !($item->getLabelType() == CS_GROUP_TYPE AND $item->isSystemLabel()) ) {
        $query .= 'name="'.encode(AS_DB,$item->getTitle()).'",';
      }
      $query .= 'description="'.encode(AS_DB,$item->getDescription()).'",'.
                'public="'.encode(AS_DB,$public).'",'.
                "extras='".encode(AS_DB,serialize($item->getExtraInformation()))."'".
                ' WHERE item_id="'.encode(AS_DB,$item->getItemID()).'"';
      $result = $this->_db_connector->performQuery($query);
      if ( !isset($result) or !$result ) {
         include_once('functions/error_functions.php');
         trigger_error('Problems updating label.',E_USER_WARNING);
      }
      unset($item);
   }

  function delete ($label_id) {
     $current_datetime = getCurrentDateTimeInMySQL();
     $current_user = $this->_environment->getCurrentUserItem();
     $user_id = $current_user->getItemID();
     $query = 'UPDATE labels SET '.
              'deletion_date="'.$current_datetime.'",'.
              'deleter_id="'.encode(AS_DB,$user_id).'"'.
              ' WHERE item_id="'.encode(AS_DB,$label_id).'"';
     $result = $this->_db_connector->performQuery($query);
     if ( !isset($result) or !$result ) {
        include_once('functions/error_functions.php');
        trigger_error('Problems deleting label.',E_USER_WARNING);
     } else {
        $link_manager = $this->_environment->getLinkManager();
        $link_manager->deleteLinksBecauseItemIsDeleted($label_id);
        unset($link_manager);
        parent::delete($label_id);
     }
  }

   /*
   checks if label type is supported in the current context
   so far only groups are checked within contexts, since they can be "switched off"
   @return boolean TRUE if supported, FALSE otherwise
   */
   function _isAvailable() {
      return true;
   }

   ########################################################
   # statistic functions
   ########################################################

   function getCountGroups ($start, $end) {
      $retour = 0;

      $query = "SELECT count(labels.item_id) as number FROM labels WHERE labels.type = '".CS_GROUP_TYPE."' AND labels.context_id = '".encode(AS_DB,$this->_room_limit)."' and ((labels.creation_date > '".encode(AS_DB,$start)."' and labels.creation_date < '".encode(AS_DB,$end)."') or (labels.modification_date > '".encode(AS_DB,$start)."' and labels.modification_date < '".encode(AS_DB,$end)."'))";
      $result = $this->_db_connector->performQuery($query);
      if ( !isset($result) ) {
         include_once('functions/error_functions.php');trigger_error('Problems counting all groups.',E_USER_WARNING);
      } else {
         foreach ($result as $rs) {
            $retour = $rs['number'];
         }
      }

      return $retour;
   }

   function getCountNewGroups ($start, $end) {
      $retour = 0;

      $query = "SELECT count(labels.item_id) as number FROM labels WHERE labels.type = '".CS_GROUP_TYPE."' AND labels.context_id = '".encode(AS_DB,$this->_room_limit)."' and labels.creation_date > '".encode(AS_DB,$start)."' and labels.creation_date < '".encode(AS_DB,$end)."'";
      $result = $this->_db_connector->performQuery($query);
      if ( !isset($result) ) {
         include_once('functions/error_functions.php');trigger_error('Problems counting groups.',E_USER_WARNING);
      } else {
         foreach ($result as $rs) {
            $retour = $rs['number'];
         }
      }
      return $retour;
   }

   function getCountModGroups ($start, $end) {
      $retour = 0;

      $query = "SELECT count(labels.item_id) as number FROM labels WHERE labels.type = '".CS_GROUP_TYPE."' AND labels.context_id = '".encode(AS_DB,$this->_room_limit)."' and labels.modification_date > '".encode(AS_DB,$start)."' and labels.modification_date < '".encode(AS_DB,$end)."' and labels.modification_date != labels.creation_date";
      $result = $this->_db_connector->performQuery($query);
      if ( !isset($result) ) {
         include_once('functions/error_functions.php');trigger_error('Problems counting groups.',E_USER_WARNING);
      } else {
         foreach ($result as $rs) {
            $retour = $rs['number'];
         }
      }
      return $retour;
   }

   function getCountTopics ($start, $end) {
      $retour = 0;

      $query = "SELECT count(labels.item_id) as number FROM labels WHERE labels.type = '".CS_TOPIC_TYPE."' AND labels.context_id = '".encode(AS_DB,$this->_room_limit)."' and ((labels.creation_date > '".encode(AS_DB,$start)."' and labels.creation_date < '".encode(AS_DB,$end)."') or (labels.modification_date > '".encode(AS_DB,$start)."' and labels.modification_date < '".encode(AS_DB,$end)."'))";
      $result = $this->_db_connector->performQuery($query);
      if ( !isset($result) ) {
         include_once('functions/error_functions.php');trigger_error('Problems counting all topics.',E_USER_WARNING);
      } else {
         foreach ($result as $rs) {
            $retour = $rs['number'];
         }
      }

      return $retour;
   }

   function getCountNewTopics ($start, $end) {
      $retour = 0;

      $query = "SELECT count(labels.item_id) as number FROM labels WHERE labels.type = '".CS_TOPIC_TYPE."' AND labels.context_id = '".encode(AS_DB,$this->_room_limit)."' and labels.creation_date > '".encode(AS_DB,$start)."' and labels.creation_date < '".encode(AS_DB,$end)."'";
      $result = $this->_db_connector->performQuery($query);
      if ( !isset($result) ) {
         include_once('functions/error_functions.php');trigger_error('Problems counting topics.',E_USER_WARNING);
      } else {
         foreach ($result as $rs) {
            $retour = $rs['number'];
         }
      }
      return $retour;
   }

   function getCountModTopics ($start, $end) {
      $retour = 0;

      $query = "SELECT count(labels.item_id) as number FROM labels WHERE labels.type = '".CS_TOPIC_TYPE."' AND labels.context_id = '".encode(AS_DB,$this->_room_limit)."' and labels.modification_date > '".encode(AS_DB,$start)."' and labels.modification_date < '".encode(AS_DB,$end)."' and labels.modification_date != labels.creation_date";
      $result = $this->_db_connector->performQuery($query);
      if ( !isset($result) ) {
         include_once('functions/error_functions.php');trigger_error('Problems counting topics.',E_USER_WARNING);
      } else {
         foreach ($result as $rs) {
            $retour = $rs['number'];
         }
      }
      return $retour;
   }

   function getCountInstitutions ($start, $end) {
      $retour = 0;

      $query = "SELECT count(labels.item_id) as number FROM labels WHERE labels.type = '".CS_INSTITUTION_TYPE."' AND labels.context_id = '".encode(AS_DB,$this->_room_limit)."' and ((labels.creation_date > '".encode(AS_DB,$start)."' and labels.creation_date < '".encode(AS_DB,$end)."') or (labels.modification_date > '".encode(AS_DB,$start)."' and labels.modification_date < '".encode(AS_DB,$end)."'))";
      $result = $this->_db_connector->performQuery($query);
      if ( !isset($result) ) {
         include_once('functions/error_functions.php');trigger_error('Problems counting all topics.',E_USER_WARNING);
      } else {
         foreach ($result as $rs) {
            $retour = $rs['number'];
         }
      }

      return $retour;
   }

   function getCountNewInstitutions ($start, $end) {
      $retour = 0;

      $query = "SELECT count(labels.item_id) as number FROM labels WHERE labels.type = '".CS_INSTITUTION_TYPE."' AND labels.context_id = '".encode(AS_DB,$this->_room_limit)."' and labels.creation_date > '".encode(AS_DB,$start)."' and labels.creation_date < '".encode(AS_DB,$end)."'";
      $result = $this->_db_connector->performQuery($query);
      if ( !isset($result) ) {
         include_once('functions/error_functions.php');trigger_error('Problems counting insitutions.',E_USER_WARNING);
      } else {
         foreach ($result as $rs) {
            $retour = $rs['number'];
         }
      }
      return $retour;
   }

   function getCountModInstitutions ($start, $end) {
      $retour = 0;

      $query = "SELECT count(labels.item_id) as number FROM labels WHERE labels.type = '".CS_INSTITUTION_TYPE."' AND labels.context_id = '".encode(AS_DB,$this->_room_limit)."' and labels.modification_date > '".encode(AS_DB,$start)."' and labels.modification_date < '".encode(AS_DB,$end)."' and labels.modification_date != labels.creation_date";
      $result = $this->_db_connector->performQuery($query);
      if ( !isset($result) ) {
         include_once('functions/error_functions.php');trigger_error('Problems counting modified institutions.',E_USER_WARNING);
      } else {
         foreach ($result as $rs) {
            $retour = $rs['number'];
         }
      }
      return $retour;
   }

   function copyDataFromRoomToRoom ($old_id, $new_id, $user_id='', $id_array='') {
      $retour = parent::copyDataFromRoomtoRoom($old_id, $new_id, $user_id, $id_array);

      // group all
      $this->reset();
      $this->setContextLimit($old_id);
      $this->setExactNameLimit('ALL');
      $this->select();
      $old_list = $this->get();
      if ($old_list->isNotEmpty() and $old_list->getCount() == 1) {
         $old_group_all = $old_list->getFirst();

         $this->reset();
         $this->setContextLimit($new_id);
         $this->setExactNameLimit('ALL');
         $this->select();
         $new_list = $this->get();
         if ($new_list->isNotEmpty() and $new_list->getCount() == 1) {
            $new_group_all = $new_list->getFirst();
            $retour[$old_group_all->getItemID()] = $new_group_all->getItemID();
            unset($new_group_all);
         }
         unset($old_group_all);
      }
      unset($old_list);

      // images of labels
      $query  = '';
      $query .= 'SELECT * FROM '.$this->_db_table.' WHERE context_id="'.encode(AS_DB,$new_id).'" AND deleter_id IS NULL AND deletion_date IS NULL';
      $result = $this->_db_connector->performQuery($query);
      if ( !isset($result) ) {
         include_once('functions/error_functions.php');
         trigger_error('Problems getting data "'.$this->_db_table.'".',E_USER_WARNING);
      } else {
         foreach ($result as $query_result) {
            $extra_array = xml2Array($query_result['extras']);
            if ( isset($extra_array['LABELPICTURE']) and !empty($extra_array['LABELPICTURE']) ) {
               $disc_manager = $this->_environment->getDiscManager();
               $disc_manager->setPortalID($this->_environment->getCurrentPortalID());
               if ( $disc_manager->copyImageFromRoomToRoom($extra_array['LABELPICTURE'],$new_id) ) {
                  $value_array = explode('_',$extra_array['LABELPICTURE']);
                  $value_array[0] = 'cid'.$new_id;
                  $extra_array['LABELPICTURE'] = implode('_',$value_array);

                  $update_query = 'UPDATE '.$this->_db_table.' SET extras="'.encode(AS_DB,serialize($extra_array)).'" WHERE item_id="'.$query_result['item_id'].'"';
                  $update_result = $this->_db_connector->performQuery($update_query);
                  if ( !isset($update_result) or !$update_result ) {
                     include_once('functions/error_functions.php');
                     trigger_error('Problems updating data "'.$this->_db_table.'".',E_USER_WARNING);
                  }
               }
               unset($disc_manager);
            }
         }
      }
      return $retour;
   }

   function deleteLabelsOfUser($uid) {
      $query  = 'SELECT labels.* FROM labels WHERE labels.creator_id = "'.$uid.'"';
      $result = $this->_db_connector->performQuery($query);
      if ( !empty($result) ) {
         foreach ( $result as $rs ) {
            //Never delete any group "ALL"
            if (!($rs['type'] == CS_GROUP_TYPE AND $rs['name'] == 'ALL')) {
               $insert_query = 'UPDATE labels SET';
               $insert_query .= ' name = "'.encode(AS_DB,getMessage('COMMON_AUTOMATIC_DELETE_TITLE')).'",';
               $insert_query .= ' description = "'.encode(AS_DB,getMessage('COMMON_AUTOMATIC_DELETE_DESCRIPTION')).'",';
               $insert_query .= ' public = "1"';
               $insert_query .=' WHERE item_id = "'.$rs['item_id'].'"';
               $result2 = $this->_db_connector->performQuery($insert_query);
               if ( !isset($result2) or !$result2 ) {
                  include_once('functions/error_functions.php');trigger_error('Problems automatic deleting labels:.',E_USER_WARNING);
               }
            }
         }
      }
   }

   public function resetCache () {
      $this->_internal_data = array();
   }
}
?>