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

use App\Repository\TasksRepository;
use App\Utils\EntityDatesTrait;
use Doctrine\ORM\Mapping as ORM;

/**
 * Tasks.
 */
#[ORM\Entity(repositoryClass: TasksRepository::class)]
#[ORM\Table(name: 'tasks')]
#[ORM\Index(name: 'context_id', columns: ['context_id'])]
#[ORM\Index(name: 'creator_id', columns: ['creator_id'])]
class Tasks
{
    use EntityDatesTrait;

    #[ORM\Column(name: 'item_id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private int $itemId;

    #[ORM\Column(name: 'context_id', type: 'integer', nullable: true)]
    private $contextId;

    #[ORM\Column(name: 'creator_id', type: 'integer', nullable: false)]
    private string $creatorId = '0';

    #[ORM\Column(name: 'deleter_id', type: 'integer', nullable: true)]
    private $deleterId;

    #[ORM\Column(name: 'title', type: 'string', length: 255, nullable: false)]
    private $title;

    #[ORM\Column(name: 'status', type: 'string', length: 20, nullable: false)]
    private $status;

    #[ORM\Column(name: 'linked_item_id', type: 'integer', nullable: false)]
    private string $linkedItemId = '0';
}
