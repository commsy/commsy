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
use App\Utils\EntityDatesTrait;
use App\Utils\EntityUsersTrait;
use Doctrine\DBAL\Types\Types;
use App\Export\ExportableEntry;
use App\Repository\AnnotationsRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

/**
 * Annotations.
 */
#[ORM\Entity(repositoryClass: AnnotationsRepository::class)]
#[ORM\Table(name: 'annotations')]
#[ORM\Index(columns: ['context_id'], name: 'context_id')]
#[ORM\Index(columns: ['creator_id'], name: 'creator_id')]
#[ORM\Index(columns: ['linked_item_id'], name: 'linked_item_id')]
class Annotations implements ExportableEntry
{
    use EntityDatesTrait;
    use EntityUsersTrait;

    // items.item_id is the id source; the annotations row reuses it
    #[ORM\Column(name: 'item_id', type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    #[Groups(['export'])]
    #[ApiProperty(description: 'The entry\'s id, unique across all rubrics.')]
    private int $itemId;

    #[ORM\Column(name: 'context_id', type: Types::INTEGER, nullable: true)]
    private ?int $contextId = null;

    #[ORM\Column(name: 'description', type: Types::TEXT, length: 16_777_215, nullable: true)]
    #[Groups(['export'])]
    #[ApiProperty(description: 'The text of the comment, as HTML.')]
    private ?string $description = null;

    #[ORM\Column(name: 'linked_item_id', type: Types::INTEGER)]
    private ?int $linkedItemId = 0;

    #[ORM\Column(name: 'linked_version_id', type: Types::INTEGER)]
    private ?int $linkedVersionId = 0;

    #[ORM\Column(name: 'extras', type: Types::TEXT, length: 65535, nullable: true)]
    private ?string $extras = null;

    #[ORM\Column(name: 'public', type: Types::BOOLEAN)]
    private ?bool $public = false;

    public function getItemId(): int
    {
        // A row that was never written has no id yet.
        return $this->itemId ?? 0;
    }

    public function getContextId(): ?int
    {
        return $this->contextId;
    }

    /**
     * A comment lives in the room the entry it belongs to lives in.
     */
    public function getRoomId(): int
    {
        return (int) $this->contextId;
    }

    /**
     * Only material and section keep versions.
     */
    public function getVersionId(): int
    {
        return self::UNVERSIONED;
    }

    /**
     * The comment itself. The entity never needed a reader for it before, the
     * interface having read the legacy item instead.
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getLinkedItemId(): ?int
    {
        return $this->linkedItemId;
    }

    public function getPublic(): bool
    {
        return (bool) $this->public;
    }
}
