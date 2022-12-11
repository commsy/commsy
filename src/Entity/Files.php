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

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Files.
 */
#[ORM\Entity(repositoryClass: \App\Repository\FilesRepository::class)]
#[ORM\Table(name: 'files')]
#[ORM\Index(name: 'context_id', columns: ['context_id'])]
#[ORM\Index(name: 'creator_id', columns: ['creator_id'])]
class Files
{
    /**
     * @var int
     */
    #[ORM\Column(name: 'files_id', type: \Doctrine\DBAL\Types\Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private ?int $filesId = null;
    /**
     * @var int
     */
    #[ORM\Column(name: 'context_id', type: \Doctrine\DBAL\Types\Types::INTEGER)]
    private ?int $contextId = null;
    /**
     * @var int
     */
    #[ORM\Column(name: 'creator_id', type: \Doctrine\DBAL\Types\Types::INTEGER)]
    private ?int $creatorId = 0;
    /**
     * @var int
     */
    #[ORM\Column(name: 'deleter_id', type: \Doctrine\DBAL\Types\Types::INTEGER, nullable: true)]
    private ?int $deleterId = null;
    #[ORM\Column(name: 'creation_date', type: \Doctrine\DBAL\Types\Types::DATETIME_MUTABLE)]
    private \DateTime $creationDate;
    /**
     * @var \DateTimeInterface
     */
    #[ORM\Column(name: 'modification_date', type: \Doctrine\DBAL\Types\Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $modificationDate = null;
    /**
     * @var \DateTimeInterface
     */
    #[ORM\Column(name: 'deletion_date', type: \Doctrine\DBAL\Types\Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $deletionDate = null;
    /**
     * @var string
     */
    #[ORM\Column(name: 'filename', type: \Doctrine\DBAL\Types\Types::STRING, length: 255)]
    private ?string $filename = null;
    /**
     * @var string
     */
    #[ORM\Column(name: 'filepath', type: \Doctrine\DBAL\Types\Types::STRING, length: 255)]
    private ?string $filepath = null;
    /**
     * @var int
     */
    #[ORM\Column(name: 'size', type: \Doctrine\DBAL\Types\Types::INTEGER, nullable: true)]
    private ?int $size = null;
    /**
     * @var string
     */
    #[ORM\Column(name: 'has_html', type: \Doctrine\DBAL\Types\Types::STRING)]
    private ?string $hasHtml = '0';
    /**
     * @var bool
     */
    #[ORM\Column(name: 'scan', type: \Doctrine\DBAL\Types\Types::BOOLEAN)]
    private ?bool $scan = false;
    /**
     * @var string
     */
    #[ORM\Column(name: 'extras', type: \Doctrine\DBAL\Types\Types::TEXT, length: 16_777_215, nullable: true)]
    private ?string $extras = null;
    /**
     * @var string
     */
    #[ORM\Column(name: 'temp_upload_session_id', type: \Doctrine\DBAL\Types\Types::STRING, length: 255, nullable: true)]
    private ?string $tempUploadSessionId = null;

    public function __construct()
    {
        $this->creationDate = new \DateTime('0000-00-00 00:00:00');
    }

    /**
     * Get file content base64 encoded.
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
     * Get filesId.
     *
     * @return int
     */
    public function getFilesId()
    {
        return $this->filesId;
    }

    /**
     * Set contextId.
     *
     * @param int $contextId
     *
     * @return Files
     */
    public function setContextId($contextId)
    {
        $this->contextId = $contextId;

        return $this;
    }

    /**
     * Get contextId.
     *
     * @return int
     */
    public function getContextId()
    {
        return $this->contextId;
    }

    /**
     * Set creatorId.
     *
     * @param int $creatorId
     *
     * @return Files
     */
    public function setCreatorId($creatorId)
    {
        $this->creatorId = $creatorId;

        return $this;
    }

    /**
     * Get creatorId.
     *
     * @return int
     */
    public function getCreatorId()
    {
        return $this->creatorId;
    }

    /**
     * Set deleterId.
     *
     * @param int $deleterId
     *
     * @return Files
     */
    public function setDeleterId($deleterId)
    {
        $this->deleterId = $deleterId;

        return $this;
    }

    /**
     * Get deleterId.
     *
     * @return int
     */
    public function getDeleterId()
    {
        return $this->deleterId;
    }

    /**
     * Set creationDate.
     *
     * @param \DateTime $creationDate
     *
     * @return Files
     */
    public function setCreationDate($creationDate)
    {
        $this->creationDate = $creationDate;

        return $this;
    }

    /**
     * Get creationDate.
     *
     * @return \DateTime
     */
    public function getCreationDate()
    {
        return $this->creationDate;
    }

    /**
     * Set modificationDate.
     *
     * @param \DateTime $modificationDate
     *
     * @return Files
     */
    public function setModificationDate($modificationDate)
    {
        $this->modificationDate = $modificationDate;

        return $this;
    }

    /**
     * Get modificationDate.
     *
     * @return \DateTime
     */
    public function getModificationDate()
    {
        return $this->modificationDate;
    }

    /**
     * Set deletionDate.
     *
     * @param \DateTime $deletionDate
     *
     * @return Files
     */
    public function setDeletionDate($deletionDate)
    {
        $this->deletionDate = $deletionDate;

        return $this;
    }

    /**
     * Get deletionDate.
     *
     * @return \DateTime
     */
    public function getDeletionDate()
    {
        return $this->deletionDate;
    }

    /**
     * Set filename.
     *
     * @param string $filename
     *
     * @return Files
     */
    public function setFilename($filename)
    {
        $this->filename = $filename;

        return $this;
    }

    /**
     * Get filename.
     *
     * @return string
     */
    public function getFilename()
    {
        return $this->filename;
    }

    /**
     * Set filepath.
     *
     * @param string $filepath
     *
     * @return Files
     */
    public function setFilepath($filepath)
    {
        $this->filepath = $filepath;

        return $this;
    }

    /**
     * Get filepath.
     *
     * @return string
     */
    public function getFilepath()
    {
        return $this->filepath;
    }

    /**
     * Set size.
     *
     * @param int $size
     *
     * @return Files
     */
    public function setSize($size)
    {
        $this->size = $size;

        return $this;
    }

    /**
     * Get size.
     *
     * @return int
     */
    public function getSize()
    {
        return $this->size;
    }

    /**
     * Set hasHtml.
     *
     * @param string $hasHtml
     *
     * @return Files
     */
    public function setHasHtml($hasHtml)
    {
        $this->hasHtml = $hasHtml;

        return $this;
    }

    /**
     * Get hasHtml.
     *
     * @return string
     */
    public function getHasHtml()
    {
        return $this->hasHtml;
    }

    /**
     * Set scan.
     *
     * @param bool $scan
     *
     * @return Files
     */
    public function setScan($scan)
    {
        $this->scan = $scan;

        return $this;
    }

    /**
     * Get scan.
     *
     * @return bool
     */
    public function getScan()
    {
        return $this->scan;
    }

    /**
     * Set extras.
     *
     * @param string $extras
     *
     * @return Files
     */
    public function setExtras($extras)
    {
        $this->extras = $extras;

        return $this;
    }

    /**
     * Get extras.
     *
     * @return string
     */
    public function getExtras()
    {
        return $this->extras;
    }

    /**
     * Set tempUploadSessionId.
     *
     * @param string $tempUploadSessionId
     *
     * @return Files
     */
    public function setTempUploadSessionId($tempUploadSessionId)
    {
        $this->tempUploadSessionId = $tempUploadSessionId;

        return $this;
    }

    /**
     * Get tempUploadSessionId.
     *
     * @return string
     */
    public function getTempUploadSessionId()
    {
        return $this->tempUploadSessionId;
    }
}
