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

use DateTime;

/**
 * Class RoomProfileData.
 *
 * Holds a user's room profile data (for a room with ID $roomID and room user with ID $itemID).
 */
class RoomProfileData
{
    private ?int $roomID = null;

    private ?string $roomType = null;

    private ?string $roomName = null;

    private ?int $itemID = null;

    private ?DateTime $creationDate = null;

    private ?int $status = null;

    private ?bool $isContact = null;

    private ?string $email = null;

    private ?bool $isEmailVisible = null;

    private ?string $title = null;

    private ?string $street = null;

    private ?string $zipcode = null;

    private ?string $city = null;

    private ?string $workspace = null;

    private ?string $organisation = null;

    private ?string $position = null;

    private ?string $phoneNumber = null;

    private ?string $cellphoneNumber = null;

    private ?string $skypeID = null;

    private ?string $homepage = null;

    private ?string $description = null;

    public function getRoomID(): int
    {
        return $this->roomID;
    }

    public function setRoomID(int $roomID): RoomProfileData
    {
        $this->roomID = $roomID;

        return $this;
    }

    public function getRoomType(): string
    {
        return $this->roomType;
    }

    public function setRoomType(string $roomType): RoomProfileData
    {
        $this->roomType = $roomType;

        return $this;
    }

    public function getRoomName(): string
    {
        return $this->roomName;
    }

    public function setRoomName(string $roomName): RoomProfileData
    {
        $this->roomName = $roomName;

        return $this;
    }

    public function getItemID(): int
    {
        return $this->itemID;
    }

    public function setItemID(int $itemID): RoomProfileData
    {
        $this->itemID = $itemID;

        return $this;
    }

    public function getCreationDate(): DateTime
    {
        return $this->creationDate;
    }

    public function setCreationDate(DateTime $creationDate): RoomProfileData
    {
        $this->creationDate = $creationDate;

        return $this;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function setStatus(int $status): RoomProfileData
    {
        $this->status = $status;

        return $this;
    }

    public function isContact(): bool
    {
        return $this->isContact;
    }

    public function setIsContact(bool $isContact): RoomProfileData
    {
        $this->isContact = $isContact;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): RoomProfileData
    {
        $this->email = $email;

        return $this;
    }

    public function isEmailVisible(): bool
    {
        return $this->isEmailVisible;
    }

    public function setIsEmailVisible(bool $isEmailVisible): RoomProfileData
    {
        $this->isEmailVisible = $isEmailVisible;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): RoomProfileData
    {
        $this->title = $title;

        return $this;
    }

    public function getStreet(): ?string
    {
        return $this->street;
    }

    public function setStreet(?string $street): RoomProfileData
    {
        $this->street = $street;

        return $this;
    }

    public function getZipcode(): ?string
    {
        return $this->zipcode;
    }

    public function setZipcode(?string $zipcode): RoomProfileData
    {
        $this->zipcode = $zipcode;

        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(?string $city): RoomProfileData
    {
        $this->city = $city;

        return $this;
    }

    public function getWorkspace(): ?string
    {
        return $this->workspace;
    }

    public function setWorkspace(?string $workspace): RoomProfileData
    {
        $this->workspace = $workspace;

        return $this;
    }

    public function getOrganisation(): ?string
    {
        return $this->organisation;
    }

    public function setOrganisation(?string $organisation): RoomProfileData
    {
        $this->organisation = $organisation;

        return $this;
    }

    public function getPosition(): ?string
    {
        return $this->position;
    }

    public function setPosition(?string $position): RoomProfileData
    {
        $this->position = $position;

        return $this;
    }

    public function getPhoneNumber(): ?string
    {
        return $this->phoneNumber;
    }

    public function setPhoneNumber(?string $phoneNumber): RoomProfileData
    {
        $this->phoneNumber = $phoneNumber;

        return $this;
    }

    public function getCellphoneNumber(): ?string
    {
        return $this->cellphoneNumber;
    }

    public function setCellphoneNumber(?string $cellphoneNumber): RoomProfileData
    {
        $this->cellphoneNumber = $cellphoneNumber;

        return $this;
    }

    public function getSkypeID(): ?string
    {
        return $this->skypeID;
    }

    public function setSkypeID(?string $skypeID): RoomProfileData
    {
        $this->skypeID = $skypeID;

        return $this;
    }

    public function getHomepage(): ?string
    {
        return $this->homepage;
    }

    public function setHomepage(?string $homepage): RoomProfileData
    {
        $this->homepage = $homepage;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): RoomProfileData
    {
        $this->description = $description;

        return $this;
    }
}
