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
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

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
     * @return array|Response
     */
    public function signUp(
        Portal $portal,
        Request $request,
        UserPasswordEncoderInterface $passwordEncoder,
        AccountCreatorFacade $accountFacade,
        LegacyEnvironment $legacyEnvironment,
        ValidatorInterface $validator
    ) {
        $legacyEnvironment->getEnvironment()->setCurrentPortalID($portal->getId());

        $localAuthSource = $portal->getAuthSources()->filter(function(AuthSource $authSource) {
            return $authSource->getType() === 'local';
        })->first();

        $account = new Account();
        $account->setAuthSource($localAuthSource);
        $account->setContextId($portal->getId());

        $form = $this->createForm(SignUpFormType::class, $account);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if ($form->get('cancel')->isClicked()) {
                return $this->redirectToRoute('app_login', [
                    'context' => $portal->getId(),
                ]);
            }
            $account->setLanguage('de');

            $password = $passwordEncoder->encodePassword($account, $account->getPlainPassword());
            $account->setPassword($password);

            $accountFacade->persistNewAccount($account);

            return $this->redirectToRoute('app_login', [
                'context' => $portal->getId(),
            ]);
        }

        return [
            'portal' => $portal,
            'form' => $form->createView(),
        ];
    }
}
