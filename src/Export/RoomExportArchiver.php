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

use RuntimeException;
use Symfony\Component\Filesystem\Filesystem;
use ZipArchive;

/**
 * Writes the export document into a ZIP archive.
 *
 * Pretty-printed on purpose: whoever writes the importer has to read this, and
 * inside a compressed archive the extra whitespace costs next to nothing.
 */
readonly class RoomExportArchiver
{
    public const DOCUMENT_NAME = 'export.json';

    /**
     * @param array<string, mixed> $document
     *
     * @return int the size of the archive in bytes
     */
    public function write(string $targetPath, array $document): int
    {
        (new Filesystem())->mkdir(dirname($targetPath), 0750);

        $json = json_encode(
            $document,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
        );

        $zip = new ZipArchive();
        if (true !== $zip->open($targetPath, ZipArchive::CREATE | ZipArchive::OVERWRITE)) {
            throw new RuntimeException(sprintf('Cannot open archive for writing at %s', $targetPath));
        }

        $zip->addFromString(self::DOCUMENT_NAME, $json);

        if (!$zip->close()) {
            throw new RuntimeException(sprintf('Cannot write archive at %s', $targetPath));
        }

        $size = filesize($targetPath);
        if (false === $size) {
            throw new RuntimeException(sprintf('Archive at %s is not readable after writing', $targetPath));
        }

        return $size;
    }
}
