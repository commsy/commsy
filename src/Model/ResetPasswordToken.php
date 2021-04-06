<?php


namespace App\Model;


use App\Entity\Account;

final class ResetPasswordToken
{
    /**
     * @var string
     */
    private $token;

    /**
     * @var \DateTimeInterface
     */
    private $expiresAt;

    /**
     * @var Account
     */
    private $account;

    /**
     * @var string
     */
    private $ip;

    public function __construct(
        string $token,
        \DateTimeInterface $expiresAt,
        Account $account,
        string $ip
    ) {
        $this->token = $token;
        $this->expiresAt = $expiresAt;
        $this->account = $account;
        $this->ip = $ip;
    }

    /**
     * @return string
     */
    public function getToken(): string
    {
        return $this->token;
    }

    /**
     * @param string $token
     * @return self
     */
    public function setToken(string $token): self
    {
        $this->token = $token;
        return $this;
    }

    /**
     * @return \DateTimeInterface
     */
    public function getExpiresAt(): \DateTimeInterface
    {
        return $this->expiresAt;
    }

    /**
     * @param \DateTimeInterface $expiresAt
     * @return self
     */
    public function setExpiresAt(\DateTimeInterface $expiresAt): self
    {
        $this->expiresAt = $expiresAt;
        return $this;
    }

    /**
     * @return Account
     */
    public function getAccount(): Account
    {
        return $this->account;
    }

    /**
     * @param Account $account
     * @return self
     */
    public function setAccount(Account $account): self
    {
        $this->account = $account;
        return $this;
    }

    /**
     * @return string
     */
    public function getIp(): string
    {
        return $this->ip;
    }

    /**
     * @param string $ip
     * @return self
     */
    public function setIp(string $ip): self
    {
        $this->ip = $ip;
        return $this;
    }
}