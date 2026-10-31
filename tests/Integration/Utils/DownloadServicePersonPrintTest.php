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

namespace Tests\Integration\Utils;

use App\Services\LegacyEnvironment;
use App\Utils\DownloadService;
use ReflectionMethod;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Tests\Factory\RoomUserFactory;
use Tests\Story\RoomWithMemberStory;
use Zenstruck\Foundry\Attribute\WithStory;

/**
 * A person downloaded as ZIP is printed with their own profile, not with the
 * profile of whoever downloads it.
 */
#[WithStory(RoomWithMemberStory::class)]
final class DownloadServicePersonPrintTest extends KernelTestCase
{
    public function testThePrintOfAPersonShowsThatPerson(): void
    {
        self::bootKernel();
        $room = RoomWithMemberStory::get('room');
        $downloader = RoomWithMemberStory::get('roomUser');
        $person = RoomUserFactory::createOne(['account' => \Tests\Factory\AccountFactory::createOne(), 'room' => $room]);

        $environment = self::getContainer()->get(LegacyEnvironment::class)->getEnvironment();
        $environment->setCurrentContextID($room->getItemId());
        $environment->setCurrentUserItem($environment->getUserManager()->getItem($downloader->getItemId()));
        $request = new Request();
        $request->attributes->set('roomId', $room->getItemId());
        self::getContainer()->get(RequestStack::class)->push($request);

        $detailInfo = new ReflectionMethod(DownloadService::class, 'getDetailInfo');
        $info = $detailInfo->invoke(self::getContainer()->get(DownloadService::class), $room->getItemId(), $person->getItemId());

        self::assertSame($person->getItemId(), (int) $info['user']->getItemID());
    }
}
