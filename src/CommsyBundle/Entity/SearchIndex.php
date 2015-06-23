<?php

namespace CommsyBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * SearchIndex
 *
 * @ORM\Table(name="search_index", indexes={@ORM\Index(name="si_item_id", columns={"si_item_id"}), @ORM\Index(name="si_sw_id", columns={"si_sw_id"})})
 * @ORM\Entity
 */
class SearchIndex
{
    /**
     * @var integer
     *
     * @ORM\Column(name="si_id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $siId;

    /**
     * @var integer
     *
     * @ORM\Column(name="si_sw_id", type="integer", nullable=false)
     */
    private $siSwId = '0';

    /**
     * @var integer
     *
     * @ORM\Column(name="si_item_id", type="integer", nullable=false)
     */
    private $siItemId = '0';

    /**
     * @var string
     *
     * @ORM\Column(name="si_item_type", type="string", length=15, nullable=false)
     */
    private $siItemType;

    /**
     * @var integer
     *
     * @ORM\Column(name="si_count", type="smallint", nullable=false)
     */
    private $siCount = '0';


}

