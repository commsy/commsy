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

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * WorkflowRead.
 */
#[ORM\Entity]
#[ORM\Table(name: 'workflow_read')]
#[ORM\Index(columns: ['item_id'], name: 'item_id')]
#[ORM\Index(columns: ['user_id'], name: 'user_id')]
class WorkflowRead
{
    #[ORM\Column(name: 'item_id', type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    private int $itemId;

    #[ORM\Column(name: 'user_id', type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    private int $userId;
}
