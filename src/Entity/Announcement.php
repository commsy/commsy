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
use Doctrine\ORM\Mapping as ORM;

/**
 * Announcement.
 */
#[ORM\Entity]
#[ORM\Table(name: 'announcement')]
#[ORM\Index(name: 'context_id', columns: ['context_id'])]
#[ORM\Index(name: 'creator_id', columns: ['creator_id'])]
class Announcement
{
    use EntityDatesTrait;

    #[ORM\Column(name: 'item_id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private int $itemId;

    /**
     * @var int
     */
    #[ORM\Column(name: 'context_id', type: 'integer', nullable: true)]
    private $contextId;

    #[ORM\ManyToOne(targetEntity: 'User')]
    #[ORM\JoinColumn(name: 'creator_id', referencedColumnName: 'item_id')]
    private ?User $creator = null;

    #[ORM\ManyToOne(targetEntity: 'User')]
    #[ORM\JoinColumn(name: 'modifier_id', referencedColumnName: 'item_id')]
    private ?User $modifier = null;

    #[ORM\ManyToOne(targetEntity: 'User')]
    #[ORM\JoinColumn(name: 'deleter_id', referencedColumnName: 'item_id')]
    private ?User $deleter = null;

    #[ORM\Column(name: 'activation_date', type: 'datetime')]
    private ?DateTime $activationDate = null;

    #[ORM\Column(name: 'title', type: 'string', length: 255)]
    private string $title;

    #[ORM\Column(name: 'description', type: 'text', length: 16_777_215, nullable: true)]
    private ?string $description;

    #[ORM\Column(name: 'enddate', type: 'datetime')]
    private DateTime $enddate;

    #[ORM\Column(name: 'public', type: 'boolean', nullable: false)]
    private string $public = '0';

    #[ORM\Column(name: 'extras', type: 'text', length: 65535, nullable: true)]
    private $extras;

    public function __construct()
    {
        $this->enddate = new DateTime();
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
     * @return Announcement
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

    public function isIndexable()
    {
        return null == $this->deleter && null == $this->deletionDate;
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
     * @return Announcement
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

    /**
     * Set description.
     *
     * @param string $description
     *
     * @return Announcement
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description.
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    public function setEnddate(DateTime $enddate): self
    {
        $this->enddate = $enddate;

        return $this;
    }

    public function getEnddate(): DateTime
    {
        return $this->enddate;
    }

    /**
     * Set public.
     *
     * @param bool $public
     *
     * @return Announcement
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
     * @return Announcement
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
     * @return Announcement
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
     * @return Announcement
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
     * Set deleter.
     *
     * @return Announcement
     */
    public function setDeleter(User $deleter = null)
    {
        $this->deleter = $deleter;

        return $this;
    }

    /**
     * Get deleter.
     *
     * @return User
     */
    public function getDeleter()
    {
        return $this->deleter;
    }
}
