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

namespace App\Files\Cleanup;

use App\Entity\Files;
use App\Entity\Labels;
use App\Entity\Portal;
use App\Entity\Room;
use App\Entity\Server;
use App\Entity\User;
use App\Files\FilesDirectory;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\DependencyInjection\Attribute\Exclude;

/**
 * Everything in the files directory the database still refers to.
 *
 * A row of the `files` table refers to its `filepath`, and also to where the
 * disc manager would put it — `<id>.<ext>`, and `cid<context>_<id>_<name>`
 * for uploads of CommSy 6 — each with an image's thumbnail next to it. Rows
 * count whether deleted or not: until the final deletion they may come back.
 *
 * Generated images are referred to by the settings that are read today:
 * the logo and background of a room, the picture of a user, the picture of
 * a group (only still read to give a new group room its logo), and the logos
 * of portals and the server. Rows count whether deleted or not here as well.
 * Room, user and group images are matched by context and name: where the
 * context's directory lies under which portal is left to the walk, since the
 * portal of a user room or of some users is not recorded reliably.
 */
#[Exclude]
final class FileReferences
{
    /** @var array<string, true> absolute paths */
    private array $paths = [];

    /** @var array<int, true> */
    private array $fileIds = [];

    /** @var array<int, array<string, true>> image names per context */
    private array $images = [];

    /** @var list<array{fileId: int, paths: list<string>, directories: list<string>, portalId: int, bytes: int, createdAt: \DateTimeInterface}> rows not marked deleted */
    private array $liveRows = [];

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly FilesDirectory $filesDirectory,
    ) {
    }

    /**
     * Reads the references; called once before the files directory is walked.
     */
    public function read(): self
    {
        $this->paths = [];
        $this->fileIds = [];
        $this->images = [];
        $this->liveRows = [];

        $this->readFileRows();
        $this->readRoomImages();
        $this->readUserPictures();
        $this->readGroupPictures();
        $this->readLogos();

        return $this;
    }

    public function isReferenced(string $absolutePath): bool
    {
        return isset($this->paths[$absolutePath]);
    }

    public function hasFileRow(int $fileId): bool
    {
        return isset($this->fileIds[$fileId]);
    }

    public function isImageReferenced(int $contextId, string $name): bool
    {
        return isset($this->images[$contextId][$name]);
    }

    /**
     * The rows not marked deleted with every place their file may lie and
     * the directories it would lie in.
     *
     * @return list<array{fileId: int, paths: list<string>, directories: list<string>, portalId: int, bytes: int, createdAt: \DateTimeInterface}>
     */
    public function liveRows(): array
    {
        return $this->liveRows;
    }

    private function readFileRows(): void
    {
        $rows = $this->entityManager->createQueryBuilder()
            ->select('f.filesId', 'f.filepath', 'f.filename', 'IDENTITY(f.portal) AS portalId', 'f.contextId', 'f.size', 'f.creationDate', 'f.deletionDate')
            ->from(Files::class, 'f');

        foreach ($this->iterate($rows) as $row) {
            $fileId = (int) $row['filesId'];
            $this->fileIds[$fileId] = true;

            $places = [];
            $directories = [];
            if ('' !== (string) $row['filepath']) {
                $places[] = $this->filesDirectory->absoluteOf((string) $row['filepath']);
            }
            if ($row['portalId'] && $row['contextId']) {
                $diskFilename = FilesDirectory::diskFilename($fileId, FilesDirectory::extensionOf((string) $row['filename']));
                foreach ($this->contextDirectories((int) $row['portalId'], (int) $row['contextId']) as $directory) {
                    $directories[] = $directory;
                    $places[] = "$directory/$diskFilename";
                    $places[] = "$directory/cid{$row['contextId']}_{$fileId}_{$row['filename']}";
                }
            }

            foreach ($places as $place) {
                $this->paths[$place] = true;
                $this->paths[$place.'_thumb'] = true;
            }

            if (null === $row['deletionDate']) {
                $this->liveRows[] = [
                    'fileId' => $fileId,
                    'paths' => $places,
                    'directories' => $directories,
                    'portalId' => (int) $row['portalId'],
                    'bytes' => (int) $row['size'],
                    'createdAt' => $row['creationDate'],
                ];
            }
        }
    }

    private function readRoomImages(): void
    {
        $rooms = $this->entityManager->createQueryBuilder()
            ->select('r.itemId', 'r.extras')
            ->from(Room::class, 'r')
            ->where('r.extras LIKE :logo OR r.extras LIKE :background')
            ->setParameter('logo', '%LOGOFILENAME%')
            ->setParameter('background', '%BGIMAGEFILENAME%');

        foreach ($this->iterate($rooms) as $row) {
            foreach (['LOGOFILENAME', 'BGIMAGEFILENAME'] as $key) {
                $this->referImage((int) $row['itemId'], $row['extras'][$key] ?? null);
            }
        }
    }

    private function readUserPictures(): void
    {
        $users = $this->entityManager->createQueryBuilder()
            ->select('IDENTITY(u.room) AS contextId', 'u.extras')
            ->from(User::class, 'u')
            ->where('u.extras LIKE :picture')
            ->setParameter('picture', '%USERPICTURE%');

        foreach ($this->iterate($users) as $row) {
            $this->referImage((int) $row['contextId'], $row['extras']['USERPICTURE'] ?? null);
        }
    }

    private function readGroupPictures(): void
    {
        $labels = $this->entityManager->createQueryBuilder()
            ->select('l.contextId', 'l.extras')
            ->from(Labels::class, 'l')
            ->where('l.extras LIKE :picture')
            ->setParameter('picture', '%LABELPICTURE%');

        foreach ($this->iterate($labels) as $row) {
            $this->referImage((int) $row['contextId'], $row['extras']['LABELPICTURE'] ?? null);
        }
    }

    private function readLogos(): void
    {
        $root = $this->filesDirectory->root();
        foreach ($this->entityManager->createQueryBuilder()
            ->select('p.logoFilename')->from(Portal::class, 'p')->where('p.logoFilename IS NOT NULL')
            ->getQuery()->getSingleColumnResult() as $name) {
            $this->paths["$root/portal/logos/$name"] = true;
        }
        foreach ($this->entityManager->createQueryBuilder()
            ->select('s.logoImageName')->from(Server::class, 's')->where('s.logoImageName IS NOT NULL')
            ->getQuery()->getSingleColumnResult() as $name) {
            $this->paths["$root/server/$name"] = true;
        }
    }

    private function referImage(int $contextId, mixed $name): void
    {
        if (\is_string($name) && '' !== $name && !str_contains($name, '/') && 0 !== $contextId) {
            $this->images[$contextId][$name] = true;
        }
    }

    /**
     * The split directory and the unsplit one of very old data.
     *
     * @return list<string>
     */
    private function contextDirectories(int $portalId, int $contextId): array
    {
        return [
            $this->filesDirectory->contextDirectory($portalId, $contextId),
            $this->filesDirectory->root()."/$portalId/$contextId",
        ];
    }

    /**
     * Rows one by one, the mapped types applied (extras come as arrays).
     *
     * @return iterable<array<string, mixed>>
     */
    private function iterate(QueryBuilder $qb): iterable
    {
        return $qb->getQuery()->toIterable([], AbstractQuery::HYDRATE_ARRAY);
    }
}
