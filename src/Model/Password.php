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
     * @Assert\Length(max=4096, min=8, allowEmptyString=false, minMessage="Your password must be at least {{ limit }} characters long.")
     * @Assert\Regex(pattern="/(*UTF8)[\p{Ll}\p{Lm}\p{Lo}]/", message="Your password must contain at least one lowercase character.")
     * @Assert\Regex(pattern="/(*UTF8)[\p{Lu}\p{Lt}]/", message="Your password must contain at least one uppercase character.")
     * @Assert\Regex(pattern="/[[:punct:]]/", message="Your password must contain at least one special character.")
     * @Assert\Regex(pattern="/\p{Nd}/", message="Your password must contain at least one numeric character.")
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