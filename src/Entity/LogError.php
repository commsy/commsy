<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * LogError
 *
 * @ORM\Table(name="log_error")
 * @ORM\Entity
 */
class LogError
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
     * @var \DateTime
     *
     * @ORM\Column(name="datetime", type="datetime", nullable=false)
     */
    private $datetime;

    /**
     * @var integer
     *
     * @ORM\Column(name="number", type="integer", nullable=false)
     */
    private $number;

    /**
     * @var string
     *
     * @ORM\Column(name="type", type="string", length=255, nullable=false)
     */
    private $type;

    /**
     * @var string
     *
     * @ORM\Column(name="message", type="text", length=16777215, nullable=false)
     */
    private $message;

    /**
     * @var string
     *
     * @ORM\Column(name="file", type="string", length=255, nullable=false)
     */
    private $file;

    /**
     * @var integer
     *
     * @ORM\Column(name="line", type="integer", nullable=false)
     */
    private $line;

    /**
     * @var integer
     *
     * @ORM\Column(name="context", type="integer", nullable=false)
     */
    private $context;

    /**
     * @var string
     *
     * @ORM\Column(name="module", type="string", length=255, nullable=false)
     */
    private $module;

    /**
     * @var string
     *
     * @ORM\Column(name="function", type="string", length=255, nullable=false)
     */
    private $function;

    /**
     * @var string
     *
     * @ORM\Column(name="user", type="string", length=255, nullable=false)
     */
    private $user;

    /**
     * @var string
     *
     * @ORM\Column(name="url", type="string", length=255, nullable=true)
     */
    private $url;

    /**
     * @var string
     *
     * @ORM\Column(name="referer", type="string", length=255, nullable=true)
     */
    private $referer;


}

