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

namespace Tests\Integration\Files\Cleanup;

use App\Entity\User;
use App\Files\Cleanup\FindingKind;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Tests\Factory\AnnouncementFactory;
use Tests\Factory\LabelFactory;
use Tests\Story\RoomWithMemberStory;
use Zenstruck\Foundry\Attribute\WithStory;
use Zenstruck\Foundry\Test\Factories;

/**
 * Pins {@see \App\Files\Cleanup\FileAudit}: what it reports, and above all
 * what it leaves alone.
 */
#[WithStory(RoomWithMemberStory::class)]
final class FileAuditTest extends KernelTestCase
{
    use BuildsFilesDirectory;
    use Factories;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->setUpFilesDirectory();
    }

    protected function tearDown(): void
    {
        $this->tearDownFilesDirectory();
        parent::tearDown();
    }

    public function testARowWithoutLinksIsReportedOnceADayOld(): void
    {
        $old = $this->fileRow();
        $this->fileRow(ageHours: 2);

        self::assertSame([$old], $this->found($this->audit(), FindingKind::UnlinkedRow));
    }

    public function testARowIsReportedWhenEveryEntryItIsLinkedToIsGone(): void
    {
        $entry = $this->entry();
        $deleted = $this->entry();
        $this->connection->executeStatement('UPDATE items SET deletion_date = NOW() - INTERVAL 200 DAY WHERE item_id = ?', [$deleted]);
        $restorable = $this->entry();
        $this->connection->executeStatement('UPDATE items SET deletion_date = NOW() - INTERVAL 10 DAY WHERE item_id = ?', [$restorable]);

        $onDeleted = $this->fileRow();
        $this->link($onDeleted, $deleted);
        $onVanished = $this->fileRow();
        $this->link($onVanished, 999_999_002);
        $onStamped = $this->fileRow();
        $this->link($onStamped, $entry);
        $this->connection->executeStatement('UPDATE item_link_file SET deletion_date = NOW() WHERE file_id = ?', [$onStamped]);
        $alive = $this->fileRow();
        $this->link($alive, $deleted);
        $this->link($alive, $entry);
        $onPortal = $this->fileRow();
        $this->link($onPortal, $this->portalId);
        $onRestorable = $this->fileRow();
        $this->link($onRestorable, $restorable);

        $expected = [$onDeleted, $onVanished, $onStamped];
        sort($expected);
        self::assertSame($expected, $this->found($this->audit(), FindingKind::DeadLinksRow));
    }

    public function testALiveRowIsMissingOnlyWhenItsFileIsNowhere(): void
    {
        $missing = $this->fileRow();
        $this->link($missing, $this->entry());

        $present = $this->fileRow();
        $this->file($this->roomDirectory()."/$present.pdf");

        // the row points elsewhere, but the file lies where the disc manager puts it
        $misplaced = $this->fileRow(relativePath: 'elsewhere/x.pdf');
        $this->file($this->roomDirectory()."/$misplaced.pdf");

        // the extension on disk differs from the one of the name shown
        $renamed = $this->fileRow('Foto.png');
        $this->file($this->roomDirectory()."/$renamed.jpeg");

        $deleted = $this->fileRow();
        $this->connection->executeStatement('UPDATE files SET deletion_date = NOW() WHERE files_id = ?', [$deleted]);

        // an upload may still be under way
        $this->link($this->fileRow(ageHours: 2), $this->entry());

        self::assertSame([$missing], $this->found($this->audit(), FindingKind::MissingOnDisk));
    }

    public function testAnUploadOrThumbnailWithoutRowIsStray(): void
    {
        $roomId = (int) $this->room->getItemId();
        $fileId = $this->fileRow('Bild.png');
        $this->file($this->roomDirectory()."/$fileId.png");
        $this->file($this->roomDirectory()."/$fileId.png_thumb");
        // a CommSy 6 upload keeps the name it had when it was stored
        $this->file($this->roomDirectory()."/cid{$roomId}_{$fileId}_Bild vom Mai.png");
        $stray = $this->handedOutFileId();
        $strayThumbnail = $this->handedOutFileId();
        $this->file($this->roomDirectory()."/$stray.pdf");
        $this->file($this->roomDirectory()."/$strayThumbnail.png_thumb");

        $result = $this->audit();

        self::assertSame(
            [$this->roomDirectory()."/$stray.pdf", $this->roomDirectory()."/$strayThumbnail.png_thumb"],
            $this->found($result, FindingKind::StrayUpload),
        );
        self::assertSame([], $this->found($result, FindingKind::StrayImage));
        self::assertFalse($result->databaseSuspicious);
    }

    public function testAnImageIsKeptWhileASettingThatIsReadRefersToIt(): void
    {
        $roomId = (int) $this->room->getItemId();
        /** @var User $user */
        $user = RoomWithMemberStory::get('roomUser');
        $label = (int) LabelFactory::createOne(['room' => $this->room, 'creator' => $user, 'type' => 'group'])->getItemId();

        $this->addExtras('room', 'item_id', $roomId, [
            'LOGOFILENAME' => "cid{$roomId}_logo_Wappen.png",
            'BGIMAGEFILENAME' => "cid{$roomId}_bgimage_Himmel mit Wolken.jpg",
        ]);
        $this->addExtras('user', 'item_id', (int) $user->getItemId(), ['USERPICTURE' => "cid{$roomId}_{$user->getUserId()}.png"]);
        // the picture of a group is read nowhere any more
        $this->addExtras('labels', 'item_id', $label, ['LABELPICTURE' => "cid{$roomId}_Gruppe.png"]);
        $this->connection->update('portal', ['logo_filename' => 'logo-abc.png'], ['id' => $this->portalId]);

        foreach (["cid{$roomId}_logo_Wappen.png", "cid{$roomId}_bgimage_Himmel mit Wolken.jpg", "cid{$roomId}_{$user->getUserId()}.png", "cid{$roomId}_Gruppe.png", "cid{$roomId}_logo_alt.png"] as $name) {
            $this->file($this->roomDirectory()."/$name");
        }
        $this->file('portal/logos/logo-abc.png');
        $this->file('portal/logos/logo-old.png');

        self::assertSame(
            [$this->roomDirectory()."/cid{$roomId}_Gruppe.png", $this->roomDirectory()."/cid{$roomId}_logo_alt.png", 'portal/logos/logo-old.png'],
            $this->found($this->audit(), FindingKind::StrayImage),
        );
    }

    public function testImagesAreMatchedByContextWhateverItsPortalIsRecordedAs(): void
    {
        $roomId = (int) $this->room->getItemId();
        /** @var User $user */
        $user = RoomWithMemberStory::get('roomUser');

        // a user room's context is its project room, not the portal it lies under
        $this->connection->update('room', ['context_id' => $this->entry()], ['item_id' => $roomId]);
        $this->addExtras('room', 'item_id', $roomId, ['LOGOFILENAME' => "cid{$roomId}_logo_Wappen.png"]);
        $this->connection->update('user', ['portal_id' => null], ['item_id' => $user->getItemId()]);
        $this->addExtras('user', 'item_id', (int) $user->getItemId(), ['USERPICTURE' => "cid{$roomId}_{$user->getUserId()}.png"]);
        $this->file($this->roomDirectory()."/cid{$roomId}_logo_Wappen.png");
        $this->file($this->roomDirectory()."/cid{$roomId}_{$user->getUserId()}.png");

        self::assertSame([], $this->audit()->findings);
    }

    public function testTheFilesOfAContextThatIsGoneAreOrphaned(): void
    {
        $goneId = $this->goneContextId();
        $gone = (string) $this->filesDirectory->relativeOf($this->filesDirectory->contextDirectory($this->portalId, $goneId));
        $stray = $this->handedOutFileId();
        $this->file("$gone/$stray.pdf");
        $this->file("$gone/cid{$goneId}_logo_x.png");
        // a row still refers to this one, wherever it lies
        $this->file("$gone/{$this->fileRow()}.pdf");
        $this->file($this->roomDirectory().'/cid'.$this->room->getItemId().'_logo_x.png');

        $result = $this->audit();

        self::assertSame([$gone], $this->found($result, FindingKind::OrphanedDirectory));
        $orphaned = $result->of(FindingKind::OrphanedDirectory)[0];
        $files = $orphaned->files;
        sort($files);
        self::assertSame(["$gone/$stray.pdf", "$gone/cid{$goneId}_logo_x.png"], $files);
        self::assertSame(\strlen('content') * 2, $orphaned->bytes);
        self::assertFalse($result->databaseSuspicious);
    }

    public function testYoungFilesAreLeftAlone(): void
    {
        $this->file($this->roomDirectory().'/987654.pdf', ageHours: 2);
        $this->file($this->filesDirectory->relativeOf($this->filesDirectory->contextDirectory($this->portalId, 999_999_004)).'/1.pdf', ageHours: 2);

        self::assertSame([], $this->audit()->findings);
    }

    public function testWhatTheAuditDoesNotKnowIsOnlyReported(): void
    {
        $this->file($this->roomDirectory().'/notizen.txt');
        $this->file('IMS/test_packages/add_person.xml');
        $this->file('temp/zip_export/1/x.zip');
        $this->file('_quarantine/20260101-000000/101/98_/1.pdf');
        // a directory named by digits only must not trip the walk
        $this->file('99/liesmich.txt');

        $expected = ['99/liesmich.txt', 'IMS/test_packages/add_person.xml', $this->roomDirectory().'/notizen.txt'];
        sort($expected);
        self::assertSame($expected, $this->found($this->audit(), FindingKind::Unknown));
    }

    public function testManyMissingFilesLookLikeAStorageNotMounted(): void
    {
        for ($i = 0; $i < 25; ++$i) {
            $this->link($this->fileRow(), $this->entry());
        }

        self::assertTrue($this->audit()->storageSuspicious);
    }

    public function testManyStrayUploadsLookLikeTheWrongDatabase(): void
    {
        for ($i = 0; $i < 25; ++$i) {
            $this->file($this->roomDirectory().'/'.$this->handedOutFileId().'.pdf');
        }

        $result = $this->audit();

        self::assertTrue($result->databaseSuspicious);
        self::assertFalse($result->storageSuspicious);
    }

    public function testAnUploadIdNeverHandedOutLooksLikeTheWrongDatabase(): void
    {
        $newest = (int) $this->connection->fetchOne('SELECT MAX(files_id) FROM files');
        $this->file($this->roomDirectory().'/'.($newest + 1000).'.pdf');

        self::assertTrue($this->audit()->databaseSuspicious);
    }

    public function testAContextIdNeverHandedOutLooksLikeTheWrongDatabase(): void
    {
        $newest = (int) $this->connection->fetchOne('SELECT MAX(item_id) FROM items');
        $this->file($this->filesDirectory->relativeOf($this->filesDirectory->contextDirectory($this->portalId, $newest + 1000)).'/1.pdf');

        self::assertTrue($this->audit()->databaseSuspicious);
    }

    private function entry(): int
    {
        /** @var User $user */
        $user = RoomWithMemberStory::get('roomUser');

        return (int) AnnouncementFactory::createOne(['room' => $this->room, 'creator' => $user])->getItemId();
    }
}
