<?php

/*
 * This file is part of CommSy.
 *
 * (c) Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
 * Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
 * Edouard Simon, Monique Strauss, Jose Mauel Gonzalez Vazquez, Johannes Schultze
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

/** class for database connection to the database table "annotations"
 * this class implements a database manager for the table "annotations".
 */
class cs_annotations_manager extends cs_manager
{
    /**
     * int - containing the id of an annotated item as a limit for the selected annotation.
     */
    public $_linked_item_id = 0;

    public $_all_annotation_list = null;

    public $_item_id_array = [];

    /**
     * string - containing an order limit for the selectd annotation.
     */
    public $_order = null;

    /*
     * Translator Object
     */
    private $_translator = null;

    /** constructor: cs_annotation_manager
     * the only available constructor, initial values for internal variables.
     *
     * @author CommSy Development Group
     */
    public function __construct($environment)
    {
        parent::__construct($environment);
        $this->_db_table = 'annotations';
        $this->_translator = $environment->getTranslationObject();
    }

    /**
     * reset limits of this class: refid limit, order limit and all limits from upper class.
     *
     * @author CommSy Development Group
     */
    public function resetLimits()
    {
        parent::resetLimits();
        $this->_linked_item_id = 0;
        $this->_order = null;
    }

    /** set linked_item_id limit
     * this method sets a refid limit for the select statement.
     *
     * @param string $limit order limit for selected annotated item
     *
     * @author CommSy Development Group
     */
    public function setLinkedItemID($limit)
    {
        $this->_linked_item_id = (int) $limit;
    }

    /** set order limit
     * this method sets an order limit for the select statement.
     *
     * @param string $limit order limit for selected annotation
     *
     * @author CommSy Development Group
     */
    public function setOrder($limit)
    {
        $this->_order = (string) $limit;
    }

     /** count all annotations limited by the limits
      * this method returns the number of annotations within the database limited by the limits. the select statement is a bit tricky, see source code for further information.
      *
      * @return int count annotations
      *
      * @version $Revision$
      */
     public function getCountAll()
     {
         $result = 0;
         if (!isset($this->_id_array)) {
             $this->_performQuery('id_array');
         }
         if (isset($this->_id_array)) {
             $result = is_countable($this->_id_array) ? count($this->_id_array) : 0;
         }

         return $result;
     }

     public function _performQuery($mode = 'select')
     {
         return $this->performQuery();
     }

    /** select annotations limited by limits
     * this method returns a list (cs_list) of annotations within the database limited by the limits. the select statement is a bit tricky, see source code for further information.
     *
     * @version $Revision$
     */
    public function performQuery($mode = 'select')
    {
        if ('id_array' == $mode) {
            $query = 'SELECT '.$this->addDatabasePrefix('annotations').'.item_id';
        } else {
            $query = 'SELECT '.$this->addDatabasePrefix('annotations').'.*';
        }
        $query .= ' FROM '.$this->addDatabasePrefix('annotations');

        $query .= ' WHERE 1';

        if (isset($this->_linked_item_id) and !empty($this->_linked_item_id)) {
            $query .= ' AND '.$this->addDatabasePrefix('annotations').'.linked_item_id='.encode(AS_DB, $this->_linked_item_id);
        }
        if (isset($this->_room_limit) and !empty($this->_room_limit)) {
            $query .= ' AND '.$this->addDatabasePrefix('annotations').'.context_id='.encode(AS_DB, $this->_room_limit);
        }
        if (isset($this->_age_limit) and !empty($this->_age_limit)) {
            $query .= ' AND '.$this->addDatabasePrefix('annotations').'.modification_date >= DATE_SUB(CURRENT_DATE,interval '.encode(AS_DB, $this->_age_limit).' day)';
        }
        if (true == $this->_delete_limit) {
            $query .= ' AND '.$this->addDatabasePrefix('annotations').'.deleter_id IS NULL';
        }

        $query .= ' ORDER BY '.$this->addDatabasePrefix('annotations').'.item_id ASC';

        // perform query
        $result = $this->_db_connector->performQuery($query);
        if (!isset($result)) {
            trigger_error('Problems selecting annotations.', E_USER_WARNING);
        } else {
            return $result;
        }
    }

     /** build a new annotations item
      * this method returns a new EMTPY annotations item.
      *
      * @return object cs_item a new EMPTY annotations
      *
      * @author CommSy Development Group
      */
     public function getNewItem()
     {
         return new cs_annotation_item($this->_environment);
     }

    /** get an annotation in newest version.
     *
     * @param int item_id id of the item
     *
     * @return object cs_item a label
     */
    public function getItem($item_id)
    {
        $annotation = null;
        if (!empty($item_id)) {
            if (!empty($this->_cache_object[$item_id])) {
                $annotation = $this->_cache_object[$item_id];
            } else {
                $query = 'SELECT * FROM '.$this->addDatabasePrefix('annotations').' WHERE '.$this->addDatabasePrefix('annotations').".item_id = '".encode(AS_DB, $item_id)."'";
                $result = $this->_db_connector->performQuery($query);
                if (!isset($result)) {
                    trigger_error('Problems selecting one annotation item.', E_USER_WARNING);
                } elseif (!empty($result[0])) {
                    $annotation = $this->_buildItem($result[0]);
                } else {
                    trigger_error('Problems selecting annotation item ['.$item_id.'].', E_USER_WARNING);
                }
            }
        }

        return $annotation;
    }

    public function getAnnotatedItemList($item_id)
    {
        $list = new cs_list();
        if (in_array($item_id, $this->_item_id_array)) {
            $annotation_list = $this->_all_annotation_list;
            $annotation_item = $annotation_list->getFirst();
            while ($annotation_item) {
                if ($item_id == $annotation_item->getLinkedItemID()) {
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
      * this method returns a list of items.
      *
      * @param array id_array ids of the items items
      *
      * @return cs_list list of cs_items
      */
     public function getItemList($id_array)
     {
         if (empty($id_array)) {
             return new cs_list();
         } else {
             if (is_array($id_array[0])) {
                 $ids = ['iid' => [], 'vid' => []];
                 foreach ($id_array as $id) {
                     $ids['iid'][] = $id['iid'];
                     $ids['vid'][] = $id['vid'];
                 }
                 $annotations = $this->_getItemList('annotations', $ids['iid']);
                 $list = new cs_list();
                 $annotation = $annotations->getFirst();
                 $i = 0;
                 while ($annotation) {
                     $annotation->setAnnotatedVersionID($ids['vid'][$i]);
                     $list->add($annotation);  // cs_list can't handle object references, so list mus be build again after changing items
                     ++$i;
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
     * this method updates an annotation.
     *
     * @param object cs_item annotation_item the annotation
     *
     * @author CommSy Development Group
     */
    public function _update($annotation_item)
    {
        parent::_update($annotation_item);

        $version_id = $annotation_item->getLinkedVersionID() ?: '0';

        $query = 'UPDATE '.$this->addDatabasePrefix('annotations').' SET '.
                 'modification_date="'.getCurrentDateTimeInMySQL().'",'.
                 'description="'.encode(AS_DB, $annotation_item->getDescription()).'",'.
                 'linked_item_id="'.encode(AS_DB, $annotation_item->getLinkedItemID()).'",'.
                 'linked_version_id="'.encode(AS_DB, $version_id).'",'.
                 'modifier_id="'.encode(AS_DB, $this->_current_user->getItemID()).'"'.
                 ' WHERE item_id="'.encode(AS_DB, $annotation_item->getItemID()).'"';

        $result = $this->_db_connector->performQuery($query);
        if (!isset($result) or !$result) {
            trigger_error('Problems updating annotation item.', E_USER_ERROR);
        }
    }

    /** create an annotation - internal, do not use -> use method save
     * this method creates a annotation.
     *
     * @param object cs_item annotation_item the annotation
     *
     * @author CommSy Development Group
     */
    public function _create($annotation_item)
    {
        $query = 'INSERT INTO '.$this->addDatabasePrefix('items').' SET '.
                 'context_id="'.encode(AS_DB, $annotation_item->getContextID()).'",'.
                 'modification_date="'.getCurrentDateTimeInMySQL().'",'.
                 'type="annotation"';
        $result = $this->_db_connector->performQuery($query);
        if (!isset($result)) {
            trigger_error('Problems creating annotation item.', E_USER_ERROR);
            $this->_create_id = null;
        } else {
            $this->_create_id = $result;
            $annotation_item->setItemID($this->getCreateID());
            $this->_newAnnotation($annotation_item);
        }
    }

    /** creates a new annotation - internal, do not use -> use method save
     * this method creates a new annotation.
     *
     * @param object cs_item annotation_item the annotation
     *
     * @author CommSy Development Group
     */
    public function _newAnnotation($annotation_item)
    {
        $current_datetime = getCurrentDateTimeInMySQL();
        $version_id = $annotation_item->getLinkedVersionID() ?: '0';

        $query = 'INSERT INTO '.$this->addDatabasePrefix('annotations').' SET '.
                 'item_id="'.encode(AS_DB, $annotation_item->getItemID()).'",'.
                 'context_id="'.encode(AS_DB, $annotation_item->getContextID()).'",'.
                 'creator_id="'.encode(AS_DB, $this->_current_user->getItemID()).'",'.
                 'creation_date="'.$current_datetime.'",'.
                 'modification_date="'.$current_datetime.'",'.
                 'description="'.encode(AS_DB, $annotation_item->getDescription()).'",'.
                 'linked_item_id="'.encode(AS_DB, $annotation_item->getLinkedItemID()).'",'.
                 'linked_version_id="'.encode(AS_DB, $version_id).'"';
        $result = $this->_db_connector->performQuery($query);
        if (!isset($result)) {
            trigger_error('Problems creating annotation.', E_USER_WARNING);
        }
    }

    /** save an annotation  -- TBD: is this needed anymore?
     *
     * @param object cs_item annotation_item the annotation
     */
    public function save($annotation_item)
    {
        // first check the correctness of the call
        $context_id = $annotation_item->getContextID();
        if (isset($context_id) and ($context_id != $this->_environment->getCurrentContextID())) {
            echo 'Warning: Context ID is not equal<br />';
        }
        $user = $annotation_item->getCreator();
        if (isset($user) and ($user->getItemID != $this->_current_user->getItemID())) {
            echo 'Warning: Creator is not equal<br />';
        }
        unset($user);
        $item_id = $annotation_item->getItemID();
        if (isset($item_id)) {
            $this->_update($annotation_item);
        } else {
            $this->_create($annotation_item);
        }
    }

    /** deletes an annotation.
     *
     * @param string item_id the id of the annotation
     */
    public function delete($item_id)
    {
        $current_datetime = getCurrentDateTimeInMySQL();
        $user_id = $this->_current_user->getItemID() ?: 0;
        $query = 'UPDATE '.$this->addDatabasePrefix('annotations').' SET '.
                 'deletion_date="'.$current_datetime.'",'.
                 'deleter_id="'.encode(AS_DB, $user_id).'"'.
                 ' WHERE item_id="'.encode(AS_DB, $item_id).'"';
        $result = $this->_db_connector->performQuery($query);
        if (!isset($result) or !$result) {
            trigger_error('Problems deleting annotation.', E_USER_WARNING);
        } else {
            parent::delete($item_id);
        }
    }

      public function deleteAnnotationsofUser($uid)
      {
          global $symfonyContainer;
          $disableOverwrite = $symfonyContainer->getParameter('commsy.security.privacy_disable_overwriting');

          if (null !== $disableOverwrite && 'TRUE' !== $disableOverwrite) {
              $currentDatetime = getCurrentDateTimeInMySQL();
              $query = 'SELECT '.$this->addDatabasePrefix('annotations').'.* FROM '.$this->addDatabasePrefix('annotations').' WHERE '.$this->addDatabasePrefix('annotations').'.creator_id = "'.encode(AS_DB, $uid).'"';
              $result = $this->_db_connector->performQuery($query);

              if (!empty($result)) {
                  foreach ($result as $rs) {
                      $updateQuery = 'UPDATE '.$this->addDatabasePrefix('annotations').' SET';

                      /* flag */
                      if ('FLAG' === $disableOverwrite) {
                          $updateQuery .= ' public = "-1",';
                          $updateQuery .= ' modification_date = "'.$currentDatetime.'"';
                      }

                      /* disabled */
                      if ('FALSE' === $disableOverwrite) {
                          $updateQuery .= ' description = "'.encode(AS_DB, $this->_translator->getMessage('COMMON_AUTOMATIC_DELETE_DESCRIPTION')).'",';
                          $updateQuery .= ' modification_date = "'.$currentDatetime.'"';
                      }

                      $updateQuery .= ' WHERE item_id = "'.encode(AS_DB, $rs['item_id']).'"';
                      $result2 = $this->_db_connector->performQuery($updateQuery);
                      if (!$result2) {
                          trigger_error('Problems automatic deleting annotations.', E_USER_WARNING);
                      }
                  }
              }
          }
      }
}
