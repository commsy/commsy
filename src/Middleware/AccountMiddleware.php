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

namespace App\Middleware;

use ApiPlatform\Symfony\Messenger\RemoveStamp;
use App\Account\AccountManager;
use App\Entity\Account;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;

final class AccountMiddleware implements MiddlewareInterface
{
    public function __construct(
        private AccountManager $accountManager
    ) {
    }

    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        $message = $envelope->getMessage();

        if ($message instanceof Account) {
            // root-Account
            if ($message->getUsername() === 'root') {
                throw new UnrecoverableMessageHandlingException('Root account cannot be deleted');
            }

            // When a DELETE operation occurs, API Platform automatically adds a
            // ApiPlatform\Symfony\Messenger\RemoveStamp “stamp” instance to the “envelope”.
            if (!empty($envelope->all(RemoveStamp::class))) {
                $this->accountManager->delete($message);
            }
        }

        return $stack->next()->handle($envelope, $stack);
    }
}
