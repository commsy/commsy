<?php

namespace App\Controller;

use App\Entity\Portal;
use App\Form\Type\TouAcceptType;
use App\Services\LegacyEnvironment;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class TouController extends AbstractController
{
    /**
     * @Route("/room/{roomId}/accept")
     * @Template("tou/accept.html.twig")
     * @param LegacyEnvironment $legacyEnvironment
     * @param Request $request
     * @param int $roomId
     * @return array|RedirectResponse
     */
    public function accept(
        LegacyEnvironment $legacyEnvironment,
        Request $request,
        int $roomId
    ) {
        $legacyEnvironment = $legacyEnvironment->getEnvironment();
        $currentContext = $legacyEnvironment->getCurrentContextItem();

        $touText = $currentContext->getAGBTextArray()[strtoupper($legacyEnvironment->getUserLanguage())];

        $form = $this->createForm(TouAcceptType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $currentUser = $legacyEnvironment->getCurrentUserItem();

            if ($form->get('decline')->isClicked()) {
                // If these are portal tou send the user to the account deletion page
                if ($currentContext->isPortal()) {
                    return $this->redirectToRoute('app_profile_deleteaccount', [
                        'roomId' => $roomId,
                        'itemId' => $currentUser->getItemID(),
                    ]);
                }

                // Otherweise redirect to the room cancel membership page
                return $this->redirectToRoute('app_profile_deleteroomprofile', [
                    'roomId' => $roomId,
                    'itemId' => $currentUser->getItemID(),
                ]);
            }

            if ($form->get('accept')->isClicked()) {
                $currentUser->setAGBAcceptance();
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

    /**
     * @Route("/portal/{portalId}/terms")
     * @ParamConverter("portal", class="App\Entity\Portal", options={"id" = "portalId"})
     * @Template("tou/show.html.twig")
     *
     * @return array
     */
    public function showPortal(Portal $portal): array
    {
        if (!$portal->hasAGBEnabled()) {
            throw $this->createNotFoundException('terms are disabled');
        }

        return [
            'portal' => $portal,
        ];
    }
}
