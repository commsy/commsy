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

use App\Entity\Portal;
use App\Entity\Room;
use App\Entity\Server;
use App\Entity\User;
use App\Facade\PortalCreatorFacade;
use App\Form\Type\Portal\PortalGeneralType;
use App\Repository\PortalRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class ServerController extends AbstractController
{
    /**
     * Calls the external website URL defined for this installation, or,
     * if no URL was defined, displays the list of active portals.
     */
    #[Route(path: '/portal/linkout')]
    public function linkout(EntityManagerInterface $entityManager): RedirectResponse
    {
        $server = $entityManager->getRepository(Server::class)->getServer();
        $url = $server->getCommsyIconLink();

        if ($url) {
            return new RedirectResponse($url);
        } else {
            return $this->redirectToRoute('app_server_show');
        }
    }

    /**
     * Shows a list of all active portals in this installation.
     */
    #[Route(path: '/portal/show')]
    public function show(
        EntityManagerInterface $entityManager,
        PortalRepository $portalRepository
    ): Response
    {
        $server = $entityManager->getRepository(Server::class)->getServer();

        $activePortals = $portalRepository->findAllActive();

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

        return $this->render('server/show.html.twig', [
            'activePortals' => $activePortals,
            'usageInformation' => $usageInformation,
            'totalMaxActivity' => $totalMaxActivity,
            'server' => $server,
        ]);
    }

    /**
     * Creates a new portal.
     */
    #[Route(path: '/portal/create')]
    #[IsGranted('ROLE_ROOT')]
    public function createPortal(
        PortalCreatorFacade $portalCreator,
        Request $request
    ): Response {
        $portal = new Portal();

        $form = $this->createForm(PortalGeneralType::class, $portal);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $portal->setStatus(1);
            $portalCreator->persistPortal($portal);

            return $this->redirectToRoute('app_server_show');
        }

        return $this->render('server/create_portal.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route(path: '/server/impressum')]
    public function impressum(EntityManagerInterface $entityManager): Response
    {
        $server = $entityManager->getRepository(Server::class)->getServer();
        if (!$server->hasImpressumEnabled()) {
            throw $this->createNotFoundException();
        }

        return $this->render('server/impressum.html.twig', [
            'content' => $server->getImpressumText(),
        ]);
    }

    #[Route(path: '/server/data_privacy')]
    public function dataPrivacy(EntityManagerInterface $entityManager): Response
    {
        $server = $entityManager->getRepository(Server::class)->getServer();
        if (!$server->hasDataPrivacyEnabled()) {
            throw $this->createNotFoundException();
        }

        return $this->render('server/data_privacy.html.twig', [
            'content' => $server->getDataPrivacyText(),
        ]);
    }

    #[Route(path: '/server/accessibility')]
    public function accessibility(EntityManagerInterface $entityManager): Response
    {
        $server = $entityManager->getRepository(Server::class)->getServer();
        if (!$server->hasAccessibilityEnabled()) {
            throw $this->createNotFoundException();
        }

        return $this->render('server/accessibility.html.twig', [
            'content' => $server->getAccessibilityText(),
        ]);
    }
}
