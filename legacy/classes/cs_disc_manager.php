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

use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;

class cs_disc_manager
{
    public const RELATIVE_FILES_PATH = 'files/';
    public const TEMP_FOLDER_NAME = 'temp';

    private $firstId = null;
    private $secondId = null;

    private string $lastSavedFilename = '';

    /**
     * @return void
     */
    public function setServerID($value)
    {
        $this->firstId = $value;
        $this->secondId = $value;
    }

    /**
     * @return void
     */
    public function setPortalID($value)
    {
        $this->firstId = $value;
    }

    /**
     * @return void
     */
    public function setContextID($value)
    {
        $this->secondId = $value;
    }

    public function getFilePath($first_id = '', $second_id = ''): string
    {
        $retour = '';
        $retour .= $this->getFilePathBasic();
        if (!empty($first_id)) {
            $retour .= $first_id.'/';
        } elseif (!empty($this->firstId)) {
            $retour .= $this->firstId.'/';
        } else {
            include_once 'functions/error_functions.php';
            trigger_error('first_id is not set', E_USER_WARNING);
        }

        if (!empty($second_id)) {
            $retour_old = $retour.$second_id.'/';
            $retour .= $this->_getSecondFolder($second_id).'/';
            if (!is_dir($retour) and is_dir($retour_old)) {
                $retour = $retour_old;
            }
        } elseif (!empty($this->secondId)) {
            $retour_old = $retour.$this->secondId.'/';
            $retour .= $this->_getSecondFolder($this->secondId).'/';
            if (!is_dir($retour) and is_dir($retour_old)) {
                $retour = $retour_old;
            }
        } else {
            include_once 'functions/error_functions.php';
            trigger_error('second_id is not set', E_USER_WARNING);
        }

        return $retour;
    }

    public function existsFile($filename): bool
    {
        if (empty($filename)) {
            return false;
        }

        $filePath = $this->getFilePath().$filename;

        return file_exists($filePath);
    }

    public function unlinkFile($filename): bool
    {
        $retour = false;
        if (!empty($filename) && $this->existsFile($filename)) {
            $retour = unlink($this->getFilePath().$filename);
        }

        return $retour;
    }

    public function copyFile($source_file, $dest_filename, $delete_source): bool
    {
        $retour = false;
        $this->makeFolder($this->firstId, $this->secondId);
        if (file_exists($source_file)) {
            $retour = copy($source_file, $this->getFilePath().$dest_filename);
        }
        if ($retour and $delete_source) {
            unlink($source_file);
        }

        return $retour;
    }

    public function copyFileFromRoomToRoom($old_room_id, $old_file_id, $filename, $new_room_id, $new_file_id): bool
    {
        $retour = false;
        if (empty($old_room_id)) {
            include_once 'functions/error_functions.php';
            trigger_error('old_room_id is not set', E_USER_ERROR);
        }
        $this->makeFolder($this->firstId, $new_room_id);
        $source_file = str_replace('//', '/', $this->getFilePath('',
            $old_room_id).'/'.$old_file_id.'.'.cs_strtolower(mb_substr(strrchr($filename, '.'), 1)));
        $target_file = str_replace('//', '/', $this->getFilePath('',
            $new_room_id).'/'.$new_file_id.'.'.cs_strtolower(mb_substr(strrchr($filename, '.'), 1)));

        // copy
        if (file_exists($source_file)) {
            $retour = copy($source_file, $target_file);
        } else {
            $retour = true;
        }

        return $retour;
    }

    public function copyImageFromRoomToRoom($picture_name, $new_room_id): bool
    {
        $retour = false;
        if (!empty($picture_name) && !empty($new_room_id)) {
            $this->makeFolder($this->firstId, $new_room_id);

            $value_array = explode('_', $picture_name);
            $old_room_id = $value_array[0];
            $old_room_id = str_replace('cid', '', $old_room_id);
            $value_array[0] = 'cid'.$new_room_id;

            $new_picture_name = implode('_', $value_array);

            // source file
            $source_file = str_replace('//', '/', $this->getFilePath('', $old_room_id).'/'.$picture_name);
            $target_file = str_replace('//', '/', $this->getFilePath('', $new_room_id).'/'.$new_picture_name);

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
     * @return void
     */
    public function makeFolder($first_id, $second_id)
    {
        if (!empty($first_id) and !empty($second_id)) {
            $this->makeDirectory($this->getFilePath($first_id, $second_id));
        } else {
            include_once 'functions/error_functions.php';
            trigger_error('first and second folder can not be empty - abort executing', E_USER_ERROR);
        }
    }

    public function makeDirectory(string $dir): bool
    {
        $fs = new Filesystem();

        if (!$fs->exists($dir)) {
            try {
                $fs->mkdir($dir);
            } catch (IOExceptionInterface) {
                return false;
            }
        }

        return true;
    }

    public function getFileAsString($file): string
    {
        $retour = '';
        if (file_exists($file)) {
            $retour .= file_get_contents($file);
        }

        return $retour;
    }

    public function getFileAsBase64($file): string
    {
        $retour = '';
        if (file_exists($file)) {
            $retour .= file_get_contents($file);
        }
        $retour = base64_encode($retour);

        return $retour;
    }

    public function getTempFolder(): string
    {
        return $this->getFilePathBasic().self::TEMP_FOLDER_NAME;
    }

    public function getLastSavedFileName(): string
    {
        return $this->lastSavedFilename;
    }

    /**
     * @return string
     */
    public function getCurrentFileName($context_id, $file_id, $file_name, $file_ext)
    {
        return $file_id.'.'.$file_ext;
    }

    public function getFilePathBasic(): string
    {
        global $symfonyContainer;
        $projectDir = $symfonyContainer->get('kernel')->getProjectDir();

        return $projectDir.'/'.self::RELATIVE_FILES_PATH;
    }

    /**
     * @return void
     */
    public function removeRoomDir($first_id, $second_id)
    {
        $dir = $this->getFilePath($first_id, $second_id);
        $this->_full_rmdir($dir);
    }

    private function _getSecondFolder($second_folder): string
    {
        $second_folder = (string) $second_folder;
        if (!empty($second_folder)) {
            $retour = '';
            for ($i = 0; $i < strlen($second_folder); ++$i) {
                if ($i > 0 and 0 == $i % 4) {
                    $retour .= '/';
                }
                $retour .= $second_folder[$i];
            }
            $retour .= '_';
        } else {
            include_once 'functions/date_functions.php';
            $retour = md5(getCurrentDateTimeInMySQL());
        }

        return $retour;
    }

    private function _full_rmdir($dirname): bool
    {
        if (is_dir($dirname)) {
            if ($dirHandle = opendir($dirname)) {
                $old_cwd = getcwd();
                chdir($dirname);

                while ($file = readdir($dirHandle)) {
                    if ('.' == $file || '..' == $file) {
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
