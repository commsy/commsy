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


namespace App\Mail\Helper;

use cs_user_item;
use Symfony\Component\DependencyInjection\Attribute\Exclude;

#[Exclude]
final readonly class EmailSendStatus
{
    /**
     * @param bool           $success Success status denoting whether the email was sent successfully (`true`) or not (`false`)
     * @param int            $numRecipients Total number of recipients
     * @param cs_user_item[] $deliveredRecipients List of recipients for whom the email could be delivered successfully
     * @param cs_user_item[] $failedRecipients List of recipients for whom the email could not be delivered
     */
    public function __construct(
        private bool $success,
        private int $numRecipients,
        private array $deliveredRecipients,
        private array $failedRecipients
    ) {
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function getNumRecipients(): int
    {
        return $this->numRecipients;
    }

    public function getDeliveredRecipients(): array
    {
        return $this->deliveredRecipients;
    }

    public function getFailedRecipients(): array
    {
        return $this->failedRecipients;
    }
}
