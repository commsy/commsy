<?php

namespace CommsyBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * SearchTime
 *
 * @ORM\Table(name="search_time", uniqueConstraints={@ORM\UniqueConstraint(name="st_item_version_id", columns={"st_item_id", "st_version_id"})})
 * @ORM\Entity
 */
class SearchTime
{
    /**
     * @var integer
     *
     * @ORM\Column(name="st_id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $stId;

    /**
     * @var integer
     *
     * @ORM\Column(name="st_item_id", type="integer", nullable=false)
     */
    private $stItemId = '0';

    /**
     * @var integer
     *
     * @ORM\Column(name="st_version_id", type="integer", nullable=true)
     */
    private $stVersionId;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="st_date", type="datetime", nullable=false)
     */
    private $stDate = '0000-00-00 00:00:00';


}

