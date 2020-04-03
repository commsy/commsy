<?php
namespace App\Privacy;

/**
 * Class RoomProfileData
 *
 * Holds a user's room profile data (for a room with ID $roomID and room user with ID $itemID).
 *
 * @package App\Privacy
 */
class RoomProfileData
{
    /**
     * @var int
     */
    private $roomID;

    /**
     * @var string
     */
    private $roomName;

    /**
     * @var int
     */
    private $itemID;

    /**
     * @var \DateTime
     */
    private $creationDate;

    /**
     * @var int
     */
    private $status;

    /**
     * @var boolean
     */
    private $isContact;

    /**
     * @var string|null
     */
    private $email;

    /**
     * @var boolean
     */
    private $isEmailVisible;

    /**
     * @var string|null
     */
    private $title;

    /**
     * @var string|null
     */
    private $street;

    /**
     * @var string|null
     */
    private $zipcode;

    /**
     * @var string|null
     */
    private $city;

    /**
     * @var string|null
     */
    private $workspace;

    /**
     * @var string|null
     */
    private $organisation;

    /**
     * @var string|null
     */
    private $position;

    /**
     * @var string|null
     */
    private $phoneNumber;

    /**
     * @var string|null
     */
    private $cellphoneNumber;

    /**
     * @var string|null
     */
    private $skypeID;

    /**
     * @var string|null
     */
    private $homepage;

    /**
     * @var string|null
     */
    private $description;

    /**
     * @return int
     */
    public function getRoomID(): int
    {
        return $this->roomID;
    }

    /**
     * @param int $roomID
     * @return RoomProfileData
     */
    public function setRoomID(int $roomID): RoomProfileData
    {
        $this->roomID = $roomID;
        return $this;
    }

    /**
     * @return string
     */
    public function getRoomName(): string
    {
        return $this->roomName;
    }

    /**
     * @param string $roomName
     * @return RoomProfileData
     */
    public function setRoomName(string $roomName): RoomProfileData
    {
        $this->roomName = $roomName;
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
     * @return RoomProfileData
     */
    public function setItemID(int $itemID): RoomProfileData
    {
        $this->itemID = $itemID;
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
     * @return RoomProfileData
     */
    public function setCreationDate(\DateTime $creationDate): RoomProfileData
    {
        $this->creationDate = $creationDate;
        return $this;
    }

    /**
     * @return int
     */
    public function getStatus(): int
    {
        return $this->status;
    }

    /**
     * @param int $status
     * @return RoomProfileData
     */
    public function setStatus(int $status): RoomProfileData
    {
        $this->status = $status;
        return $this;
    }

    /**
     * @return bool
     */
    public function isContact(): bool
    {
        return $this->isContact;
    }

    /**
     * @param bool $isContact
     * @return RoomProfileData
     */
    public function setIsContact(bool $isContact): RoomProfileData
    {
        $this->isContact = $isContact;
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
     * @return RoomProfileData
     */
    public function setEmail(?string $email): RoomProfileData
    {
        $this->email = $email;
        return $this;
    }

    /**
     * @return bool
     */
    public function isEmailVisible(): bool
    {
        return $this->isEmailVisible;
    }

    /**
     * @param bool $isEmailVisible
     * @return RoomProfileData
     */
    public function setIsEmailVisible(bool $isEmailVisible): RoomProfileData
    {
        $this->isEmailVisible = $isEmailVisible;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getTitle(): ?string
    {
        return $this->title;
    }

    /**
     * @param string|null $title
     * @return RoomProfileData
     */
    public function setTitle(?string $title): RoomProfileData
    {
        $this->title = $title;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getStreet(): ?string
    {
        return $this->street;
    }

    /**
     * @param string|null $street
     * @return RoomProfileData
     */
    public function setStreet(?string $street): RoomProfileData
    {
        $this->street = $street;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getZipcode(): ?string
    {
        return $this->zipcode;
    }

    /**
     * @param string|null $zipcode
     * @return RoomProfileData
     */
    public function setZipcode(?string $zipcode): RoomProfileData
    {
        $this->zipcode = $zipcode;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getCity(): ?string
    {
        return $this->city;
    }

    /**
     * @param string|null $city
     * @return RoomProfileData
     */
    public function setCity(?string $city): RoomProfileData
    {
        $this->city = $city;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getWorkspace(): ?string
    {
        return $this->workspace;
    }

    /**
     * @param string|null $workspace
     * @return RoomProfileData
     */
    public function setWorkspace(?string $workspace): RoomProfileData
    {
        $this->workspace = $workspace;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getOrganisation(): ?string
    {
        return $this->organisation;
    }

    /**
     * @param string|null $organisation
     * @return RoomProfileData
     */
    public function setOrganisation(?string $organisation): RoomProfileData
    {
        $this->organisation = $organisation;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getPosition(): ?string
    {
        return $this->position;
    }

    /**
     * @param string|null $position
     * @return RoomProfileData
     */
    public function setPosition(?string $position): RoomProfileData
    {
        $this->position = $position;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getPhoneNumber(): ?string
    {
        return $this->phoneNumber;
    }

    /**
     * @param string|null $phoneNumber
     * @return RoomProfileData
     */
    public function setPhoneNumber(?string $phoneNumber): RoomProfileData
    {
        $this->phoneNumber = $phoneNumber;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getCellphoneNumber(): ?string
    {
        return $this->cellphoneNumber;
    }

    /**
     * @param string|null $cellphoneNumber
     * @return RoomProfileData
     */
    public function setCellphoneNumber(?string $cellphoneNumber): RoomProfileData
    {
        $this->cellphoneNumber = $cellphoneNumber;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getSkypeID(): ?string
    {
        return $this->skypeID;
    }

    /**
     * @param string|null $skypeID
     * @return RoomProfileData
     */
    public function setSkypeID(?string $skypeID): RoomProfileData
    {
        $this->skypeID = $skypeID;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getHomepage(): ?string
    {
        return $this->homepage;
    }

    /**
     * @param string|null $homepage
     * @return RoomProfileData
     */
    public function setHomepage(?string $homepage): RoomProfileData
    {
        $this->homepage = $homepage;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @param string|null $description
     * @return RoomProfileData
     */
    public function setDescription(?string $description): RoomProfileData
    {
        $this->description = $description;
        return $this;
    }
}
