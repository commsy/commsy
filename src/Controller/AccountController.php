<?php

namespace App\Controller;

use App\Entity\Account;
use App\Entity\AuthSource;
use App\Entity\Portal;
use App\Facade\AccountCreatorFacade;
use App\Form\Type\SignUpFormType;
use App\Services\LegacyEnvironment;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class AccountController extends AbstractController
{
    /**
     * @Route("/register/{id}")
     * @Template()
     * @ParamConverter("portal", class="App\Entity\Portal")
     * @param Portal $portal
     * @param Request $request
     * @param UserPasswordEncoderInterface $passwordEncoder
     * @param AccountCreatorFacade $accountFacade
     * @param LegacyEnvironment $legacyEnvironment
     * @return array
     */
    public function signUp(
        Portal $portal,
        Request $request,
        UserPasswordEncoderInterface $passwordEncoder,
        AccountCreatorFacade $accountFacade,
        LegacyEnvironment $legacyEnvironment
    ) {
        $legacyEnvironment->getEnvironment()->setCurrentPortalID($portal->getId());

        $account = new Account();

        $form = $this->createForm(SignUpFormType::class, $account);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $localAuthSource = $portal->getAuthSources()->filter(function(AuthSource $authSource) {
                return $authSource->getType() === 'local';
            })->first();

            $account->setAuthSource($localAuthSource);
            $account->setContextId($portal->getId());
            $account->setLanguage('de');

            $password = $passwordEncoder->encodePassword($account, $account->getPlainPassword());
            $account->setPassword($password);

            $accountFacade->persistNewAccount($account);
        }

        return [
            'form' => $form->createView(),
        ];
    }
}
