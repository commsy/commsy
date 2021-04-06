<?php


namespace App\Security;


use App\Entity\Account;
use App\Entity\AuthSourceShibboleth;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
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
     * @var UrlGeneratorInterface
     */
    private $urlGenerator;

    /**
     * LogoutSuccessHandler constructor.
     * @param Security $security
     * @param HttpUtils $httpUtils
     * @param string $targetUrl
     */
    public function __construct(
        Security $security,
        HttpUtils $httpUtils,
        UrlGeneratorInterface $urlGenerator,
        string $targetUrl = '/'
    ) {
        $this->security = $security;
        $this->urlGenerator = $urlGenerator;

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
            } else {
                // Redirect to portal login if we find the id in the session
                $session = $request->getSession();
                if ($session->has('context')) {
                    $loginUrl = $this->urlGenerator->generate('app_login', [
                        'context' => $session->get('context'),
                    ]);
                    return $this->httpUtils->createRedirectResponse($request, $loginUrl);
                }
            }
        }

        return $this->httpUtils->createRedirectResponse($request, $this->targetUrl);
    }
}