<?PHP
// $Id$
//
// Release $Name$
//
// Copyright (c)2002-2003 Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
// Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
// Edouard Simon, Monique Strauss, Jose Manuel Gonzalez Vazquez
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

/** cs_annotation_item is needed to create annotation items
 */
include_once('classes/cs_annotation_item.php');

/** upper class of the annotation manager
 */
include_once('classes/cs_manager.php');

/** class for database connection to the database table "annotations"
 * this class implements a database manager for the table "annotations"
 */
class cs_annotations_manager extends cs_manager implements cs_export_import_interface {

  /**
   * object manager - containing object to the select links for annotations
   */
  var $_link_manager = NULL;

  /**
   * int - containing the id of an annotated item as a limit for the selected annotation
   */
  var $_linked_item_id = 0;

  var $_all_annotation_list = NULL;

  var $_item_id_array = array();

  /**
   * string - containing an order limit for the selectd annotation
   */
  var $_order = NULL;

  /*
   * Translator Object
   */
  private $_translator = null;

  /** constructor: cs_annotation_manager
   * the only available constructor, initial values for internal variables
   *
    * @author CommSy Development Group
   */
  function __construct($environment) {
    cs_manager::__construct($environment);
    $this->_db_table = 'annotations';
    $this->_translator = $environment->getTranslationObject();
  }

  /** reset limits
   * reset limits of this class: refid limit, order limit and all limits from upper class
   *
   * @author CommSy Development Group
   */
  function resetLimits () {
     parent::resetLimits();
     $this->_linked_item_id = 0;
     $this->_order = NULL;
  }

   /** set linked_item_id limit
    * this method sets an refid limit for the select statement
    *
    * @param string limit order limit for selected annotated item
    *
    * @author CommSy Development Group
    */
  function setLinkedItemID ($limit) {
     $this->_linked_item_id = (int)$limit;
  }

  /** set order limit
    * this method sets an order limit for the select statement
    *
    * @param string limit order limit for selected annotation
    *
    * @author CommSy Development Group
    */
  function setOrder ($limit) {
     $this->_order = (string)$limit;
  }

  /** count all annotations limited by the limits
    * this method returns the number of annotations within the database limited by the limits. the select statement is a bit tricky, see source code for further information
    *
    * @return integer count annotations
    *
    * @version $Revision$
    */
   function getCountAll () {
      $result = 0;
      if ( !isset($this->_id_array) ) {
         $this->_performQuery('id_array');
      }
      if ( isset( $this->_id_array) ) {
         $result = count( $this->_id_array);
      }
      return $result;
   }

   function _performQuery ($mode = 'select') {
      return $this->performQuery();
   }

  /** select annotations limited by limits
    * this method returns a list (cs_list) of annotations within the database limited by the limits. the select statement is a bit tricky, see source code for further information
    *
    * @version $Revision$
    */
  function performQuery ($mode = 'select') {

     if ( $mode == 'id_array' ) {
        $query = 'SELECT '.$this->addDatabasePrefix('annotations').'.item_id';
     } else {
        $query = 'SELECT '.$this->addDatabasePrefix('annotations').'.*';
     }
     $query .= ' FROM '.$this->addDatabasePrefix('annotations');

     $query .= ' WHERE 1';

     if ( isset($this->_linked_item_id) and !empty($this->_linked_item_id) ) {
        $query .= ' AND '.$this->addDatabasePrefix('annotations').'.linked_item_id='.encode(AS_DB,$this->_linked_item_id);
     }
     if ( isset($this->_room_limit) and !empty($this->_room_limit) ) {
        $query .= ' AND '.$this->addDatabasePrefix('annotations').'.context_id='.encode(AS_DB,$this->_room_limit);
     }
     if ( isset($this->_age_limit) and !empty($this->_age_limit) ) {
     	  $query .= ' AND '.$this->addDatabasePrefix('annotations').'.modification_date >= DATE_SUB(CURRENT_DATE,interval '.encode(AS_DB,$this->_age_limit).' day)';
     }
     if ($this->_delete_limit == true) {
        $query .= ' AND '.$this->addDatabasePrefix('annotations').'.deleter_id IS NULL';
     }

     $query .= ' ORDER BY '.$this->addDatabasePrefix('annotations').'.item_id ASC';

     // perform query
     $result = $this->_db_connector->performQuery($query);
     if ( !isset($result) ) {
        include_once('functions/error_functions.php');
        trigger_error('Problems selecting annotations.',E_USER_WARNING);
     } else {
        return $result;
     }
  }

   /** build a new annotations item
    * this method returns a new EMTPY annotations item
    *
    * @return object cs_item a new EMPTY annotations
    *
    * @author CommSy Development Group
    */
   function getNewItem () {
      return new cs_annotation_item($this->_environment);
   }

  /** get an annotation in newest version
    *
    * @param integer item_id id of the item
    *
    * @return object cs_item a label
    */
  function getItem ($item_id) {
     $annotation = NULL;
     if ( !empty($item_id) ) {
        if ( !empty($this->_cache_object[$item_id]) ) {
           $annotation = $this->_cache_object[$item_id];
        } else {
           $query = "SELECT * FROM ".$this->addDatabasePrefix("annotations")." WHERE ".$this->addDatabasePrefix("annotations").".item_id = '".encode(AS_DB,$item_id)."'";
           $result = $this->_db_connector->performQuery($query);
           if ( !isset($result) ) {
              include_once('functions/error_functions.php');
              trigger_error('Problems selecting one annotation item.',E_USER_WARNING);
           } elseif ( !empty($result[0]))  {
              $annotation = $this->_buildItem($result[0]);
           } else {
              include_once('functions/error_functions.php');
              trigger_error('Problems selecting annotation item ['.$item_id.'].',E_USER_WARNING);
           }
        }
     }
     return $annotation;
  }

  function getAllAnnotationItemListByIDArray($id_array){
      // ------------------
      // --->UTF8 - OK<----
      // ------------------
      if (!empty($id_array)){
         foreach($id_array as $id){
            if (!in_array($id,$this->_item_id_array)){
               $this->_item_id_array[] = $id;
            }
         }
         $query = 'SELECT '.$this->addDatabasePrefix('annotations').'.* FROM '.$this->addDatabasePrefix('annotations').
               ' WHERE '.$this->addDatabasePrefix('annotations').'.linked_item_id IN ('.implode(",",encode(AS_DB,$id_array)).')'.
               ' AND '.$this->addDatabasePrefix('annotations').'.deleter_id IS NULL GROUP BY '.$this->addDatabasePrefix('annotations').'.item_id';
         $result = $this->_db_connector->performQuery($query);
         if ( !isset($result) ) {
            include_once('functions/error_functions.php');
            trigger_error('Problems selecting list of '.$this->_type.' items.',E_USER_WARNING);
         } else {
            if (!isset($this->_all_annotation_list)){
               $this->_all_annotation_list = new cs_list();
            }
            foreach ($result as $rs ) {
               $this->_all_annotation_list->add($this->_buildItem($rs));
            }
            unset($result);
         }
      }
      return $this->_all_annotation_list;
  }

  function getAnnotatedItemList($item_id){
     $list = new cs_list();
     if (in_array($item_id,$this->_item_id_array)){
        $annotation_list = $this->_all_annotation_list;
        $annotation_item = $annotation_list->getFirst();
        $return_list = new cs_list();
        while($annotation_item){
           if($item_id == $annotation_item->getLinkedItemID()){
              $list->add($annotation_item);
           }
           $annotation_item = $annotation_list->getNext();
        }
     } else {
         $item_manager = $this->_environment->getItemManager();
         $item = $item_manager->getItem($item_id);
         $list = $item->getAnnotationList();
     }
     return $list;
  }

    /** get a list of items (newest version)
    * this method returns a list of items
    *
    * @param array id_array ids of the items items
    *
    * @return cs_list list of cs_items
    */
   function getItemList($id_array) {
      if(empty($id_array)) {
         return new cs_list();
      } else {
         if(is_array($id_array[0])) {
            $ids = array('iid' => array(), 'vid' => array());
            foreach($id_array as $id) {
               $ids['iid'][] = $id['iid'];
               $ids['vid'][] = $id['vid'];
            }
            $annotations = $this->_getItemList('annotations', $ids['iid']);
            $list = new cs_list();
            $annotation = $annotations->getFirst();
            $i = 0;
            while($annotation) {
               $annotation->setAnnotatedVersionID($ids['vid'][$i]);
               $list->add($annotation);  // cs_list can't handle object references, so list mus be build again after changing items
               $i++;
               unset($annotation);
               $annotation = $annotations->getNext();
            }
            unset($annotations);
            return $list;
         } else {
            return $this->_getItemList('annotations', $id_array);
         }
      }
   }

  /** update an annotation - internal, do not use -> use method save
    * this method updates an annotation
    *
    * @param object cs_item annotation_item the annotation
    *
    * @author CommSy Development Group
    */
  function _update ($annotation_item) {
      parent::_update($annotation_item);

      if ($annotation_item->getLinkedVersionID()) {
         $version_id = $annotation_item->getLinkedVersionID();
      } else {
         $version_id = '0';
      }

     $query = 'UPDATE '.$this->addDatabasePrefix('annotations').' SET '.
              'modification_date="'.getCurrentDateTimeInMySQL().'",'.
              'title="'.encode(AS_DB,$annotation_item->getTitle()).'",'.
              'description="'.encode(AS_DB,$annotation_item->getDescription()).'",'.
              'linked_item_id="'.encode(AS_DB,$annotation_item->getLinkedItemID()).'",'.
              'linked_version_id="'.encode(AS_DB,$version_id).'",'.
              'modifier_id="'.encode(AS_DB,$this->_current_user->getItemID()).'"'.
              ' WHERE item_id="'.encode(AS_DB,$annotation_item->getItemID()).'"';

     $result = $this->_db_connector->performQuery($query);
     if ( !isset($result) or !$result ) {
        include_once('functions/error_functions.php');trigger_error('Problems updating annotation item.', E_USER_ERROR);
     }
     unset($annotation_item);
  }

  /** create an annotation - internal, do not use -> use method save
    * this method creates a annotation
    *
    * @param object cs_item annotation_item the annotation
    *
    * @author CommSy Development Group
    */
  function _create ($annotation_item) {
     $query = 'INSERT INTO '.$this->addDatabasePrefix('items').' SET '.
              'context_id="'.encode(AS_DB,$annotation_item->getContextID()).'",'.
              'modification_date="'.getCurrentDateTimeInMySQL().'",'.
              'type="annotation"';
     $result = $this->_db_connector->performQuery($query);
     if ( !isset($result) ) {
        include_once('functions/error_functions.php');trigger_error('Problems creating annotation item.', E_USER_ERROR);
        $this->_create_id = NULL;
     } else {
        $this->_create_id = $result;
        $annotation_item->setItemID($this->getCreateID());
        $this->_newAnnotation($annotation_item);
     }
     unset($annotation_item);
  }

  /** creates a new annotation - internal, do not use -> use method save
    * this method creates a new annotation
    *
    * @param object cs_item annotation_item the annotation
    *
    * @author CommSy Development Group
    */
  function _newAnnotation ($annotation_item) {
     $current_datetime = getCurrentDateTimeInMySQL();
      if ($annotation_item->getLinkedVersionID()) {
         $version_id = $annotation_item->getLinkedVersionID();
      } else {
         $version_id = '0';
      }

     $query = 'INSERT INTO '.$this->addDatabasePrefix('annotations').' SET '.
              'item_id="'.encode(AS_DB,$annotation_item->getItemID()).'",'.
              'context_id="'.encode(AS_DB,$annotation_item->getContextID()).'",'.
              'creator_id="'.encode(AS_DB,$this->_current_user->getItemID()).'",'.
              'creation_date="'.$current_datetime.'",'.
              'modification_date="'.$current_datetime.'",'.
              'title="'.encode(AS_DB,$annotation_item->getTitle()).'",'.
              'description="'.encode(AS_DB,$annotation_item->getDescription()).'",'.
              'linked_item_id="'.encode(AS_DB,$annotation_item->getLinkedItemID()).'",'.
              'linked_version_id="'.encode(AS_DB,$version_id).'"';
     $result = $this->_db_connector->performQuery($query);
     if ( !isset($result) ) {
        include_once('functions/error_functions.php');trigger_error('Problems creating annotation.',E_USER_WARNING);
     }
     unset($annotation_item);
  }

  /** save an annotation  -- TBD: is this needed anymore?
    *
    * @param object cs_item annotation_item the annotation
    */
  function save ($annotation_item) {
     // first check the correctness of the call
     $context_id = $annotation_item->getContextID();
     if (isset($context_id) and ($context_id != $this->_environment->getCurrentContextID())) {
        echo('Warning: Context ID is not equal<br />');
     }
     $user = $annotation_item->getCreator();
     if (isset($user) and ($user->getItemID != $this->_current_user->getItemID())) {
        echo('Warning: Creator is not equal<br />');
     }
     unset($user);
     $item_id = $annotation_item->getItemID();
     if (isset($item_id)) {
        $this->_update($annotation_item);
     } else {
        $this->_create($annotation_item);
     }
     unset($annotation_item);
  }

    /** deletes an annotation
    *
    * @param string item_id the id of the annotation
    */
  function delete ($item_id) {
     $current_datetime = getCurrentDateTimeInMySQL();
     $user_id = $this->_current_user->getItemID();
     $query = 'UPDATE '.$this->addDatabasePrefix('annotations').' SET '.
              'deletion_date="'.$current_datetime.'",'.
              'deleter_id="'.encode(AS_DB,$user_id).'"'.
              ' WHERE item_id="'.encode(AS_DB,$item_id).'"';
     $result = $this->_db_connector->performQuery($query);
     if ( !isset($result) or !$result ) {
        include_once('functions/error_functions.php');trigger_error('Problems deleting annotation.',E_USER_WARNING);
     } else {
        parent::delete($item_id);
     }
  }

   function getCountExistingAnnotationsOfUser($user_id) {
     $query = 'SELECT count('.$this->addDatabasePrefix('annotations').'.item_id) as count';
     $query .= ' FROM '.$this->addDatabasePrefix('annotations');
     $query .= ' WHERE 1';

     if (isset($this->_room_limit)) {
        $query .= ' AND '.$this->addDatabasePrefix('annotations').'.context_id = "'.encode(AS_DB,$this->_room_limit).'"';
     } else {
        $query .= ' AND '.$this->addDatabasePrefix('annotations').'.context_id = "'.encode(AS_DB,$this->_environment->getCurrentContextID()).'"';
     }
     $query .= ' AND '.$this->addDatabasePrefix('annotations').'.deleter_id IS NULL';
     $query .= ' AND '.$this->addDatabasePrefix('annotations').'.creator_id ="'.encode(AS_DB,$user_id).'"';

     // perform query
     $result = $this->_db_connector->performQuery($query);
     if ( !isset($result) ) {
         include_once('functions/error_functions.php');trigger_error('Problems selecting items.',E_USER_WARNING);
     } else {
         return $result[0]['count'];
     }
   }

    function deleteAnnotationsofUser($uid) {
        global $symfonyContainer;
        $disableOverwrite = $symfonyContainer->getParameter('commsy.security.privacy_disable_overwriting');

        if ($disableOverwrite !== null && $disableOverwrite !== true) {
            // create backup of item
            $this->backupItem($uid, array(
                'title' => 'title',
                'description' => 'description',
                'modification_date' => 'modification_date',
            ));

            $currentDatetime = getCurrentDateTimeInMySQL();
            $query  = 'SELECT ' . $this->addDatabasePrefix('annotations').'.* FROM ' . $this->addDatabasePrefix('annotations').' WHERE ' . $this->addDatabasePrefix('annotations') . '.creator_id = "' . encode(AS_DB,$uid) . '"';
            $result = $this->_db_connector->performQuery($query);

            if (!empty($result)) {
                foreach ($result as $rs) {
                    $updateQuery = 'UPDATE ' . $this->addDatabasePrefix('annotations') . ' SET';

                    /* flag */
                    if ($disableOverwrite === 'flag') {
                        $updateQuery .= ' public = "-1",';
                        $updateQuery .= ' modification_date = "' . $currentDatetime . '"';
                    }

                    /* disabled */
                    if ($disableOverwrite === false) {
                        $updateQuery .= ' title = "' . encode(AS_DB,$this->_translator->getMessage('COMMON_AUTOMATIC_DELETE_TITLE')) . '",';
                        $updateQuery .= ' description = "' . encode(AS_DB,$this->_translator->getMessage('COMMON_AUTOMATIC_DELETE_DESCRIPTION')) . '",';
                        $updateQuery .= ' modification_date = "' . $currentDatetime . '"';
                    }

                    $updateQuery .= ' WHERE item_id = "' . encode(AS_DB,$rs['item_id']) . '"';
                    $result2 = $this->_db_connector->performQuery($updateQuery);
                    if (!$result2) {
                        include_once('functions/error_functions.php');
                        trigger_error('Problems automatic deleting annotations.', E_USER_WARNING);
                    }
                }
            }
        }
    }
	
	function export_item($id) {
	   $item = $this->getItem($id);
	
   	$xml = new SimpleXMLElementExtended('<annotations_item></annotations_item>');
   	$xml->addChildWithCDATA('item_id', $item->getItemID());
      $xml->addChildWithCDATA('context_id', $item->getContextID());
      $xml->addChildWithCDATA('creator_id', $item->getCreatorID());
      $xml->addChildWithCDATA('modifier_id', $item->getModificatorID());
      $xml->addChildWithCDATA('creation_date', $item->getCreationDate());
      $xml->addChildWithCDATA('deleter_id', $item->getDeleterID());
      $xml->addChildWithCDATA('deletion_date', $item->getDeleterID());
      $xml->addChildWithCDATA('modification_date', $item->getModificationDate());
      $xml->addChildWithCDATA('title', $item->getTitle());
      $xml->addChildWithCDATA('description', $item->getDescription());
      $xml->addChildWithCDATA('linked_item_id', $item->getLinkedItemID());
      $xml->addChildWithCDATA('linked_version_id', $item->getLinkedVersionID());
      
   	$extras_array = $item->getExtraInformation();
      $xmlExtras = $this->getArrayAsXML($xml, $extras_array, true, 'extras');
      $this->simplexml_import_simplexml($xml, $xmlExtras);
   
      $xml->addChildWithCDATA('public', $item->isPublic());
   
   	return $xml;
	}
	
   function export_sub_items($xml, $top_item) {
      
   }
   
   function import_item($xml, $top_item, &$options) {
      $item = null;
      if ($xml != null) {
         $item = $this->getNewItem();
         $item->setTitle((string)$xml->title[0]);
         $item->setDescription((string)$xml->description[0]);
         $item->setLinkedItemID($top_item->getItemID());
         $item->setContextID($top_item->getContextID());
         $item->save();
      }
      
      $options[(string)$xml->item_id[0]] = $item->getItemId();
      
      return $item;
   }
   
   function import_sub_items($xml, $top_item, &$options) {
      
   }
}
?>