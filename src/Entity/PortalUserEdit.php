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

/**
 * Class PortalUserEdit.
 */
class PortalUserEdit
{
    private $firstName;

    private $lastName;

    private $academicDegree;

    private $birthday;

    private $street;

    private $zip;

    /**
     * @var mixed|null
     */
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

    private $mayCreateContext;

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(?string $firstName): void
    {
        $this->firstName = $firstName;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(?string $lastName): void
    {
        $this->lastName = $lastName;
    }

    public function getAcademicDegree(): ?string
    {
        return $this->academicDegree;
    }

    public function setAcademicDegree(?string $academicDegree): void
    {
        $this->academicDegree = $academicDegree;
    }

    public function getBirthday(): ?string
    {
        return $this->birthday;
    }

    public function setBirthday(?string $birthday): void
    {
        $this->birthday = $birthday;
    }

    public function getStreet(): ?string
    {
        return $this->street;
    }

    public function setStreet($street): void
    {
        $this->street = $street;
    }

    public function getCity(): mixed
    {
        return $this->city;
    }

    public function setCity(mixed $city): void
    {
        $this->city = $city;
    }

    public function getZip(): ?string
    {
        return $this->zip;
    }

    public function setZip(?string $zip): void
    {
        $this->zip = $zip;
    }

    public function getWorkspace(): ?string
    {
        return $this->workspace;
    }

    public function setWorkspace(?string $workspace): void
    {
        $this->workspace = $workspace;
    }

    public function getTelephone(): ?string
    {
        return $this->telephone;
    }

    public function setTelephone(?string $telephone): void
    {
        $this->telephone = $telephone;
    }

    public function getSecondTelephone(): ?string
    {
        return $this->secondTelephone;
    }

    public function setSecondTelephone(?string $secondTelephone): void
    {
        $this->secondTelephone = $secondTelephone;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): void
    {
        $this->email = $email;
    }

    public function getEmailChangeAll(): ?bool
    {
        return $this->emailChangeAll;
    }

    public function setEmailChangeAll(?bool $emailChangeAll): void
    {
        $this->emailChangeAll = $emailChangeAll;
    }

    public function getIcq(): ?string
    {
        return $this->icq;
    }

    public function setIcq(?string $icq): void
    {
        $this->icq = $icq;
    }

    public function getMsn(): ?string
    {
        return $this->msn;
    }

    public function setMsn(?string $msn): void
    {
        $this->msn = $msn;
    }

    public function getSkype(): ?string
    {
        return $this->skype;
    }

    public function setSkype(?string $skype): void
    {
        $this->skype = $skype;
    }

    public function getYahoo(): ?string
    {
        return $this->yahoo;
    }

    public function setYahoo(?string $yahoo): void
    {
        $this->yahoo = $yahoo;
    }

    public function getHomepage(): ?string
    {
        return $this->homepage;
    }

    public function setHomepage(?string $homepage): void
    {
        $this->homepage = $homepage;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function getMayCreateContext(): ?string
    {
        return $this->mayCreateContext;
    }

    public function setMayCreateContext(?string $mayCreateContext): void
    {
        $this->mayCreateContext = $mayCreateContext;
    }
}
