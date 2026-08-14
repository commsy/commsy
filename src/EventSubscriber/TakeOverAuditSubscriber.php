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

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Audit\AuditEvent;
use App\Audit\AuditLogger;
use App\Entity\Account;
use App\Entity\Portal;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\Authentication\Token\SwitchUserToken;
use Symfony\Component\Security\Http\Event\SwitchUserEvent;

/**
 * Records every account take-over.
 *
 * Deliberately hooked into the firewall rather than into the portal settings
 * action that offers the take-over: the `_switch_user` parameter is handled
 * firewall-wide and works on any url, so a recorder sitting in that one
 * controller would only cover the convenient path.
 */
readonly class TakeOverAuditSubscriber implements EventSubscriberInterface
{
    public function __construct(private AuditLogger $auditLogger)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [SwitchUserEvent::class => 'onSwitchUser'];
    }

    public function onSwitchUser(SwitchUserEvent $event): void
    {
        $token = $event->getToken();

        // The same event announces the end of a take-over, and then carries the
        // restored original token. Only the switch itself builds a
        // SwitchUserToken, so the type tells the two apart — no reading of a
        // request parameter the firewall has already consumed.
        if (!$token instanceof SwitchUserToken) {
            return;
        }

        $subject = $event->getTargetUser();
        $actor = $token->getOriginalToken()->getUser();

        if (!$subject instanceof Account || !$actor instanceof Account) {
            return;
        }

        // An entry belongs to the portal of the account that was taken over —
        // that is the portal whose moderation is answerable for it, and it is
        // also where a take-over performed by root shows up. root itself has no
        // portal, and being taken over is barred for it anyway.
        $portal = $subject->getPortal();
        if (!$portal instanceof Portal) {
            return;
        }

        $this->auditLogger->recordAccountEvent(
            AuditEvent::AccountTakeOver,
            $portal,
            $subject,
            actor: $actor
        );
    }
}
