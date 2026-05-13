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

declare(strict_types=1);

namespace Tests\Integration\Repository;

use App\Entity\Portal;
use App\Repository\AuthSourceRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Tests\Factory\AuthSourceLocalFactory;
use Tests\Factory\PortalFactory;

final class AuthSourceRepositoryTest extends KernelTestCase
{
    private AuthSourceRepository $repository;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->repository = self::getContainer()->get(AuthSourceRepository::class);
    }

    public function testFindByPortalReturnsAuthSourcesAttachedToThatPortal(): void
    {
        /** @var Portal $portal */
        $portal = PortalFactory::createOne([
            'authSources' => [AuthSourceLocalFactory::new()],
        ]);

        $sources = $this->repository->findByPortal($portal->getId());

        self::assertNotEmpty($sources);
        foreach ($sources as $source) {
            self::assertSame($portal->getId(), $source->getPortal()?->getId());
        }
    }

    public function testFindByPortalReturnsEmptyForUnknownPortal(): void
    {
        self::assertSame([], $this->repository->findByPortal(999_999));
    }
}
