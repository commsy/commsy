<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Auth
 *
 * @ORM\Table(name="auth")
 * @ORM\Entity
 */
class Auth
{
    /**
     * @var integer
     *
     * @ORM\Column(name="commsy_id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     */
    private $commsyId = '0';

    /**
     * @var string
     *
     * @ORM\Column(name="user_id", type="string", length=100)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     */
    private $userId;

    /**
     * @var string
     *
     * @ORM\Column(name="password_md5", type="string", length=32, nullable=false)
     */
    private $passwordMd5;

    /**
     * @var string
     *
     * @ORM\Column(name="firstname", type="string", length=50, nullable=false)
     */
    private $firstname;

    /**
     * @var string
     *
     * @ORM\Column(name="lastname", type="string", length=100, nullable=false)
     */
    private $lastname;

    /**
     * @var string
     *
     * @ORM\Column(name="email", type="string", length=100, nullable=false)
     */
    private $email;

    /**
     * @var string
     *
     * @ORM\Column(name="language", type="string", length=10, nullable=false)
     */
    private $language;


}

