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

use App\Cron\Tasks\CronCleanTemporary;
use App\Entity\Account;
use App\Entity\RoomExport;
use App\Export\RoomExportStatus;
use App\Message\RoomExportRequested;
use App\Repository\RoomExportRepository;
use App\Repository\RoomRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
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

    /**
     * The page says by when an archive is gone, in a translation. If the
     * cleanup's limit is changed and the text is not, this is where it shows.
     */
    public function testThePageStatesTheLatestTimeAnArchiveIsKept(): void
    {
        $this->client->request('GET', "/room/{$this->roomId}/settings/export");

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('main', (string) CronCleanTemporary::LATEST_REMOVAL_HOURS);
    }

    /**
     * Once its archive is gone at the latest, a row has nothing left to say and
     * would only read "no longer available" for as long as it stood there.
     */
    public function testAnExportOlderThanItsArchiveCanBeIsNoLongerListed(): void
    {
        $room = self::getContainer()->get(RoomRepository::class)->find($this->roomId);

        $beyond = CronCleanTemporary::LATEST_REMOVAL_HOURS + 1;
        $within = CronCleanTemporary::LATEST_REMOVAL_HOURS - 1;

        foreach ([$beyond, $within] as $hours) {
            $export = new RoomExport($room);
            $export->markFinished('room-'.$this->roomId.'-export-'.$hours.'.zip', 10);
            $this->exportRepository()->save($export);
            $this->backdate($export, "-{$hours} hours");
        }

        $crawler = $this->client->request('GET', "/room/{$this->roomId}/settings/export");

        $this->assertResponseIsSuccessful();
        $this->assertCount(1, $crawler->filter('tbody tr'), 'only the export within the window is listed');
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

    /**
     * A worker can be restarted or killed at any moment. The row it leaves
     * behind would otherwise keep the room's moderation waiting for a run that
     * nothing is doing.
     */
    public function testAnExportNoWorkerPickedUpIsSettledAndMakesWayForANewOne(): void
    {
        $this->startExport();

        $abandoned = $this->exportRepository()->findBy([])[0];
        $this->backdate($abandoned, '-20 minutes');
        $this->transport->reset();

        $this->startExport();

        // The client boots a fresh kernel per request, so the row has to come
        // back from the database rather than from the object held here.
        $settled = $this->exportRepository()->find($abandoned->getId());

        $this->assertSame(RoomExportStatus::Abandoned, $settled->getStatus());
        $this->assertCount(2, $this->exportRepository()->findBy([]), 'the room takes a new export');
        $this->assertCount(1, $this->transport->getSent());
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

    /**
     * requestedAt is set in the constructor and has no setter — the row is a
     * record, not a form. Ageing it for the test goes through SQL.
     */
    private function backdate(RoomExport $export, string $modifier): void
    {
        $manager = self::getContainer()->get(EntityManagerInterface::class);

        $manager->getConnection()->executeStatement(
            'UPDATE room_export SET requested_at = :requestedAt WHERE id = :id',
            [
                'requestedAt' => (new DateTimeImmutable($modifier))->format('Y-m-d H:i:s'),
                'id' => $export->getId(),
            ]
        );

        $manager->refresh($export);
    }

    private function exportRepository(): RoomExportRepository
    {
        return self::getContainer()->get(RoomExportRepository::class);
    }
}
