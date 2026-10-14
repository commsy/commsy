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
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use App\Repository\LabelRepository;
use App\Rubric\Label\LabelType;
use App\Utils\EntityDatesTrait;
use App\Utils\EntityUsersTrait;
use App\Validator\Constraints as CommsyAssert;
use DateTime;
use App\Doctrine\Type\PhpArrayType;
use Doctrine\DBAL\Types\Types;
use App\Export\ExportableEntry;
use App\State\PageSize;
use App\State\RubricEntryProvider;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: LabelRepository::class)]
#[ORM\Table(name: 'labels')]
#[ORM\Index(columns: ['context_id'], name: 'context_id')]
#[ORM\Index(columns: ['creator_id'], name: 'creator_id')]
#[ORM\Index(columns: ['type'], name: 'type')]
#[CommsyAssert\UniqueLabelName]
#[ApiResource(
    shortName: 'Label',
    operations: [
        new GetCollection(
            uriTemplate: '/rooms/{roomId}/labels',
            uriVariables: ['roomId' => new Link(fromClass: Room::class, identifiers: ['itemId'])],
        ),
        new Get(uriTemplate: '/labels/{itemId}'),
    ],
    // The export's field list and shape: the interface hands out what the export
    // carries, a null included.
    normalizationContext: ['groups' => ['export'], 'skip_null_values' => false],
    security: "is_granted('ROLE_API_READ')",
    provider: RubricEntryProvider::class,
    paginationItemsPerPage: PageSize::DEFAULT,
    paginationMaximumItemsPerPage: PageSize::MAXIMUM,
    paginationClientItemsPerPage: true,
)]
class Labels implements ExportableEntry
{
    use EntityDatesTrait;
    use EntityUsersTrait;

    #[ORM\Column(name: 'item_id', type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[Groups(['export'])]
    #[ApiProperty(description: 'The entry\'s id, unique across all rubrics.')]
    private ?int $itemId = null;

    #[ORM\Column(name: 'context_id', type: Types::INTEGER, nullable: false)]
    private int $contextId;

    #[ORM\Column(name: 'activation_date', type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Groups(['export'])]
    #[ApiProperty(
        description: 'When the entry becomes visible in the room; null if it is visible right away. '
            .'Entries not visible yet are part of the export.',
    )]
    private ?DateTime $activationDate = null;

    #[ORM\Column(name: 'name', type: Types::STRING, length: 255, nullable: false)]
    #[Groups(['export'])]
    #[ApiProperty(description: 'The title of the topic, the group or the institution.')]
    private string $name;

    #[ORM\Column(name: 'description', type: Types::TEXT, length: 16_777_215, nullable: true)]
    #[Groups(['export'])]
    #[ApiProperty(description: 'As HTML, the way CommSy stores it.')]
    private ?string $description = null;

    #[ORM\Column(name: 'type', type: Types::STRING, length: 15, nullable: false)]
    #[Groups(['export'])]
    #[ApiProperty(schema: [
        'type' => 'string',
        'enum' => [LabelType::Topic->value, LabelType::Group->value, LabelType::Institution->value],
        'description' => 'What kind of label it is.',
    ])]
    private string $type;

    #[ORM\Column(name: 'extras', type: PhpArrayType::NAME, nullable: true)]
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

    public function getItemId(): int
    {
        // A row that was never written has no id yet.
        return $this->itemId ?? 0;
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

    /**
     * A label lives in a room; its context is never anything else.
     */
    public function getRoomId(): int
    {
        return $this->contextId;
    }

    /**
     * Only material and section keep versions.
     */
    public function getVersionId(): int
    {
        return self::UNVERSIONED;
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

    public function getTitle(): string
    {
        return $this->getName();
    }
}
