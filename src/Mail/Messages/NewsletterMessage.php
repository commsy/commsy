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

namespace App\Mail\Messages;

use App\Entity\Portal;
use App\Mail\Message;
use App\Services\LegacyEnvironment;
use cs_environment;

class NewsletterMessage extends Message
{
    private readonly cs_environment $legacyEnvironment;

    public function __construct(
        LegacyEnvironment $legacyEnvironment,
        private readonly Portal $portal,
        private readonly array $newsletterData
    ) {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
    }

    public function getSubject(): string
    {
        return $this->newsletterData['mailSequence'] === 'daily'
            ? 'mail.newsletter_subject_daily'
            : 'mail.newsletter_subject_weekly';
    }

    public function getTemplateName(): string
    {
        return 'mail/newsletter.html.twig';
    }

    public function getParameters(): array
    {
        return [
            'portal' => $this->portal,
            'newsletterData' => $this->newsletterData,
        ];
    }

    public function getTranslationParameters(): array
    {
        return [
            '%portal%' => $this->portal->getTitle(),
        ];
    }
}
