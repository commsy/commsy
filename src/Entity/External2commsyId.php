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
 * External2commsyId.
 */
#[ORM\Entity]
#[ORM\Table(name: 'external2commsy_id')]
class External2commsyId
{
    /**
     * @var string
     */
    #[ORM\Column(name: 'external_id', type: \Doctrine\DBAL\Types\Types::STRING, length: 255)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    private ?string $externalId = null;

    /**
     * @var string
     */
    #[ORM\Column(name: 'source_system', type: \Doctrine\DBAL\Types\Types::STRING, length: 60)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    private ?string $sourceSystem = null;

    /**
     * @var int
     */
    #[ORM\Column(name: 'commsy_id', type: \Doctrine\DBAL\Types\Types::INTEGER)]
    private ?int $commsyId = null;
}
