<?php


namespace App\Twig;


use App\Entity\Portal;
use App\Entity\Room;
use App\Entity\Server;
use App\Utils\PortalGuessService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class CommsyGlobals
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var ParameterBagInterface
     */
    private $parameterBag;

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var PortalGuessService
     */
    private $portalGuessService;

    public function __construct(
        EntityManagerInterface $entityManager,
        ParameterBagInterface $parameterBag,
        RequestStack $requestStack,
        PortalGuessService $portalGuessService
    ) {
        $this->entityManager = $entityManager;
        $this->parameterBag = $parameterBag;
        $this->requestStack = $requestStack;
        $this->portalGuessService = $portalGuessService;
    }

    /**
     * Returns the server context entity
     *
     * @return Server
     */
    public function server(): Server
    {
        return $this->entityManager->getRepository(Server::class)->getServer();
    }

    /**
     * Return the portal context entity or null
     *
     * @return Portal|null
     */
    public function portal(): ?Portal
    {
        return $this->portalGuessService->fetchPortal($this->requestStack->getCurrentRequest());
    }

    /**
     * Returns the Commsy version string
     *
     * @return string
     */
    public function version(): string
    {
        $projectDir = $this->parameterBag->get('kernel.project_dir');
        return file_get_contents($projectDir . '/VERSION');
    }
}