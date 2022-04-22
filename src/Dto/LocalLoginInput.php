<?php

namespace App\Dto;

use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

final class LocalLoginInput
{
    /**
     * @var int
     *
     * @Assert\NotBlank()
     *
     * @Groups({"api_check_local_login"})
     */
    private int $contextId;

    /**
     * @var string
     *
     * @Assert\NotBlank()
     *
     * @Groups({"api_check_local_login"})
     */
    private string $username;

    /**
     * @var string
     *
     * @Assert\NotBlank()
     *
     * @Groups({"api_check_local_login"})
     */
    private string $password;

    /**
     * @return int
     */
    public function getContextId(): int
    {
        return $this->contextId;
    }

    /**
     * @param int $contextId
     * @return LocalLoginInput
     */
    public function setContextId(int $contextId): LocalLoginInput
    {
        $this->contextId = $contextId;
        return $this;
    }

    /**
     * @return string
     */
    public function getUsername(): string
    {
        return $this->username;
    }

    /**
     * @param string $username
     * @return LocalLoginInput
     */
    public function setUsername(string $username): LocalLoginInput
    {
        $this->username = $username;
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
     * @return LocalLoginInput
     */
    public function setPassword(string $password): LocalLoginInput
    {
        $this->password = $password;
        return $this;
    }
}