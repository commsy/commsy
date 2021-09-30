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
     * @param $username
     * @return Account|null
     *
     * @throws UsernameNotFoundException if the user is not found
     */
    public function loadUserByUsername($username): ?Account
    {
        // Load a User object from your data source or throw UsernameNotFoundException.
        // The $username argument may not actually be a username:
        // it is whatever value is being returned by the getUsername()
        // method in your User class.
        $contextId = $this->extractContexIdFromRequest();
        $authSourceId = $this->extractAuthSourceIdFromRequest();

        try {
            $authSource = $this->entityManager->getRepository(AuthSource::class)->find($authSourceId);
            $user = $this->entityManager->getRepository(Account::class)
                ->findOneByCredentials($username, $contextId, $authSource);

            if (!$user) {
                throw new UsernameNotFoundException();
            }

            return $user;
        } catch (NonUniqueResultException $e) {
        }

        return null;
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
     * @param UserInterface $user
     * @return Account|null
     *
     * @throws UsernameNotFoundException if the user is not found
     */
    public function refreshUser(UserInterface $user): ?Account
    {
        if (!$user instanceof Account) {
            throw new UnsupportedUserException(sprintf('Invalid user class "%s".', get_class($user)));
        }

        // Return a User object after making sure its data is "fresh".
        // Or throw a UsernameNotFoundException if the user no longer exists.
        $contextId = $this->extractContexIdFromRequest();
        $authSourceId = $this->extractAuthSourceIdFromRequest();

        try {
            $authSource = $this->entityManager->getRepository(AuthSource::class)->find($authSourceId);
            $user = $this->entityManager->getRepository(Account::class)
                ->findOneByCredentials($user->getUsername(), $contextId, $authSource);

            if (!$user) {
                throw new UsernameNotFoundException();
            }

            return $user;
        } catch (NonUniqueResultException $e) {
        }

        return null;
    }

    /**
     * Tells Symfony to use this provider for this User class.
     */
    public function supportsClass($class)
    {
        return Account::class === $class;
    }

    /**
     * Extracts context id from the request
     *
     * @return int
     */
    private function extractContexIdFromRequest(): int
    {
        $currentRequest = $this->requestStack->getCurrentRequest();
        if (!$currentRequest) {
            throw new UsernameNotFoundException();
        }

        $session = $currentRequest->getSession();
        $contextId = $session->get('context');
        $contextId = $session->get('takeover_context', $contextId);
        $session->remove('takeover_context');

        if (!$contextId) {
            throw new UsernameNotFoundException();
        }

        return $contextId;
    }

    /**
     * Extracts auth source id from the request
     *
     * @return int
     */
    private function extractAuthSourceIdFromRequest(): int
    {
        $currentRequest = $this->requestStack->getCurrentRequest();
        if (!$currentRequest) {
            throw new UsernameNotFoundException();
        }

        $session = $currentRequest->getSession();
        $authSourceId = $session->get('authSourceId');
        $authSourceId = $session->get('takeover_authSourceId', $authSourceId);
        $session->remove('takeover_authSourceId');

        if (!$authSourceId) {
            throw new UsernameNotFoundException();
        }

        return $authSourceId;
    }
}