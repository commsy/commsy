<?php

namespace App\Controller;

use App\Entity\Portal;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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
}
