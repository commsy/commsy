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

use App\Utils\EntityDatesTrait;
use App\Utils\EntityUsersTrait;
use DateTime;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'discussions')]
#[ORM\Index(columns: ['context_id'], name: 'context_id')]
#[ORM\Index(columns: ['creator_id'], name: 'creator_id')]
class Discussions
{
    use EntityDatesTrait;
    use EntityUsersTrait;

    #[ORM\Column(name: 'item_id', type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private int $itemId;

    #[ORM\Column(name: 'context_id', type: Types::INTEGER, nullable: true)]
    private ?int $contextId = null;

    #[ORM\Column(name: 'activation_date', type: Types::DATETIME_MUTABLE)]
    private ?DateTime $activationDate = null;

    #[ORM\Column(name: 'title', type: Types::STRING, length: 200, nullable: false)]
    private string $title;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private string $description;

    #[ORM\Column(name: 'latest_article_item_id', type: Types::INTEGER, nullable: true)]
    private ?int $latestArticleItemId = null;

    #[ORM\Column(name: 'latest_article_modification_date', type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?DateTimeInterface $latestArticleModificationDate = null;

    #[ORM\Column(name: 'status', type: Types::INTEGER, nullable: false)]
    private int $status = 1;

    #[ORM\Column(name: 'discussion_type', type: Types::STRING, length: 10, nullable: false)]
    private string $discussionType = 'simple';

    #[ORM\Column(name: 'public', type: Types::BOOLEAN, nullable: false)]
    private bool $public = false;

    #[ORM\Column(name: 'extras', type: Types::ARRAY, nullable: true)]
    private ?array $extras = null;

    /**
     * @var Discussionarticles[]|null
     */
    #[ORM\OneToMany(targetEntity: 'Discussionarticles', mappedBy: 'discussion')]
    private Collection $discussionarticles;

    public function __construct()
    {
        $this->discussionarticles = new ArrayCollection();
    }

    public function isIndexable()
    {
        return null === $this->deleter && null === $this->deletionDate;
    }

    public function getItemId(): int
    {
        return $this->itemId;
    }

    public function setContextId(?int $contextId): static
    {
        $this->contextId = $contextId;

        return $this;
    }

    public function getContextId(): ?int
    {
        return $this->contextId;
    }

    public function setActivationDate(DateTime $activationDate): static
    {
        $this->activationDate = $activationDate;

        return $this;
    }

    public function getActivationDate(): ?DateTime
    {
        return $this->activationDate;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function setLatestArticleItemId(?int $latestArticleItemId): static
    {
        $this->latestArticleItemId = $latestArticleItemId;

        return $this;
    }

    public function getLatestArticleItemId(): ?int
    {
        return $this->latestArticleItemId;
    }

    public function setLatestArticleModificationDate(?DateTime $latestArticleModificationDate): static
    {
        $this->latestArticleModificationDate = $latestArticleModificationDate;

        return $this;
    }

    public function getLatestArticleModificationDate(): ?DateTime
    {
        return $this->latestArticleModificationDate;
    }

    public function setStatus(int $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function setDiscussionType(string $discussionType): static
    {
        $this->discussionType = $discussionType;

        return $this;
    }

    public function getDiscussionType(): string
    {
        return $this->discussionType;
    }

    public function setPublic(bool $public): static
    {
        $this->public = $public;

        return $this;
    }

    public function getPublic(): bool
    {
        return $this->public;
    }

    public function setExtras(?array $extras): static
    {
        $this->extras = $extras;

        return $this;
    }

    public function getExtras(): ?array
    {
        return $this->extras;
    }

    public function addDiscussionarticle(Discussionarticles $discussionarticle): Materials
    {
        $this->discussionarticles[] = $discussionarticle;

        return $this;
    }

    public function removeDiscussionarticle(Discussionarticles $discussionarticle): void
    {
        $this->discussionarticles->removeElement($discussionarticle);
    }

    public function getDiscussionarticles(): Collection
    {
        return $this->discussionarticles;
    }
}
