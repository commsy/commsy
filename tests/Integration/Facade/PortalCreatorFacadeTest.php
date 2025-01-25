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

namespace Tests\Integration\Facade;

use App\Entity\AuthSource;
use App\Entity\Portal;
use App\Facade\PortalCreatorFacade;
use App\Repository\AuthSourceRepository;
use App\Repository\PortalRepository;
use App\Repository\TranslationRepository;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class PortalCreatorFacadeTest extends KernelTestCase
{
    private ?EntityManager $entityManager;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();

        $this->entityManager = $kernel->getContainer()
            ->get('doctrine')
            ->getManager();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // doing this is recommended to avoid memory leaks
        $this->entityManager->close();
        $this->entityManager = null;
    }

    public function testCreatePortal()
    {
        $container = self::getContainer();

        /** @var PortalCreatorFacade $portalCreator */
        $portalCreator = $container->get(PortalCreatorFacade::class);

        $portal = new Portal();
        $portal->setTitle('Testportal');
        $portal->setStatus(1);

        $portalCreator->persistPortal($portal);

        $this->assertNotEmpty($portal->getAuthSources());

        $portalRepository = $container->get(PortalRepository::class);
        $portal = $portalRepository->findOneBy(['title' => 'Testportal']);
        $this->assertInstanceOf(Portal::class, $portal);

        $authSourceRepository = $container->get(AuthSourceRepository::class);
        $authSource = $authSourceRepository->findOneBy(['portal' => $portal]);
        $this->assertInstanceOf(AuthSource::class, $authSource);

        $translationRepository = $container->get(TranslationRepository::class);
        $translations = $translationRepository->findBy(['contextId' => $portal->getId()]);
        $this->assertCount(3, $translations);

        //EMAIL_REGEX_ERROR
        //REGISTRATION_USERNAME_HELP
        //ROOM_SETTINGS_SLUG_HELP
    }
}
