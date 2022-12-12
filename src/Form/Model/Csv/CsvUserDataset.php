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

namespace App\Form\Model\Csv;

use Symfony\Component\Validator\Constraints as Assert;

class CsvUserDataset
{
    #[Assert\NotBlank(message: 'The field firstname must not be blank.')]
    private ?string $firstname = null;

    #[Assert\NotBlank(message: 'The field lastname must not be blank.')]
    private ?string $lastname = null;

    #[Assert\NotBlank(message: 'The field email must not be blank.')]
    #[Assert\Email(message: 'The field email must be a valid email address.', strict: true)]
    private ?string $email = null;

    #[Assert\NotBlank(message: 'The field identifier must not be blank.')]
    private ?string $identifier = null;

    #[Assert\NotBlank(message: 'The field password must not be blank.')]
    private $password;

    #[Assert\Expression("value === '' or value === null or value matches '/\\\\d+\\s{0,1}/'", message: 'The field rooms must be empty or a list of room ids.')]
    private ?string $rooms = null;

    public function getFirstname(): string
    {
        return $this->firstname;
    }

    public function setFirstname(string $firstname): CsvUserDataset
    {
        $this->firstname = $firstname;

        return $this;
    }

    public function getLastname(): string
    {
        return $this->lastname;
    }

    public function setLastname(string $lastname): CsvUserDataset
    {
        $this->lastname = $lastname;

        return $this;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): CsvUserDataset
    {
        $this->email = $email;

        return $this;
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function setIdentifier(string $identifier): CsvUserDataset
    {
        $this->identifier = $identifier;

        return $this;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    /**
     * @param string $password
     */
    public function setPassword($password): CsvUserDataset
    {
        $this->password = $password;

        return $this;
    }

    public function getRooms(): string
    {
        return $this->rooms;
    }

    public function setRooms(string $rooms): CsvUserDataset
    {
        $this->rooms = $rooms;

        return $this;
    }
}
