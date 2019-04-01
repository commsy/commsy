<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Noticed
 *
 * @ORM\Table(name="noticed")
 * @ORM\Entity
 */
class Noticed
{
    /**
     * @var integer
     *
     * @ORM\Column(name="item_id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     */
    private $itemId = '0';

    /**
     * @var integer
     *
     * @ORM\Column(name="version_id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     */
    private $versionId = '0';

    /**
     * @var integer
     *
     * @ORM\Column(name="user_id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     */
    private $userId = '0';

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="read_date", type="datetime")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     */
    private $readDate = '0000-00-00 00:00:00';


}

