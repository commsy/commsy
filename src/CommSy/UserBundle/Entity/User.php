<?php

namespace CommSy\UserBundle\Entity;

use FOS\UserBundle\Model\User as BaseUser;
use Doctrine\ORM\Mapping as ORM;

/**
 * Class User.php
 *
 * CommSy user class
 *
 * @ORM\Entity
 * @ORM\Table(name="user")
 */
class User extends BaseUser
{
    /**
     * The user id
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * The users firstname
     * @var string
     *
     * @ORM\Column(type="string", length=255)
     */
    private $firstname;

    /**
     * The users lastname
     * @var string
     *
     * @ORM\Column(type="string", length=255)
     */
    private $lastname;

    /**
     * The users preferred language
     * @var string
     *
     * @ORM\Column(type="string", length=5)
     */
    private $language;

    public function __construct()
    {
        parent::__construct();
    }

    public function getFirstname()
    {
        return $this->firstname;
    }

    public function getLastname()
    {
        return $this->lastname;
    }

    public function getLanguage()
    {
        return $this->language;
    }

    public function setFirstname($firstname)
    {
        $this->firstname = $firstname;
        return $this;
    }

    public function setLastname($lastname)
    {
        $this->lastname = $lastname;
        return $this;
    }

    public function setLanguage($language)
    {
        $this->language = $language;
        return $this;
    }
}