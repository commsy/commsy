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

use App\Utils\EntityDatesTrait;
use App\Utils\EntityUsersTrait;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'tag2tag')]
#[ORM\Index(columns: ['from_item_id'], name: 'from_item_id')]
#[ORM\Index(name: 'context_id', columns: ['context_id'])]
#[ORM\Index(name: 'deletion_date', columns: ['deletion_date'])]
#[ORM\Index(name: 'deleter_id', columns: ['deleter_id'])]
class Tag2tag
{
    use EntityDatesTrait;
    use EntityUsersTrait;

    #[ORM\Column(name: 'link_id', type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private ?int $linkId = null;

    #[ORM\Column(name: 'from_item_id', type: Types::INTEGER)]
    private int $fromItemId;

    #[ORM\Column(name: 'to_item_id', type: Types::INTEGER)]
    private int $toItemId;

    #[ORM\Column(name: 'context_id', type: Types::INTEGER)]
    private int $contextId;

    #[ORM\Column(name: 'sorting_place', type: Types::BOOLEAN, nullable: true)]
    private ?bool $sortingPlace = null;
}
