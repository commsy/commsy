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

use cs_item;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Dispatched when an item is annotated. Carries both the annotated entry (the
 * parent) and the annotation itself, so a listener can attribute the event to
 * the annotator and snapshot the parent — without a legacy lookup. Kept separate
 * from CommsyEditEvent so hooking it has no side effects on other save listeners.
 */
class ItemAnnotatedEvent extends Event
{
    final public const NAME = 'item.annotated';

    public function __construct(
        private readonly cs_item $item,
        private readonly cs_item $annotation,
    ) {
    }

    /** The annotated entry (the parent item). */
    public function getItem(): cs_item
    {
        return $this->item;
    }

    /** The annotation that was created. */
    public function getAnnotation(): cs_item
    {
        return $this->annotation;
    }
}
