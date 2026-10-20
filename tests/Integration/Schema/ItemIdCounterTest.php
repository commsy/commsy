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

namespace Tests\Integration\Schema;

use App\Entity\Discussionarticles;
use App\Entity\Discussions;
use App\Entity\Room;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * items.item_id is the only id source: every other table that keys on item_id
 * reuses the number of its items row and must not count on its own.
 */
final class ItemIdCounterTest extends KernelTestCase
{
    public function testOnlyItemsCountsItemIds(): void
    {
        self::bootKernel();
        $connection = self::getContainer()->get(EntityManagerInterface::class)->getConnection();

        $counting = $connection->fetchFirstColumn(
            "SELECT table_name FROM information_schema.columns
             WHERE table_schema = DATABASE() AND column_name = 'item_id' AND extra LIKE '%auto_increment%'
             ORDER BY table_name"
        );

        self::assertSame(['items'], $counting);
    }

    public function testTheEntitiesTakeTheIdOfTheirItemsRow(): void
    {
        self::bootKernel();
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);

        foreach ([Discussions::class, Discussionarticles::class, Room::class] as $entity) {
            self::assertSame(
                ClassMetadata::GENERATOR_TYPE_NONE,
                $entityManager->getClassMetadata($entity)->generatorType,
                $entity.' must not let the database generate its id',
            );
        }
    }
}
