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

namespace Tests\Integration\Services;

use App\Entity\Account;
use App\Entity\Room;
use App\Services\CurrentContextResolver;
use App\Services\LegacyEnvironment;
use App\Services\PrintService;
use App\Utils\FileService;
use App\Repository\FilesRepository;
use Knp\Snappy\Pdf;
use ReflectionMethod;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Tests\Factory\RoomFactory;
use Tests\Story\AccountStory;
use Zenstruck\Foundry\Attribute\WithStory;

/**
 * Schloss 2 / Schritt 3.5 — characterization of PrintService::setOptions(),
 * the (private) method that feeds the legacy getCurrentContextItem() /
 * getCurrentPortalItem() into the PDF header.
 *
 * Pinned faithful seam: header-left == the current context room's
 * title; for a private room the title switches to the current portal.
 * Invoked via reflection with a mocked Pdf engine (capture setOptions)
 * and a real LegacyEnvironment, so the assertion is the context seam,
 * not the PDF backend.
 *
 * Reuse-priority suite; must stay green when the context seam is
 * rerouted onto CurrentContextResolver.
 */
#[WithStory(AccountStory::class)]
final class PrintServiceCurrentContextCharacterizationTest extends KernelTestCase
{
    private \cs_environment $legacyEnvironment;
    private Account $account;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->legacyEnvironment = self::getContainer()
            ->get(LegacyEnvironment::class)
            ->getEnvironment();
        $this->account = AccountStory::get('account');
    }

    public function testHeaderLeftIsCurrentContextRoomTitle(): void
    {
        $room = $this->room('project', 'Schloss2 PrintService Project');
        $this->legacyEnvironment->setCurrentContextID($room->getItemId());

        $captured = $this->invokeSetOptions();

        self::assertArrayHasKey('header-left', $captured);
        self::assertSame($room->getTitle(), $captured['header-left']);
    }

    public function testPrivateRoomSwitchesHeaderToCurrentPortal(): void
    {
        $room = $this->room('privateroom', 'Schloss2 PrintService Private');
        $this->legacyEnvironment->setCurrentContextID($room->getItemId());

        $portalTitle = $this->legacyEnvironment->getCurrentPortalItem()?->getTitle();
        self::assertNotNull($portalTitle);

        $captured = $this->invokeSetOptions();

        // private room -> setOptions() swaps to the current portal item
        self::assertSame($portalTitle, $captured['header-left']);
    }

    // ---- helpers

    /**
     * Builds a PrintService with a mocked Pdf (to capture setOptions)
     * and the real LegacyEnvironment, then reflection-invokes the
     * private setOptions().
     *
     * @return array<string, mixed> the options array passed to Pdf::setOptions()
     */
    private function invokeSetOptions(): array
    {
        $captured = [];
        $pdf = $this->createMock(Pdf::class);
        $pdf->method('setOptions')->willReturnCallback(
            function (array $options) use (&$captured): void {
                $captured = $options;
            }
        );

        $request = new Request();
        $request->setSession(new Session(new MockArraySessionStorage()));
        $requestStack = new RequestStack();
        $requestStack->push($request);

        $legacyEnvironment = self::getContainer()->get(LegacyEnvironment::class);

        $service = new PrintService(
            $legacyEnvironment,
            self::getContainer()->get(CurrentContextResolver::class),
            $pdf,
            $requestStack,
            self::getContainer()->get(FilesRepository::class),
            self::getContainer()->get(FileService::class),
            '',
            '',
            'test',
        );

        (new ReflectionMethod($service, 'setOptions'))->invoke($service);

        return $captured;
    }

    private function room(string $type, string $title): Room
    {
        $state = 'privateroom' === $type ? 'privateRoom' : 'project';

        return RoomFactory::new()->{$state}()->create([
            'title' => $title,
            'contextId' => $this->account->getPortal()?->getId(),
            'portal' => $this->account->getPortal(),
        ]);
    }
}
