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

namespace Tests\Integration\Entity;

use App\Repository\ServerRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\File\File;

/**
 * The server logo is injected on load (vich_uploader inject_on_load), which
 * touches modification_date. That value must suit the column's mutable
 * datetime type, or every later flush with the server loaded fails.
 */
final class ServerLogoTest extends KernelTestCase
{
    public function testAServerWithAnInjectedLogoCanBeFlushed(): void
    {
        self::bootKernel();
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $server = self::getContainer()->get(ServerRepository::class)->getServer();

        $server->setLogoImageFile(new File(__FILE__));
        $entityManager->flush();

        $stored = $entityManager->getConnection()->fetchOne(
            'SELECT modification_date FROM server WHERE item_id = ?',
            [$server->getId()]
        );
        self::assertSame((new DateTime())->format('Y-m-d'), substr((string) $stored, 0, 10));
    }
}
