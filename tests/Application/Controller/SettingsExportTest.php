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
use App\Export\RoomExportStatus;
use App\Message\RoomExportRequested;
use App\Repository\RoomExportRepository;
use Symfony\Component\Messenger\Transport\InMemory\InMemoryTransport;
use Tests\Application\AbstractApplicationTestCase;
use Tests\Story\AccountStory;
use Zenstruck\Foundry\Attribute\WithStory;
use Zenstruck\Foundry\Test\Factories;

/**
 * The export hands out a room's whole content in one file, so who may ask for
 * one and who may fetch it are the properties worth pinning.
 */
#[WithStory(AccountStory::class)]
class SettingsExportTest extends AbstractApplicationTestCase
{
    use Factories;

    private Account $account;
    private int $portalId;
    private int $roomId;
    private InMemoryTransport $transport;

    /** @var array<int, string> CSRF token per room, kept across page renders */
    private array $tokens = [];

    public function setUp(): void
    {
        parent::setUp();

        $this->account = AccountStory::get('account');
        $this->portalId = $this->account->getPortal()->getId();

        $this->loginAsUser(
            $this->portalId,
            $this->account->getUsername(),
            $this->account->getPlainPassword()
        );

        // Whoever creates a room is its moderator.
        $this->roomId = $this->createRoom($this->portalId, 'Export-Raum');

        $this->transport = self::getContainer()->get('messenger.transport.async');
        $this->transport->reset();
    }

    public function testPageRendersForAModerator(): void
    {
        $this->client->request('GET', "/room/{$this->roomId}/settings/export");

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form[action$="/settings/export/start"]');
    }

    public function testStartingAnExportRecordsItAndHandsItToAWorker(): void
    {
        $this->startExport();

        $this->assertResponseRedirects("/room/{$this->roomId}/settings/export");

        $exports = $this->exportRepository()->findBy([]);
        $this->assertCount(1, $exports);
        $this->assertSame(RoomExportStatus::Pending, $exports[0]->getStatus());
        $this->assertSame($this->roomId, $exports[0]->getRoom()->getItemId());

        // The room is not read in the request that asked for the export: how
        // long it takes follows from its size, which nothing knows up front.
        $sent = $this->transport->getSent();
        $this->assertCount(1, $sent);
        $this->assertInstanceOf(RoomExportRequested::class, $sent[0]->getMessage());
        $this->assertSame($exports[0]->getId(), $sent[0]->getMessage()->getRoomExportId());
    }

    public function testTheRequestingModeratorIsRecordedByNameNotByRelation(): void
    {
        $this->startExport();

        $export = $this->exportRepository()->findBy([])[0];

        // A room membership disappears when someone leaves; the record of who
        // asked for an archive of the room should not go with it.
        $this->assertNotNull($export->getRequestedByUserId());
        $this->assertNotEmpty($export->getRequestedByName());
    }

    public function testASecondExportOfTheSameRoomIsRefused(): void
    {
        $this->startExport();
        $this->transport->reset();

        $this->startExport();

        $this->assertCount(1, $this->exportRepository()->findBy([]), 'no second row');
        $this->assertCount(0, $this->transport->getSent(), 'and no second message');
    }

    public function testStartingWithoutATokenIsRefused(): void
    {
        $this->client->request('POST', "/room/{$this->roomId}/settings/export/start");

        $this->assertAccessDenied('a post without a token starts nothing');
        $this->assertCount(0, $this->exportRepository()->findBy([]));
    }

    public function testAnExportOfAnotherRoomIsNotServedHere(): void
    {
        $otherRoomId = $this->createRoom($this->portalId, 'Fremder Raum');
        $this->startExport($otherRoomId);

        $foreignExport = $this->exportRepository()->findBy([])[0];

        // Being a moderator of this room says nothing about the other one.
        $this->client->request(
            'GET',
            "/room/{$this->roomId}/settings/export/{$foreignExport->getId()}/download"
        );

        $this->assertResponseStatusCodeSame(404);
    }

    public function testAFinishedExportWhoseFileExpiredSaysSoInsteadOfFailing(): void
    {
        $this->startExport();

        $export = $this->exportRepository()->findBy([])[0];
        $export->markFinished('room-1-export-1.zip', 1234);
        $this->exportRepository()->save($export);

        // Nothing ever wrote the file — the same situation as after the cleanup
        // swept it by age.
        $this->client->request(
            'GET',
            "/room/{$this->roomId}/settings/export/{$export->getId()}/download"
        );

        $this->assertResponseRedirects("/room/{$this->roomId}/settings/export");
    }

    public function testAnonymousAccessIsRefused(): void
    {
        $this->logout();

        $this->client->request('GET', "/room/{$this->roomId}/settings/export");

        $this->assertAccessDenied('anonymous visitors cannot export a room');
        $this->assertStringNotContainsString(
            'settings/export',
            (string) $this->client->getResponse()->headers->get('Location', '')
        );
    }

    /**
     * Denial in a room context is a redirect to the room, not a 403 — see
     * App\Security\AccessDeniedHandler. What matters is that no content comes
     * back; a 5xx is explicitly not a denial, or a crashing template would count
     * as protected.
     */
    private function assertAccessDenied(string $message): void
    {
        $status = $this->client->getResponse()->getStatusCode();

        $this->assertContains($status, [301, 302, 401, 403], $message." (got HTTP $status)");
    }

    private function startExport(?int $roomId = null): void
    {
        $roomId ??= $this->roomId;

        $crawler = $this->client->request('GET', "/room/{$roomId}/settings/export");
        $this->assertResponseIsSuccessful();

        $field = $crawler->filter('form[action$="/settings/export/start"] input[name="_token"]');

        // Once an export is under way the page stops offering the button. Reuse
        // the token from the earlier render so the request is well-formed and it
        // is the server, not a missing token, that refuses.
        $token = 0 === $field->count()
            ? ($this->tokens[$roomId] ?? '')
            : (string) $field->attr('value');

        $this->tokens[$roomId] = $token;

        $this->client->request('POST', "/room/{$roomId}/settings/export/start", [
            '_token' => $token,
        ]);
    }

    private function exportRepository(): RoomExportRepository
    {
        return self::getContainer()->get(RoomExportRepository::class);
    }
}
