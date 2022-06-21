<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ZzzFiles
 *
 * @ORM\Table(name="zzz_files", indexes={
 *     @ORM\Index(name="context_id", columns={"context_id"}),
 *     @ORM\Index(name="creator_id", columns={"creator_id"})
 * })
 * @ORM\Entity(repositoryClass="App\Repository\ZzzFilesRepository")
 */
class ZzzFiles
{
    /**
     * @var integer
     *
     * @ORM\Column(name="files_id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $filesId;

    /**
     * @var integer
     *
     * @ORM\Column(name="context_id", type="integer", nullable=true)
     */
    private $contextId;

    /**
     * @var integer
     *
     * @ORM\Column(name="creator_id", type="integer", nullable=false)
     */
    private $creatorId = '0';

    /**
     * @var integer
     *
     * @ORM\Column(name="deleter_id", type="integer", nullable=true)
     */
    private $deleterId;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="creation_date", type="datetime", nullable=false)
     */
    private $creationDate = '0000-00-00 00:00:00';

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="modification_date", type="datetime", nullable=true)
     */
    private $modificationDate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="deletion_date", type="datetime", nullable=true)
     */
    private $deletionDate;

    /**
     * @var string
     *
     * @ORM\Column(name="filename", type="string", length=255, nullable=false)
     */
    private $filename;

    /**
     * @var string
     *
     * @ORM\Column(name="filepath", type="string", length=255, nullable=false)
     */
    private $filepath;

    /**
     * @var integer
     *
     * @ORM\Column(name="size", type="integer", nullable=true)
     */
    private $size;

    /**
     * @var string
     *
     * @ORM\Column(name="has_html", type="string", nullable=false)
     */
    private $hasHtml = '0';

    /**
     * @var boolean
     *
     * @ORM\Column(name="scan", type="boolean", nullable=false)
     */
    private $scan = '-1';

    /**
     * @var string
     *
     * @ORM\Column(name="extras", type="text", length=16777215, nullable=true)
     */
    private $extras;

    /**
     * @var string
     *
     * @ORM\Column(name="temp_upload_session_id", type="string", length=255, nullable=true)
     */
    private $tempUploadSessionId;

    /**
     * Get file content base64 encoded
     *
     * @return string (base64)
     */
    public function getContent()
    {
        $filePath = $this->getFilepath();

        if (file_exists($filePath)) {
            return file_get_contents(
                $filePath, 
                'r'
            );
        } else {
            return null;
        }
    }


    /**
     * Get filesId
     *
     * @return integer
     */
    public function getFilesId()
    {
        return $this->filesId;
    }

    /**
     * Set contextId
     *
     * @param integer $contextId
     *
     * @return ZzzFiles
     */
    public function setContextId($contextId)
    {
        $this->contextId = $contextId;

        return $this;
    }

    /**
     * Get contextId
     *
     * @return integer
     */
    public function getContextId()
    {
        return $this->contextId;
    }

    /**
     * Set creatorId
     *
     * @param integer $creatorId
     *
     * @return ZzzFiles
     */
    public function setCreatorId($creatorId)
    {
        $this->creatorId = $creatorId;

        return $this;
    }

    /**
     * Get creatorId
     *
     * @return integer
     */
    public function getCreatorId()
    {
        return $this->creatorId;
    }

    /**
     * Set deleterId
     *
     * @param integer $deleterId
     *
     * @return ZzzFiles
     */
    public function setDeleterId($deleterId)
    {
        $this->deleterId = $deleterId;

        return $this;
    }

    /**
     * Get deleterId
     *
     * @return integer
     */
    public function getDeleterId()
    {
        return $this->deleterId;
    }

    /**
     * Set creationDate
     *
     * @param \DateTime $creationDate
     *
     * @return ZzzFiles
     */
    public function setCreationDate($creationDate)
    {
        $this->creationDate = $creationDate;

        return $this;
    }

    /**
     * Get creationDate
     *
     * @return \DateTime
     */
    public function getCreationDate()
    {
        return $this->creationDate;
    }

    /**
     * Set modificationDate
     *
     * @param \DateTime $modificationDate
     *
     * @return ZzzFiles
     */
    public function setModificationDate($modificationDate)
    {
        $this->modificationDate = $modificationDate;

        return $this;
    }

    /**
     * Get modificationDate
     *
     * @return \DateTime
     */
    public function getModificationDate()
    {
        return $this->modificationDate;
    }

    /**
     * Set deletionDate
     *
     * @param \DateTime $deletionDate
     *
     * @return ZzzFiles
     */
    public function setDeletionDate($deletionDate)
    {
        $this->deletionDate = $deletionDate;

        return $this;
    }

    /**
     * Get deletionDate
     *
     * @return \DateTime
     */
    public function getDeletionDate()
    {
        return $this->deletionDate;
    }

    /**
     * Set filename
     *
     * @param string $filename
     *
     * @return ZzzFiles
     */
    public function setFilename($filename)
    {
        $this->filename = $filename;

        return $this;
    }

    /**
     * Get filename
     *
     * @return string
     */
    public function getFilename()
    {
        return $this->filename;
    }

    /**
     * Set filepath
     *
     * @param string $filepath
     *
     * @return ZzzFiles
     */
    public function setFilepath($filepath)
    {
        $this->filepath = $filepath;

        return $this;
    }

    /**
     * Get filepath
     *
     * @return string
     */
    public function getFilepath()
    {
        return $this->filepath;
    }

    /**
     * Set size
     *
     * @param integer $size
     *
     * @return ZzzFiles
     */
    public function setSize($size)
    {
        $this->size = $size;

        return $this;
    }

    /**
     * Get size
     *
     * @return integer
     */
    public function getSize()
    {
        return $this->size;
    }

    /**
     * Set hasHtml
     *
     * @param string $hasHtml
     *
     * @return ZzzFiles
     */
    public function setHasHtml($hasHtml)
    {
        $this->hasHtml = $hasHtml;

        return $this;
    }

    /**
     * Get hasHtml
     *
     * @return string
     */
    public function getHasHtml()
    {
        return $this->hasHtml;
    }

    /**
     * Set scan
     *
     * @param boolean $scan
     *
     * @return ZzzFiles
     */
    public function setScan($scan)
    {
        $this->scan = $scan;

        return $this;
    }

    /**
     * Get scan
     *
     * @return boolean
     */
    public function getScan()
    {
        return $this->scan;
    }

    /**
     * Set extras
     *
     * @param string $extras
     *
     * @return ZzzFiles
     */
    public function setExtras($extras)
    {
        $this->extras = $extras;

        return $this;
    }

    /**
     * Get extras
     *
     * @return string
     */
    public function getExtras()
    {
        return $this->extras;
    }

    /**
     * Set tempUploadSessionId
     *
     * @param string $tempUploadSessionId
     *
     * @return ZzzFiles
     */
    public function setTempUploadSessionId($tempUploadSessionId)
    {
        $this->tempUploadSessionId = $tempUploadSessionId;

        return $this;
    }

    /**
     * Get tempUploadSessionId
     *
     * @return string
     */
    public function getTempUploadSessionId()
    {
        return $this->tempUploadSessionId;
    }
}

