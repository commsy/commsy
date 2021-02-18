<?php


namespace App\Security;


use App\Entity\Account;
use App\Entity\AuthSourceShibboleth;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Http\HttpUtils;
use Symfony\Component\Security\Http\Logout\DefaultLogoutSuccessHandler;

class LogoutSuccessHandler extends DefaultLogoutSuccessHandler
{
    /**
     * @var Security
     */
    private $security;

    /**
     * LogoutSuccessHandler constructor.
     * @param Security $security
     * @param HttpUtils $httpUtils
     * @param string $targetUrl
     */
    public function __construct(Security $security, HttpUtils $httpUtils, string $targetUrl = '/')
    {
        $this->security = $security;

        parent::__construct($httpUtils, $targetUrl);
    }

    /**
     * {@inheritdoc}
     */
    public function onLogoutSuccess(Request $request)
    {
        /** @var Account $account */
        $account = $this->security->getUser();
        if ($account) {
            $authSource = $account->getAuthSource();
            if ($authSource instanceof AuthSourceShibboleth) {
                $logoutUrl = $authSource->getLogoutUrl();
                if ($logoutUrl !== '') {
                    return new RedirectResponse($logoutUrl);
                }
            }
        }

        return $this->httpUtils->createRedirectResponse($request, $this->targetUrl);
    }
}