<?php


namespace App\Language;


use App\Proxy\PortalProxy;
use cs_environment;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class LanguageDecisionStrategy
{
    /**
     * @var SessionInterface
     */
    private SessionInterface $session;

    public function __construct(SessionInterface $session)
    {
        $this->session = $session;
    }

    public function decide(object $contextItem, cs_environment $environment): string
    {
        if (get_class($contextItem) == PortalProxy::class) {
            return $this->session->get('_locale', 'de');
        }

        $contextLanguage = $contextItem->getLanguage();
        if ($contextLanguage === 'user') {
            return $environment->getUserLanguage();
        }

        return $contextLanguage;
    }
}