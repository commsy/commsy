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

use App\Repository\TranslationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Translation.
 */
#[ORM\Entity(repositoryClass: TranslationRepository::class)]
#[ORM\Table(name: 'translation')]
class Translation
{
    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private int $id;

    #[ORM\Column(name: 'context_id', type: Types::INTEGER)]
    private int $contextId;

    #[ORM\Column(name: 'translation_key', type: Types::STRING, length: 255)]
    private string $translationKey;

    #[ORM\Column(name: 'translation_de', type: Types::STRING, length: 2000)]
    private string $translationDe;

    #[ORM\Column(name: 'translation_en', type: Types::STRING, length: 2000)]
    private string $translationEn;

    /**
     * Get id.
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Set contextId.
     */
    public function setContextId(int $contextId): self
    {
        $this->contextId = $contextId;

        return $this;
    }

    /**
     * Get contextId.
     */
    public function getContextId(): int
    {
        return $this->contextId;
    }

    /**
     * Set translationKey.
     */
    public function setTranslationKey(string $translationKey): self
    {
        $this->translationKey = $translationKey;

        return $this;
    }

    /**
     * Get translationKey.
     */
    public function getTranslationKey(): string
    {
        return $this->translationKey;
    }

    /**
     * Get german translation.
     */
    public function getTranslationDe(): string
    {
        return $this->translationDe;
    }

    /**
     * Set german translation.
     */
    public function setTranslationDe(string $translationDe): self
    {
        $this->translationDe = $translationDe;

        return $this;
    }

    /**
     * Get english translation.
     */
    public function getTranslationEn(): string
    {
        return $this->translationEn;
    }

    /**
     * Set english translation.
     */
    public function setTranslationEn(string $translationEn): self
    {
        $this->translationEn = $translationEn;

        return $this;
    }

    public function getTranslationForLocale($locale): string
    {
        if ('de' === $locale) {
            return $this->getTranslationDe();
        }
        if ('en' === $locale) {
            return $this->getTranslationEn();
        }

        return '';
    }
}
