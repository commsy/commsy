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

namespace App\Privacy;

/**
 * Class AccountData.
 *
 * Holds a user's account data (for the portal user with ID $itemID and user ID $userID).
 */
class AccountData
{
    private ?int $portalID = null;

    private ?string $portalName = null;

    private ?int $itemID = null;

    private ?string $userID = null;

    private ?\DateTime $creationDate = null;

    private ?\DateTime $lastLoginDate = null;

    private ?string $email = null;

    private ?string $firstName = null;

    private ?string $lastName = null;

    private ?\DateTime $birthdate = null;

    private ?string $language = null;

    /**
     * @var string|null newsletter activity interval ("daily", "weekly" or "none")
     */
    private ?string $newsletterStatus = null;

    public function getPortalID(): int
    {
        return $this->portalID;
    }

    public function setPortalID(int $portalID): AccountData
    {
        $this->portalID = $portalID;

        return $this;
    }

    public function getPortalName(): string
    {
        return $this->portalName;
    }

    public function setPortalName(string $portalName): AccountData
    {
        $this->portalName = $portalName;

        return $this;
    }

    public function getItemID(): int
    {
        return $this->itemID;
    }

    public function setItemID(int $itemID): AccountData
    {
        $this->itemID = $itemID;

        return $this;
    }

    public function getUserID(): string
    {
        return $this->userID;
    }

    public function setUserID(string $userID): AccountData
    {
        $this->userID = $userID;

        return $this;
    }

    public function getCreationDate(): \DateTime
    {
        return $this->creationDate;
    }

    public function setCreationDate(\DateTime $creationDate): AccountData
    {
        $this->creationDate = $creationDate;

        return $this;
    }

    public function getLastLoginDate(): ?\DateTime
    {
        return $this->lastLoginDate;
    }

    public function setLastLoginDate(?\DateTime $lastLoginDate): AccountData
    {
        $this->lastLoginDate = $lastLoginDate;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): AccountData
    {
        $this->email = $email;

        return $this;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(?string $firstName): AccountData
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(?string $lastName): AccountData
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getBirthdate(): ?\DateTime
    {
        return $this->birthdate;
    }

    public function setBirthdate(?\DateTime $birthdate): AccountData
    {
        $this->birthdate = $birthdate;

        return $this;
    }

    public function getLanguage(): ?string
    {
        return $this->language;
    }

    public function setLanguage(?string $language): AccountData
    {
        $this->language = $language;

        return $this;
    }

    public function getNewsletterStatus(): string
    {
        if (!isset($this->newsletterStatus) || empty($this->newsletterStatus)) {
            return 'none';
        }

        return $this->newsletterStatus;
    }

    public function setNewsletterStatus(?string $newsletterStatus): AccountData
    {
        // newsletterStatus value must be either "daily", "weekly" or "none"
        if (!isset($newsletterStatus) || empty($newsletterStatus) || ('daily' !== $newsletterStatus && 'weekly' !== $newsletterStatus)) {
            $newsletterStatus = 'none';
        }
        $this->newsletterStatus = $newsletterStatus;

        return $this;
    }
}
