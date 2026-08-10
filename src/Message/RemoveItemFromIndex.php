<?php

declare(strict_types=1);

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

namespace App\Message;

/**
 * Carries only the item's identity; the document is addressed by id in its index.
 */
final readonly class RemoveItemFromIndex
{
    public function __construct(
        private int $itemId,
        private string $itemType,
    ) {
    }

    public function getItemId(): int
    {
        return $this->itemId;
    }

    public function getItemType(): string
    {
        return $this->itemType;
    }
}
