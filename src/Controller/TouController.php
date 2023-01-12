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

namespace App\Controller;

use App\Entity\Account;
use App\Entity\Portal;
use App\Form\Type\TouAcceptType;
use App\Services\LegacyEnvironment;
use App\Utils\UserService;
use DateTimeImmutable;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;

class TouController extends AbstractController
{
    #[Route(path: '/portal/{portalId}/terms')]
    #[ParamConverter('portal', class: Portal::class, options: ['id' => 'portalId'])]
    public function portal(
        Portal $portal,
        Security $security,
        Request $request,
        UserService $userService
    ): Response {
        if (!$portal->hasAGBEnabled()) {
            throw $this->createNotFoundException('terms are disabled');
        }

        /** @var Account $account */
        $account = $security->getUser();

        $form = $this->createForm(TouAcceptType::class, null, [
            'uikit3' => true,
        ]);

        $portalUser = null;
        if (null !== $account) {
            $portalUser = $userService->getPortalUser($account);

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
        }

        return $this->render('tou/portal.html.twig', [
            'form' => $form->createView(),
            'portal' => $portal,
            'portalUser' => $portalUser,
        ]);
    }

    #[Route(path: '/room/{roomId}/terms')]
    public function room(
        LegacyEnvironment $legacyEnvironment,
        Request $request,
        int $roomId
    ): Response {
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

        return $this->render('tou/room.html.twig', [
            'context' => $currentContext,
            'touText' => $touText,
            'form' => $form->createView(),
        ]);
    }
}
