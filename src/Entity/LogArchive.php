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

use DateTime;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * LogArchive.
 */
#[ORM\Entity]
#[ORM\Table(name: 'log_archive')]
#[ORM\Index(columns: ['ulogin'], name: 'ulogin')]
#[ORM\Index(columns: ['cid'], name: 'cid')]
class LogArchive
{

    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private ?int $id = null;

    #[ORM\Column(name: 'ip', type: Types::STRING, length: 15, nullable: true)]
    private ?string $ip = null;

    #[ORM\Column(name: 'agent', type: Types::STRING, length: 250, nullable: true)]
    private ?string $agent = null;

    #[ORM\Column(name: 'timestamp', type: Types::DATETIME_MUTABLE)]
    private DateTime $timestamp;

    #[ORM\Column(name: 'request', type: Types::STRING, length: 250, nullable: true)]
    private ?string $request = null;

    #[ORM\Column(name: 'post_content', type: Types::TEXT, length: 16_777_215, nullable: true)]
    private ?string $postContent = null;

    #[ORM\Column(name: 'method', type: Types::STRING, length: 10, nullable: true)]
    private ?string $method = null;

    #[ORM\Column(name: 'uid', type: Types::INTEGER, nullable: true)]
    private ?int $uid = null;

    #[ORM\Column(name: 'ulogin', type: Types::STRING, length: 250, nullable: true)]
    private ?string $ulogin = null;

    #[ORM\Column(name: 'cid', type: Types::INTEGER, nullable: true)]
    private ?int $cid = null;

    #[ORM\Column(name: 'module', type: Types::STRING, length: 250, nullable: true)]
    private ?string $module = null;

    #[ORM\Column(name: 'fct', type: Types::STRING, length: 250, nullable: true)]
    private ?string $fct = null;

    #[ORM\Column(name: 'param', type: Types::STRING, length: 250, nullable: true)]
    private ?string $param = null;

    #[ORM\Column(name: 'iid', type: Types::INTEGER, nullable: true)]
    private ?int $iid = null;

    #[ORM\Column(name: 'queries', type: Types::SMALLINT, nullable: true)]
    private ?int $queries = null;

    #[ORM\Column(name: 'time', type: Types::FLOAT, precision: 10, nullable: true)]
    private ?float $time = null;

    public function __construct()
    {
        $this->timestamp = new DateTime('CURRENT_TIMESTAMP');
    }
}
