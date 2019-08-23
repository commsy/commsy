<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * AuthSource
 *
 * @ORM\Table(name="auth_source", indexes={
 *     @ORM\Index(name="context_id", columns={"context_id"})
 * })
 * @ORM\Entity(repositoryClass="App\Repository\AuthSourceRepository")
 */
class AuthSource
{
    /**
     * @var integer
     *
     * @ORM\Column(type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255, nullable=false)
     */
    private $title;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Portal", inversedBy="authSources")
     * @ORM\JoinColumn(name="portal", referencedColumnName="item_id")
     */
    private $portal;

    /**
     * @var string
     *
     * @ORM\Column(name="extras", type="object", nullable=true)
     */
    private $extras;

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return AuthSource
     */
    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @param string $title
     * @return AuthSource
     */
    public function setTitle(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    public function getPortal(): ?Portal
    {
        return $this->portal;
    }

    public function setPortal(?Portal $portal): self
    {
        $this->portal = $portal;

        return $this;
    }

    /**
     * @return string
     */
    public function getExtras(): string
    {
        return $this->extras;
    }

    /**
     * @param string $extras
     * @return AuthSource
     */
    public function setExtras(string $extras): self
    {
        $this->extras = $extras;
        return $this;
    }
}
