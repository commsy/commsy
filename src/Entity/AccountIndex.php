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

class AccountIndex
{
    private ?string $indexViewAction;

    private array $ids = [];

    public function getIndexViewAction(): ?string
    {
        return $this->indexViewAction;
    }

    public function setIndexViewAction(string $indexViewAction): self
    {
        $this->indexViewAction = $indexViewAction;
        return $this;
    }

    public function getIds(): array
    {
        return $this->ids;
    }

    public function setIds(mixed $ids): self
    {
        $this->ids = $ids;
        return $this;
    }
}
