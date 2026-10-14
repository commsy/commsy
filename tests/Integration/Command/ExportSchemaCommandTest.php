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

namespace Tests\Integration\Command;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

final class ExportSchemaCommandTest extends KernelTestCase
{
    public function testItPrintsTheSchemaAsJson(): void
    {
        $tester = new CommandTester((new Application(self::bootKernel()))->find('commsy:export:schema'));

        $tester->execute([]);

        $tester->assertCommandIsSuccessful();
        $schema = json_decode($tester->getDisplay(), true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame('http://json-schema.org/draft-07/schema#', $schema['$schema']);
        $this->assertArrayHasKey('Announcement-export', $schema['definitions']);
    }
}
