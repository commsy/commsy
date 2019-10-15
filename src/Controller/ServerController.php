<?php

namespace App\Controller;

use App\Entity\Portal;
use App\Facade\PortalCreatorFacade;
use App\Form\Type\PortalType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class ServerController extends AbstractController
{
    /**
     * Shows a list of all active portals in this installation
     *
     * @Route("/portal/show")
     * @Template()
     */
    public function show()
    {
        $activePortals = $this->getDoctrine()->getRepository(Portal::class)
            ->findAllActive();

        return [
            'activePortals' => $activePortals,
        ];
    }

    /**
     * Creates a new portal
     *
     * @Route("/portal/create")
     * @Template()
     */
    public function createPortal(Request $request, PortalCreatorFacade $portalCreator)
    {
        $portal = new Portal();

        $form = $this->createForm(PortalType::class, $portal);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $portal->setStatus(1);
            $portalCreator->persistPortal($portal);

            return $this->redirectToRoute('app_server_show');
        }

        return [
            'form' => $form->createView(),
        ];
    }
}
