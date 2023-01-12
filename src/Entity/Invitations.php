<?php

/*
 * This file is part of CommSy.
 *
 * (c) Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
 * Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
 * Edouard Simon, Monique Strauss, Jose Mauel Gonzalez Vazquez, Johannes Schultze
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace App\Entity;

use DateTime;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Invitations.
 */
#[ORM\Entity]
#[ORM\Table(name: 'invitations')]
class Invitations
{
    /**
     * @var int
     */
    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private ?int $id = null;
    /**
     * @var string
     */
    #[ORM\Column(name: 'hash', type: Types::STRING)]
    private ?string $hash = null;
    /**
     * @var string
     */
    #[ORM\Column(name: 'email', type: Types::STRING)]
    private ?string $email = null;
    /**
     * @var int
     */
    #[ORM\Column(name: 'authsource_id', type: Types::INTEGER)]
    private ?int $authSourceId = null;
    /**
     * @var int
     */
    #[ORM\Column(name: 'context_id', type: Types::INTEGER)]
    private ?int $contextId = null;
    #[ORM\Column(name: 'creation_date', type: Types::DATETIME_MUTABLE)]
    private DateTime $creationDate;
    #[ORM\Column(name: 'expiration_date', type: Types::DATETIME_MUTABLE, nullable: true)]
    private DateTime $expirationDate;

    public function __construct()
    {
        $this->creationDate = new DateTime('0000-00-00 00:00:00');
        $this->expirationDate = new DateTime('0000-00-00 00:00:00');
    }

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set hash.
     *
     * @param string $hash
     *
     * @return Invitations
     */
    public function setHash($hash)
    {
        $this->hash = $hash;

        return $this;
    }

    /**
     * Get hash.
     *
     * @return string
     */
    public function getHash()
    {
        return $this->hash;
    }

    /**
     * Set email.
     *
     * @param string $email
     *
     * @return Invitations
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Get email.
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Set authSourceId.
     *
     * @param int $authSourceId
     *
     * @return Invitations
     */
    public function setAuthSourceId($authSourceId)
    {
        $this->authSourceId = $authSourceId;

        return $this;
    }

    /**
     * Get authSourceId.
     *
     * @return int
     */
    public function getAuthSourceId()
    {
        return $this->authSourceId;
    }

    /**
     * Set contextId.
     *
     * @param int $contextId
     *
     * @return Invitations
     */
    public function setContextId($contextId)
    {
        $this->contextId = $contextId;

        return $this;
    }

    /**
     * Get contextId.
     *
     * @return int
     */
    public function getContextId()
    {
        return $this->contextId;
    }

    /**
     * Set creationDate.
     *
     * @param DateTime $creationDate
     *
     * @return Invitations
     */
    public function setCreationDate($creationDate)
    {
        $this->creationDate = $creationDate;

        return $this;
    }

    /**
     * Get creationDate.
     *
     * @return DateTime
     */
    public function getCreationDate()
    {
        return $this->creationDate;
    }

    /**
     * Set expirationDate.
     *
     * @param DateTime $expirationDate
     *
     * @return Invitations
     */
    public function setExpirationDate($expirationDate)
    {
        $this->expirationDate = $expirationDate;

        return $this;
    }

    /**
     * Get expirationDate.
     *
     * @return DateTime
     */
    public function getExpirationDate()
    {
        return $this->expirationDate;
    }
}
