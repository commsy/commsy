<?php


namespace App\Twig;


use App\Entity\Portal;
use App\Entity\Room;
use App\Entity\Server;
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

    public function __construct(
        EntityManagerInterface $entityManager,
        ParameterBagInterface $parameterBag,
        RequestStack $requestStack
    ) {
        $this->entityManager = $entityManager;
        $this->parameterBag = $parameterBag;
        $this->requestStack = $requestStack;
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
        $request = $this->requestStack->getCurrentRequest();
        if ($request !== null) {
            $portalId = $request->attributes->get('portalId')
                ?? $request->attributes->get('context');
            if ($portalId !== null) {
                return $this->entityManager->getRepository(Portal::class)->find($portalId);
            }

            $roomId = $request->attributes->get('roomId');
            if ($roomId !== null) {
                /** @var Room $room */
                $room = $this->entityManager->getRepository(Room::class)->find($roomId);
                if ($room !== null) {
                    return $this->entityManager->getRepository(Portal::class)->find($room->getContextId());
                }
            }
        }

        return null;
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