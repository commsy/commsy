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

use App\Enum\EditableSection;
use cs_item;
use Symfony\Contracts\EventDispatcher\Event;

final class CommsyEditEvent extends Event
{
    final public const EDIT = 'commsy.edit';
    final public const SAVE = 'commsy.save';
    final public const CANCEL = 'commsy.cancel';

    public function __construct(
        private readonly cs_item $item,
        private readonly EditableSection $editableSection = EditableSection::UNKNOWN,
        private readonly array $extras = []
    ) {
    }

    public function getItem(): cs_item
    {
        return $this->item;
    }

    public function getEditableSection(): EditableSection
    {
        return $this->editableSection;
    }

    public function getExtras(): array
    {
        return $this->extras;
    }
}
