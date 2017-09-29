<?php

namespace CommsyBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Jsvrcek\ICS\Model\Calendar;
use Nette\Utils\Strings;

/**
 * Invitations
 *
 * @ORM\Table(name="room_categories_links", indexes={@ORM\Index(name="id", columns={"id"})})
 * @ORM\Entity
 */
class RoomCategoriesLinks
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id = '0';

    /**
     * @var integer
     *
     * @ORM\Column(name="context_id", type="integer", nullable=false)
     */
    private $context_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="category_id", type="integer", nullable=false)
     */
    private $category_id;


    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set contextId
     *
     * @param integer $contextId
     *
     * @return RoomCategoriesLinks
     */
    public function setContextId($contextId)
    {
        $this->context_id = $contextId;

        return $this;
    }

    /**
     * Get contextId
     *
     * @return integer
     */
    public function getContextId()
    {
        return $this->context_id;
    }

    /**
     * Set categoryId
     *
     * @param integer $ccategoryId
     *
     * @return RoomcategoriesLinks
     */
    public function setCategoryId($categoryId)
    {
        $this->category_id = $categoryId;

        return $this;
    }

    /**
     * Get categoryId
     *
     * @return integer
     */
    public function getCategoryId()
    {
        return $this->category_id;
    }
}
