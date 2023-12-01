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
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Discussions.
 */
#[ORM\Entity]
#[ORM\Table(name: 'discussions')]
#[ORM\Index(columns: ['context_id'], name: 'context_id')]
#[ORM\Index(columns: ['creator_id'], name: 'creator_id')]
class Discussions
{
    use EntityDatesTrait;

    #[ORM\Column(name: 'item_id', type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private int $itemId;

    #[ORM\Column(name: 'context_id', type: Types::INTEGER, nullable: true)]
    private ?int $contextId = null;

    #[ORM\ManyToOne(targetEntity: 'User')]
    #[ORM\JoinColumn(name: 'creator_id', referencedColumnName: 'item_id')]
    private ?User $creator = null;

    #[ORM\ManyToOne(targetEntity: 'User')]
    #[ORM\JoinColumn(name: 'modifier_id', referencedColumnName: 'item_id')]
    private ?User $modifier = null;

    #[ORM\Column(name: 'deleter_id', type: Types::INTEGER, nullable: true)]
    private ?int $deleterId = null;

    #[ORM\Column(name: 'activation_date', type: Types::DATETIME_MUTABLE)]
    private ?DateTime $activationDate = null;

    /**
     * @var string
     */
    #[ORM\Column(name: 'title', type: Types::STRING, length: 200, nullable: false)]
    private string $title;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private string $description;

    /**
     * @var int
     */
    #[ORM\Column(name: 'latest_article_item_id', type: Types::INTEGER, nullable: true)]
    private ?int $latestArticleItemId = null;

    /**
     * @var \DateTimeInterface
     */
    #[ORM\Column(name: 'latest_article_modification_date', type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $latestArticleModificationDate = null;

    #[ORM\Column(name: 'status', type: Types::INTEGER, nullable: false)]
    private string $status = '1';

    #[ORM\Column(name: 'discussion_type', type: Types::STRING, length: 10, nullable: false)]
    private string $discussionType = 'simple';

    #[ORM\Column(name: 'public', type: Types::BOOLEAN, nullable: false)]
    private string $public = '0';

    /**
     * @var string
     */
    #[ORM\Column(name: 'extras', type: Types::TEXT, length: 65535, nullable: true)]
    private ?string $extras = null;

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
        return null == $this->deleterId && null == $this->deletionDate;
    }

    public function getItemId(): int
    {
        return $this->itemId;
    }

    /**
     * Set contextId.
     *
     * @param int $contextId
     *
     * @return Discussions
     */
    public function setContextId($contextId)
    {
        $this->contextId = $contextId;

        return $this;
    }

    /**
     * Get contextId.
     *
     * @return int
     */
    public function getContextId()
    {
        return $this->contextId;
    }

    /**
     * Set deleterId.
     *
     * @param int $deleterId
     *
     * @return Discussions
     */
    public function setDeleterId($deleterId)
    {
        $this->deleterId = $deleterId;

        return $this;
    }

    /**
     * Get deleterId.
     *
     * @return int
     */
    public function getDeleterId()
    {
        return $this->deleterId;
    }

    /**
     * Set activationDate.
     */
    public function setActivationDate(DateTime $activationDate): self
    {
        $this->activationDate = $activationDate;

        return $this;
    }

    /**
     * Get activationDate.
     */
    public function getActivationDate(): ?DateTime
    {
        return $this->activationDate;
    }

    /**
     * Set title.
     *
     * @param string $title
     *
     * @return Discussions
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title.
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Set latestArticleItemId.
     *
     * @param int $latestArticleItemId
     *
     * @return Discussions
     */
    public function setLatestArticleItemId($latestArticleItemId)
    {
        $this->latestArticleItemId = $latestArticleItemId;

        return $this;
    }

    /**
     * Get latestArticleItemId.
     *
     * @return int
     */
    public function getLatestArticleItemId()
    {
        return $this->latestArticleItemId;
    }

    /**
     * Set latestArticleModificationDate.
     *
     * @param DateTime $latestArticleModificationDate
     *
     * @return Discussions
     */
    public function setLatestArticleModificationDate($latestArticleModificationDate)
    {
        $this->latestArticleModificationDate = $latestArticleModificationDate;

        return $this;
    }

    /**
     * Get latestArticleModificationDate.
     *
     * @return DateTime
     */
    public function getLatestArticleModificationDate()
    {
        return $this->latestArticleModificationDate;
    }

    /**
     * Set status.
     *
     * @param int $status
     *
     * @return Discussions
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status.
     *
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set discussionType.
     *
     * @param string $discussionType
     *
     * @return Discussions
     */
    public function setDiscussionType($discussionType)
    {
        $this->discussionType = $discussionType;

        return $this;
    }

    /**
     * Get discussionType.
     *
     * @return string
     */
    public function getDiscussionType()
    {
        return $this->discussionType;
    }

    /**
     * Set public.
     *
     * @param bool $public
     *
     * @return Discussions
     */
    public function setPublic($public)
    {
        $this->public = $public;

        return $this;
    }

    /**
     * Get public.
     *
     * @return bool
     */
    public function getPublic()
    {
        return $this->public;
    }

    /**
     * Set extras.
     *
     * @param string $extras
     *
     * @return Discussions
     */
    public function setExtras($extras)
    {
        $this->extras = $extras;

        return $this;
    }

    /**
     * Get extras.
     *
     * @return string
     */
    public function getExtras()
    {
        return $this->extras;
    }

    /**
     * Set creator.
     *
     * @return Discussions
     */
    public function setCreator(User $creator = null)
    {
        $this->creator = $creator;

        return $this;
    }

    /**
     * Get creator.
     *
     * @return User
     */
    public function getCreator()
    {
        return $this->creator;
    }

    /**
     * Set modifier.
     *
     * @return Discussions
     */
    public function setModifier(User $modifier = null)
    {
        $this->modifier = $modifier;

        return $this;
    }

    /**
     * Get modifier.
     *
     * @return User
     */
    public function getModifier()
    {
        return $this->modifier;
    }

    /**
     * Add discussionarticle.
     *
     * @return Materials
     */
    public function addDiscussionarticle(Discussionarticles $discussionarticle)
    {
        $this->discussionarticles[] = $discussionarticle;

        return $this;
    }

    /**
     * Remove discussionarticle.
     */
    public function removeDiscussionarticle(Discussionarticles $discussionarticle)
    {
        $this->discussionarticles->removeElement($discussionarticle);
    }

    /**
     * Get discussionarticles.
     *
     * @return Collection
     */
    public function getDiscussionarticles()
    {
        return $this->discussionarticles;
    }
}
