<?php

namespace App\Controller;

use App\Entity\Portal;
use App\Entity\Room;
use App\Entity\Server;
use App\Entity\User;
use App\Facade\PortalCreatorFacade;
use App\Form\Type\Portal\GeneralType;
use App\Form\Type\PortalType;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
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
    public function show(EntityManagerInterface $entityManager)
    {
        $server = $entityManager->getRepository(Server::class)->getServer();

        $activePortals = $this->getDoctrine()->getRepository(Portal::class)
            ->findAllActive();

        $userRepository = $entityManager->getRepository(User::class);
        $roomRepository = $entityManager->getRepository(Room::class);

        $usageInformation = [];
        $totalMaxActivity = 0;
        foreach ($activePortals as $activePortal) {
            /** @var Portal $activePortal */
            $portalId = $activePortal->getId();
            $usageInformation[$portalId] = [
                'users' => $userRepository->getNumActiveUsersByContext($portalId),
                'rooms' => $roomRepository->getNumActiveRoomsByPortal($portalId),
            ];

            $totalMaxActivity = max($totalMaxActivity, $activePortal->getActivity());
        }

        return [
            'activePortals' => $activePortals,
            'usageInformation' => $usageInformation,
            'totalMaxActivity' => $totalMaxActivity,
            'server' => $server,
        ];
    }

    /**
     * Creates a new portal
     *
     * @Route("/portal/create")
     * @IsGranted("ROLE_ROOT")
     * @Template()
     * @param PortalCreatorFacade $portalCreator
     * @param Request $request
     * @return array|RedirectResponse
     */
    public function createPortal(
        PortalCreatorFacade $portalCreator,
        Request $request
    ) {
        $portal = new Portal();

        $form = $this->createForm(GeneralType::class, $portal);

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

    /**
     * @Route("/server/impressum")
     * @Template()
     */
    public function impressum(EntityManagerInterface $entityManager)
    {
        $server = $entityManager->getRepository(Server::class)->getServer();
        if (!$server->hasImpressumEnabled()) {
            throw $this->createNotFoundException();
        }

        return [
            'content' => $server->getImpressumText(),
        ];
    }

    /**
     * @Route("/server/data_privacy")
     * @Template()
     */
    public function dataPrivacy(EntityManagerInterface $entityManager)
    {
        $server = $entityManager->getRepository(Server::class)->getServer();
        if (!$server->hasDataPrivacyEnabled()) {
            throw $this->createNotFoundException();
        }

        return [
            'content' => $server->getDataPrivacyText(),
        ];
    }

    /**
     * @Route("/server/accessibility")
     * @Template()
     */
    public function accessibility(EntityManagerInterface $entityManager)
    {
        $server = $entityManager->getRepository(Server::class)->getServer();
        if (!$server->hasAccessibilityEnabled()) {
            throw $this->createNotFoundException();
        }

        return [
            'content' => $server->getAccessibilityText(),
        ];
    }
}
