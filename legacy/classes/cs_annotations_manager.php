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
     * @var int id of an annotated item as a limit for the selected annotation.
     */
    private int $linkedItemId = 0;

    /**
     * @var cs_translator
     */
    private cs_translator $translator;

    public function __construct($environment)
    {
        parent::__construct($environment);
        $this->_db_table = 'annotations';
        $this->translator = $environment->getTranslationObject();
    }

    /**
     * reset limits of this class: refid limit, order limit and all limits from upper class.
     *
     * @author CommSy Development Group
     */
    public function resetLimits()
    {
        parent::resetLimits();
        $this->linkedItemId = 0;
    }

    /**
     * Set a limit for the linked item
     *
     * @param int $limit order limit for selected annotated item
     */
    public function setLinkedItemID(int $limit)
    {
        $this->linkedItemId = $limit;
    }

    /**
     * count all annotations limited by the limits
     * this method returns the number of annotations within the database limited by the limits. the select statement is a bit tricky, see source code for further information.
     */
    public function getCountAll(): int
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
     * @throws \Doctrine\DBAL\Exception
     * @version $Revision$
     */
    public function performQuery($mode = 'select')
    {
        if ('id_array' == $mode) {
            $query = 'SELECT ' . $this->addDatabasePrefix('annotations') . '.item_id';
        } else {
            $query = 'SELECT ' . $this->addDatabasePrefix('annotations') . '.*';
        }
        $query .= ' FROM ' . $this->addDatabasePrefix('annotations');

        $query .= ' WHERE 1';

        if (isset($this->linkedItemId) and !empty($this->linkedItemId)) {
            $query .= ' AND ' . $this->addDatabasePrefix('annotations') . '.linked_item_id=' . encode(AS_DB, $this->linkedItemId);
        }
        if (isset($this->_room_limit) and !empty($this->_room_limit)) {
            $query .= ' AND ' . $this->addDatabasePrefix('annotations') . '.context_id=' . encode(AS_DB, $this->_room_limit);
        }
        if (isset($this->_age_limit) and !empty($this->_age_limit)) {
            $query .= ' AND ' . $this->addDatabasePrefix('annotations') . '.modification_date >= DATE_SUB(CURRENT_DATE,interval ' . encode(AS_DB, $this->_age_limit) . ' day)';
        }
        if ($this->_delete_limit) {
            $query .= ' AND ' . $this->addDatabasePrefix('annotations') . '.deleter_id IS NULL';
        }

        $query .= ' ORDER BY ' . $this->addDatabasePrefix('annotations') . '.item_id ASC';

        // perform query
        $result = $this->_db_connector->performQuery($query);
        if (!isset($result)) {
            trigger_error('Problems selecting annotations.', E_USER_WARNING);
        }

        return $result;
    }

    /**
     * build a new annotations item
     * this method returns a new EMTPY annotations item.
     */
    public function getNewItem(): cs_annotation_item
    {
        return new cs_annotation_item($this->_environment);
    }

    /**
     * get an annotation in newest version.
     *
     * @param int|null $item_id id of the item
     *
     * @return object cs_item a label
     * @throws \Doctrine\DBAL\Exception
     */
    public function getItem(?int $item_id): ?cs_annotation_item
    {
        $annotation = null;
        if (!empty($item_id)) {
            if (!empty($this->_cache_object[$item_id])) {
                $annotation = $this->_cache_object[$item_id];
            } else {
                $query = 'SELECT * FROM ' . $this->addDatabasePrefix('annotations') . ' WHERE ' . $this->addDatabasePrefix('annotations') . ".item_id = '" . encode(AS_DB, $item_id) . "'";
                $result = $this->_db_connector->performQuery($query);
                if (!isset($result)) {
                    trigger_error('Problems selecting one annotation item.', E_USER_WARNING);
                } elseif (!empty($result[0])) {
                    $annotation = $this->_buildItem($result[0]);
                } else {
                    trigger_error('Problems selecting annotation item [' . $item_id . '].', E_USER_WARNING);
                }
            }
        }

        return $annotation;
    }

    /** get a list of items (newest version)
     * this method returns a list of items.
     *
     * @param array $id_array ids of the items
     *
     * @return cs_list list of cs_items
     */
    public function getItemList(array $id_array): cs_list
    {
        if (empty($id_array)) {
            return new cs_list();
        }

        if (is_array($id_array[0])) {
            $ids = ['iid' => [], 'vid' => []];
            foreach ($id_array as $id) {
                $ids['iid'][] = $id['iid'];
                $ids['vid'][] = $id['vid'];
            }
            $annotations = $this->_getItemList('annotations', $ids['iid']);
            $list = new cs_list();

            $i = 0;
            foreach ($annotations as $annotation) {
                $annotation->setAnnotatedVersionID($ids['vid'][$i]);
                $list->add($annotation);  // cs_list can't handle object references, so list mus be build again after changing items
                ++$i;
            }
            return $list;
        } else {
            return $this->_getItemList('annotations', $id_array);
        }
    }

    /** update an annotation - internal, do not use -> use method save
     * this method updates an annotation.
     *
     * @param cs_annotation_item $annotation_item the annotation
     * @throws \Doctrine\DBAL\Exception
     */
    public function _update($annotation_item): void
    {
        parent::_update($annotation_item);

        $version_id = $annotation_item->getLinkedVersionID() ? $annotation_item->getLinkedVersionID() : '0';

        $query = 'UPDATE ' . $this->addDatabasePrefix('annotations') . ' SET ' .
            'modification_date="' . getCurrentDateTimeInMySQL() . '",' .
            'description="' . encode(AS_DB, $annotation_item->getDescription()) . '",' .
            'linked_item_id="' . encode(AS_DB, $annotation_item->getLinkedItemID()) . '",' .
            'linked_version_id="' . encode(AS_DB, $version_id) . '",' .
            'modifier_id="' . encode(AS_DB, $this->_current_user->getItemID()) . '"' .
            ' WHERE item_id="' . encode(AS_DB, $annotation_item->getItemID()) . '"';

        $result = $this->_db_connector->performQuery($query);
        if (!isset($result) or !$result) {
            trigger_error('Problems updating annotation item.', E_USER_ERROR);
        }
    }

    /**
     * create an annotation - internal, do not use -> use method save
     * this method creates a annotation.
     *
     * @param cs_annotation_item $annotation_item the annotation
     * @throws \Doctrine\DBAL\Exception
     */
    public function _create(cs_annotation_item $annotation): void
    {
        $queryBuilder = $this->_db_connector->getConnection()->createQueryBuilder();

        $queryBuilder
            ->insert($this->addDatabasePrefix('items'))
            ->setValue('context_id', ':contextId')
            ->setValue('modification_date', ':modificationDate')
            ->setValue('type', ':type')
            ->setParameter('contextId', $annotation->getContextID())
            ->setParameter('modificationDate', getCurrentDateTimeInMySQL())
            ->setParameter('type', 'annotation');

        try {
            $queryBuilder->executeStatement();

            $this->_create_id = $this->_db_connector->getConnection()->lastInsertId();
            $annotation->setItemID($this->getCreateID());
            $this->_newAnnotation($annotation);
        } catch (\Doctrine\DBAL\Exception $e) {
            $this->_create_id = null;
            trigger_error('Problems creating annotation.', E_USER_WARNING);
        }
    }

    /**
     * creates a new annotation - internal, do not use -> use method save
     * this method creates a new annotation.
     *
     * @param cs_annotation_item $annotation
     */
    private function _newAnnotation(cs_annotation_item $annotation): void
    {
        $currentDateTime = getCurrentDateTimeInMySQL();

        $queryBuilder = $this->_db_connector->getConnection()->createQueryBuilder();

        $queryBuilder
            ->insert($this->addDatabasePrefix('annotations'))
            ->setValue('item_id', ':itemId')
            ->setValue('context_id', ':contextId')
            ->setValue('creator_id', ':creatorId')
            ->setValue('creation_date', ':creationDate')
            ->setValue('modifier_id', ':modifierId')
            ->setValue('modification_date', ':modificationDate')
            ->setValue('description', ':description')
            ->setValue('linked_item_id', ':linkedItemId')
            ->setValue('linked_version_id', ':linkedVersionId')
            ->setParameter('itemId', $annotation->getItemID())
            ->setParameter('contextId', $annotation->getContextID())
            ->setParameter('creatorId', $annotation->getCreatorItem()->getItemID())
            ->setParameter('creationDate', $currentDateTime)
            ->setParameter('modifierId', $annotation->getModificatorItem()->getItemID())
            ->setParameter('modificationDate', $currentDateTime)
            ->setParameter('description', $annotation->getDescription())
            ->setParameter('linkedItemId', $annotation->getLinkedItemID())
            ->setParameter('linkedVersionId', $annotation->getLinkedVersionID() ? $annotation->getLinkedVersionID() : '0');

        try {
            $queryBuilder->executeStatement();
        } catch (\Doctrine\DBAL\Exception $e) {
            trigger_error('Problems creating annotation.', E_USER_WARNING);
        }
    }

    /** deletes an annotation.
     *
     * @param int $itemId the id of the annotation
     * @throws \Doctrine\DBAL\Exception
     */
    public function delete(int $itemId): void
    {
        $current_datetime = getCurrentDateTimeInMySQL();
        $user_id = $this->_current_user->getItemID() ?: 0;
        $query = 'UPDATE ' . $this->addDatabasePrefix('annotations') . ' SET ' .
            'deletion_date="' . $current_datetime . '",' .
            'deleter_id="' . encode(AS_DB, $user_id) . '"' .
            ' WHERE item_id="' . encode(AS_DB, $itemId) . '"';
        $result = $this->_db_connector->performQuery($query);
        if (!isset($result) or !$result) {
            trigger_error('Problems deleting annotation.', E_USER_WARNING);
        }

        parent::delete($itemId);
    }

    public function deleteAnnotationsOfUser($uid): void
    {
        global $symfonyContainer;
        $disableOverwrite = $symfonyContainer->getParameter('commsy.security.privacy_disable_overwriting');

        if (null !== $disableOverwrite && 'TRUE' !== $disableOverwrite) {
            $currentDatetime = getCurrentDateTimeInMySQL();
            $query = 'SELECT ' . $this->addDatabasePrefix('annotations') . '.* FROM ' . $this->addDatabasePrefix('annotations') . ' WHERE ' . $this->addDatabasePrefix('annotations') . '.creator_id = "' . encode(AS_DB, $uid) . '"';
            $result = $this->_db_connector->performQuery($query);

            if (!empty($result)) {
                foreach ($result as $rs) {
                    $updateQuery = 'UPDATE ' . $this->addDatabasePrefix('annotations') . ' SET';

                    /* flag */
                    if ('FLAG' === $disableOverwrite) {
                        $updateQuery .= ' public = "-1",';
                        $updateQuery .= ' modification_date = "' . $currentDatetime . '"';
                    }

                    /* disabled */
                    if ('FALSE' === $disableOverwrite) {
                        $updateQuery .= ' description = "' . encode(AS_DB, $this->translator->getMessage('COMMON_AUTOMATIC_DELETE_DESCRIPTION')) . '",';
                        $updateQuery .= ' modification_date = "' . $currentDatetime . '"';
                    }

                    $updateQuery .= ' WHERE item_id = "' . encode(AS_DB, $rs['item_id']) . '"';
                    $result2 = $this->_db_connector->performQuery($updateQuery);
                    if (!$result2) {
                        trigger_error('Problems automatic deleting annotations.', E_USER_WARNING);
                    }
                }
            }
        }
    }
}
