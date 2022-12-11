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
 * LogArchive.
 */
#[ORM\Entity]
#[ORM\Table(name: 'log_archive')]
#[ORM\Index(name: 'ulogin', columns: ['ulogin'])]
#[ORM\Index(name: 'cid', columns: ['cid'])]
class LogArchive
{
    /**
     * @var int
     */
    #[ORM\Column(name: 'id', type: \Doctrine\DBAL\Types\Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private ?int $id = null;
    /**
     * @var string
     */
    #[ORM\Column(name: 'ip', type: \Doctrine\DBAL\Types\Types::STRING, length: 15, nullable: true)]
    private ?string $ip = null;
    /**
     * @var string
     */
    #[ORM\Column(name: 'agent', type: \Doctrine\DBAL\Types\Types::STRING, length: 250, nullable: true)]
    private ?string $agent = null;
    #[ORM\Column(name: 'timestamp', type: \Doctrine\DBAL\Types\Types::DATETIME_MUTABLE)]
    private \DateTime $timestamp;
    /**
     * @var string
     */
    #[ORM\Column(name: 'request', type: \Doctrine\DBAL\Types\Types::STRING, length: 250, nullable: true)]
    private ?string $request = null;
    /**
     * @var string
     */
    #[ORM\Column(name: 'post_content', type: \Doctrine\DBAL\Types\Types::TEXT, length: 16_777_215, nullable: true)]
    private ?string $postContent = null;
    /**
     * @var string
     */
    #[ORM\Column(name: 'method', type: \Doctrine\DBAL\Types\Types::STRING, length: 10, nullable: true)]
    private ?string $method = null;
    /**
     * @var int
     */
    #[ORM\Column(name: 'uid', type: \Doctrine\DBAL\Types\Types::INTEGER, nullable: true)]
    private ?int $uid = null;
    /**
     * @var string
     */
    #[ORM\Column(name: 'ulogin', type: \Doctrine\DBAL\Types\Types::STRING, length: 250, nullable: true)]
    private ?string $ulogin = null;
    /**
     * @var int
     */
    #[ORM\Column(name: 'cid', type: \Doctrine\DBAL\Types\Types::INTEGER, nullable: true)]
    private ?int $cid = null;
    /**
     * @var string
     */
    #[ORM\Column(name: 'module', type: \Doctrine\DBAL\Types\Types::STRING, length: 250, nullable: true)]
    private ?string $module = null;
    /**
     * @var string
     */
    #[ORM\Column(name: 'fct', type: \Doctrine\DBAL\Types\Types::STRING, length: 250, nullable: true)]
    private ?string $fct = null;
    /**
     * @var string
     */
    #[ORM\Column(name: 'param', type: \Doctrine\DBAL\Types\Types::STRING, length: 250, nullable: true)]
    private ?string $param = null;
    /**
     * @var int
     */
    #[ORM\Column(name: 'iid', type: \Doctrine\DBAL\Types\Types::INTEGER, nullable: true)]
    private ?int $iid = null;
    /**
     * @var int
     */
    #[ORM\Column(name: 'queries', type: \Doctrine\DBAL\Types\Types::SMALLINT, nullable: true)]
    private ?int $queries = null;
    /**
     * @var float
     */
    #[ORM\Column(name: 'time', type: \Doctrine\DBAL\Types\Types::FLOAT, precision: 10, nullable: true)]
    private ?float $time = null;

    public function __construct()
    {
        $this->timestamp = new \DateTime('CURRENT_TIMESTAMP');
    }
}
