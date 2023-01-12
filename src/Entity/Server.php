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

use ApiPlatform\Core\Annotation\ApiResource;
use App\Controller\Api\GetServerAnnouncement;
use App\Repository\ServerRepository;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;
use OpenApi\Annotations as OA;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Serializer\Annotation\Groups;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

/**
 * Server.
 *
 * @Vich\Uploadable
 * @ApiResource(
 *     security="is_granted('ROLE_API_READ')",
 *     collectionOperations={
 *         "get",
 *     },
 *     itemOperations={
 *         "get",
 *         "get_announcement"={
 *             "method"="GET",
 *             "path"="servers/{id}/announcement",
 *             "controller"=GetServerAnnouncement::class,
 *             "openapi_context"={
 *                 "summary"="Get server announcement",
 *                 "responses"={
 *                     "200"={
 *                         "description"="Server announcement",
 *                         "content"={
 *                             "application/json"={
 *                                 "schema"={
 *                                     "type"="object",
 *                                     "properties"={
 *                                         "enabled"={
 *                                             "type"="boolean",
 *                                         },
 *                                         "title"={
 *                                             "type"="string",
 *                                         },
 *                                         "severity"={
 *                                             "type"="string",
 *                                         },
 *                                         "text"={
 *                                             "type"="string",
 *                                         },
 *                                     },
 *                                 },
 *                             },
 *                         },
 *                     },
 *                 },
 *             },
 *         },
 *     },
 *     normalizationContext={
 *         "groups"={"api"},
 *     },
 *     denormalizationContext={
 *         "groups"={"api"},
 *     }
 * )
 */
#[ORM\Entity(repositoryClass: ServerRepository::class)]
#[ORM\Table(name: 'server')]
#[ORM\Index(name: 'context_id', columns: ['context_id'])]
#[ORM\Index(name: 'creator_id', columns: ['creator_id'])]
class Server
{
    /**
     * @OA\Property(description="The unique identifier.")
     */
    #[ORM\Id]
    #[ORM\Column(name: 'item_id', type: 'integer')]
    #[ORM\GeneratedValue]
    #[Groups(['api'])]
    private int $id;

    #[ORM\Column(name: 'context_id', type: 'integer', nullable: true)]
    private int $contextId;
    /**
     * @var int
     */
    #[ORM\Column(name: 'creator_id', type: 'integer', nullable: false)]
    private $creatorId = '0';

    #[ORM\Column(name: 'modifier_id', type: 'integer', nullable: true)]
    private int $modifierId;

    #[ORM\Column(name: 'deleter_id', type: 'integer', nullable: true)]
    private int $deleterId;
    #[ORM\Column(name: 'creation_date', type: 'datetime', nullable: false)]
    private DateTime $creationDate;
    #[ORM\Column(name: 'modification_date', type: 'datetime', nullable: false)]
    private ?DateTimeInterface $modificationDate = null;
    /**
     * @var DateTimeInterface
     */
    #[ORM\Column(name: 'deletion_date', type: 'datetime', nullable: true)]
    private $deletionDate;

    #[ORM\Column(name: 'title', type: 'string', length: 255, nullable: false)]
    private string $title;

    #[ORM\Column(name: 'extras', type: 'array', nullable: true)]
    private ?array $extras;

    #[ORM\Column(name: 'status', type: 'string', length: 20, nullable: false)]
    private string $status;
    /**
     * @var int
     */
    #[ORM\Column(name: 'activity', type: 'integer', nullable: false)]
    private $activity = '0';

    #[ORM\Column(name: 'type', type: 'string', length: 10, nullable: false)]
    private string $type = 'server';
    /**
     * @var bool
     */
    #[ORM\Column(name: 'is_open_for_guests', type: 'boolean', nullable: false)]
    private $isOpenForGuests = '1';

    #[ORM\Column(name: 'url', type: 'string', length: 255, nullable: true)]
    private ?string $url = null;
    /**
     * @Vich\UploadableField(mapping="server_logo", fileNameProperty="logoImageName")
     */
    private ?File $logoImageFile = null;

    #[ORM\Column(name: 'logo_image_name', type: 'string', length: 255, nullable: true)]
    private ?string $logoImageName = null;

    #[ORM\Column(name: 'commsy_icon_link', type: 'string', length: 255, nullable: true)]
    private ?string $commsyIconLink = null;

    public function __construct()
    {
        $this->creationDate = new DateTime('0000-00-00 00:00:00');
    }

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
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

    /**
     * Set extras.
     *
     * @return Portal
     */
    public function setExtras(array $extras): self
    {
        $this->extras = $extras;

        return $this;
    }

    /**
     * Get extras.
     */
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
