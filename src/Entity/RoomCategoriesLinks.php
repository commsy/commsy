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

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Invitations.
 */
#[ORM\Entity]
#[ORM\Table(name: 'room_categories_links')]
#[ORM\Index(columns: ['id'], name: 'id')]
class RoomCategoriesLinks
{
    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private int $id;

    #[ORM\Column(name: 'context_id', type: Types::INTEGER, nullable: false)]
    private int $context_id;

    #[ORM\Column(name: 'category_id', type: Types::INTEGER, nullable: false)]
    private int $category_id;

    public function getId(): int
    {
        return $this->id;
    }

    public function setContextId(int $contextId): static
    {
        $this->context_id = $contextId;

        return $this;
    }

    public function getContextId(): int
    {
        return $this->context_id;
    }

    public function setCategoryId(int $categoryId): static
    {
        $this->category_id = $categoryId;

        return $this;
    }

    public function getCategoryId(): int
    {
        return $this->category_id;
    }
}
