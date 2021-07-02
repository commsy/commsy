<?php


namespace App\Security;


use App\Entity\Account;
use App\Entity\AuthSource;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class UserProvider implements UserProviderInterface
{
    /**
     * @var RequestStack
     */
    private RequestStack $requestStack;

    /**
     * @var EntityManagerInterface
     */
    private EntityManagerInterface $entityManager;

    public function __construct(RequestStack $requestStack, EntityManagerInterface $entityManager)
    {
        $this->requestStack = $requestStack;
        $this->entityManager = $entityManager;
    }

    /**
     * Symfony calls this method if you use features like switch_user
     * or remember_me.
     *
     * If you're not using these features, you do not need to implement
     * this method.
     *
     * @return UserInterface
     *
     * @throws UsernameNotFoundException if the user is not found
     */
    public function loadUserByUsername($username)
    {
        // Load a User object from your data source or throw UsernameNotFoundException.
        // The $username argument may not actually be a username:
        // it is whatever value is being returned by the getUsername()
        // method in your User class.
        $currentRequest = $this->requestStack->getCurrentRequest();
        if (!$currentRequest) {
            throw new UsernameNotFoundException();
        }

        $session = $currentRequest->getSession();
        $contextId = $session->get('context');
        $authSourceId = $session->get('authSourceId');

        $contextId = $session->get('takeover_context', $contextId);
        $authSourceId = $session->get('takeover_authSourceId', $authSourceId);
        $session->remove('takeover_context');
        $session->remove('takeover_authSourceId');

        if (!$contextId || !$authSourceId) {
            throw new UsernameNotFoundException();
        }

        try {
            $authSource = $this->entityManager->getRepository(AuthSource::class)->find($authSourceId);
            $user = $this->entityManager->getRepository(Account::class)
                ->findOneByCredentials($username, $contextId, $authSource);
        } catch (NonUniqueResultException $e) {
        }

        if (!$user) {
            throw new UsernameNotFoundException();
        }

        return $user;
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
     * @return UserInterface
     */
    public function refreshUser(UserInterface $user)
    {
        if (!$user instanceof Account) {
            throw new UnsupportedUserException(sprintf('Invalid user class "%s".', get_class($user)));
        }

        // Return a User object after making sure its data is "fresh".
        // Or throw a UsernameNotFoundException if the user no longer exists.
        $currentRequest = $this->requestStack->getCurrentRequest();
        if (!$currentRequest) {
            throw new UsernameNotFoundException();
        }

        $session = $currentRequest->getSession();
        $contextId = $session->get('context');
        $authSourceId = $session->get('authSourceId');

        $contextId = $session->get('takeover_context', $contextId);
        $authSourceId = $session->get('takeover_authSourceId', $authSourceId);
        $session->remove('takeover_context');
        $session->remove('takeover_authSourceId');

        try {
            $authSource = $this->entityManager->getRepository(AuthSource::class)->find($authSourceId);
            $user = $this->entityManager->getRepository(Account::class)
                ->findOneByCredentials($user->getUsername(), $contextId, $authSource);
        } catch (NonUniqueResultException $e) {
        }

        if (!$user) {
            throw new UsernameNotFoundException();
        }

        return $user;
    }

    /**
     * Tells Symfony to use this provider for this User class.
     */
    public function supportsClass($class)
    {
        return Account::class === $class;
    }
}