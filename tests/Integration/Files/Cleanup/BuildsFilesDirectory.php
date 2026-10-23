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

use App\Entity\Room;
use App\Files\Cleanup\AuditResult;
use App\Files\Cleanup\FileAudit;
use App\Files\Cleanup\FindingKind;
use App\Files\FileDeleter;
use App\Files\FilesDirectory;
use DateTime;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Tests\Factory\AnnouncementFactory;
use Tests\Factory\FilesFactory;
use Tests\Story\RoomWithMemberStory;

/**
 * A files directory of its own per test, below the test files directory,
 * and the rows and links that refer into it.
 */
trait BuildsFilesDirectory
{
    private Connection $connection;
    private FilesDirectory $filesDirectory;
    private string $projectDir;
    private Room $room;
    private int $portalId;

    private function setUpFilesDirectory(): void
    {
        $container = self::getContainer();
        $this->connection = $container->get(Connection::class);
        $this->projectDir = (string) $container->getParameter('kernel.project_dir');
        $root = $container->getParameter('files_directory').'/phpunit-cleanup/'.uniqid();
        (new Filesystem())->mkdir($root);
        $this->filesDirectory = new FilesDirectory($root, $this->projectDir);

        $this->room = RoomWithMemberStory::get('room');
        $this->portalId = (int) $this->room->getContextId();
    }

    private function tearDownFilesDirectory(): void
    {
        (new Filesystem())->remove($this->filesDirectory->root());
    }

    /**
     * The room's directory relative to the root.
     */
    private function roomDirectory(): string
    {
        return (string) $this->filesDirectory->relativeOf(
            $this->filesDirectory->contextDirectory($this->portalId, (int) $this->room->getItemId())
        );
    }

    /**
     * Writes a file relative to the root, by default old enough to be judged.
     */
    private function file(string $relativePath, string $content = 'content', int $ageHours = 48): string
    {
        $path = $this->filesDirectory->root().'/'.$relativePath;
        (new Filesystem())->dumpFile($path, $content);
        touch($path, time() - $ageHours * 3600);

        return $path;
    }

    /**
     * A row of the files table in the room; the file itself is written
     * separately, so a row can also lack it.
     */
    private function fileRow(string $filename = 'Bericht.pdf', ?string $relativePath = null, int $ageHours = 48): int
    {
        $fileId = FilesFactory::createOne([
            'contextId' => (int) $this->room->getItemId(),
            'portal' => $this->room->getPortal(),
            'filename' => $filename,
            'filepath' => '',
            'creationDate' => new DateTime("-$ageHours hours"),
        ])->getFilesId();

        $path = $this->filesDirectory->root().'/'.($relativePath ?? $this->roomDirectory()."/$fileId.".pathinfo($filename, PATHINFO_EXTENSION));
        $this->connection->update('files', ['filepath' => ltrim(substr($path, \strlen($this->projectDir)), '/')], ['files_id' => $fileId]);

        return $fileId;
    }

    /**
     * An id the database handed out to a file whose row is gone for good,
     * below the newest one.
     */
    private function handedOutFileId(): int
    {
        $fileId = $this->fileRow();
        $this->fileRow(ageHours: 2);
        $this->connection->delete('files', ['files_id' => $fileId]);

        return $fileId;
    }

    /**
     * An id the database handed out to an entry that is gone for good,
     * below the newest one.
     */
    private function goneContextId(): int
    {
        $user = RoomWithMemberStory::get('roomUser');
        $gone = (int) AnnouncementFactory::createOne(['room' => $this->room, 'creator' => $user])->getItemId();
        AnnouncementFactory::createOne(['room' => $this->room, 'creator' => $user]);
        $this->connection->delete('announcement', ['item_id' => $gone]);
        $this->connection->delete('items', ['item_id' => $gone]);

        return $gone;
    }

    private function link(int $fileId, int $itemId, int $versionId = 0): void
    {
        $this->connection->insert('item_link_file', ['file_id' => $fileId, 'item_iid' => $itemId, 'item_vid' => $versionId]);
    }

    /**
     * @param array<string, string> $values
     */
    private function addExtras(string $table, string $idColumn, int $id, array $values): void
    {
        $extras = $this->connection->fetchOne("SELECT extras FROM `$table` WHERE $idColumn = ?", [$id]);
        $current = \is_string($extras) && '' !== $extras ? unserialize($extras, ['allowed_classes' => false]) : [];
        $this->connection->update($table, ['extras' => serialize([...(\is_array($current) ? $current : []), ...$values])], [$idColumn => $id]);
    }

    private function audit(): AuditResult
    {
        $container = self::getContainer();

        return (new FileAudit(
            $container->get(EntityManagerInterface::class),
            $container->get(FileDeleter::class),
            $this->filesDirectory,
        ))->audit();
    }

    /**
     * Paths, or file ids for rows, of one kind of finding, sorted.
     *
     * @return list<string|int>
     */
    private function found(AuditResult $result, FindingKind $kind): array
    {
        $found = array_map(
            static fn ($finding): string|int => $kind->concernsDisk() ? (string) $finding->path : (int) $finding->fileId,
            $result->of($kind),
        );
        sort($found);

        return $found;
    }
}
