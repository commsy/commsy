<?php

namespace CommsyBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * SearchWord
 *
 * @ORM\Table(name="search_word", uniqueConstraints={@ORM\UniqueConstraint(name="sw_word", columns={"sw_word"})})
 * @ORM\Entity
 */
class SearchWord
{
    /**
     * @var integer
     *
     * @ORM\Column(name="sw_id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $swId;

    /**
     * @var string
     *
     * @ORM\Column(name="sw_word", type="string", length=32, nullable=false)
     */
    private $swWord = '';


}

