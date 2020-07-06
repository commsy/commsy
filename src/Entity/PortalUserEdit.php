<?php


namespace App\Entity;

/**
 * Class PortalUserEdit
 * @package App\Entity
 */
class PortalUserEdit
{

    public $itemId = '0';

    private $contextId;

    private $firstName;

    private $lastName;

    private $academicDegree;

    private $birthday;

    private $street;

    private $zip;

    private $city;

    private $workspace;

    private $telephone;

    private $secondTelephone;

    private $email;

    private $emailChangeAll;

    private $icq;

    private $msn;

    private $skype;

    private $yahoo;

    private $homepage;

    private $description;

    private $picture;

    private $userIsAllowedToUseCaldav;

    private $mayCreateContext;

    private $mayUseCaldav;

    private $uploadUrl;

    /** @var bool */
    private $changeMailEverywhere;

    /** @var bool */
    private $overrideExistingPicture;

    /**
     * @return string
     */
    public function getItemId(): string
    {
        return $this->itemId;
    }

    /**
     * @param string $itemId
     */
    public function setItemId(string $itemId): void
    {
        $this->itemId = $itemId;
    }

    /**
     * @return string
     */
    public function getContextId()
    {
        return $this->contextId;
    }

    /**
     * @param string $contextId
     */
    public function setContextId($contextId): void
    {
        $this->contextId = $contextId;
    }

    /**
     * @return string
     */
    public function getFirstName()
    {
        return $this->firstName;
    }

    /**
     * @param string $firstName
     */
    public function setFirstName($firstName): void
    {
        $this->firstName = $firstName;
    }

    /**
     * @return string
     */
    public function getLastName()
    {
        return $this->lastName;
    }

    /**
     * @param string $lastName
     */
    public function setLastName($lastName): void
    {
        $this->lastName = $lastName;
    }

    /**
     * @return string
     */
    public function getAcademicDegree()
    {
        return $this->academicDegree;
    }

    /**
     * @param string $academicDegree
     */
    public function setAcademicDegree($academicDegree): void
    {
        $this->academicDegree = $academicDegree;
    }

    /**
     * @return string
     */
    public function getBirthday()
    {
        return $this->birthday;
    }

    /**
     * @param string $birthday
     */
    public function setBirthday($birthday): void
    {
        $this->birthday = $birthday;
    }

    /**
     * @return string
     */
    public function getStreet()
    {
        return $this->street;
    }

    /**
     * @param string $street
     */
    public function setStreet($street): void
    {
        $this->street = $street;
    }

    /**
     * @return mixed
     */
    public function getCity()
    {
        return $this->city;
    }

    /**
     * @param mixed $city
     */
    public function setCity($city): void
    {
        $this->city = $city;
    }

    /**
     * @return string
     */
    public function getZip()
    {
        return $this->zip;
    }

    /**
     * @param string $zip
     */
    public function setZip($zip): void
    {
        $this->zip = $zip;
    }

    /**
     * @return string
     */
    public function getWorkspace()
    {
        return $this->workspace;
    }

    /**
     * @param string $workspace
     */
    public function setWorkspace($workspace): void
    {
        $this->workspace = $workspace;
    }

    /**
     * @return string
     */
    public function getTelephone()
    {
        return $this->telephone;
    }

    /**
     * @param string $telephone
     */
    public function setTelephone($telephone): void
    {
        $this->telephone = $telephone;
    }

    /**
     * @return string
     */
    public function getSecondTelephone()
    {
        return $this->secondTelephone;
    }

    /**
     * @param string $secondTelephone
     */
    public function setSecondTelephone($secondTelephone): void
    {
        $this->secondTelephone = $secondTelephone;
    }

    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param string $email
     */
    public function setEmail($email): void
    {
        $this->email = $email;
    }

    /**
     * @return bool
     */
    public function getEmailChangeAll()
    {
        return $this->emailChangeAll;
    }

    /**
     * @param bool $emailChangeAll
     */
    public function setEmailChangeAll($emailChangeAll): void
    {
        $this->emailChangeAll = $emailChangeAll;
    }

    /**
     * @return string
     */
    public function getIcq()
    {
        return $this->icq;
    }

    /**
     * @param string $icq
     */
    public function setIcq($icq): void
    {
        $this->icq = $icq;
    }

    /**
     * @return string
     */
    public function getMsn()
    {
        return $this->msn;
    }

    /**
     * @param string $msn
     */
    public function setMsn($msn): void
    {
        $this->msn = $msn;
    }

    /**
     * @return string
     */
    public function getSkype()
    {
        return $this->skype;
    }

    /**
     * @param string $skype
     */
    public function setSkype($skype): void
    {
        $this->skype = $skype;
    }

    /**
     * @return string
     */
    public function getYahoo()
    {
        return $this->yahoo;
    }

    /**
     * @param string $yahoo
     */
    public function setYahoo($yahoo): void
    {
        $this->yahoo = $yahoo;
    }

    /**
     * @return string
     */
    public function getHomepage()
    {
        return $this->homepage;
    }

    /**
     * @param string $homepage
     */
    public function setHomepage($homepage): void
    {
        $this->homepage = $homepage;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string $description
     */
    public function setDescription($description): void
    {
        $this->description = $description;
    }

    /**
     * @return string
     */
    public function getPicture()
    {
        return $this->picture;
    }

    /**
     * @param string $picture
     */
    public function setPicture($picture): void
    {
        $this->picture = $picture;
    }

    /**
     * @return string
     */
    public function getMayCreateContext()
    {
        return $this->mayCreateContext;
    }

    /**
     * @param string $mayCreateContext
     */
    public function setMayCreateContext($mayCreateContext): void
    {
        $this->mayCreateContext = $mayCreateContext;
    }

    /**
     * @return string
     */
    public function getMayUseCaldav()
    {
        return $this->mayUseCaldav;
    }

    /**
     * @param string $mayUseCaldav
     */
    public function setMayUseCaldav($mayUseCaldav): void
    {
        $this->mayUseCaldav = $mayUseCaldav;
    }

    /**
     * @return bool
     */
    public function isChangeMailEverywhere(): bool
    {
        return $this->changeMailEverywhere;
    }

    /**
     * @param bool $changeMailEverywhere
     */
    public function setChangeMailEverywhere(bool $changeMailEverywhere): void
    {
        $this->changeMailEverywhere = $changeMailEverywhere;
    }

    /**
     * @return bool
     */
    public function isOverrideExistingPicture(): bool
    {
        return $this->overrideExistingPicture ?? 0;
    }

    /**
     * @param bool $overrideExistingPicture
     */
    public function setOverrideExistingPicture(bool $overrideExistingPicture): void
    {
        $this->overrideExistingPicture = $overrideExistingPicture;
    }

    /**
     * @return mixed
     */
    public function getUserIsAllowedToUseCaldav()
    {
        return $this->userIsAllowedToUseCaldav ?? 0;
    }

    /**
     * @param mixed $userIsAllowedToUseCaldav
     */
    public function setUserIsAllowedToUseCaldav($userIsAllowedToUseCaldav): void
    {
        $this->userIsAllowedToUseCaldav = $userIsAllowedToUseCaldav;
    }

    /**
     * @return mixed
     */
    public function getUploadUrl()
    {
        return $this->uploadUrl;
    }

    /**
     * @param mixed $uploadUrl
     */
    public function setUploadUrl($uploadUrl): void
    {
        $this->uploadUrl = $uploadUrl;
    }


}