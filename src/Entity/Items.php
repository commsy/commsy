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
 * Items.
 */
#[ORM\Entity(repositoryClass: \App\Repository\ItemRepository::class)]
#[ORM\InheritanceType('JOINED')]
#[ORM\DiscriminatorColumn(name: 'type', type: 'string')]
#[ORM\DiscriminatorMap(['annotation' => 'Annotations', 'announcement' => 'Announcement', 'assessments' => 'Assessments', 'auth_source' => 'AuthSource', 'community' => 'Room', 'date' => 'Dates', 'discarticle' => 'Discussionarticles', 'discussion' => 'Discussions', 'grouproom' => 'Room', 'label' => 'Labels', 'link_item' => 'LinkItems', 'material' => 'Materials', 'portal' => 'Portal', 'portfolio' => 'Portfolio', 'privateroom' => 'RoomPrivat', 'project' => 'Room', 'section' => 'Section', 'server' => 'Server', 'step' => 'Step', 'tag' => 'Tag', 'task' => 'Tasks', 'todo' => 'Todos', 'user' => 'User'])]
#[ORM\Table(name: 'items')]
#[ORM\Index(name: 'context_id', columns: ['context_id'])]
#[ORM\Index(name: 'type', columns: ['type'])]
abstract class Items
{
    /**
     * @var int
     */
    #[ORM\Column(name: 'item_id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private $itemId;

    /**
     * @var int
     */
    #[ORM\Column(name: 'context_id', type: 'integer', nullable: true)]
    private $contextId;

    /**
     * @var int
     */
    #[ORM\Column(name: 'deleter_id', type: 'integer', nullable: true)]
    private $deleterId;

    /**
     * @var \DateTime
     */
    #[ORM\Column(name: 'deletion_date', type: 'datetime', nullable: true)]
    private $deletionDate;

    /**
     * @var \DateTime
     */
    #[ORM\Column(name: 'modification_date', type: 'datetime', nullable: true)]
    private $modificationDate;

    #[ORM\Column(name: 'activation_date', type: 'datetime')]
    private ?\DateTime $activationDate = null;
}
