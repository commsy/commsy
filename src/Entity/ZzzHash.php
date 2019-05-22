<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ZzzHash
 *
 * @ORM\Table(name="zzz_hash", indexes={@ORM\Index(name="rss", columns={"rss"}), @ORM\Index(name="ical", columns={"ical"})})
 * @ORM\Entity
 */
class ZzzHash
{
    /**
     * @var integer
     *
     * @ORM\Column(name="user_item_id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $userItemId;

    /**
     * @var string
     *
     * @ORM\Column(name="rss", type="string", length=32, nullable=true)
     */
    private $rss;

    /**
     * @var string
     *
     * @ORM\Column(name="ical", type="string", length=32, nullable=true)
     */
    private $ical;


}

