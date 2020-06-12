<?php


namespace App\Entity;

/**
 * Class PortalUserChangeStatus
 * @package App\Entity
 */
class PortalUserChangeStatus
{

    private $name;

    private $userID;

    private $lastLogin;

    private $currentStatus;

    private $newStatus;

    /** @var bool */
    private $contact;

    private $loginIsDeactivated;

    private $loginAsActiveForDays;

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name): void
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getUserID()
    {
        return $this->userID;
    }

    /**
     * @param string $userID
     */
    public function setUserID($userID): void
    {
        $this->userID = $userID;
    }

    /**
     * @return string
     */
    public function getLastLogin()
    {
        return $this->lastLogin;
    }

    /**
     * @param string $lastLogin
     */
    public function setLastLogin($lastLogin): void
    {
        $this->lastLogin = $lastLogin;
    }

    /**
     * @return string
     */
    public function getCurrentStatus()
    {
        return $this->currentStatus;
    }

    /**
     * @param string $currentStatus
     */
    public function setCurrentStatus($currentStatus): void
    {
        $this->currentStatus = $currentStatus;
    }

    /**
     * @return string
     */
    public function getNewStatus()
    {
        return $this->newStatus;
    }

    /**
     * @param string $newStatus
     */
    public function setNewStatus($newStatus): void
    {
        $this->newStatus = $newStatus;
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
     */
    public function setContact(bool $contact): void
    {
        $this->contact = $contact;
    }

    /**
     * @return string
     */
    public function getLoginIsDeactivated()
    {
        return $this->loginIsDeactivated;
    }

    /**
     * @param string $loginIsDeactivated
     */
    public function setLoginIsDeactivated($loginIsDeactivated): void
    {
        $this->loginIsDeactivated = $loginIsDeactivated;
    }

    /**
     * @return string
     */
    public function getLoginAsActiveForDays()
    {
        return $this->loginAsActiveForDays;
    }

    /**
     * @param string $loginAsActiveForDays
     */
    public function setLoginAsActiveForDays($loginAsActiveForDays): void
    {
        $this->loginAsActiveForDays = $loginAsActiveForDays;
    }


}