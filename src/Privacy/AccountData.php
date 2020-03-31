<?php
namespace App\Privacy;

/**
 * Class AccountData
 *
 * Holds a user's account data (for the portal user with ID $itemID and user ID $userID).
 *
 * @package App\Privacy
 */
class AccountData
{
    /**
     * @var int
     */
    private $portalID;

    /**
     * @var string
     */
    private $portalName;

    /**
     * @var int
     */
    private $itemID;

    /**
     * @var string
     */
    private $userID;

    /**
     * @return int
     */
    public function getPortalID(): int
    {
        return $this->portalID;
    }

    /**
     * @param int $portalID
     * @return AccountData
     */
    public function setPortalID(int $portalID): AccountData
    {
        $this->portalID = $portalID;
        return $this;
    }

    /**
     * @return string
     */
    public function getPortalName(): string
    {
        return $this->portalName;
    }

    /**
     * @param string $portalName
     * @return AccountData
     */
    public function setPortalName(string $portalName): AccountData
    {
        $this->portalName = $portalName;
        return $this;
    }

    /**
     * @return int
     */
    public function getItemID(): int
    {
        return $this->itemID;
    }

    /**
     * @param int $itemID
     * @return AccountData
     */
    public function setItemID(int $itemID): AccountData
    {
        $this->itemID = $itemID;
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
     * @return AccountData
     */
    public function setUserID(string $userID): AccountData
    {
        $this->userID = $userID;
        return $this;
    }
}
