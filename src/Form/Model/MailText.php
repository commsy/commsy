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
    private ?string $contentGerman = null;

    private bool $resetContentGerman = false;

    #[Assert\NotBlank]
    private ?string $contentEnglish = null;

    private bool $resetContentEnglish = false;

    public function getMailText(): ?string
    {
        return $this->mailText;
    }

    public function setMailText(?string $mailText): MailText
    {
        $this->mailText = $mailText;
        return $this;
    }

    public function getContentGerman(): ?string
    {
        return $this->contentGerman;
    }
    public function setContentGerman(?string $contentGerman): MailText
    {
        $this->contentGerman = $contentGerman;
        return $this;
    }

    public function isResetContentGerman(): bool
    {
        return $this->resetContentGerman;
    }

    public function setResetContentGerman(bool $resetContentGerman): MailText
    {
        $this->resetContentGerman = $resetContentGerman;
        return $this;
    }

    public function getContentEnglish(): ?string
    {
        return $this->contentEnglish;
    }

    public function setContentEnglish(?string $contentEnglish): MailText
    {
        $this->contentEnglish = $contentEnglish;
        return $this;
    }

    public function isResetContentEnglish(): bool
    {
        return $this->resetContentEnglish;
    }

    public function setResetContentEnglish(bool $resetContentEnglish): MailText
    {
        $this->resetContentEnglish = $resetContentEnglish;
        return $this;
    }
}
