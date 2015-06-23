<?php

namespace CommsyBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * LogAds
 *
 * @ORM\Table(name="log_ads", indexes={@ORM\Index(name="cid", columns={"cid"}), @ORM\Index(name="timestamp", columns={"timestamp"})})
 * @ORM\Entity
 */
class LogAds
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
     * @var integer
     *
     * @ORM\Column(name="cid", type="integer", nullable=true)
     */
    private $cid;

    /**
     * @var string
     *
     * @ORM\Column(name="aim", type="string", length=255, nullable=false)
     */
    private $aim;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="timestamp", type="datetime", nullable=false)
     */
    private $timestamp = 'CURRENT_TIMESTAMP';


}

