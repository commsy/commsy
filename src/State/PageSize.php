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

declare(strict_types=1);

namespace App\State;

use Symfony\Component\DependencyInjection\Attribute\Exclude;

/**
 * How long a page of a list on the interface is, for the lists our own
 * providers serve. One place, so rooms and rubrics cannot drift apart.
 */
#[Exclude]
final class PageSize
{
    /**
     * A page unless the client asks for another size.
     */
    public const DEFAULT = 30;

    /**
     * The most a client may ask for at once.
     */
    public const MAXIMUM = 100;
}
