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

namespace App\Export;

use App\Entity\Files;
use App\Repository\ItemLinkFileRepository;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Resolves the attachments of the entries an export carries.
 *
 * Entries are asked for by id together with the version they are exported at,
 * because that is what decides which attachments an entry has.
 *
 * Every file lands in its own directory inside the archive, named after its id.
 * Two entries attaching files of the same name is ordinary, and a scheme that
 * has to invent a second name for the collision would hand the importer a name
 * nobody chose.
 */
final readonly class RoomFileCollector
{
    public const DIRECTORY = 'files';

    public function __construct(
        private ItemLinkFileRepository $links,
        #[Autowire('%kernel.project_dir%')]
        private string $projectDir,
    ) {
    }

    /**
     * @param array<int, int> $versionByItemId version per item id
     *
     * @return list<ExportFile> each file once, in the order of their ids
     */
    public function collect(array $versionByItemId): array
    {
        $files = $this->links->findLinkedFiles($versionByItemId);
        ksort($files);

        return array_values(array_map($this->toExportFile(...), $files));
    }

    private function toExportFile(Files $file): ExportFile
    {
        $sourcePath = $this->projectDir.'/'.$file->getFilepath();
        $name = $this->archiveName($file);

        return new ExportFile(
            id: $file->getFilesId(),
            name: $name,
            size: (int) $file->getSize(),
            sourcePath: $sourcePath,
            archivePath: sprintf('%s/%d/%s', self::DIRECTORY, $file->getFilesId(), $name),
            available: is_file($sourcePath),
        );
    }

    /**
     * The stored name is what someone uploaded, so it decides nothing about
     * where the file goes — only what it is called once it is there.
     */
    private function archiveName(Files $file): string
    {
        $name = trim(basename(str_replace('\\', '/', $file->getFilename())));

        return in_array($name, ['', '.', '..'], true)
            ? 'file-'.$file->getFilesId()
            : $name;
    }
}
