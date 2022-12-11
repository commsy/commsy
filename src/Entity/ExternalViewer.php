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
 * ExternalViewer.
 */
#[ORM\Entity]
#[ORM\Table(name: 'external_viewer')]
#[ORM\Index(name: 'item_id', columns: ['item_id', 'user_id'])]
class ExternalViewer
{
    /**
     * @var int
     */
    #[ORM\Column(name: 'item_id', type: \Doctrine\DBAL\Types\Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    private ?int $itemId = null;

    /**
     * @var string
     */
    #[ORM\Column(name: 'user_id', type: \Doctrine\DBAL\Types\Types::STRING, length: 32)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    private ?string $userId = null;
}
