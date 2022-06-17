<?php


namespace App\Twig;


use App\Entity\Portal;
use App\Entity\Server;
use App\Utils\RequestContext;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class CommsyGlobals
{
    /**
     * @var EntityManagerInterface
     */
    private EntityManagerInterface $entityManager;

    /**
     * @var ParameterBagInterface
     */
    private ParameterBagInterface $parameterBag;

    /**
     * @var RequestStack
     */
    private RequestStack $requestStack;

    /**
     * @var RequestContext
     */
    private RequestContext $requestContext;

    public function __construct(
        EntityManagerInterface $entityManager,
        ParameterBagInterface $parameterBag,
        RequestStack $requestStack,
        RequestContext $requestContext
    ) {
        $this->entityManager = $entityManager;
        $this->parameterBag = $parameterBag;
        $this->requestStack = $requestStack;
        $this->requestContext = $requestContext;
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
        return $this->requestContext->fetchPortal($this->requestStack->getCurrentRequest());
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