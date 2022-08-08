<?php
// $Id$
//
// Release $Name$
//
// Copyright (c)2002-2006 Iver Jackewitz
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

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;

class cs_disc_manager
{
    const RELATIVE_FILES_PATH = 'files/';
    const TEMP_FOLDER_NAME = 'temp';

    private $firstId = null;
    private $secondId = null;

    private string $lastSavedFilename = '';

    /**
     * @param $value
     * @return void
     */
    public function setServerID($value)
    {
        $this->firstId = $value;
        $this->secondId = $value;
    }

    /**
     * @param $value
     * @return void
     */
    public function setPortalID($value)
    {
        $this->firstId = $value;
    }

    /**
     * @param $value
     * @return void
     */
    public function setContextID($value)
    {
        $this->secondId = $value;
    }

    /**
     * @param $first_id
     * @param $second_id
     * @return string
     */
    public function getFilePath($first_id = '', $second_id = ''): string
    {
        $retour = '';
        $retour .= $this->getFilePathBasic();
        if (!empty($first_id)) {
            $retour .= $first_id . '/';
        } elseif (!empty($this->firstId)) {
            $retour .= $this->firstId . '/';
        } else {
            include_once('functions/error_functions.php');
            trigger_error('first_id is not set', E_USER_WARNING);
        }

        if (!empty($second_id)) {
            $retour_old = $retour . $second_id . '/';
            $retour .= $this->_getSecondFolder($second_id) . '/';
            if (!is_dir($retour) and is_dir($retour_old)) {
                $retour = $retour_old;
            }
        } elseif (!empty($this->secondId)) {
            $retour_old = $retour . $this->secondId . '/';
            $retour .= $this->_getSecondFolder($this->secondId) . '/';
            if (!is_dir($retour) and is_dir($retour_old)) {
                $retour = $retour_old;
            }
        } else {
            include_once('functions/error_functions.php');
            trigger_error('second_id is not set', E_USER_WARNING);
        }
        return $retour;
    }

    /**
     * @param $filename
     * @return bool
     */
    public function existsFile($filename): bool
    {
        if (empty($filename)) {
            return false;
        }

        $filePath = $this->getFilePath() . $filename;
        return file_exists($filePath);
    }

    /**
     * @param $filename
     * @return bool
     */
    public function unlinkFile($filename): bool
    {
        $retour = false;
        if (!empty($filename) && $this->existsFile($filename)) {
            $retour = unlink($this->getFilePath() . $filename);
        }
        return $retour;
    }

    /**
     * @param $source_file
     * @param $dest_filename
     * @param $delete_source
     * @return bool
     */
    public function copyFile($source_file, $dest_filename, $delete_source): bool
    {
        $retour = false;
        $this->makeFolder($this->firstId, $this->secondId);
        if (file_exists($source_file)) {
            $retour = copy($source_file, $this->getFilePath() . $dest_filename);
        }
        if ($retour and $delete_source) {
            unlink($source_file);
        }
        return $retour;
    }

    /**
     * @param $old_room_id
     * @param $old_file_id
     * @param $filename
     * @param $new_room_id
     * @param $new_file_id
     * @return bool
     */
    public function copyFileFromRoomToRoom($old_room_id, $old_file_id, $filename, $new_room_id, $new_file_id): bool
    {
        $retour = false;
        if (empty($old_room_id)) {
            include_once('functions/error_functions.php');
            trigger_error('old_room_id is not set', E_USER_ERROR);
        }
        $this->makeFolder($this->firstId, $new_room_id);
        $source_file = str_replace('//', '/', $this->getFilePath('',
                $old_room_id) . '/' . $old_file_id . '.' . cs_strtolower(mb_substr(strrchr($filename, '.'), 1)));
        $target_file = str_replace('//', '/', $this->getFilePath('',
                $new_room_id) . '/' . $new_file_id . '.' . cs_strtolower(mb_substr(strrchr($filename, '.'), 1)));

        // copy
        if (file_exists($source_file)) {
            $retour = copy($source_file, $target_file);
        } else {
            $retour = true;
        }
        return $retour;
    }

    /**
     * @param $picture_name
     * @param $new_room_id
     * @return bool
     */
    public function copyImageFromRoomToRoom($picture_name, $new_room_id): bool
    {
        $retour = false;
        if (!empty($picture_name) && !empty($new_room_id)) {
            $this->makeFolder($this->firstId, $new_room_id);

            $value_array = explode('_', $picture_name);
            $old_room_id = $value_array[0];
            $old_room_id = str_replace('cid', '', $old_room_id);
            $value_array[0] = 'cid' . $new_room_id;

            $new_picture_name = implode('_', $value_array);

            // source file
            $source_file = str_replace('//', '/', $this->getFilePath('', $old_room_id) . '/' . $picture_name);
            $target_file = str_replace('//', '/', $this->getFilePath('', $new_room_id) . '/' . $new_picture_name);

            // copy
            if (file_exists($source_file)) {
                if ($source_file != $target_file) {
                    $retour = copy($source_file, $target_file);
                    if ($retour) {
                        $this->lastSavedFilename = $new_picture_name;
                    }
                } else {
                    $retour = true;
                }
            } else {
                $retour = true;
            }
        } else {
            $retour = true;
        }

        return $retour;
    }

    /**
     * @param $first_id
     * @param $second_id
     * @return void
     */
    public function makeFolder($first_id, $second_id)
    {
        if (!empty($first_id) and !empty($second_id)) {
            $this->makeDirectory($this->getFilePath($first_id, $second_id));
        } else {
            include_once('functions/error_functions.php');
            trigger_error('first and second folder can not be empty - abort executing', E_USER_ERROR);
        }
    }

    /**
     * @param string $dir
     * @return bool
     */
    public function makeDirectory(string $dir): bool
    {
        $fs = new Filesystem();

        if (!$fs->exists($dir)) {
            try {
                $fs->mkdir($dir);
            } catch (IOExceptionInterface $exception) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param $file
     * @return string
     */
    public function getFileAsString($file): string
    {
        $retour = '';
        if (file_exists($file)) {
            $retour .= file_get_contents($file);
        }
        return $retour;
    }

    /**
     * @param $file
     * @return string
     */
    public function getFileAsBase64($file): string
    {
        $retour = '';
        if (file_exists($file)) {
            $retour .= file_get_contents($file);
        }
        $retour = base64_encode($retour);
        return $retour;
    }

    /**
     * @return string
     */
    public function getTempFolder(): string
    {
        return $this->getFilePathBasic() . self::TEMP_FOLDER_NAME;
    }

    /**
     * @return string
     */
    public function getLastSavedFileName(): string
    {
        return $this->lastSavedFilename;
    }

    /**
     * @param $context_id
     * @param $file_id
     * @param $file_name
     * @param $file_ext
     * @return string
     */
    public function getCurrentFileName($context_id, $file_id, $file_name, $file_ext)
    {
        return $file_id . '.' . $file_ext;
    }

    /**
     * @return string
     */
    public function getFilePathBasic(): string
    {
        global $symfonyContainer;
        $projectDir = $symfonyContainer->get('kernel')->getProjectDir();

        return $projectDir . '/' . self::RELATIVE_FILES_PATH;
    }

    /**
     * @param $first_id
     * @param $second_id
     * @return void
     */
    public function removeRoomDir($first_id, $second_id)
    {
        $dir = $this->getFilePath($first_id, $second_id);
        $this->_full_rmdir($dir);
    }

    /**
     * @param $second_folder
     * @return string
     */
    private function _getSecondFolder($second_folder): string
    {
        $second_folder = (string)$second_folder;
        if (!empty($second_folder)) {
            $retour = '';
            for ($i = 0; $i < strlen($second_folder); $i++) {
                if ($i > 0 and $i % 4 == 0) {
                    $retour .= '/';
                }
                $retour .= $second_folder[$i];
            }
            $retour .= '_';
        } else {
            include_once('functions/date_functions.php');
            $retour = md5(getCurrentDateTimeInMySQL());
        }
        return $retour;
    }

    /**
     * @param $dirname
     * @return bool
     */
    private function _full_rmdir($dirname): bool
    {
        if (is_dir($dirname)) {
            if ($dirHandle = opendir($dirname)) {
                $old_cwd = getcwd();
                chdir($dirname);

                while ($file = readdir($dirHandle)) {
                    if ($file == '.' || $file == '..') {
                        continue;
                    }
                    if (is_dir($file)) {
                        if (!$this->_full_rmdir($file)) {
                            chdir($old_cwd);
                            return false;
                        }
                    } else {
                        if (!@unlink($file)) {
                            chdir($old_cwd);
                            return false;
                        }
                    }
                }

                closedir($dirHandle);
                chdir($old_cwd);
                if (!rmdir($dirname)) {
                    return false;
                }
                return true;
            }
        }

        return false;
    }
}