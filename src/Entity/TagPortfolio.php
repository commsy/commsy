<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * TagPortfolio
 *
 * @ORM\Table(name="tag_portfolio", indexes={@ORM\Index(name="row", columns={"row", "column"})})
 * @ORM\Entity
 */
class TagPortfolio
{
    /**
     * @var integer
     *
     * @ORM\Column(name="p_id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     */
    private $pId = '0';

    /**
     * @var integer
     *
     * @ORM\Column(name="t_id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     */
    private $tId = '0';

    /**
     * @var integer
     *
     * @ORM\Column(name="row", type="integer", nullable=true)
     */
    private $row = '0';

    /**
     * @var integer
     *
     * @ORM\Column(name="column", type="integer", nullable=true)
     */
    private $column = '0';

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="text", length=65535, nullable=true)
     */
    private $description;


}

