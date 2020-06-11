<?php
namespace App\Form\Model;

class File
{
    private $fileId;

    private $filename;

    private $filePath;

    private $creationDate;

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

    public function setCreationDate(\DateTime $creationDate)
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