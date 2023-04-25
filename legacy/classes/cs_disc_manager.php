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

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

class cs_disc_manager
{
    final public const RELATIVE_FILES_PATH = 'files/';

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

    public function getFilePath($firstId = '', $secondId = ''): string
    {
        $path = $this->getFilePathBasic();

        if (empty($firstId) && empty($this->firstId)) {
            throw new LogicException('first id is not set');
        }
        $path .= (!empty($firstId) ? $firstId : $this->firstId) .'/';

        if (empty($secondId) && empty($this->secondId)) {
            throw new LogicException('second id is not set');
        }

        if (!empty($secondId)) {
            $pathAlt = $path.$secondId.'/';
            $path .= $this->_getSecondFolder($secondId).'/';
            if (!is_dir($path) && is_dir($pathAlt)) {
                return $pathAlt;
            }
            return $path;
        }

        $pathAlt = $path.$this->secondId.'/';
        $path .= $this->_getSecondFolder($this->secondId).'/';
        if (!is_dir($path) && is_dir($pathAlt)) {
            return $pathAlt;
        }
        return $path;
    }

    public function getAbsoluteFilePath(int $portalId, int $contextId, string $fileName): string
    {
        return $this->getFilePath($portalId, $contextId).$fileName;
    }

    public function getRelativeFilePath(int $portalId, int $contextId, string $fileName): string
    {
        /** @var ContainerInterface $symfonyContainer */
        global $symfonyContainer;
        $projectDir = $symfonyContainer->getParameter('kernel.project_dir');

        return Path::makeRelative($this->getAbsoluteFilePath($portalId, $contextId, $fileName), $projectDir);
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
        if (!empty($filename) && $this->existsFile($filename)) {
            return unlink($this->getFilePath().$filename);
        }

        return false;
    }

    public function copyFile($source_file, $dest_filename, $delete_source): bool
    {
        $retour = false;
        $this->makeFolder($this->firstId, $this->secondId);
        if (file_exists($source_file)) {
            $retour = copy($source_file, $this->getFilePath() . $dest_filename);
        }
        if ($retour && $delete_source) {
            unlink($source_file);
        }

        return $retour;
    }

    public function copyFileFromRoomToRoom($old_room_id, $old_file_id, $filename, $new_room_id, $new_file_id): bool
    {
        if (empty($old_room_id)) {
            trigger_error('old_room_id is not set', E_USER_ERROR);
        }
        $this->makeFolder($this->firstId, $new_room_id);
        $source_file = str_replace('//', '/', $this->getFilePath('',
            $old_room_id).'/'.$old_file_id.'.'.cs_strtolower(mb_substr(strrchr((string) $filename, '.'), 1)));
        $target_file = str_replace('//', '/', $this->getFilePath('',
            $new_room_id).'/'.$new_file_id.'.'.cs_strtolower(mb_substr(strrchr((string) $filename, '.'), 1)));

        // copy
        if (file_exists($source_file)) {
            return copy($source_file, $target_file);
        } else {
            return true;
        }
    }

    public function copyImageFromRoomToRoom($picture_name, $new_room_id): bool
    {
        if (!empty($picture_name) && !empty($new_room_id)) {
            $this->makeFolder($this->firstId, $new_room_id);

            $value_array = explode('_', (string) $picture_name);
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
                    return $retour;
                } else {
                    return true;
                }
            } else {
                return true;
            }
        } else {
            return true;
        }

        return false;
    }

    /**
     * @return void
     */
    public function makeFolder($first_id, $second_id)
    {
        if (!empty($first_id) and !empty($second_id)) {
            $this->makeDirectory($this->getFilePath($first_id, $second_id));
        } else {
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

    public function getLastSavedFileName(): string
    {
        return $this->lastSavedFilename;
    }

    public function getCurrentFileName(int $fileId, string $fileExt): string
    {
        return "$fileId.$fileExt";
    }

    /**
     * @return void
     */
    public function removeRoomDir($first_id, $second_id)
    {
        $dir = $this->getFilePath($first_id, $second_id);
        $fs = new Filesystem();

        if ($fs->exists($dir)) {
            $fs->remove($dir);
        }
    }

    private function getFilePathBasic(): string
    {
        /** @var ContainerInterface $symfonyContainer */
        global $symfonyContainer;
        $projectDir = $symfonyContainer->getParameter('kernel.project_dir');

        return $projectDir.'/'.self::RELATIVE_FILES_PATH;
    }

    private function _getSecondFolder(string $second_folder): string
    {
        if (empty($second_folder)) {
            return md5(getCurrentDateTimeInMySQL());
        }

        $retour = '';
        for ($i = 0; $i < strlen($second_folder); ++$i) {
            if ($i > 0 && 0 == $i % 4) {
                $retour .= '/';
            }
            $retour .= $second_folder[$i];
        }
        $retour .= '_';

        return $retour;
    }
}
