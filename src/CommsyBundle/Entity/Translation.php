<?php

namespace CommsyBundle\Entity;

use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Translatable\Translatable;

/**
 * Translation
 *
 * @ORM\Table(name="translation")
 * @ORM\Entity(repositoryClass="CommsyBundle\Repository\TranslationRepository")
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
     * @Gedmo\Translatable
     * @ORM\Column(name="translation", type="string", length=255)
     */
    private $translation;

    /**
     * @Gedmo\Locale
     * Used locale to override Translation listener`s locale
     * this is not a mapped field of entity metadata, just a simple property
     * and it is not necessary because globally locale can be set in listener
     */
    private $locale;


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
     * Set translation
     *
     * @param string $translation
     *
     * @return Translation
     */
    public function setTranslation($translation)
    {
        $this->translation = $translation;

        return $this;
    }

    /**
     * Get translation
     *
     * @return string
     */
    public function getTranslation()
    {
        return $this->translation;
    }

    /**
     * @param $locale
     */
    public function setTranslatableLocale($locale)
    {
        $this->locale = $locale;
    }

    /**
     * Get german translation
     *
     * @return string
     */
    public function getTranslationDe()
    {
        $this->setTranslatableLocale('de_de');
        return $this->translation;
    }

    /**
     * Set german translation
     */
    public function setTranslationDe($translation)
    {
        $this->setTranslatableLocale('de_de');
        $this->translation = $translation;
    }

    /**
     * Get english translation
     *
     * @return string
     */
    public function getTranslationEn()
    {
        $this->setTranslatableLocale('en_en');
        return $this->translation;
    }

    /**
     * Set english translation
     */
    public function setTranslationEn($translation)
    {
        $this->setTranslatableLocale('en_en');
        $this->translation = $translation;
    }
}

