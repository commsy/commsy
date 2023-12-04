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
    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private int $id;

    #[ORM\Column(name: 'hash', type: Types::STRING)]
    private ?string $hash = null;

    #[ORM\Column(name: 'email', type: Types::STRING)]
    private ?string $email = null;

    #[ORM\Column(name: 'authsource_id', type: Types::INTEGER)]
    private ?int $authSourceId = null;

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

    public function getId(): int
    {
        return $this->id;
    }

    public function setHash(?string $hash): static
    {
        $this->hash = $hash;

        return $this;
    }

    public function getHash(): ?string
    {
        return $this->hash;
    }

    public function setEmail(?string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setAuthSourceId(?int $authSourceId): static
    {
        $this->authSourceId = $authSourceId;

        return $this;
    }

    public function getAuthSourceId(): ?int
    {
        return $this->authSourceId;
    }

    public function setContextId(?int $contextId): static
    {
        $this->contextId = $contextId;

        return $this;
    }

    public function getContextId(): ?int
    {
        return $this->contextId;
    }

    public function setCreationDate(DateTime $creationDate): static
    {
        $this->creationDate = $creationDate;

        return $this;
    }

    public function getCreationDate(): DateTime
    {
        return $this->creationDate;
    }

    public function setExpirationDate(DateTime $expirationDate): static
    {
        $this->expirationDate = $expirationDate;

        return $this;
    }

    public function getExpirationDate(): DateTime
    {
        return $this->expirationDate;
    }
}
