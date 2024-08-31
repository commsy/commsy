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

namespace App\Form\Model;

class RoomData
{
    private ?string $title = null;
    private ?string $language = null;
    private ?string $roomDescription = null;
    private array $categories = [];
    private ?string $masterTemplate = null;
    private array $communityRooms = [];
    private bool $createUserRooms = false;
    private ?string $userroomTemplate = null;
    private array $timeInterval = [];

    public function getTimeInterval(): array
    {
        return $this->timeInterval;
    }

    public function setTimeInterval(array $timeInterval): RoomData
    {
        $this->timeInterval = $timeInterval;
        return $this;
    }

    public function getUserroomTemplate(): ?string
    {
        return $this->userroomTemplate;
    }

    public function setUserroomTemplate(?string $userroomTemplate): RoomData
    {
        $this->userroomTemplate = $userroomTemplate;
        return $this;
    }

    public function isCreateUserRooms(): bool
    {
        return $this->createUserRooms;
    }

    public function setCreateUserRooms(bool $createUserRooms): RoomData
    {
        $this->createUserRooms = $createUserRooms;
        return $this;
    }

    public function getCommunityRooms(): array
    {
        return $this->communityRooms;
    }

    public function setCommunityRooms(array $communityRooms): RoomData
    {
        $this->communityRooms = $communityRooms;
        return $this;
    }

    public function getMasterTemplate(): ?string
    {
        return $this->masterTemplate;
    }

    public function setMasterTemplate(?string $masterTemplate): RoomData
    {
        $this->masterTemplate = $masterTemplate;
        return $this;
    }

    public function getCategories(): array
    {
        return $this->categories;
    }

    public function setCategories(array $categories): RoomData
    {
        $this->categories = $categories;
        return $this;
    }

    public function getRoomDescription(): ?string
    {
        return $this->roomDescription;
    }

    public function setRoomDescription(?string $roomDescription): RoomData
    {
        $this->roomDescription = $roomDescription;
        return $this;
    }

    public function getLanguage(): ?string
    {
        return $this->language;
    }

    public function setLanguage(?string $language): RoomData
    {
        $this->language = $language;
        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): RoomData
    {
        $this->title = $title;
        return $this;
    }
}
