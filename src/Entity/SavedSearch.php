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

use App\Repository\SavedSearchRepository;
use DateTime;
use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;

/**
 * SavedSearch.
 */
#[ORM\Entity(repositoryClass: SavedSearchRepository::class)]
#[ORM\Table(name: 'saved_searches')]
#[ORM\Index(name: 'account_id', columns: ['account_id'])]
class SavedSearch
{
    /**
     * @var int
     */
    #[ORM\Column(name: 'id', type: 'integer', nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private $id;

    #[ORM\Column(name: 'account_id', type: 'integer', nullable: false)]
    private ?int $accountId = null;

    #[ORM\Column(name: 'deleter_id', type: 'integer', nullable: true)]
    private ?int $deleterId = null;

    /**
     * @var DateTime|null
     */
    #[ORM\Column(name: 'deletion_date', type: 'datetime', nullable: true)]
    private ?DateTimeInterface $deletionDate = null;

    #[ORM\Column(name: 'title', type: 'string', length: 255, nullable: false)]
    private ?string $title = null;

    #[ORM\Column(name: 'search_url', type: 'string', length: 3000, nullable: false)]
    private ?string $searchUrl = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAccountId(): ?int
    {
        return $this->accountId;
    }

    public function setAccountId(int $accountId): self
    {
        $this->accountId = $accountId;

        return $this;
    }

    public function getDeleterId(): ?int
    {
        return $this->deleterId;
    }

    public function setDeleterId(?int $deleterId): self
    {
        $this->deleterId = $deleterId;

        return $this;
    }

    public function getDeletionDate(): ?DateTimeInterface
    {
        return $this->deletionDate;
    }

    public function setDeletionDate(?DateTimeInterface $deletionDate): self
    {
        $this->deletionDate = $deletionDate;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getSearchUrl(): ?string
    {
        return $this->searchUrl;
    }

    public function setSearchUrl(string $searchUrl): self
    {
        $this->searchUrl = $searchUrl;

        return $this;
    }
}
