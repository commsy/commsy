<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * AnnotationPortfolio
 *
 * @ORM\Table(name="annotation_portfolio", indexes={@ORM\Index(name="row", columns={"row", "column"})})
 * @ORM\Entity
 */
class AnnotationPortfolio
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
     * @ORM\Column(name="a_id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     */
    private $aId = '0';

    /**
     * @var integer
     *
     * @ORM\Column(name="row", type="integer", nullable=false)
     */
    private $row = '0';

    /**
     * @var integer
     *
     * @ORM\Column(name="column", type="integer", nullable=false)
     */
    private $column = '0';


}

