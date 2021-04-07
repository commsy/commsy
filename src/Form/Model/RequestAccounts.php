<?php


namespace App\Form\Model;

use Symfony\Component\Validator\Constraints as Assert;

class RequestAccounts
{
    /**
     * @var string
     * @Assert\Email()
     */
    private $email;

    /**
     * @var int
     */
    private $contextId;

    public function __construct(int $contextId)
    {
        $this->contextId = $contextId;
    }

    /**
     * @return string
     */
    public function getEmail(): ?string
    {
        return $this->email;
    }

    /**
     * @param string $email
     * @return self
     */
    public function setEmail(string $email): self
    {
        $this->email = $email;
        return $this;
    }

    /**
     * @return int
     */
    public function getContextId(): int
    {
        return $this->contextId;
    }

    /**
     * @param int $contextId
     * @return self
     */
    public function setContextId(int $contextId): self
    {
        $this->contextId = $contextId;
        return $this;
    }
}