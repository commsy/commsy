<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * External2commsyId
 *
 * @ORM\Table(name="external2commsy_id")
 * @ORM\Entity
 */
class External2commsyId
{
    /**
     * @var string
     *
     * @ORM\Column(name="external_id", type="string", length=255)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     */
    private $externalId;

    /**
     * @var string
     *
     * @ORM\Column(name="source_system", type="string", length=60)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     */
    private $sourceSystem;

    /**
     * @var integer
     *
     * @ORM\Column(name="commsy_id", type="integer", nullable=false)
     */
    private $commsyId;


}

