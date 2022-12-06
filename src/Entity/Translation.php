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
    private int $id;

    /**
     * @var int
     *
     * @ORM\Column(name="context_id", type="integer")
     */
    private int $contextId;

    /**
     * @var string
     *
     * @ORM\Column(name="translation_key", type="string", length=255)
     */
    private string $translationKey;

    /**
     * @var string
     *
     * @ORM\Column(name="translation_de", type="string", length=2000)
     */
    private string $translationDe;

    /**
     * @var string
     *
     * @ORM\Column(name="translation_en", type="string", length=2000)
     */
    private string $translationEn;

    /**
     * Get id
     *
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Set contextId
     *
     * @param int $contextId
     *
     * @return Translation
     */
    public function setContextId(int $contextId): self
    {
        $this->contextId = $contextId;

        return $this;
    }

    /**
     * Get contextId
     *
     * @return int
     */
    public function getContextId(): int
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
    public function setTranslationKey(string $translationKey): self
    {
        $this->translationKey = $translationKey;

        return $this;
    }

    /**
     * Get translationKey
     *
     * @return string
     */
    public function getTranslationKey(): string
    {
        return $this->translationKey;
    }

    /**
     * Get german translation
     *
     * @return string
     */
    public function getTranslationDe(): string
    {
        return $this->translationDe;
    }

    /**
     * Set german translation
     */
    public function setTranslationDe(string $translationDe): self
    {
        $this->translationDe = $translationDe;

        return $this;
    }

    /**
     * Get english translation
     *
     * @return string
     */
    public function getTranslationEn(): string
    {
        return $this->translationEn;
    }

    /**
     * Set english translation
     */
    public function setTranslationEn(string $translationEn): self
    {
        $this->translationEn = $translationEn;

        return $this;
    }

    public function getTranslationForLocale($locale): string
    {
        if ($locale === 'de')  return $this->getTranslationDe();
        if ($locale === 'en') return $this->getTranslationEn();

        return '';
    }
}

