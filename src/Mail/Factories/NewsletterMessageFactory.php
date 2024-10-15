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

namespace App\Mail\Factories;

use App\Entity\Portal;
use App\Mail\MessageInterface;
use App\Mail\Messages\NewsletterMessage;
use App\Services\LegacyEnvironment;

class NewsletterMessageFactory
{
    public function __construct(private readonly LegacyEnvironment $legacyEnvironment)
    {
    }

    public function createNewsletterMessage(Portal $portal, array $newsletterData): MessageInterface
    {
        return new NewsletterMessage($this->legacyEnvironment, $portal, $newsletterData);
    }
}
