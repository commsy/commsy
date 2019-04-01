<?php
/**
 * Created by PhpStorm.
 * User: cschoenf
 * Date: 30.07.18
 * Time: 02:02
 */

namespace App\Form\Model\Csv;

use Symfony\Component\Validator\Constraints as Assert;

class CsvUserDataset
{
    /**
     * @Assert\NotBlank(
     *     message = "The field firstname must not be blank."
     * )
     */
    private $firstname;

    /**
     * @Assert\NotBlank(
     *     message = "The field lastname must not be blank."
     * )
     */
    private $lastname;

    /**
     * @Assert\NotBlank(
     *     message = "The field email must not be blank."
     * )
     * @Assert\Email(
     *     message = "The field email must be a valid email address.",
     *     strict = true
     * )
     */
    private $email;

    /**
     * @Assert\NotBlank(
     *     message = "The field identifier must not be blank."
     * )
     */
    private $identifier;

    /**
     * @Assert\NotBlank(
     *     message = "The field password must not be blank."
     * )
     */
    private $password;

    /**
     * @Assert\Expression(
     *     "value === '' or value === null or value matches '/\\d+\s{0,1}/'",
     *     message = "The field rooms must be empty or a list of room ids."
     * )
     */
    private $rooms;

    /**
     * @return string
     */
    public function getFirstname(): string
    {
        return $this->firstname;
    }

    /**
     * @param string $firstname
     * @return CsvUserDataset
     */
    public function setFirstname(string $firstname): CsvUserDataset
    {
        $this->firstname = $firstname;
        return $this;
    }

    /**
     * @return string
     */
    public function getLastname(): string
    {
        return $this->lastname;
    }

    /**
     * @param string $lastname
     * @return CsvUserDataset
     */
    public function setLastname(string $lastname): CsvUserDataset
    {
        $this->lastname = $lastname;
        return $this;
    }

    /**
     * @return string
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * @param string $email
     * @return CsvUserDataset
     */
    public function setEmail(string $email): CsvUserDataset
    {
        $this->email = $email;
        return $this;
    }

    /**
     * @return string
     */
    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    /**
     * @param string $identifier
     * @return CsvUserDataset
     */
    public function setIdentifier(string $identifier): CsvUserDataset
    {
        $this->identifier = $identifier;
        return $this;
    }

    /**
     * @return string
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    /**
     * @param string $password
     * @return CsvUserDataset
     */
    public function setPassword($password): CsvUserDataset
    {
        $this->password = $password;
        return $this;
    }

    /**
     * @return string
     */
    public function getRooms(): string
    {
        return $this->rooms;
    }

    /**
     * @param string $rooms
     * @return CsvUserDataset
     */
    public function setRooms(string $rooms): CsvUserDataset
    {
        $this->rooms = $rooms;
        return $this;
    }


}