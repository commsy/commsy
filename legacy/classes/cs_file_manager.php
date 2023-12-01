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

/** class for database connection to the database table "material"
 * this class implements a database manager for the table "material".
 */
class cs_file_manager extends cs_manager
{
    // maximal length of a picture side in pixel- if a picture that is showd inline is bigger, there is a thumbnale with this size shown
    public $_MAX_PICTURE_SIDE = 200;

    public $_cache = [];
    public $_limit_newer = '';

    /** constructor: cs_file_manager
     * the only available constructor, initial values for internal variables.
     *
     * @param cs_environment $environment the environment
     */
    public function __construct($environment)
    {
        parent::__construct($environment);
        $this->_db_table = 'files';
        $this->_type = 'file';
    }

    /**
     * get empty file item.
     *
     * @return cs_file_item
     */
    public function getNewItem()
    {
        $item = new cs_file_item($this->_environment);
        $item->setContextID($this->_environment->getCurrentContextID());

        return $item;
    }

    public function getItem($file_id)
    {
        $file = null;
        $query = 'SELECT * FROM ' . $this->addDatabasePrefix('files');
        $query .= ' WHERE 1';
        if (true == $this->_delete_limit) {
            $query .= ' AND ' . $this->addDatabasePrefix('files') . '.deleter_id IS NULL';
        }
        $query .= ' AND ' . $this->addDatabasePrefix('files') . '.files_id="' . encode(AS_DB, $file_id) . '"';
        $result = $this->_db_connector->performQuery($query);
        if (!isset($result)) {
            trigger_error('Problems get file entry [' . $file_id . '].', E_USER_WARNING);
            $file = [];
        } elseif (!empty($result[0])) {
            $query_result = $result[0];
            $file = $this->_buildItem($query_result);
        }

        return $file;
    }

    public function saveItem($file_item)
    {
        /** @var cs_file_item $file_item */
        $saved = false;
        $current_user = $this->_environment->getCurrentUser();
        $query = 'INSERT INTO ' . $this->addDatabasePrefix($this->_db_table) . ' SET' .
            ' portal_id="' . encode(AS_DB, $file_item->getPortalId()) . '",' .
            ' context_id="' . encode(AS_DB, $file_item->getContextID()) . '",' .
            ' creation_date="' . getCurrentDateTimeInMySQL() . '", ' .
            ' creator_id="' . encode(AS_DB, $current_user->getItemID()) . '", ' .
            ' filename="' . encode(AS_DB, $file_item->getFileName()) . '", ' .
            ' filepath="' . encode(AS_DB, $file_item->getFilePath()) . '", ' .
            ' extras="' . encode(AS_DB, serialize($file_item->getExtraInformation())) . '"';
        $result = $this->_db_connector->performQuery($query);
        if (isset($result)) {
            $file_item->setFileID($result);
            $saved = $this->_saveOnDisk($file_item);
            if ($saved) {
                $discManager = $this->_environment->getDiscManager();
                $filePath = $discManager->getRelativeFilePath(
                    $file_item->getPortalId(),
                    $file_item->getContextID(),
                    $file_item->getDiskFileNameWithoutFolder()
                );

                $fileSize = filesize($discManager->getAbsoluteFilePath(
                    $file_item->getPortalId(),
                    $file_item->getContextID(),
                    $file_item->getDiskFileNameWithoutFolder()
                ));

                $query = 'UPDATE ' . $this->addDatabasePrefix($this->_db_table) . ' SET' .
                    ' size="' . encode(AS_DB, $fileSize) . '",' .
                    ' filepath="' . encode(AS_DB, $filePath) . '"' .
                    ' WHERE files_id="' . encode(AS_DB, $file_item->getFileID()) . '"';
                $this->_db_connector->performQuery($query);
            }
        } else {
            trigger_error('Filemanager: Problem creating file entry: ' . $query, E_USER_ERROR);
        }

        return $saved;
    }

    public function updateItem($file_item)
    {
        $query = 'UPDATE ' . $this->addDatabasePrefix('files') . ' SET ' .
            'extras="' . encode(AS_DB, serialize($file_item->getExtraInformation())) . '"' .
            ' WHERE files_id="' . encode(AS_DB, $file_item->getFileID()) . '"';
        $result = $this->_db_connector->performQuery($query);
        if (!isset($result) or !$result) {
            trigger_error('Problems updating file from query: "' . $query . '"', E_USER_WARNING);
        }
    }

    public function _saveOnDisk($file_item)
    {
        /** @var cs_file_item $file_item */
        $success = false;
        $tempname = $file_item->getTempName();
        if (!empty($tempname)) {
            $disc_manager = $this->_environment->getDiscManager();
            $disc_manager->setContextID($file_item->getContextID());
            $disc_manager->setPortalID($file_item->getPortalId());

            // Currently, the file manager does not unlink a file here, because it is also used for copying files when copying material between rooms.
            $success = $disc_manager->copyFile($tempname, $file_item->getDiskFileNameWithoutFolder(), false);
            if (!$success) {
                throw new Exception();
            } else {
                if (function_exists('gd_info')) {
                    $size_info = @getimagesize($file_item->getDiskFileName());
                    if (is_array($size_info)) {
                        if ($size_info[0] > $this->_MAX_PICTURE_SIDE or $size_info[1] > $this->_MAX_PICTURE_SIDE) {
                            // create Filename: origname.xxx -> origname_thumb.png
                            $destination = $this->_create_thumb_name_from_image_name($file_item->getDiskFileNameWithoutFolder());
                            $this->_miniatur($file_item->getDiskFileName(), $destination);
                        }
                    }
                }
            }
            $disc_manager->setContextID($this->_environment->getCurrentContextID());
        }

        return $success;
    }

    public function setNewerLimit($datetime)
    {
        $this->_limit_newer = $datetime;
    }

    public function resetLimits()
    {
        $this->_limit_newer = '';
    }

    public function _performQuery($count = false)
    {
        $queryBuilder = $this->_db_connector->getConnection()->createQueryBuilder();

        $queryBuilder
            ->select('f.files_id', 'f.portal_id', 'f.context_id', 'f.creator_id', 'f.deleter_id',
                'f.creation_date', 'f.modification_date', 'f.deletion_date', 'f.filename', 'f.filepath',
                'f.size', 'f.extras')
            ->from($this->addDatabasePrefix('files'), 'f');

        if ($this->_delete_limit) {
            $queryBuilder
                ->andWhere('f.deleter_id IS NULL')
                ->andWhere('f.deletion_date IS NULL');
        }

        if (isset($this->_id_array_limit)) {
            $queryBuilder
                ->andWhere($queryBuilder->expr()->in('f.files_id', $this->_id_array_limit));
        }

        if (!empty($this->_room_limit)) {
            $queryBuilder
                ->andWhere('f.context_id = :roomLimit')
                ->setParameter('roomLimit', $this->_room_limit);
        }
        if (!empty($this->_limit_newer)) {
            $queryBuilder
                ->andWhere('f.creation_date > :limitNewer')
                ->setParameter('limitNewer', $this->_limit_newer);
        }

        $queryBuilder
            ->orderBy('f.filename', 'DESC');

        $cache_exists = false;
        if (!empty($this->_cache)) {
            if (isset($this->_id_array_limit)) {
                $cache_exists = true;
                foreach ($this->_id_array_limit as $id) {
                    if (!array_key_exists($id, $this->_cache)) {
                        $cache_exists = false;
                    } else {
                        $result[] = $this->_cache[$id];
                    }
                }
            }

            if (!$cache_exists) {
                $result = [];
            }
        }

        if (!$cache_exists) {
            try {
                $r = $this->_db_connector->performQuery(
                    $queryBuilder->getSQL(),
                    $queryBuilder->getParameters()
                );

                if ($this->_cache_on) {
                    foreach ($r as $res) {
                        $this->_cache[$res['files_id']] = $res;
                    }
                }
                $result = $r;
            } catch (\Doctrine\DBAL\Exception $e) {
                trigger_error('Problems selecting '.$this->_db_table.' items.', E_USER_WARNING);
            }
        }

        if (empty($result)) {
            $result = [];
        }

        return $result;
    }

    /**  delete a file "item".
     *
     * @param cs_file_item the file "item" to be deleted
     */
    public function delete(int $itemId): void
    {
        $current_datetime = getCurrentDateTimeInMySQL();
        $current_user = $this->_environment->getCurrentUserItem();
        $user_id = $current_user->getItemID() ?: 0;
        $query = 'UPDATE ' . $this->addDatabasePrefix($this->_db_table) . ' SET ' .
            'deletion_date="' . $current_datetime . '",' .
            'deleter_id="' . encode(AS_DB, $user_id) . '"' .
            ' WHERE files_id="' . encode(AS_DB, $itemId) . '"';
        $result = $this->_db_connector->performQuery($query);
        if (!isset($result) or !$result) {
            trigger_error('Problems deleting files from query: "' . $query . '"', E_USER_WARNING);
        } else {
            $link_manager = $this->_environment->getLinkItemFileManager();
            $link_manager->deleteByFileID($itemId);
        }
    }

    public function deleteReally($file_item)
    {
        $query = 'DELETE FROM ' . $this->addDatabasePrefix($this->_db_table) .
            ' WHERE files_id="' . encode(AS_DB, $file_item->getFileID()) . '"';
        $result = $this->_db_connector->performQuery($query);
        if (!isset($result)) {
            trigger_error('Problems deleting files from query: "' . $query . '"', E_USER_WARNING);
        } else {
            $disc_manager = $this->_environment->getDiscManager();
            $disc_manager->unlinkFile($file_item->getDiskFileNameWithoutFolder());
            unset($disc_manager);

            $link_manager = $this->_environment->getLinkItemFileManager();
            $link_manager->deleteByFileReally($file_item->getFileID());
            unset($link_manager);
        }
        unset($file_item);
    }

    private function _deleteReallyByFileIDOnlyDB($file_id)
    {
        $query = 'DELETE FROM ' . $this->addDatabasePrefix($this->_db_table) .
            ' WHERE files_id="' . encode(AS_DB, $file_id) . '"';
        $result = $this->_db_connector->performQuery($query);
        if (!isset($result)) {
            trigger_error('Problems deleting links of a file item from query: "' . $query . '"', E_USER_WARNING);
        }
    }

    public function _miniatur($pict, $dest_pict)
    {
        $image_in_info = getimagesize($pict);
        $x_orig = $image_in_info[0];
        $y_orig = $image_in_info[1];
        $file_type = $image_in_info[2];

        // Depending of image format, use the corrct function to read the image
        switch ($file_type) {
            case 1: // Gif
                $image_in = imagecreatefromgif($pict);
                break;
            case 2: // Jpeg
                $image_in = imagecreatefromjpeg($pict);
                break;
            case 3: // Png
                $image_in = imagecreatefrompng($pict);
        }

        if (isset($image_in)) {
            // scale the image- the longest side is _MAX_PICTURE_SIDE px long
            $scale = $this->_MAX_PICTURE_SIDE / $x_orig;
            if ($x_orig < $y_orig) {
                $scale = $this->_MAX_PICTURE_SIDE / $y_orig;
            }

            $horizontal = round($x_orig * $scale);
            $vertikal = round($y_orig * $scale);

            $x0 = 0;
            $y0 = 0;
            $xw = $horizontal;
            $yw = $vertikal;

            // create pitput picture
            if (1 != $file_type) { // all but gif
                $image_out = imagecreatetruecolor($horizontal, $vertikal);
            } else {
                $image_out = imagecreate($horizontal, $vertikal);
            }
            $color = imagecolorallocate($image_out, 255, 128, 255); // magenta
            imagefill($image_out, 0, 0, $color);
            imagecolortransparent($image_out, $color);
            imagecopyresampled($image_out, $image_in, $x0, $y0, 0, 0, $xw, $yw, $x_orig, $y_orig);
            $disc_manager = $this->_environment->getDiscManager();
            imagepng($image_out, $disc_manager->getFilePath() . $dest_pict);
            imagedestroy($image_in);
            imagedestroy($image_out);
        }
    }

    // create Filename: origname.xxx -> origname_thumb.png
    public function _create_thumb_name_from_image_name($name)
    {
        // $thumb_name = $name;
        // $point_position = mb_strrpos($thumb_name,'.');
        // $thumb_name = substr_replace ( $thumb_name, '_thumb.png', $point_position , mb_strlen($thumb_name));
        // $thumb_name = substr($thumb_name, 0, $point_position).'_thumb.png'.substr($thumb_name, $point_position+mb_strlen($thumb_name));
        $thumb_name = $name . '_thumb';

        return $thumb_name;
    }

    public function copyDataFromRoomToRoom($old_id, $new_id, $user_id = '', $id_array = '')
    {
        $retour = [];
        $current_date = getCurrentDateTimeInMySQL();
        $current_data_array = [];

        $query = '';
        $query .= 'SELECT * FROM ' . $this->addDatabasePrefix($this->_db_table) . ' WHERE context_id="' . encode(AS_DB, $old_id) . '" AND deleter_id IS NULL AND deletion_date IS NULL';
        $result = $this->_db_connector->performQuery($query);
        if (!isset($result)) {
            trigger_error('Problems getting data "' . $this->_db_table . '" from query: "' . $query . '"', E_USER_WARNING);
        } else {
            $item_id = 'files_id';
            $modification_date = 'creation_date';
            $sql = 'SELECT ' . $item_id . ',' . $modification_date . ',extras FROM ' . $this->addDatabasePrefix($this->_db_table) . ' WHERE context_id="' . encode(AS_DB, $new_id) . '"';
            $sql .= ' AND extras LIKE "%s:4:\"COPY\";a:2:{s:7:\"ITEM_ID\";%"';
            $sql .= ' AND deleter_id IS NULL AND deletion_date IS NULL;';
            $sql_result = $this->_db_connector->performQuery($sql);
            if (!isset($sql_result)) {
                trigger_error('Problems getting data "' . $this->_db_table . '".', E_USER_WARNING);
            } else {
                foreach ($sql_result as $sql_row) {
                    $extra_array = unserialize($sql_row['extras']);
                    $current_data_array[$extra_array['COPY']['ITEM_ID']] = $sql_row[$item_id];
                    // $current_copy_date_array[$extra_array['COPY']['ITEM_ID']] = $extra_array['COPY']['DATETIME'];
                    // $current_mod_date_array[$extra_array['COPY']['ITEM_ID']] = $sql_row[$modification_date];
                }
            }
            foreach ($result as $query_result) {
                $do_it = true;

                if (array_key_exists($query_result['files_id'], $current_data_array)) {
                    $retour[CS_FILE_TYPE . $query_result['files_id']] = $current_data_array[$query_result['files_id']];
                    $do_it = false;
                }

                if ($do_it) {
                    $insert_query = '';
                    $insert_query .= 'INSERT INTO ' . $this->addDatabasePrefix($this->_db_table) . ' SET';
                    $first = true;
                    $old_item_id = '';
                    foreach ($query_result as $key => $value) {
                        $value = encode(FROM_DB, $value);
                        if ('files_id' == $key) {
                            $old_item_id = $value;
                        } elseif ('context_id' == $key) {
                            $after = $key . '="' . $new_id . '"';
                        } elseif ('modification_date' == $key
                            or 'creation_date' == $key
                        ) {
                            $after = $key . '="' . $current_date . '"';
                        } elseif (!empty($user_id)
                            and ('creator_id' == $key
                                or 'modifier_id' == $key)
                        ) {
                            $after = $key . '="' . $user_id . '"';
                        } elseif ('deletion_date' == $key
                            or 'deleter_id' == $key
                            or 'material_id' == $key
                            or 'material_vid' == $key
                        ) {
                            // do nothing
                        } // extra
                        elseif ('extras' == $key
                            and !empty($old_item_id)
                        ) {
                            $extra_array = unserialize($value);
                            $extra_array['COPY']['ITEM_ID'] = $old_item_id;
                            $extra_array['COPY']['COPYING_DATE'] = $current_date;
                            $value = serialize($extra_array);
                            $after = $key . '="' . encode(AS_DB, $value) . '"';
                        } else {
                            $after = $key . '="' . encode(AS_DB, $value) . '"';
                        }

                        if (!empty($after)) {
                            if ($first) {
                                $first = false;
                                $before = ' ';
                            } else {
                                $before = ',';
                            }
                            $insert_query .= $before . $after;
                            unset($after);
                        }
                    }
                    $result_insert = $this->_db_connector->performQuery($insert_query);
                    if (!isset($result_insert)) {
                        trigger_error('Problem creating item from query: "' . $insert_query . '"', E_USER_ERROR);
                    } else {
                        $new_item_id = $result_insert;
                        if (!empty($old_item_id)) {
                            $retour[CS_FILE_TYPE . $old_item_id] = $new_item_id;

                            // copy file
                            $disc_manager = $this->_environment->getDiscManager();
                            $disc_manager->setPortalID($this->_environment->getCurrentPortalID());
                            $file_item = $this->getItem($old_item_id);
                            if (!empty($file_item)) {
                                $result = $disc_manager->copyFileFromRoomToRoom($old_id, $old_item_id, $file_item->getFileName(), $new_id, $new_item_id);
                            } else {
                                trigger_error('can not get old file item', E_USER_ERROR);
                            }
                            unset($file_item);
                            unset($disc_manager);
                        } else {
                            trigger_error('lost old item id at copying data', E_USER_ERROR);
                        }
                    }
                }
            }
        }

        return $retour;
    }

    public function deleteReallyOlderThan($days)
    {
        $disc_manager = $this->_environment->getDiscManager();
        $retour = true;
        $timestamp = getCurrentDateTimeMinusDaysInMySQL($days);

        $query = 'SELECT ' .
            $this->addDatabasePrefix($this->_db_table) . '.files_id, ' .
            $this->addDatabasePrefix($this->_db_table) . '.portal_id, ' .
            $this->addDatabasePrefix($this->_db_table) . '.context_id, ' .
            $this->addDatabasePrefix($this->_db_table) . '.filename
            FROM ' . $this->addDatabasePrefix($this->_db_table) . '
            WHERE deletion_date IS NOT NULL and deletion_date < "' . $timestamp . '";';

        $result = $this->_db_connector->performQuery($query);
        if (!isset($result)) {
            trigger_error('Problem selecting items from query: "' . $query . '"', E_USER_ERROR);
        } else {
            // foreign key constraint
            foreach ($result as $file) {
                $linkItemFileManager = $this->_environment->getLinkItemFileManager();
                $linkItemFileManager->deleteByFileReally($file['files_id']);
            }

            $retour = parent::deleteReallyOlderThan($days);
            foreach ($result as $query_result) {
                $filename = 'cid' . $query_result['context_id'] . '_' . $query_result['files_id'] . '_' . $query_result['filename'];
                $disc_manager->setPortalID($query_result['portal_id']);
                $disc_manager->setContextID($query_result['context_id']);
                if ($disc_manager->existsFile($filename)) {
                    $retour = $retour && $disc_manager->unlinkFile($filename);
                }
            }
        }

        return $retour;
    }

    public function deleteUnneededFiles($context_id, $portal_id = '')
    {
        if (!isset($context_id) or empty($context_id)) {
            trigger_error('deleteUnneededFiles: no context_id given', E_USER_ERROR);
            $retour = false;
        } else {
            $retour = true;

            // get all file ids in the given context
            $sql = 'SELECT ' . $this->addDatabasePrefix($this->_db_table) . '.files_id, ' . $this->addDatabasePrefix($this->_db_table) . '.context_id, ' . $this->addDatabasePrefix($this->_db_table) . '.filename FROM ' . $this->addDatabasePrefix($this->_db_table) . ' WHERE ' . $this->addDatabasePrefix($this->_db_table) . '.context_id="' . $context_id . '";';
            $result = $this->_db_connector->performQuery($sql);
            if (!isset($result)) {
                trigger_error('Problem selecting items from query: "' . $sql . '"', E_USER_ERROR);
                $retour = false;
            } else {
                $file_id_array = [];
                foreach ($result as $query_result) {
                    if (!empty($query_result['files_id'])) {
                        $file_id_array[] = $query_result['files_id'];
                    }
                }

                // try to get the same file ids from the item_link_file table
                if (!empty($file_id_array)) {
                    $sql2 = 'SELECT file_id FROM ' . $this->addDatabasePrefix('item_link_file') . ' WHERE file_id IN (' . implode(',', $file_id_array) . ');';
                    $result2 = $this->_db_connector->performQuery($sql2);
                    if (!isset($result2)) {
                        trigger_error('Problem selecting items from query: "' . $sql2 . '"', E_USER_ERROR);
                        $retour = false;
                    } else {
                        $file_id_array2 = [];
                        foreach ($result2 as $query_result2) {
                            if (!empty($query_result2['file_id'])) {
                                $file_id_array2[] = $query_result2['file_id'];
                            }
                        }
                    }
                }

                // file_id_diff will contain all file ids that are not linked anymore
                if (!empty($file_id_array)) {
                    $file_id_array = array_unique($file_id_array);
                }
                if (!empty($file_id_array2)) {
                    $file_id_array2 = array_unique($file_id_array2);
                    $file_id_diff = array_diff($file_id_array, $file_id_array2);
                } else {
                    $file_id_diff = [];
                }

                $disc_manager = $this->_environment->getDiscManager();
                foreach ($result as $query_result) {
                    if (!empty($query_result['files_id']) and in_array($query_result['files_id'], $file_id_diff)) {
                        $sql = 'DELETE FROM ' . $this->addDatabasePrefix($this->_db_table) . ' WHERE files_id="' . $query_result['files_id'] . '";';
                        $result_delete = $this->_db_connector->performQuery($sql);

                        // get the current portal id, if it was not given
                        if (empty($portal_id)) {
                            $query2 = 'SELECT context_id as portal_id FROM ' . $this->addDatabasePrefix('room') . ' WHERE item_id="' . $query_result['context_id'] . '"';
                            $result2 = $this->_db_connector->performQuery($query2);
                            if (!isset($result2)) {
                                trigger_error('Problem selecting items from query: "' . $query2 . '"', E_USER_ERROR);
                                $retour = false;
                            } elseif (!empty($result2[0])) {
                                $query_result2 = $result2[0];
                                if (!empty($query_result2['portal_id'])) {
                                    $portal_id = $query_result2['portal_id'];
                                }
                            }
                        }

                        if (!empty($portal_id)) {
                            $disc_manager->setPortalID($portal_id);
                            $disc_manager->setContextID($query_result['context_id']);
                            $file_info = [];
                            if (!empty($query_result['filename'])) {
                                $file_info = pathinfo((string) $query_result['filename']);
                            }
                            $file_ext = '';
                            if (!empty($file_info['extension'])) {
                                $file_ext = $file_info['extension'];
                            }
                            $filename = $disc_manager->getCurrentFileName($query_result['files_id'], $file_ext);
                            if (!empty($filename) and $disc_manager->existsFile($filename)) {
                                $retour = $retour and $disc_manager->unlinkFile($filename);
                            }
                        }
                    }
                }
            }
            unset($disc_manager);
        }

        return $retour;
    }

    /** Prepares the db_array for the item.
     *
     * @param $db_array Contains the data from the database
     *
     * @return array Contains prepared data ( textfunctions applied etc. )
     */
    public function _buildItem($db_array)
    {
        $db_array['extras'] = unserialize($db_array['extras']);

        return parent::_buildItem($db_array);
    }
}
