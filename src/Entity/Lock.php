<?php

namespace App\Entity;

use App\Repository\LockRepository;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LockRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ORM\Table(name: '`lock`')]
class Lock
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'cascade')]
    private ?Account $account = null;

    #[ORM\Column(unique: true)]
    private int $itemId;

    #[ORM\Column(length: 255)]
    private string $token;

    #[ORM\Column]
    private DateTimeImmutable $lockDate;

    public function getId(): int
    {
        return $this->id;
    }

    public function getAccount(): Account
    {
        return $this->account;
    }

    public function setAccount(Account $account): self
    {
        $this->account = $account;

        return $this;
    }

    public function getItemId(): int
    {
        return $this->itemId;
    }

    public function setItemId(int $itemId): self
    {
        $this->itemId = $itemId;

        return $this;
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function setToken(string $token): self
    {
        $this->token = $token;

        return $this;
    }

    public function getLockDate(): DateTimeImmutable
    {
        return $this->lockDate;
    }

    public function setLockDate(DateTimeImmutable $lockDate): self
    {
        $this->lockDate = $lockDate;

        return $this;
    }

    #[ORM\PrePersist]
    public function setLockDateValue(): self
    {
        $this->lockDate = new DateTimeImmutable();

        return $this;
    }
}
