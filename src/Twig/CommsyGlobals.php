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

namespace App\Twig;

use App\Entity\Portal;
use App\Entity\Room;
use App\Entity\Server;
use App\Utils\RequestContext;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class CommsyGlobals
{
    public function __construct(private EntityManagerInterface $entityManager, private ParameterBagInterface $parameterBag, private RequestStack $requestStack, private RequestContext $requestContext)
    {
    }

    /**
     * Returns the server context entity.
     */
    public function server(): Server
    {
        return $this->entityManager->getRepository(Server::class)->getServer();
    }

    /**
     * Return the portal context entity or null.
     */
    public function portal(): ?Portal
    {
        return $this->requestContext->fetchPortal($this->requestStack->getCurrentRequest());
    }

    /**
     * Return the room context entity or null.
     */
    public function room(): ?Room
    {
        return $this->requestContext->fetchRoom($this->requestStack->getCurrentRequest());
    }

    /**
     * Returns the Commsy version string.
     */
    public function version(): string
    {
        $projectDir = $this->parameterBag->get('kernel.project_dir');

        return file_get_contents($projectDir.'/VERSION');
    }
}
