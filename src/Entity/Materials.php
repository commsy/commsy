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
use App\Repository\MaterialsRepository;
use App\Utils\EntityDatesTrait;
use App\Utils\EntityUsersTrait;
use DateTime;
use DateTimeInterface;
use App\Doctrine\Type\PhpArrayType;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use App\Export\ExportableEntry;
use App\State\PageSize;
use App\State\RubricEntryProvider;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: MaterialsRepository::class)]
#[ORM\Table(name: 'materials')]
#[ORM\Index(name: 'context_id', columns: ['context_id'])]
#[ORM\Index(name: 'creator_id', columns: ['creator_id'])]
#[ORM\Index(name: 'modifier_id', columns: ['modifier_id'])]
#[ApiResource(
    shortName: 'Material',
    operations: [
        new GetCollection(
            uriTemplate: '/rooms/{roomId}/materials',
            uriVariables: ['roomId' => new Link(fromClass: Room::class, identifiers: ['itemId'])],
        ),
        new Get(
            uriTemplate: '/materials/{itemId}',
            // The version is not part of the address: the room shows one.
            uriVariables: ['itemId' => new Link(fromClass: Materials::class, identifiers: ['itemId'])],
        ),
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
class Materials implements ExportableEntry
{
    use EntityDatesTrait;
    use EntityUsersTrait;

    /**
     * The structured citation fields legacy keeps in `extras`, by the name they
     * carry in the export. The name is the key without its `BIB_` prefix, in
     * camel case — nothing is invented, so it maps back one to one.
     *
     * `author` and `publishing_date` are columns and stand on the material
     * itself; the kind and the free text are handled apart from these.
     */
    private const BIBLIOGRAPHIC_FIELDS = [
        'address' => 'BIB_ADDRESS',
        'booktitle' => 'BIB_BOOKTITLE',
        'documentEditor' => 'BIB_DOCUMENT_EDITOR',
        'documentMaintainer' => 'BIB_DOCUMENT_MAINTAINER',
        'documentReleaseDate' => 'BIB_DOCUMENT_RELEASE_DATE',
        'documentReleaseNumber' => 'BIB_DOCUMENT_RELEASE_NUMBER',
        'edition' => 'BIB_EDITION',
        'editor' => 'BIB_EDITOR',
        'faculty' => 'BIB_FACULTY',
        'fotoCopyright' => 'BIB_FOTO_COPYRIGHT',
        'fotoDate' => 'BIB_FOTO_DATE',
        'fotoReason' => 'BIB_FOTO_REASON',
        'isbn' => 'BIB_ISBN',
        'issn' => 'BIB_ISSN',
        'issue' => 'BIB_ISSUE',
        'journal' => 'BIB_JOURNAL',
        'pages' => 'BIB_PAGES',
        'publisher' => 'BIB_PUBLISHER',
        'series' => 'BIB_SERIES',
        'thesisKind' => 'BIB_THESIS_KIND',
        'university' => 'BIB_UNIVERSITY',
        'url' => 'BIB_URL',
        'urlDate' => 'BIB_URL_DATE',
        'volume' => 'BIB_VOLUME',
    ];

    #[ORM\Id]
    #[ORM\Column(name: 'item_id', type: Types::INTEGER)]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    #[Groups(['export'])]
    #[ApiProperty(description: 'The entry\'s id, unique across all rubrics.')]
    private int $itemId;

    #[ORM\Id]
    #[ORM\Column(name: 'version_id', type: Types::INTEGER)]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    #[Groups(['export'])]
    #[ApiProperty(description: 'The version the room shows; earlier versions are not part of the output.')]
    private int $versionId;

    #[ORM\Column(name: 'context_id', type: Types::INTEGER, nullable: true)]
    private ?int $contextId = null;

    #[ORM\Column(name: 'activation_date', type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Groups(['export'])]
    #[ApiProperty(
        description: 'When the entry becomes visible in the room; null if it is visible right away. '
            .'Entries not visible yet are part of the export.',
    )]
    private ?DateTime $activationDate = null;

    #[ORM\Column(name: 'title', type: Types::STRING, length: 255, nullable: false)]
    #[Groups(['export'])]
    private string $title;

    #[ORM\Column(name: 'description', type: Types::TEXT, length: 16_777_215, nullable: true)]
    #[Groups(['export'])]
    #[ApiProperty(
        description: 'As HTML, the way CommSy stores it. For a material written in Etherpad, the content '
            .'of the pad, which CommSy takes over when the material is saved.',
    )]
    private ?string $description = null;

    #[ORM\Column(name: 'author', type: Types::STRING, length: 200, nullable: true)]
    #[Groups(['export'])]
    #[ApiProperty(description: 'Its author or authors, as entered.')]
    private ?string $author = null;

    #[ORM\Column(name: 'publishing_date', type: Types::STRING, length: 20, nullable: true)]
    #[Groups(['export'])]
    #[ApiProperty(description: 'Year or date of publication, as free text.')]
    private ?string $publishingDate = null;

    #[ORM\Column(name: 'public', type: Types::BOOLEAN, nullable: false)]
    private bool $public = false;

    #[ORM\Column(name: 'world_public', type: Types::BOOLEAN, nullable: false)]
    #[Groups(['export'])]
    #[ApiProperty(description: 'Whether it is published beyond the room.')]
    private bool $worldPublic = false;

    #[ORM\Column(name: 'extras', type: PhpArrayType::NAME, nullable: true)]
    private ?array $extras = null;

    #[ORM\Column(name: 'new_hack', type: Types::BOOLEAN, nullable: false)]
    private bool $newHack = false;

    #[ORM\Column(name: 'copy_of', type: Types::INTEGER, nullable: true)]
    private ?int $copyOf = null;

    #[ORM\Column(name: 'workflow_status', type: Types::STRING, length: 255, nullable: false)]
    #[Groups(['export'])]
    #[ApiProperty(
        description: 'The workflow\'s traffic light as stored: 0_green, 1_yellow, 2_red or 3_none. '
            .'May be empty.',
    )]
    private string $workflowStatus = '3_none';

    #[ORM\Column(name: 'workflow_resubmission_date', type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Groups(['export'])]
    #[ApiProperty(description: 'When the workflow asks for it to be looked at again.')]
    private ?DateTimeInterface $workflowResubmissionDate = null;

    #[ORM\Column(name: 'workflow_validity_date', type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Groups(['export'])]
    #[ApiProperty(description: 'Until when the workflow considers it valid.')]
    private ?DateTimeInterface $workflowValidityDate = null;

    /**
     * The room's own licence this material was filed under, if any. A room
     * defines its licences; the material points at one.
     */
    #[ORM\Column(name: 'license_id', type: Types::INTEGER, nullable: true)]
    #[Groups(['export'])]
    #[ApiProperty(description: 'Its licence, as an id into the room\'s licences.')]
    private ?int $licenseId = null;

    #[ORM\OneToMany(mappedBy: 'material', targetEntity: 'Section')]
    private Collection $sections;

    public function __construct()
    {
        $this->sections = new ArrayCollection();
    }

    /**
     * Add section.
     */
    public function addSection(Section $section): static
    {
        $this->sections[] = $section;

        return $this;
    }

    public function removeSection(Section $section): void
    {
        $this->sections->removeElement($section);
    }

    public function getSections(): Collection
    {
        return $this->sections;
    }

    public function setItemId(int $itemId): static
    {
        $this->itemId = $itemId;

        return $this;
    }

    public function getItemId(): int
    {
        return $this->itemId;
    }

    public function setVersionId(int $versionId): static
    {
        $this->versionId = $versionId;

        return $this;
    }

    public function getVersionId(): int
    {
        return $this->versionId;
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

    /**
     * A material lives in a room; its context is never anything else.
     */
    public function getRoomId(): int
    {
        return (int) $this->contextId;
    }

    public function getLicenseId(): ?int
    {
        return $this->licenseId;
    }

    /**
     * The citation a material carries, if it carries one.
     *
     * Legacy keeps it in `extras`: what kind of source it is, the citation as
     * free text, and — depending on the kind — a few dozen structured fields. All
     * of that is content rather than bookkeeping, so it stands as a field of its
     * own while the rest of `extras` stays out.
     *
     * Every known field that holds something is handed on, whatever the kind:
     * the interface shows only those of the chosen kind, but the others stay in
     * the row when the kind is changed, and an importer is better placed to
     * decide what it needs than the export is. Values are handed on as stored,
     * editor markers and all — an export does not rewrite what the room shows.
     *
     * @return array<string, string|null>|null
     */
    #[ApiProperty(schema: [
        'type' => ['object', 'null'],
        'description' => 'Its citation; null if none is given. Every structured field that holds something is '
            .'included, whatever the kind; empty ones are left out. Field names are CommSy\'s keys without their '
            .'BIB_ prefix.',
        'additionalProperties' => false,
        'required' => ['kind', 'text'],
        'properties' => [
            'kind' => [
                'type' => ['string', 'null'],
                'description' => 'The kind of source as chosen, such as book, article or website. Older rooms hold '
                    .'kinds the interface no longer offers.',
            ],
            'text' => ['type' => ['string', 'null'], 'description' => 'The citation as free text, as HTML.'],
            'address' => ['type' => 'string'],
            'booktitle' => ['type' => 'string'],
            'documentEditor' => ['type' => 'string'],
            'documentMaintainer' => ['type' => 'string'],
            'documentReleaseDate' => ['type' => 'string'],
            'documentReleaseNumber' => ['type' => 'string'],
            'edition' => ['type' => 'string'],
            'editor' => ['type' => 'string'],
            'faculty' => ['type' => 'string'],
            'fotoCopyright' => ['type' => 'string'],
            'fotoDate' => ['type' => 'string'],
            'fotoReason' => ['type' => 'string'],
            'isbn' => ['type' => 'string'],
            'issn' => ['type' => 'string'],
            'issue' => ['type' => 'string'],
            'journal' => ['type' => 'string'],
            'pages' => ['type' => 'string'],
            'publisher' => ['type' => 'string'],
            'series' => ['type' => 'string'],
            'thesisKind' => ['type' => 'string'],
            'university' => ['type' => 'string'],
            'url' => ['type' => 'string'],
            'urlDate' => ['type' => 'string'],
            'volume' => ['type' => 'string'],
        ],
    ])]
    #[Groups(['export'])]
    public function getBibliographic(): ?array
    {
        $kind = $this->extras['BIB_KIND'] ?? null;
        $text = $this->extras['BIBLIOGRAPHIC'] ?? null;

        // `none` is legacy's way of saying no kind was chosen.
        $kind = in_array($kind, ['', 'none'], true) ? null : $kind;
        $text = '' === $text ? null : $text;

        $citation = ['kind' => $kind, 'text' => $text];

        foreach (self::BIBLIOGRAPHIC_FIELDS as $name => $key) {
            $value = $this->extras[$key] ?? null;

            if (is_string($value) && '' !== trim($value)) {
                $citation[$name] = $value;
            }
        }

        return null === $kind && null === $text && 2 === count($citation) ? null : $citation;
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

    public function setAuthor(?string $author): static
    {
        $this->author = $author;

        return $this;
    }

    public function getAuthor(): ?string
    {
        return $this->author;
    }

    public function setPublishingDate(?string $publishingDate): static
    {
        $this->publishingDate = $publishingDate;

        return $this;
    }

    public function getPublishingDate(): ?string
    {
        return $this->publishingDate;
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

    public function setWorldPublic(bool $worldPublic): static
    {
        $this->worldPublic = $worldPublic;

        return $this;
    }

    public function getWorldPublic(): bool
    {
        return $this->worldPublic;
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

    public function setNewHack(bool $newHack): static
    {
        $this->newHack = $newHack;

        return $this;
    }

    public function getNewHack(): bool
    {
        return $this->newHack;
    }

    public function setCopyOf(?int $copyOf): static
    {
        $this->copyOf = $copyOf;

        return $this;
    }

    public function getCopyOf(): ?int
    {
        return $this->copyOf;
    }

    public function setWorkflowStatus(string $workflowStatus): static
    {
        $this->workflowStatus = $workflowStatus;

        return $this;
    }

    public function getWorkflowStatus(): string
    {
        return $this->workflowStatus;
    }

    public function setWorkflowResubmissionDate(?DateTimeInterface $workflowResubmissionDate): static
    {
        $this->workflowResubmissionDate = $workflowResubmissionDate;

        return $this;
    }

    public function getWorkflowResubmissionDate(): ?DateTimeInterface
    {
        return $this->workflowResubmissionDate;
    }

    public function setWorkflowValidityDate(?DateTimeInterface $workflowValidityDate): static
    {
        $this->workflowValidityDate = $workflowValidityDate;

        return $this;
    }

    public function getWorkflowValidityDate(): ?DateTimeInterface
    {
        return $this->workflowValidityDate;
    }

    public function isIndexable()
    {
        return null == $this->deleter && null == $this->deletionDate;
    }

}
