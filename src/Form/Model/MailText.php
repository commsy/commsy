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

namespace App\Form\Model;

use Symfony\Component\Validator\Constraints as Assert;

class MailText
{
    private ?string $mailText = null;

    #[Assert\NotBlank]
    private ?string $contentGerman;

    private bool $resetContentGerman = false;

    #[Assert\NotBlank]
    private ?string $contentEnglish;

    private bool $resetContentEnglish = false;

    /**
     * @return string|null
     */
    public function getMailText(): ?string
    {
        return $this->mailText;
    }

    /**
     * @param string|null $mailText
     * @return MailText
     */
    public function setMailText(?string $mailText): MailText
    {
        $this->mailText = $mailText;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getContentGerman(): ?string
    {
        return $this->contentGerman;
    }

    /**
     * @param string|null $contentGerman
     * @return MailText
     */
    public function setContentGerman(?string $contentGerman): MailText
    {
        $this->contentGerman = $contentGerman;
        return $this;
    }

    /**
     * @return bool
     */
    public function isResetContentGerman(): bool
    {
        return $this->resetContentGerman;
    }

    /**
     * @param bool $resetContentGerman
     * @return MailText
     */
    public function setResetContentGerman(bool $resetContentGerman): MailText
    {
        $this->resetContentGerman = $resetContentGerman;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getContentEnglish(): ?string
    {
        return $this->contentEnglish;
    }

    /**
     * @param string|null $contentEnglish
     * @return MailText
     */
    public function setContentEnglish(?string $contentEnglish): MailText
    {
        $this->contentEnglish = $contentEnglish;
        return $this;
    }

    /**
     * @return bool
     */
    public function isResetContentEnglish(): bool
    {
        return $this->resetContentEnglish;
    }

    /**
     * @param bool $resetContentEnglish
     * @return MailText
     */
    public function setResetContentEnglish(bool $resetContentEnglish): MailText
    {
        $this->resetContentEnglish = $resetContentEnglish;
        return $this;
    }
}
