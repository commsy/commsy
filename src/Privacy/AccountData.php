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
     * @var \DateTime
     */
    private $creationDate;

    /**
     * @var \DateTime|null
     */
    private $lastLoginDate;

    /**
     * @var string|null
     */
    private $email;

    /**
     * @var string|null
     */
    private $firstName;

    /**
     * @var string|null
     */
    private $lastName;

    /**
     * @var \DateTime|null
     */
    private $birthdate;

    /**
     * @var string|null
     */
    private $language;

    /**
     * @var string|null $newsletterStatus newsletter activity interval ("daily", "weekly" or "none")
     */
    private $newsletterStatus;

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

    /**
     * @return \DateTime
     */
    public function getCreationDate(): \DateTime
    {
        return $this->creationDate;
    }

    /**
     * @param \DateTime $creationDate
     * @return AccountData
     */
    public function setCreationDate(\DateTime $creationDate): AccountData
    {
        $this->creationDate = $creationDate;
        return $this;
    }

    /**
     * @return \DateTime|null
     */
    public function getLastLoginDate(): ?\DateTime
    {
        return $this->lastLoginDate;
    }

    /**
     * @param \DateTime|null $lastLoginDate
     * @return AccountData
     */
    public function setLastLoginDate(?\DateTime $lastLoginDate): AccountData
    {
        $this->lastLoginDate = $lastLoginDate;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getEmail(): ?string
    {
        return $this->email;
    }

    /**
     * @param string|null $email
     * @return AccountData
     */
    public function setEmail(?string $email): AccountData
    {
        $this->email = $email;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    /**
     * @param string|null $firstName
     * @return AccountData
     */
    public function setFirstName(?string $firstName): AccountData
    {
        $this->firstName = $firstName;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    /**
     * @param string|null $lastName
     * @return AccountData
     */
    public function setLastName(?string $lastName): AccountData
    {
        $this->lastName = $lastName;
        return $this;
    }

    /**
     * @return \DateTime|null
     */
    public function getBirthdate(): ?\DateTime
    {
        return $this->birthdate;
    }

    /**
     * @param \DateTime|null $birthdate
     * @return AccountData
     */
    public function setBirthdate(?\DateTime $birthdate): AccountData
    {
        $this->birthdate = $birthdate;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getLanguage(): ?string
    {
        return $this->language;
    }

    /**
     * @param string|null $language
     * @return AccountData
     */
    public function setLanguage(?string $language): AccountData
    {
        $this->language = $language;
        return $this;
    }

    /**
     * @return string
     */
    public function getNewsletterStatus(): string
    {
        if (!isset($this->newsletterStatus) || empty($this->newsletterStatus)) {
            return "none";
        }

        return $this->newsletterStatus;
    }

    /**
     * @param string|null $newsletterStatus
     * @return AccountData
     */
    public function setNewsletterStatus(?string $newsletterStatus): AccountData
    {
        // newsletterStatus value must be either "daily", "weekly" or "none"
        if (!isset($newsletterStatus) || empty($newsletterStatus) || ($newsletterStatus !== "daily" && $newsletterStatus !== "weekly")) {
            $newsletterStatus = "none";
        }
        $this->newsletterStatus = $newsletterStatus;
        return $this;
    }
}
