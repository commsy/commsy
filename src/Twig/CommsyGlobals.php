<?php


namespace App\Twig;


use App\Entity\Server;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

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

    public function __construct(EntityManagerInterface $entityManager, ParameterBagInterface $parameterBag)
    {
        $this->entityManager = $entityManager;
        $this->parameterBag = $parameterBag;
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