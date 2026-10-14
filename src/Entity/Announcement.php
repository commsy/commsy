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
use App\Export\ExportableEntry;
use App\State\PageSize;
use App\State\RubricEntryProvider;
use App\Utils\EntityDatesTrait;
use App\Utils\EntityUsersTrait;
use DateTime;
use Doctrine\DBAL\Types\Types;
use App\Repository\AnnouncementRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

/**
 * Announcement.
 */
#[ORM\Entity(repositoryClass: AnnouncementRepository::class)]
#[ORM\Table(name: 'announcement')]
#[ORM\Index(columns: ['context_id'], name: 'context_id')]
#[ORM\Index(columns: ['creator_id'], name: 'creator_id')]
#[ApiResource(
    operations: [
        new GetCollection(
            uriTemplate: '/rooms/{roomId}/announcements',
            uriVariables: ['roomId' => new Link(fromClass: Room::class, identifiers: ['itemId'])],
        ),
        new Get(uriTemplate: '/announcements/{itemId}'),
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
class Announcement implements ExportableEntry
{
    use EntityDatesTrait;
    use EntityUsersTrait;

    #[ORM\Column(name: 'item_id', type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[Groups(['export'])]
    #[ApiProperty(description: 'The entry\'s id, unique across all rubrics.')]
    private int $itemId;

    #[ORM\Column(name: 'context_id', type: Types::INTEGER, nullable: true)]
    private ?int $contextId = null;

    /**
     * An entry not activated yet is part of the export; the date says so.
     */
    #[ORM\Column(name: 'activation_date', type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Groups(['export'])]
    #[ApiProperty(
        description: 'When the entry becomes visible in the room; null if it is visible right away. '
            .'Entries not visible yet are part of the export.',
    )]
    private ?DateTime $activationDate = null;

    #[ORM\Column(name: 'title', type: Types::STRING, length: 255)]
    #[Groups(['export'])]
    private string $title;

    #[ORM\Column(name: 'description', type: Types::TEXT, length: 16_777_215, nullable: true)]
    #[Groups(['export'])]
    #[ApiProperty(description: 'As HTML, the way CommSy stores it.')]
    private ?string $description = null;

    /**
     * When the announcement stops being shown in the room. It stays in the
     * export either way — having run out is not the same as never having been
     * said.
     */
    #[ORM\Column(name: 'enddate', type: Types::DATETIME_MUTABLE)]
    #[Groups(['export'])]
    #[ApiProperty(
        description: 'Until when the room shows the announcement. Announcements past it are part of the '
            .'export.',
    )]
    private DateTime $enddate;

    #[ORM\Column(name: 'public', type: Types::BOOLEAN, nullable: false)]
    private bool $public = false;

    #[ORM\Column(name: 'extras', type: Types::ARRAY, length: 65535, nullable: true)]
    private ?array $extras = null;

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
    public function setContextId($contextId): Announcement
    {
        $this->contextId = $contextId;

        return $this;
    }

    public function getContextId(): ?int
    {
        return $this->contextId;
    }

    /**
     * An announcement lives in a room; its context is never anything else.
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

    public function setTitle(string $title): Announcement
    {
        $this->title = $title;

        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setDescription($description): Announcement
    {
        $this->description = $description;

        return $this;
    }

    public function getDescription(): ?string
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

    public function setPublic(bool $public): Announcement
    {
        $this->public = $public;

        return $this;
    }

    public function getPublic(): bool
    {
        return $this->public;
    }

    public function setExtras(array $extras): Announcement
    {
        $this->extras = $extras;

        return $this;
    }

    public function getExtras(): ?array
    {
        return $this->extras;
    }

}
