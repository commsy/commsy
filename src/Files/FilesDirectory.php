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

namespace App\Files;

use App\Utils\StringCase;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Path;

/**
 * The layout of the files directory, the one place it is spelled out:
 * `<portal>/<context id in chunks of four digits, the last one ending in _>/`,
 * e.g. context 1234567 of portal 101 lies in `101/1234/567_/`, and an upload
 * is stored there as `<file id>.<extension>`. Very old data may still use
 * the unsplit form `<portal>/<context id>/`. {@see \cs_disc_manager} and
 * {@see \App\Utils\RoomService::getRoomFileDirectory()} build on it.
 */
readonly class FilesDirectory
{
    private string $root;

    public function __construct(
        #[Autowire('%files_directory%')]
        string $filesDirectory,
        #[Autowire('%kernel.project_dir%')]
        private string $projectDir,
    ) {
        $this->root = Path::canonicalize($filesDirectory);
    }

    /**
     * The directory of a context below its portal's: `1234/567_` for 1234567.
     */
    public static function contextSegment(int|string $contextId): string
    {
        return implode('/', str_split((string) $contextId, 4)).'_';
    }

    /**
     * The extension of a stored file name, lower case, as the disc manager
     * names the file on disk.
     */
    public static function extensionOf(string $filename): string
    {
        $displayName = mb_convert_encoding(rawurldecode($filename), 'UTF-8');
        $dot = strrchr($displayName, '.');

        return false === $dot ? '' : StringCase::toLower(mb_substr($dot, 1));
    }

    /**
     * The name of an upload on disk.
     */
    public static function diskFilename(int $fileId, string $extension): string
    {
        return "$fileId.$extension";
    }

    public function root(): string
    {
        return $this->root;
    }

    /**
     * The absolute directory of a context.
     */
    public function contextDirectory(int $portalId, int $contextId): string
    {
        return $this->root.'/'.$portalId.'/'.self::contextSegment($contextId);
    }

    /**
     * Every directory a context's files may lie in: the split one and the
     * unsplit one of very old data.
     *
     * @return list<string>
     */
    public function contextDirectories(int $portalId, int $contextId): array
    {
        return [$this->contextDirectory($portalId, $contextId), $this->root.'/'.$portalId.'/'.$contextId];
    }

    /**
     * Portal and context of a directory relative to the root, or null when
     * the directory is not a context directory.
     *
     * @return array{portalId: int, contextId: int}|null
     */
    public function parseContextDirectory(string $relativeDirectory): ?array
    {
        $segments = explode('/', trim($relativeDirectory, '/'));
        if (\count($segments) < 2 || !ctype_digit($segments[0])) {
            return null;
        }

        $portalId = (int) array_shift($segments);
        $last = array_pop($segments);

        // the unsplit legacy form: one segment of digits without the mark
        if ([] === $segments && ctype_digit((string) $last)) {
            return ['portalId' => $portalId, 'contextId' => (int) $last];
        }

        if (!str_ends_with((string) $last, '_')) {
            return null;
        }
        $last = substr((string) $last, 0, -1);
        if (!ctype_digit($last) || \strlen($last) > 4) {
            return null;
        }
        foreach ($segments as $segment) {
            if (4 !== \strlen($segment) || !ctype_digit($segment)) {
                return null;
            }
        }

        return ['portalId' => $portalId, 'contextId' => (int) implode('', [...$segments, $last])];
    }

    /**
     * The absolute path of a `files.filepath` value, which is stored relative
     * to the project directory — always read that way, even when it starts
     * with a slash.
     */
    public function absoluteOf(string $filepath): string
    {
        return Path::canonicalize($this->projectDir.'/'.$filepath);
    }

    /**
     * The path relative to the root, or null when it lies outside.
     */
    public function relativeOf(string $absolutePath): ?string
    {
        $path = Path::canonicalize($absolutePath);
        if ($path === $this->root || !Path::isBasePath($this->root, $path)) {
            return null;
        }

        return Path::makeRelative($path, $this->root);
    }
}
