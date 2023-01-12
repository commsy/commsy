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

namespace App\Event;

use Symfony\Contracts\EventDispatcher\Event;

class CommsyEditEvent extends Event
{
    public const EDIT = 'commsy.edit';
    public const SAVE = 'commsy.save';
    public const CANCEL = 'commsy.cancel';

    public function __construct(protected $item)
    {
    }

    public function getItem()
    {
        return $this->item;
    }
}
