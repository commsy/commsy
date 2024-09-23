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

use App\Repository\LabelRepository;
use App\Utils\EntityDatesTrait;
use App\Validator\Constraints as CommsyAssert;
use DateTime;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LabelRepository::class)]
#[ORM\Table(name: 'labels')]
#[ORM\Index(columns: ['context_id'], name: 'context_id')]
#[ORM\Index(columns: ['creator_id'], name: 'creator_id')]
#[ORM\Index(columns: ['type'], name: 'type')]
#[CommsyAssert\UniqueLabelName]
class Labels
{
    use EntityDatesTrait;

    #[ORM\Column(name: 'item_id', type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private ?int $itemId = null;

    #[ORM\Column(name: 'context_id', type: Types::INTEGER, nullable: false)]
    private int $contextId;

    #[ORM\ManyToOne(targetEntity: 'User')]
    #[ORM\JoinColumn(name: 'creator_id', referencedColumnName: 'item_id')]
    private ?User $creator = null;

    #[ORM\ManyToOne(targetEntity: 'User')]
    #[ORM\JoinColumn(name: 'modifier_id', referencedColumnName: 'item_id')]
    private ?User $modifier = null;

    #[ORM\ManyToOne(targetEntity: 'User')]
    #[ORM\JoinColumn(name: 'deleter_id', referencedColumnName: 'item_id')]
    private ?User $deleter = null;

    #[ORM\Column(name: 'activation_date', type: Types::DATETIME_MUTABLE)]
    private ?DateTime $activationDate = null;

    #[ORM\Column(name: 'name', type: Types::STRING, length: 255, nullable: false)]
    private string $name;

    #[ORM\Column(name: 'description', type: Types::TEXT, length: 16_777_215, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(name: 'type', type: Types::STRING, length: 15, nullable: false)]
    private string $type;

    #[ORM\Column(name: 'extras', type: Types::ARRAY, nullable: true)]
    private ?array $extras = null;

    #[ORM\Column(name: 'public', type: Types::BOOLEAN, nullable: false)]
    private bool $public = false;

    public function __construct()
    {
        $this->creationDate = new DateTime();
        $this->modificationDate = new DateTime();
    }

    public function isIndexable()
    {
        return null == $this->deleter && null == $this->deletionDate &&
                'ALL' != $this->name && 'GROUP_ALL_DESC' != $this->description && in_array($this->type, [
                    'group',
                    'topic',
                    'institution',
                ])
        ;
    }

    public function getItemId(): ?int
    {
        return $this->itemId;
    }

    public function setContextId(int $contextId): static
    {
        $this->contextId = $contextId;

        return $this;
    }

    public function getContextId(): int
    {
        return $this->contextId;
    }

    public function setActivationDate(?DateTime $activationDate): self
    {
        $this->activationDate = $activationDate;

        return $this;
    }

    public function getActivationDate(): ?DateTime
    {
        return $this->activationDate;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getType(): string
    {
        return $this->type;
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

    public function setPublic(bool $public): static
    {
        $this->public = $public;

        return $this;
    }

    public function getPublic(): bool
    {
        return $this->public;
    }

    public function setCreator(?User $creator): static
    {
        $this->creator = $creator;

        return $this;
    }

    public function getCreator(): ?User
    {
        return $this->creator;
    }

    public function setModifier(?User $modifier): static
    {
        $this->modifier = $modifier;

        return $this;
    }

    public function getModifier(): ?User
    {
        return $this->modifier;
    }

    public function setDeleter(?User $deleter): static
    {
        $this->deleter = $deleter;

        return $this;
    }

    public function getDeleter(): ?User
    {
        return $this->deleter;
    }

    public function getTitle(): string
    {
        return $this->getName();
    }
}
