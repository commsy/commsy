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

namespace App\Security\Authorization\Voter;

use App\Entity\Account;
use App\Entity\User;
use App\Services\CurrentUserResolver;
use App\Utils\RequestContext;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Doctrine-native replacement for cs_user_item::isAllowedToCreateContext().
 *
 * Reproduces the legacy 4-branch decision table 1:1 (pinned by
 * {@see \Tests\Integration\Security\ContextCreateCharacterizationTest}):
 *
 *   1. guest / no membership                -> false
 *   2. root                                 -> true
 *   3. context is portal AND moderator      -> true
 *   4. per-user extra IS_ALLOWED_TO_CREATE_CONTEXT:
 *        '-1' (loose)                        -> false
 *        any other value != 'standard'        -> true
 *        'standard' (default / unset)         -> Account->AuthSource->getCreateRoom()
 *
 * The per-user setting is persisted in user.extras. PortalSettings
 * Controller writes it as a PHP bool; legacy cs_user_item (no
 * declare(strict_types)) coerces it through a ": string" return:
 * true -> "1", false -> "". Doctrine maps `extras` as Types::ARRAY and
 * preserves the RAW bool, so we replicate that coercion here before the
 * loose comparisons — otherwise stored false would wrongly deny (legacy
 * allows it).
 */
class ContextCreateVoter extends Voter
{
    final public const CONTEXT_CREATE = 'CONTEXT_CREATE';

    public function __construct(
        private readonly CurrentUserResolver $currentUserResolver,
        private readonly RequestContext $requestContext,
        private readonly RequestStack $requestStack,
    ) {
    }

    protected function supports($attribute, $subject): bool
    {
        return self::CONTEXT_CREATE === $attribute;
    }

    protected function voteOnAttribute($attribute, $subject, TokenInterface $token): bool
    {
        // The Voter stays subject-less (every caller asks about the current
        // actor in the current context — the legacy method had no param).
        // It only gathers the ambient inputs and delegates the decision to
        // the pure, directly testable/reusable decide().
        return $this->decide(
            $this->currentUserResolver->getUser(),
            $this->currentUserResolver->getAccount(),
            $this->currentContextIsPortal(),
        );
    }

    /**
     * Pure 1:1 port of the legacy decision table — no ambient state, so
     * it is unit-testable and reusable for "could THIS user/account
     * create a context" questions without a request/token.
     */
    public function decide(?User $user, ?Account $account, bool $contextIsPortal): bool
    {
        // 1. guest: no account, or an account with no (non-deleted)
        //    membership in the current context — legacy leaves the empty
        //    status-0 user item, whose isGuest() short-circuits to false.
        if (null === $account || null === $user || $user->isGuest()) {
            return false;
        }

        // 2. root
        if ($user->isRoot()) {
            return true;
        }

        // 3. portal context + moderator
        if ($contextIsPortal && $user->isModerator()) {
            return true;
        }

        // 4. per-user extra, replicating the legacy ": string" coercion
        $raw = ($user->getExtras() ?? [])['IS_ALLOWED_TO_CREATE_CONTEXT'] ?? 'standard';
        $value = is_string($raw) ? $raw : (string) $raw;

        if ('standard' !== $value) {
            // legacy: `-1 == $value` (loose) — "-1" denies, "1"/"" allow
            return -1 != $value;
        }

        return (bool) $account->getAuthSource()?->getCreateRoom();
    }

    private function currentContextIsPortal(): bool
    {
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            return false;
        }

        return null === $this->requestContext->fetchRoom($request)
            && null !== $this->requestContext->fetchPortal($request);
    }
}
