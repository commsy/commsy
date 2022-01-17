<?php


namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity()
 */
class IdP
{

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", length=255, nullable=false)
     * @Assert\Length(max=255)
     */
    private ?string $name;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", length=255, nullable=false)
     * @Assert\Length(max=255)
     */
    private ?string $url;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\AuthSourceShibboleth")
     * @ORM\JoinColumn(name="auth_source_shibboleth_id", referencedColumnName="id")
     */
    private $authSourceShibboleth;

    private $idps;

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

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId(int $id): void
    {
        $this->id = $id;
    }

    /**
     * @return mixed
     */
    public function getAuthSourceShibboleth()
    {
        return $this->authSourceShibboleth;
    }

    /**
     * @param mixed $authSourceShibboleth
     */
    public function setAuthSourceShibboleth($authSourceShibboleth): void
    {
        $this->authSourceShibboleth = $authSourceShibboleth;
    }

    /**
     * @return mixed
     */
    public function getIdps()
    {
        return $this->idps;
    }

    /**
     * @param mixed $idps
     */
    public function setIdps($idps): void
    {
        $this->idps = $idps;
    }

}