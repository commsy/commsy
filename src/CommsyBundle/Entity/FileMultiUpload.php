<?php

namespace CommsyBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * FileMultiUpload
 *
 * @ORM\Table(name="file_multi_upload")
 * @ORM\Entity
 */
class FileMultiUpload
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="session_id", type="string", length=150, nullable=false)
     */
    private $sessionId;

    /**
     * @var string
     *
     * @ORM\Column(name="file_array", type="text", length=65535, nullable=false)
     */
    private $fileArray;

    /**
     * @var integer
     *
     * @ORM\Column(name="cid", type="integer", nullable=true)
     */
    private $cid;


}

