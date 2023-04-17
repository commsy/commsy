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

namespace App\Security;

use App\Entity\Account;
use App\Entity\AuthSource;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class UserProvider implements UserProviderInterface
{
    public function __construct(
        private RequestStack $requestStack,
        private EntityManagerInterface $entityManager
    ) {
    }

    /**
     * Symfony calls this method if you use features like switch_user
     * or remember_me.
     *
     * If you're not using these features, you do not need to implement
     * this method.
     *
     * @throws UserNotFoundException if the user is not found
     */
    public function loadUserByUsername($username): Account
    {
        // Load a User object from your data source or throw UsernameNotFoundException.
        // The $username argument may not actually be a username:
        // it is whatever value is being returned by the getUsername()
        // method in your User class.
        $account = $this->loadUser(
            $username,
            $this->extractContexIdFromRequest(),
            $this->extractAuthSourceIdFromRequest()
        );
        if (null === $account) {
            $account = $this->loadUser(
                $username,
                $this->extractContexIdFromRequest('takeover_context'),
                $this->extractAuthSourceIdFromRequest('takeover_authSourceId')
            );
        }

        if (null === $account) {
            throw new UserNotFoundException();
        }

        return $account;
    }

    /**
     * Refreshes the user after being reloaded from the session.
     *
     * When a user is logged in, at the beginning of each request, the
     * User object is loaded from the session and then this method is
     * called. Your job is to make sure the user's data is still fresh by,
     * for example, re-querying for fresh User data.
     *
     * If your firewall is "stateless: true" (for a pure API), this
     * method is not called.
     *
     * @throws UserNotFoundException if the user is not found
     */
    public function refreshUser(UserInterface $user): Account
    {
        if (!$user instanceof Account) {
            throw new UnsupportedUserException(sprintf('Invalid user class "%s".', $user::class));
        }

        // Return a User object after making sure its data is "fresh".
        // Or throw a UsernameNotFoundException if the user no longer exists.
        $account = $this->loadUser(
            $user->getUsername(),
            $this->extractContexIdFromRequest(),
            $this->extractAuthSourceIdFromRequest()
        );
        if (null === $account) {
            $account = $this->loadUser(
                $user->getUsername(),
                $this->extractContexIdFromRequest('takeover_context'),
                $this->extractAuthSourceIdFromRequest('takeover_authSourceId')
            );
        }

        if (null === $account) {
            throw new UserNotFoundException();
        }

        return $account;
    }

    /**
     * Tells Symfony to use this provider for this User class.
     */
    public function supportsClass($class)
    {
        return Account::class === $class;
    }

    private function loadUser(string $username, int $contextId, int $authSourceId): ?Account
    {
        try {
            $authSource = $this->entityManager->getRepository(AuthSource::class)->find($authSourceId);

            return $this->entityManager->getRepository(Account::class)
                ->findOneByCredentials($username, $contextId, $authSource);
        } catch (NonUniqueResultException) {
        }

        return null;
    }

    /**
     * Extracts context id from the request.
     */
    private function extractContexIdFromRequest(string $key = 'context'): int
    {
        $currentRequest = $this->requestStack->getCurrentRequest();
        if ($currentRequest) {
            $session = $currentRequest->getSession();
            $contextId = $session->get($key);

            if (null !== $contextId) {
                return $contextId;
            }
        }

        throw new UserNotFoundException();
    }

    /**
     * Extracts auth source id from the request.
     */
    private function extractAuthSourceIdFromRequest(string $key = 'authSourceId'): int
    {
        $currentRequest = $this->requestStack->getCurrentRequest();
        if ($currentRequest) {
            $session = $currentRequest->getSession();
            $authSourceId = $session->get($key);

            if (null !== $authSourceId) {
                return $authSourceId;
            }
        }

        throw new UserNotFoundException();
    }
}
