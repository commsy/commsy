<?php

namespace App\Entity;

use ApiPlatform\Core\Action\NotFoundAction;
use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use App\Controller\Api\GetServerAnnouncement;
use Doctrine\ORM\Mapping as ORM;
use OpenApi\Annotations as OA;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Serializer\Annotation\Groups;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

/**
 * Server
 *
 * @ORM\Table(name="server", indexes={
 *     @ORM\Index(name="context_id", columns={"context_id"}),
 *     @ORM\Index(name="creator_id", columns={"creator_id"})
 * })
 * @ORM\Entity(repositoryClass="App\Repository\ServerRepository")
 * @Vich\Uploadable
 * @ApiResource(
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
class Server
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="item_id", type="integer")
     * @ORM\GeneratedValue()
     *
     * @Groups({"api"})
     * @OA\Property(description="The unique identifier.")
     */
    private $id;

    /**
     * @var integer
     *
     * @ORM\Column(name="context_id", type="integer", nullable=true)
     */
    private $contextId;

    /**
     * @var integer
     *
     * @ORM\Column(name="creator_id", type="integer", nullable=false)
     */
    private $creatorId = '0';

    /**
     * @var integer
     *
     * @ORM\Column(name="modifier_id", type="integer", nullable=true)
     */
    private $modifierId;

    /**
     * @var integer
     *
     * @ORM\Column(name="deleter_id", type="integer", nullable=true)
     */
    private $deleterId;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="creation_date", type="datetime", nullable=false)
     */
    private $creationDate = '0000-00-00 00:00:00';

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="modification_date", type="datetime", nullable=false)
     */
    private $modificationDate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="deletion_date", type="datetime", nullable=true)
     */
    private $deletionDate;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string", length=255, nullable=false)
     */
    private $title;

    /**
     * @var array
     *
     * @ORM\Column(name="extras", type="array", nullable=true)
     */
    private $extras;

    /**
     * @var string
     *
     * @ORM\Column(name="status", type="string", length=20, nullable=false)
     */
    private $status;

    /**
     * @var integer
     *
     * @ORM\Column(name="activity", type="integer", nullable=false)
     */
    private $activity = '0';

    /**
     * @var string
     *
     * @ORM\Column(name="type", type="string", length=10, nullable=false)
     */
    private $type = 'server';

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_open_for_guests", type="boolean", nullable=false)
     */
    private $isOpenForGuests = '1';

    /**
     * @var string
     *
     * @ORM\Column(name="url", type="string", length=255, nullable=true)
     */
    private $url;

    /**
     * @var File|null
     *
     * @Vich\UploadableField(mapping="server_logo", fileNameProperty="logoImageName")
     */
    private $logoImageFile;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string")
     */
    private $logoImageName;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return File|null
     */
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
     *
     * @param File|null $logoImageFile
     * @return self
     */
    public function setLogoImageFile(?File $logoImageFile = null): self
    {
        $this->logoImageFile = $logoImageFile;

        if ($logoImageFile !== null) {
            // It is required that at least one field changes if you are using doctrine
            // otherwise the event listeners won't be called and the file is lost
            $this->modificationDate = new \DateTimeImmutable();
        }

        return $this;
    }

    /**
     * @return string|null
     */
    public function getLogoImageName(): ?string
    {
        return $this->logoImageName;
    }

    /**
     * @param string|null $logoImageName
     * @return self
     */
    public function setLogoImageName(?string $logoImageName): self
    {
        $this->logoImageName = $logoImageName;
        return $this;
    }

    /**
     * Set extras
     *
     * @param array $extras
     *
     * @return Portal
     */
    public function setExtras(array $extras): self
    {
        $this->extras = $extras;

        return $this;
    }

    /**
     * Get extras
     *
     * @return array
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

