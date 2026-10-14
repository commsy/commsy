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

use ApiPlatform\Metadata\ApiProperty;
use App\Export\ExportableEntry;
use App\Repository\SectionRepository;
use App\Utils\EntityDatesTrait;
use App\Utils\EntityUsersTrait;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: SectionRepository::class)]
#[ORM\Table(name: 'section')]
#[ORM\Index(name: 'context_id', columns: ['context_id'])]
#[ORM\Index(name: 'creator_id', columns: ['creator_id'])]
#[ORM\Index(name: 'material_item_id', columns: ['material_item_id'])]
class Section implements ExportableEntry
{
    use EntityDatesTrait;
    use EntityUsersTrait;

    #[ORM\Column(name: 'item_id', type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    #[Groups(['export'])]
    #[ApiProperty(description: 'The entry\'s id, unique across all rubrics.')]
    private int $itemId;

    #[ORM\Id]
    #[ORM\Column(name: 'version_id', type: Types::INTEGER)]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    #[Groups(['export'])]
    #[ApiProperty(description: 'The version of the material it belongs to.')]
    private int $versionId;

    #[ORM\Column(name: 'context_id', type: Types::INTEGER)]
    private int $contextId;

    #[ORM\Column(name: 'title', type: Types::STRING, length: 255, nullable: false)]
    #[Groups(['export'])]
    private string $title;

    #[ORM\Column(name: 'description', type: Types::TEXT, length: 16_777_215, nullable: true)]
    #[Groups(['export'])]
    #[ApiProperty(description: 'As HTML, the way CommSy stores it.')]
    private ?string $description = null;

    #[ORM\Column(name: 'number', type: Types::SMALLINT, nullable: false)]
    #[Groups(['export'])]
    #[ApiProperty(description: 'Its place within the material.')]
    private int $number = 0;

    #[ORM\ManyToOne(targetEntity: 'Materials', inversedBy: 'sections')]
    #[ORM\JoinColumn(name: 'material_item_id', referencedColumnName: 'item_id')]
    #[ORM\JoinColumn(name: 'version_id', referencedColumnName: 'version_id')]
    private ?Materials $material = null;

    #[ORM\Column(name: 'extras', type: Types::ARRAY, nullable: true)]
    private ?array $extras = null;

    #[ORM\Column(name: 'public', type: Types::BOOLEAN, nullable: false)]
    private bool $public = false;

    public function setItemId(int $itemId)
    {
        $this->itemId = $itemId;

        return $this;
    }

    public function getItemId(): int
    {
        return $this->itemId;
    }

    public function setVersionId(int $versionId)
    {
        $this->versionId = $versionId;

        return $this;
    }

    public function getVersionId(): int
    {
        return $this->versionId;
    }

    public function setContextId(int $contextId)
    {
        $this->contextId = $contextId;

        return $this;
    }

    public function getContextId(): int
    {
        return $this->contextId;
    }

    /**
     * A section lives in the room its material lives in.
     */
    public function getRoomId(): int
    {
        return $this->contextId;
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

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setNumber(int $number): static
    {
        $this->number = $number;

        return $this;
    }

    public function getNumber(): int
    {
        return $this->number;
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

    public function setMaterial(?Materials $material): static
    {
        $this->material = $material;

        return $this;
    }

    public function getMaterial(): ?Materials
    {
        return $this->material;
    }
}
