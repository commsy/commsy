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

namespace App\Mail\Messages;

use App\Mail\Message;
use cs_item;
use cs_user_item;

class ItemDeletedMessage extends Message
{
    private $item;
    private $deleter;

    public function __construct(cs_item $item, cs_user_item $deleter)
    {
        $this->item = $item;
        $this->deleter = $deleter;
    }

    public function getSubject(): string
    {
        return 'Deleted entry (%room_name%)';
    }

    public function getTemplateName(): string
    {
        return 'mail/item_deleted.html.twig';
    }

    public function getParameters(): array
    {
        return [
            'item' => $this->item,
            'deleter' => $this->deleter,
        ];
    }

    public function getTranslationParameters(): array
    {
        return [
            '%room_name%' => $this->item->getContextItem()->getTitle(),
        ];
    }
}
