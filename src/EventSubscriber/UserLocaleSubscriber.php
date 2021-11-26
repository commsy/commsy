<?php


namespace App\EventSubscriber;


use App\Entity\Account;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\SecurityEvents;

/**
 * Class UserLocaleSubscriber
 * @package App\EventSubscriber
 *
 * This subscriber will update the user's session after the login.
 * See https://symfony.com/doc/4.4/session/locale_sticky_session.html for more information.
 */
class UserLocaleSubscriber implements EventSubscriberInterface
{
    /**
     * @var SessionInterface
     */
    private SessionInterface $session;

    /**
     * UserLocaleSubscriber constructor.
     * @param SessionInterface $session
     */
    public function __construct(SessionInterface $session)
    {
        $this->session = $session;
    }

    /**
     * @return string[]
     */
    public static function getSubscribedEvents()
    {
        return [
            SecurityEvents::INTERACTIVE_LOGIN => 'onInteractiveLogin',
        ];
    }

    /**
     * @param InteractiveLoginEvent $event
     */
    public function onInteractiveLogin(InteractiveLoginEvent $event)
    {
        /** @var Account $account */
        $account = $event->getAuthenticationToken()->getUser();

        if (!$account instanceof Account) {
            return;
        }

        $this->session->set('_locale', $account->getLanguage());
    }
}