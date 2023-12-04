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

use DateTimeImmutable;

/**
 * Class PortalUserChangeStatus.
 */
class PortalUserChangeStatus
{
    private ?string $name = null;

    private ?string $userID = null;

    private ?string $lastLogin = null;

    private ?string $currentStatus = null;

    private ?string $newStatus = null;

    private ?bool $contact = null;

    private ?bool $loginIsDeactivated = null;

    private ?DateTimeImmutable $impersonateExpiryDate = null;

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getUserID(): string
    {
        return $this->userID;
    }

    public function setUserID(string $userID): self
    {
        $this->userID = $userID;

        return $this;
    }

    public function getLastLogin(): string
    {
        return $this->lastLogin;
    }

    public function setLastLogin(string $lastLogin): self
    {
        $this->lastLogin = $lastLogin;

        return $this;
    }

    public function getCurrentStatus(): string
    {
        return $this->currentStatus;
    }

    public function setCurrentStatus(string $currentStatus): self
    {
        $this->currentStatus = $currentStatus;

        return $this;
    }

    public function getNewStatus(): string
    {
        return $this->newStatus;
    }

    public function setNewStatus(string $newStatus): self
    {
        $this->newStatus = $newStatus;

        return $this;
    }

    public function isContact(): bool
    {
        return $this->contact;
    }

    public function setContact(bool $contact): self
    {
        $this->contact = $contact;

        return $this;
    }
    public function getLoginIsDeactivated(): ?bool
    {
        return $this->loginIsDeactivated;
    }

    public function setLoginIsDeactivated(bool $loginIsDeactivated): self
    {
        $this->loginIsDeactivated = $loginIsDeactivated;

        return $this;
    }

    public function getImpersonateExpiryDate(): ?DateTimeImmutable
    {
        return $this->impersonateExpiryDate;
    }

    public function setImpersonateExpiryDate(?DateTimeImmutable $expiry): self
    {
        $this->impersonateExpiryDate = $expiry;

        return $this;
    }
}
