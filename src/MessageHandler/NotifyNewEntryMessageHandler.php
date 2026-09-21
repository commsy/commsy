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

namespace App\MessageHandler;

use App\Message\NotifyNewEntryMessage;
use App\Notification\NotificationManager;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly class NotifyNewEntryMessageHandler
{
    public function __construct(
        private NotificationManager $notificationManager,
    ) {
    }

    public function __invoke(NotifyNewEntryMessage $message): void
    {
        $this->notificationManager->notifyNewEntry($message);
    }
}
