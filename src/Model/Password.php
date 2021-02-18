<?php


namespace App\Model;


use Symfony\Component\Validator\Constraints as Assert;

class Password
{
    /**
     * @var string
     *
     * @Assert\NotBlank()
     * @Assert\NotCompromisedPassword()
     */
    private $password;

    /**
     * @return string
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    /**
     * @param string $password
     * @return self
     */
    public function setPassword(string $password): self
    {
        $this->password = $password;
        return $this;
    }
}