<?php


namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

class ShibbolethIdentityProvider
{
    /**
     * @var string|null
     *
     * @ORM\Column(type="string", length=255, nullable=false)
     * @Assert\Length(max=255)
     * @Assert\NotBlank()
     */
    private ?string $name;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", length=255, nullable=false)
     * @Assert\Length(max=255)
     * @Assert\Url()
     */
    private ?string $url;

    /**
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @param string|null $name
     */
    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return string|null
     */
    public function getUrl(): ?string
    {
        return $this->url;
    }

    /**
     * @param string|null $url
     */
    public function setUrl(?string $url): void
    {
        $this->url = $url;
    }
}