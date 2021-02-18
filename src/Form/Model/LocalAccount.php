<?php


namespace App\Form\Model;

use Symfony\Component\Validator\Constraints as Assert;
use App\Validator\Constraints\LocalAccount as LocalAccountAssert;

/**
 * @LocalAccountAssert()
 */
class LocalAccount
{
    /**
     * @var string
     * @Assert\NotBlank()
     */
    private $username;

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
    public function getUsername(): ?string
    {
        return $this->username;
    }

    /**
     * @param string $username
     * @return self
     */
    public function setUsername(string $username): self
    {
        $this->username = $username;
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