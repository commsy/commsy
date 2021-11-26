<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity()
 */
class AuthSourceLocal extends AuthSource
{
    /**
     * @var string|null
     *
     * @ORM\Column(type="string", length=100, nullable=false)
     * @Assert\Length(max=100)
     */
    private ?string $mailRegex;

    protected string $type = 'local';

    public function __construct()
    {
        $this->addAccount = self::ADD_ACCOUNT_YES;
        $this->changeUsername = true;
        $this->deleteAccount = true;
        $this->changeUserdata = true;
        $this->changePassword = true;
    }

    /**
     * @return string
     */
    public function getMailRegex(): ?string
    {
        return $this->mailRegex;
    }

    /**
     * @param string $mailRegex
     * @return self
     */
    public function setMailRegex(string $mailRegex): self
    {
        $this->mailRegex = $mailRegex;
        return $this;
    }
}