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
 * Invitations.
 */
#[ORM\Entity]
#[ORM\Table(name: 'room_categories_links')]
#[ORM\Index(name: 'id', columns: ['id'])]
class RoomCategoriesLinks
{
    /**
     * @var int
     */
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private $id = '0';

    /**
     * @var int
     */
    #[ORM\Column(name: 'context_id', type: 'integer', nullable: false)]
    private $context_id;

    /**
     * @var mixed|null
     */
    #[ORM\Column(name: 'category_id', type: 'integer', nullable: false)]
    private $category_id;

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set contextId.
     *
     * @param int $contextId
     *
     * @return RoomCategoriesLinks
     */
    public function setContextId($contextId)
    {
        $this->context_id = $contextId;

        return $this;
    }

    /**
     * Get contextId.
     *
     * @return int
     */
    public function getContextId()
    {
        return $this->context_id;
    }

    /**
     * Set categoryId.
     *
     * @param int $ccategoryId
     *
     * @return RoomcategoriesLinks
     */
    public function setCategoryId($categoryId)
    {
        $this->category_id = $categoryId;

        return $this;
    }

    /**
     * Get categoryId.
     *
     * @return int
     */
    public function getCategoryId()
    {
        return $this->category_id;
    }
}
