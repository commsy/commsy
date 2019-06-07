<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Translation
 *
 * @ORM\Table(name="translation")
 * @ORM\Entity(repositoryClass="App\Repository\TranslationRepository")
 */
class Translation
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
     * @var int
     *
     * @ORM\Column(name="context_id", type="integer")
     */
    private $contextId;

    /**
     * @var string
     *
     * @ORM\Column(name="translation_key", type="string", length=255)
     */
    private $translationKey;

    /**
     * @var string
     *
     * @ORM\Column(name="translation_de", type="string", length=2000)
     */
    private $translationDe;

    /**
     * @var string
     *
     * @ORM\Column(name="translation_en", type="string", length=2000)
     */
    private $translationEn;

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
     * @param integer $contextId
     *
     * @return Translation
     */
    public function setContextId($contextId)
    {
        $this->contextId = $contextId;

        return $this;
    }

    /**
     * Get contextId
     *
     * @return int
     */
    public function getContextId()
    {
        return $this->contextId;
    }

    /**
     * Set translationKey
     *
     * @param string $translationKey
     *
     * @return Translation
     */
    public function setTranslationKey($translationKey)
    {
        $this->translationKey = $translationKey;

        return $this;
    }

    /**
     * Get translationKey
     *
     * @return string
     */
    public function getTranslationKey()
    {
        return $this->translationKey;
    }

    /**
     * Get german translation
     *
     * @return string
     */
    public function getTranslationDe()
    {
        return $this->translationDe;
    }

    /**
     * Set german translation
     */
    public function setTranslationDe($translationDe)
    {
        $this->translationDe = $translationDe;
    }

    /**
     * Get english translation
     *
     * @return string
     */
    public function getTranslationEn()
    {
        return $this->translationEn;
    }

    /**
     * Set english translation
     */
    public function setTranslationEn($translationEn)
    {
        $this->translationEn = $translationEn;
    }

    public function getTranslationForLocale($locale)
    {
        if ($locale == 'de') {
            return $this->getTranslationDe();
        } else if ($locale == 'en') {
            return $this->getTranslationEn();
        }

        return '';
    }
}

