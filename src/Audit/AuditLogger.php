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

namespace App\Audit;

use App\Entity\Account;
use App\Entity\AuditLogEntry;
use App\Entity\Portal;
use App\Repository\AuditLogEntryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\SwitchUserToken;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * The one way an administrative act reaches the audit log.
 */
readonly class AuditLogger
{
    public function __construct(
        private AuditLogEntryRepository $entries,
        private EntityManagerInterface $entityManager,
        private Security $security
    ) {
    }

    /**
     * Records an act performed on an account.
     *
     * The acting person is taken from the current token unless the caller knows
     * better. Callers that run while the token is being rebuilt — the take-over
     * itself — pass their actor.
     *
     * @param array<string, scalar> $details
     */
    public function recordAccountEvent(
        AuditEvent $event,
        Portal $portal,
        Account $subject,
        array $details = [],
        ?Account $actor = null
    ): void {
        $token = $this->security->getToken();

        // While a take-over is running the token holds the taken-over account.
        // Answerable for the act is the person behind it, so the original token
        // is what names the actor, and the account being worn is worth noting.
        if ($token instanceof SwitchUserToken) {
            $actor ??= $this->accountFrom($token->getOriginalToken());
            $details['acting_as'] = $token->getUserIdentifier();
        } else {
            $actor ??= $this->accountFrom($token);
        }

        // The names are read from the account at hand, the relation only from
        // one the manager can hold. The two come apart while a take-over is
        // running: the original token carries an account restored from the
        // session, which this manager has never seen, and persisting an entry
        // pointing at it fails with "a new entity was found".
        $this->entries->add(new AuditLogEntry(
            portal: $portal,
            event: $event,
            subjectType: AuditSubjectType::Account,
            subjectId: $subject->getId(),
            subjectLabel: $subject->getUsername(),
            subjectName: self::nameOf($subject),
            actor: $this->managedAccount($actor),
            actorUsername: $actor?->getUsername(),
            actorName: $actor instanceof Account ? self::nameOf($actor) : null,
            details: [] !== $details ? $details : null,
        ));
    }

    /**
     * The account as the entity manager knows it, or null.
     *
     * Null also covers an account that has been deleted in the meantime — the
     * entry then rests on the copied names, which is what they are there for.
     */
    private function managedAccount(?Account $account): ?Account
    {
        $id = $account?->getId();

        return null === $id ? null : $this->entityManager->find(Account::class, $id);
    }

    private function accountFrom(?TokenInterface $token): ?Account
    {
        $user = $token?->getUser();

        return $user instanceof Account ? $user : null;
    }

    /**
     * The person's name as it reads at the time of the act.
     */
    private static function nameOf(Account $account): string
    {
        $name = trim(($account->getFirstname() ?? '') . ' ' . ($account->getLastname() ?? ''));

        return '' !== $name ? $name : $account->getDisplayName();
    }
}
