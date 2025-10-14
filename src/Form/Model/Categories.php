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

namespace App\Form\Model;

final class Categories
{
    private ?array $categories;

    private ?string $newCategory = null;

    public function getCategories(): ?array
    {
        return $this->categories;
    }

    public function setCategories(?array $categories): self
    {
        $this->categories = $categories;
        return $this;
    }

    public function getNewCategory(): ?string
    {
        return $this->newCategory;
    }

    public function setNewCategory(?string $newCategory): Categories
    {
        $this->newCategory = $newCategory;
        return $this;
    }
}
