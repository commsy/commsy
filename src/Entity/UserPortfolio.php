<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * UserPortfolio
 *
 * @ORM\Table(name="user_portfolio")
 * @ORM\Entity
 */
class UserPortfolio
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
     * @var string
     *
     * @ORM\Column(name="u_id", type="string", length=32)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     */
    private $uId;


}

