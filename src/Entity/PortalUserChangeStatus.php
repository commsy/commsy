<?php


namespace App\Entity;

use DateTimeImmutable;

/**
 * Class PortalUserChangeStatus
 * @package App\Entity
 */
class PortalUserChangeStatus
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $userID;

    /**
     * @var string
     */
    private $lastLogin;

    /**
     * @var string
     */
    private $currentStatus;

    /**
     * @var string
     */
    private $newStatus;

    /**
     * @var bool
     */
    private $contact;

    /**
     * @var bool
     */
    private $loginIsDeactivated;

    /**
     * @var DateTimeImmutable|null
     */
    private $impersonateExpiryDate;

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return PortalUserChangeStatus
     */
    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return string
     */
    public function getUserID(): string
    {
        return $this->userID;
    }

    /**
     * @param string $userID
     * @return PortalUserChangeStatus
     */
    public function setUserID(string $userID): self
    {
        $this->userID = $userID;
        return $this;
    }

    /**
     * @return string
     */
    public function getLastLogin(): string
    {
        return $this->lastLogin;
    }

    /**
     * @param string $lastLogin
     * @return PortalUserChangeStatus
     */
    public function setLastLogin(string $lastLogin): self
    {
        $this->lastLogin = $lastLogin;
        return $this;
    }

    /**
     * @return string
     */
    public function getCurrentStatus(): string
    {
        return $this->currentStatus;
    }

    /**
     * @param string $currentStatus
     * @return PortalUserChangeStatus
     */
    public function setCurrentStatus(string $currentStatus): self
    {
        $this->currentStatus = $currentStatus;
        return $this;
    }

    /**
     * @return string
     */
    public function getNewStatus(): string
    {
        return $this->newStatus;
    }

    /**
     * @param string $newStatus
     * @return PortalUserChangeStatus
     */
    public function setNewStatus(string $newStatus): self
    {
        $this->newStatus = $newStatus;
        return $this;
    }

    /**
     * @return bool
     */
    public function isContact(): bool
    {
        return $this->contact;
    }

    /**
     * @param bool $contact
     * @return PortalUserChangeStatus
     */
    public function setContact(bool $contact): self
    {
        $this->contact = $contact;
        return $this;
    }

    /**
     * @return string
     */
    public function getLoginIsDeactivated()
    {
        return $this->loginIsDeactivated;
    }

    /**
     * @param bool $loginIsDeactivated
     * @return PortalUserChangeStatus
     */
    public function setLoginIsDeactivated(bool $loginIsDeactivated): self
    {
        $this->loginIsDeactivated = $loginIsDeactivated;
        return $this;
    }

    /**
     * @return DateTimeImmutable|null
     */
    public function getImpersonateExpiryDate(): ?DateTimeImmutable
    {
        return $this->impersonateExpiryDate;
    }

    /**
     * @param DateTimeImmutable|null $expiry
     * @return PortalUserChangeStatus
     */
    public function setImpersonateExpiryDate(?DateTimeImmutable $expiry): self
    {
        $this->impersonateExpiryDate = $expiry;
        return $this;
    }
}