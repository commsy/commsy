<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Term
 *
 * @ORM\Table(name="terms")
 * @ORM\Entity(repositoryClass="CommsyBundle\Repository\TermsRepository")
 */
class Terms
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var contextId
     *
     * @ORM\Column(name="context_id", type="integer")
     */
    private $contextId;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string", length=255)
     */
    private $title;

    /**
     * @var string
     *
     * @ORM\Column(name="content_de", type="text")
     */
    private $contentDe;

    /**
     * @var string
     *
     * @ORM\Column(name="content_en", type="text")
     */
    private $contentEn;


    /**
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set contextId
     *
     * @param string $contextId
     *
     * @return Term
     */
    public function setContextId($contextId)
    {
        $this->contextId = $contextId;

        return $this;
    }

    /**
     * Get contextId
     *
     * @return string
     */
    public function getContextId()
    {
        return $this->contextId;
    }

    /**
     * Set title
     *
     * @param string $title
     *
     * @return Term
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set contentDe
     *
     * @param string $contentDe
     *
     * @return Term
     */
    public function setContentDe($contentDe)
    {
        $this->contentDe = $contentDe;

        return $this;
    }

    /**
     * Get contentDe
     *
     * @return string
     */
    public function getContentDe()
    {
        return $this->contentDe;
    }

    /**
     * Set contentEn
     *
     * @param string $contentEn
     *
     * @return Term
     */
    public function setContentEn($contentEn)
    {
        $this->contentEn = $contentEn;

        return $this;
    }

    /**
     * Get contentEn
     *
     * @return string
     */
    public function getContentEn()
    {
        return $this->contentEn;
    }
}

