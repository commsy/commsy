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
     * @var string
     *
     * @ORM\Column(type="string", length=100, nullable=false)
     * @Assert\Length(max=100)
     */
    private $mailRegex;

    public function __construct()
    {
        $this->addAccount = true;
        $this->changeUsername = true;
        $this->deleteAccount = true;
        $this->changeUserdata = true;
        $this->changePassword = true;
    }

    public function getType(): string
    {
        return 'local';
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