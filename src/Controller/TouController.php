<?php

namespace App\Controller;

use App\Entity\Account;
use App\Entity\Portal;
use App\Form\Type\TouAcceptType;
use App\Services\LegacyEnvironment;
use App\Utils\UserService;
use DateTimeImmutable;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;

class TouController extends AbstractController
{
    /**
     * @Route("/portal/{portalId}/terms")
     * @ParamConverter("portal", class="App\Entity\Portal", options={"id" = "portalId"})
     * @Template("tou/portal.html.twig")
     * @param Portal $portal
     * @param Security $security
     * @param Request $request
     * @param UserService $userService
     * @return array|RedirectResponse
     */
    public function portal(
        Portal $portal,
        Security $security,
        Request $request,
        UserService $userService
    ) {
        if (!$portal->hasAGBEnabled()) {
            throw $this->createNotFoundException('terms are disabled');
        }

        /** @var Account $account */
        $account = $security->getUser();
        $portalUser = $userService->getPortalUser($account);

        $form = $this->createForm(TouAcceptType::class, null, [
            'uikit3' => true,
        ]);

        if ($portalUser->getAGBAcceptanceDate() < $portal->getAGBChangeDate()) {
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                if ($form->get('decline')->isClicked()) {
                    return $this->redirectToRoute('app_account_deleteaccount', [
                        'portalId' => $portal->getId(),
                    ]);
                }

                if ($form->get('accept')->isClicked()) {
                    $portalUser->setAGBAcceptanceDate(new DateTimeImmutable());
                    $portalUser->save();

                    return $this->redirect($request->get('redirect'));
                }
            }
        }

        return [
            'form' => $form->createView(),
            'portal' => $portal,
            'portalUser' => $portalUser,
        ];
    }

    /**
     * @Route("/room/{roomId}/terms")
     * @Template("tou/room.html.twig")
     * @param LegacyEnvironment $legacyEnvironment
     * @param Request $request
     * @param int $roomId
     * @return array|RedirectResponse
     */
    public function room(
        LegacyEnvironment $legacyEnvironment,
        Request $request,
        int $roomId
    ) {
        $legacyEnvironment = $legacyEnvironment->getEnvironment();
        $currentContext = $legacyEnvironment->getCurrentContextItem();

        $touText = $currentContext->getAGBTextArray()[strtoupper($legacyEnvironment->getUserLanguage())];

        $form = $this->createForm(TouAcceptType::class, null, [
            'uikit3' => false,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $currentUser = $legacyEnvironment->getCurrentUserItem();

            if ($form->get('decline')->isClicked()) {
                // Otherwise redirect to the room cancel membership page
                return $this->redirectToRoute('app_profile_deleteroomprofile', [
                    'roomId' => $roomId,
                    'itemId' => $currentUser->getItemID(),
                ]);
            }

            if ($form->get('accept')->isClicked()) {
                $currentUser->setAGBAcceptanceDate(new DateTimeImmutable());
                $currentUser->save();

                return $this->redirect($request->get('redirect'));
            }
        }

        return [
            'context' => $currentContext,
            'touText' => $touText,
            'form' => $form->createView(),
        ];
    }
}
