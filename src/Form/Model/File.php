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

namespace App\Form\Model;

use DateTime;

class File
{
    /**
     * @var mixed|null
     */
    private $fileId;

    /**
     * @var mixed|null
     */
    private $filename;

    /**
     * @var mixed|null
     */
    private $filePath;

    private ?DateTime $creationDate = null;

    /**
     * @var mixed|null
     */
    private $checked;

    public function setFileId($fileId)
    {
        $this->fileId = $fileId;

        return $this;
    }

    public function getFileId()
    {
        return $this->fileId;
    }

    public function setFilename($filename)
    {
        $this->filename = $filename;

        return $this;
    }

    public function getFilename()
    {
        return $this->filename;
    }

    public function setFilePath($filePath)
    {
        $this->filePath = $filePath;

        return $this;
    }

    public function getFilePath()
    {
        return $this->filePath;
    }

    public function setCreationDate(DateTime $creationDate)
    {
        $this->creationDate = $creationDate;

        return $this;
    }

    public function getCreationDate()
    {
        return $this->creationDate;
    }

    public function setChecked($checked)
    {
        $this->checked = $checked;

        return $this;
    }

    public function getChecked()
    {
        return $this->checked;
    }
}
