<?php

/*
 * This file is part of CommSy.
 *
 * (c) Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
 * Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
 * Edouard Simon, Monique Strauss, Jose Mauel Gonzalez Vazquez, Johannes Schultze
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace App\Entity;

use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
#[ORM\Table(name: 'log')]
#[ORM\Index(name: 'cid_timestamp_idx', columns: ['cid', 'timestamp'])]
class Log
{
    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private int $id;

    #[ORM\Column(name: 'ip', type: Types::STRING, length: 15)]
    private string $ip;

    #[ORM\Column(name: 'agent', type: Types::STRING, length: 250)]
    private string $agent;

    #[ORM\Column(name: 'timestamp', type: Types::DATETIME_IMMUTABLE)]
    private DateTimeInterface $timestamp;

    #[ORM\Column(name: 'request', type: Types::STRING, length: 2500)]
    private string $request;

    #[ORM\Column(name: 'post_content', type: Types::STRING, length: 2500, nullable: true)]
    private ?string $postContent = null;

    #[ORM\Column(name: 'method', type: Types::STRING, length: 10)]
    private string $method;

    #[ORM\Column(name: 'ulogin', type: Types::STRING, length: 250, nullable: true)]
    private ?string $ulogin = null;

    #[ORM\Column(name: 'cid', type: Types::INTEGER, nullable: true)]
    private ?int $cid = null;

    #[ORM\Column(nullable: true)]
    private ?bool $ajax = null;

    #[ORM\PrePersist]
    public function setCreatedAtValue(): void
    {
        $this->timestamp = new DateTimeImmutable();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): static
    {
        $this->id = $id;
        return $this;
    }

    public function getIp(): string
    {
        return $this->ip;
    }

    public function setIp(string $ip): static
    {
        $this->ip = $ip;
        return $this;
    }

    public function getAgent(): string
    {
        return $this->agent;
    }

    public function setAgent(string $agent): static
    {
        $this->agent = $agent;
        return $this;
    }

    public function getTimestamp(): DateTimeInterface
    {
        return $this->timestamp;
    }

    public function setTimestamp(DateTimeInterface $timestamp): static
    {
        $this->timestamp = $timestamp;
        return $this;
    }

    public function getRequest(): string
    {
        return $this->request;
    }

    public function setRequest(string $request): static
    {
        $this->request = $request;
        return $this;
    }

    public function getPostContent(): ?string
    {
        return $this->postContent;
    }

    public function setPostContent(?string $postContent): static
    {
        $this->postContent = $postContent;
        return $this;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function setMethod(string $method): static
    {
        $this->method = $method;
        return $this;
    }

    public function getUlogin(): ?string
    {
        return $this->ulogin;
    }

    public function setUlogin(?string $ulogin): static
    {
        $this->ulogin = $ulogin;
        return $this;
    }

    public function getCid(): ?int
    {
        return $this->cid;
    }

    public function setCid(?int $cid): static
    {
        $this->cid = $cid;
        return $this;
    }

    public function isAjax(): ?bool
    {
        return $this->ajax;
    }

    public function setAjax(?bool $ajax): static
    {
        $this->ajax = $ajax;

        return $this;
    }
}
