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
use App\Controller\Api\GetServerAnnouncement;
use App\Repository\ServerRepository;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Serializer\Annotation\Groups;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

#[ORM\Entity(repositoryClass: ServerRepository::class)]
#[ORM\Table(name: 'server')]
#[ORM\Index(columns: ['context_id'], name: 'context_id')]
#[ORM\Index(columns: ['creator_id'], name: 'creator_id')]
#[Vich\Uploadable]
#[ApiResource(
    operations: [
        new Get(),
        new Get(
            uriTemplate: 'servers/{id}/announcement',
            controller: GetServerAnnouncement::class,
            openapiContext: [
                'summary' => 'Get server announcement',
                'responses' => [[
                    'description' => 'Server announcement',
                    'content' => [
                        'application/json' => [
                            'schema' => [
                                'type' => 'object',
                                'properties' => [
                                    'enabled' => ['type' => 'boolean'],
                                    'title' => ['type' => 'string'],
                                    'severity' => ['type' => 'string'],
                                    'text' => ['type' => 'string'],
                                ],
                            ],
                        ],
                    ],
                ]],
            ],
        ),
        new GetCollection(),
    ],
    normalizationContext: ['groups' => ['api']],
    denormalizationContext: ['groups' => ['api']],
    security: "is_granted('ROLE_API_READ')",
)]
class Server
{
    #[ApiProperty(description: 'The unique identifier.')]
    #[ORM\Id]
    #[ORM\Column(name: 'item_id', type: Types::INTEGER)]
    #[ORM\GeneratedValue]
    #[Groups(['api'])]
    private int $id;

    #[ORM\Column(name: 'context_id', type: Types::INTEGER, nullable: true)]
    private int $contextId;

    #[ORM\Column(name: 'creator_id', type: Types::INTEGER, nullable: false)]
    private ?int $creatorId = 0;

    #[ORM\Column(name: 'modifier_id', type: Types::INTEGER, nullable: true)]
    private int $modifierId;

    #[ORM\Column(name: 'deleter_id', type: Types::INTEGER, nullable: true)]
    private int $deleterId;

    #[ORM\Column(name: 'creation_date', type: Types::DATETIME_MUTABLE, nullable: false)]
    private DateTime $creationDate;

    #[ORM\Column(name: 'modification_date', type: Types::DATETIME_MUTABLE, nullable: false)]
    private ?DateTimeInterface $modificationDate = null;

    #[ORM\Column(name: 'deletion_date', type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?DateTime $deletionDate = null;

    #[ORM\Column(name: 'title', type: Types::STRING, length: 255, nullable: false)]
    private string $title;

    #[ORM\Column(name: 'extras', type: Types::ARRAY, nullable: true)]
    private ?array $extras = null;

    #[ORM\Column(name: 'status', type: Types::STRING, length: 20, nullable: false)]
    private string $status;

    #[ORM\Column(name: 'activity', type: Types::INTEGER, nullable: false)]
    private int $activity = 0;

    #[ORM\Column(name: 'type', type: Types::STRING, length: 10, nullable: false)]
    private string $type = 'server';

    #[ORM\Column(name: 'is_open_for_guests', type: Types::BOOLEAN, nullable: false)]
    private bool $isOpenForGuests = true;

    #[ORM\Column(name: 'url', type: Types::STRING, length: 255, nullable: true)]
    private ?string $url = null;

    // NOTE: This is not a mapped field of entity metadata, just a simple property.
    #[Vich\UploadableField(mapping: 'server_logo', fileNameProperty: 'logoImageName')]
    private ?File $logoImageFile = null;

    #[ORM\Column(name: 'logo_image_name', type: Types::STRING, length: 255, nullable: true)]
    private ?string $logoImageName = null;

    #[ORM\Column(name: 'commsy_icon_link', type: Types::STRING, length: 255, nullable: true)]
    private ?string $commsyIconLink = null;

    public function __construct()
    {
        $this->creationDate = new DateTime('0000-00-00 00:00:00');
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getLogoImageFile(): ?File
    {
        return $this->logoImageFile;
    }

    /**
     * If manually uploading a file (i.e. not using Symfony Form) ensure an instance
     * of 'UploadedFile' is injected into this setter to trigger the update. If this
     * bundle's configuration parameter 'inject_on_load' is set to 'true' this setter
     * must be able to accept an instance of 'File' as the bundle will inject one here
     * during Doctrine hydration.
     */
    public function setLogoImageFile(?File $logoImageFile = null): self
    {
        $this->logoImageFile = $logoImageFile;
        if (null !== $logoImageFile) {
            // It is required that at least one field changes if you are using doctrine
            // otherwise the event listeners won't be called and the file is lost
            $this->modificationDate = new DateTimeImmutable();
        }
        return $this;
    }

    public function getLogoImageName(): ?string
    {
        return $this->logoImageName;
    }

    public function setLogoImageName(?string $logoImageName): self
    {
        $this->logoImageName = $logoImageName;
        return $this;
    }

    public function getCommsyIconLink(): ?string
    {
        return $this->commsyIconLink;
    }

    public function setCommsyIconLink(?string $commsyIconLink): self
    {
        $this->commsyIconLink = $commsyIconLink;
        return $this;
    }

    public function setExtras(array $extras): Server
    {
        $this->extras = $extras;
        return $this;
    }

    public function getExtras(): ?array
    {
        return $this->extras;
    }

    public function getAnnouncementText(): ?string
    {
        return $this->extras['ANNOUNCEMENT_TEXT'] ?? '';
    }

    public function setAnnouncementText(?string $text): self
    {
        $this->extras['ANNOUNCEMENT_TEXT'] = $text;
        return $this;
    }

    public function getAnnouncementTitle(): ?string
    {
        return $this->extras['ANNOUNCEMENT_TITLE'] ?? '';
    }

    public function setAnnouncementTitle(string $title): self
    {
        $this->extras['ANNOUNCEMENT_TITLE'] = $title;
        return $this;
    }

    public function getAnnouncementSeverity(): ?string
    {
        return $this->extras['ANNOUNCEMENT_SEVERITY'] ?? '';
    }

    public function setAnnouncementSeverity(string $severity): self
    {
        $this->extras['ANNOUNCEMENT_SEVERITY'] = $severity;
        return $this;
    }

    public function hasAnnouncementEnabled(): bool
    {
        return $this->extras['ANNOUNCEMENT_ENABLED'] ?? false;
    }

    public function setAnnouncementEnabled(bool $enabled): self
    {
        $this->extras['ANNOUNCEMENT_ENABLED'] = $enabled;
        return $this;
    }

    public function hasDataPrivacyEnabled(): bool
    {
        return $this->extras['CONTENT_DATAPRIVACY_ENABLED'] ?? false;
    }

    public function setDataPrivacyEnabled(bool $enabled): self
    {
        $this->extras['CONTENT_DATAPRIVACY_ENABLED'] = $enabled;
        return $this;
    }

    public function getDataPrivacyText(): ?string
    {
        return $this->extras['CONTENT_DATAPRIVACY_TEXT'] ?? '';
    }

    public function setDataPrivacyText(?string $text): self
    {
        $this->extras['CONTENT_DATAPRIVACY_TEXT'] = $text;
        return $this;
    }

    public function hasImpressumEnabled(): bool
    {
        return $this->extras['CONTENT_IMPRESSUM_ENABLED'] ?? false;
    }

    public function setImpressumEnabled(bool $enabled): self
    {
        $this->extras['CONTENT_IMPRESSUM_ENABLED'] = $enabled;
        return $this;
    }

    public function getImpressumText(): ?string
    {
        return $this->extras['CONTENT_IMPRESSUM_TEXT'] ?? '';
    }

    public function setImpressumText(?string $text): self
    {
        $this->extras['CONTENT_IMPRESSUM_TEXT'] = $text;
        return $this;
    }

    public function hasAccessibilityEnabled(): bool
    {
        return $this->extras['CONTENT_ACCESSIBILITY_ENABLED'] ?? false;
    }

    public function setAccessibilityEnabled(bool $enabled): self
    {
        $this->extras['CONTENT_ACCESSIBILITY_ENABLED'] = $enabled;
        return $this;
    }

    public function getAccessibilityText(): ?string
    {
        return $this->extras['CONTENT_ACCESSIBILITY_TEXT'] ?? '';
    }

    public function setAccessibilityText(?string $text): self
    {
        $this->extras['CONTENT_ACCESSIBILITY_TEXT'] = $text;
        return $this;
    }
}
