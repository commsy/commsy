<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * LogArchive
 *
 * @ORM\Table(name="log_archive", indexes={@ORM\Index(name="ulogin", columns={"ulogin"}), @ORM\Index(name="cid", columns={"cid"})})
 * @ORM\Entity
 */
class LogArchive
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
     * @ORM\Column(name="ip", type="string", length=15, nullable=true)
     */
    private $ip;

    /**
     * @var string
     *
     * @ORM\Column(name="agent", type="string", length=250, nullable=true)
     */
    private $agent;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="timestamp", type="datetime", nullable=false)
     */
    private $timestamp = 'CURRENT_TIMESTAMP';

    /**
     * @var string
     *
     * @ORM\Column(name="request", type="string", length=250, nullable=true)
     */
    private $request;

    /**
     * @var string
     *
     * @ORM\Column(name="post_content", type="text", length=16777215, nullable=true)
     */
    private $postContent;

    /**
     * @var string
     *
     * @ORM\Column(name="method", type="string", length=10, nullable=true)
     */
    private $method;

    /**
     * @var integer
     *
     * @ORM\Column(name="uid", type="integer", nullable=true)
     */
    private $uid;

    /**
     * @var string
     *
     * @ORM\Column(name="ulogin", type="string", length=250, nullable=true)
     */
    private $ulogin;

    /**
     * @var integer
     *
     * @ORM\Column(name="cid", type="integer", nullable=true)
     */
    private $cid;

    /**
     * @var string
     *
     * @ORM\Column(name="module", type="string", length=250, nullable=true)
     */
    private $module;

    /**
     * @var string
     *
     * @ORM\Column(name="fct", type="string", length=250, nullable=true)
     */
    private $fct;

    /**
     * @var string
     *
     * @ORM\Column(name="param", type="string", length=250, nullable=true)
     */
    private $param;

    /**
     * @var integer
     *
     * @ORM\Column(name="iid", type="integer", nullable=true)
     */
    private $iid;

    /**
     * @var integer
     *
     * @ORM\Column(name="queries", type="smallint", nullable=true)
     */
    private $queries;

    /**
     * @var float
     *
     * @ORM\Column(name="time", type="float", precision=10, scale=0, nullable=true)
     */
    private $time;


}

