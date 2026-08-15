<?php

/*
 * This file is part of CommSy.
 *
 * (c) Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
 * Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
 * Edouard Simon, Monique Strauss, Jose Mauel Gonzalez Vazquez, Johannes Schultze
 */

declare(strict_types=1);

namespace Tests\Application\Controller;

use App\Entity\Account;
use App\Entity\Room;
use App\Entity\User;
use App\Repository\RoomExportRepository;
use Tests\Application\AbstractApplicationTestCase;
use Tests\Story\RoomWithMemberStory;
use Zenstruck\Foundry\Attribute\WithStory;
use Zenstruck\Foundry\Test\Factories;

/**
 * The export hands out a room's whole content in one file. Everyone below
 * moderation must be turned away from it, which is the one property that cannot
 * be checked from the moderator's own session.
 *
 * Separate from SettingsExportTest because the member has to be the only login
 * of the request cycle — the login helper needs the login form to render, which
 * it does not for a session that is already authenticated.
 */
#[WithStory(RoomWithMemberStory::class)]
class SettingsExportAuthorizationTest extends AbstractApplicationTestCase
{
    use Factories;

    private Account $member;
    private Room $room;
    private int $roomId;

    public function setUp(): void
    {
        parent::setUp();

        $this->member = RoomWithMemberStory::get('account');
        $this->room = RoomWithMemberStory::get('room');
        $this->roomId = $this->room->getItemId();

        /** @var User $roomUser */
        $roomUser = RoomWithMemberStory::get('roomUser');
        $this->assertSame(2, $roomUser->getStatus(), 'the story member is an ordinary member, not moderation');
    }

    public function testAnOrdinaryMemberCannotOpenTheExportPage(): void
    {
        $this->loginAsMember();

        $this->client->request('GET', "/room/{$this->roomId}/settings/export");

        $this->assertAccessDenied('an ordinary member must not reach the export page');
    }

    public function testAnOrdinaryMemberCannotStartAnExport(): void
    {
        $this->loginAsMember();

        $this->client->request('POST', "/room/{$this->roomId}/settings/export/start");

        $this->assertAccessDenied('an ordinary member must not start an export');
        $this->assertCount(
            0,
            self::getContainer()->get(RoomExportRepository::class)->findBy([]),
            'and nothing was recorded'
        );
    }

    public function testAnOrdinaryMemberCannotDownloadAnExport(): void
    {
        $this->loginAsMember();

        $this->client->request('GET', "/room/{$this->roomId}/settings/export/1/download");

        $this->assertAccessDenied('an ordinary member must not download an archive');
    }

    private function loginAsMember(): void
    {
        $this->loginAsUser(
            $this->member->getPortal()->getId(),
            $this->member->getUsername(),
            (string) $this->member->getPlainPassword()
        );
    }

    /**
     * Denial inside a room context is a redirect to the room, not a 403 — see
     * App\Security\AccessDeniedHandler. What matters is that no content comes
     * back; a 5xx is explicitly not a denial, or a crashing template would count
     * as protected.
     */
    private function assertAccessDenied(string $message): void
    {
        $status = $this->client->getResponse()->getStatusCode();

        $this->assertContains($status, [301, 302, 401, 403], $message." (got HTTP $status)");
    }
}
