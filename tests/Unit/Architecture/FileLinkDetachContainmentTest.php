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

namespace Tests\Unit\Architecture;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * A file lives as long as one live link points at it. Whoever lets go of a
 * link therefore has to end the file when that was its last one — which
 * {@see \App\Files\FileDeleter::detachFromItem()} does in one step. Writing
 * to `item_link_file` elsewhere bypasses that and either strands the file
 * or, worse, takes it off versions that still carry it.
 *
 * Strict equality, as in the other containment tests: a new writer fails,
 * and so does an allowlisted file that stopped writing.
 */
final class FileLinkDetachContainmentTest extends TestCase
{
    /**
     * Files allowed to write `item_link_file` (relative to the project root):
     * the deleter itself, the legacy link manager (inserts links or revives
     * them on re-attach) and the legacy file link manager (drops the links of
     * a room that is erased for good).
     *
     * @var string[]
     */
    private const ALLOWLIST = [
        'legacy/classes/cs_link_item_file_manager.php',
        'legacy/classes/cs_links_manager.php',
        'src/Files/FileDeleter.php',
    ];

    private const WRITE_PATTERNS = [
        // literal table names in SQL
        '/(UPDATE|DELETE\s+(\w+\s+)?FROM)\s+[\'"`.\w]*item_link_file/i',
        // legacy managers prefixing the table name
        '/(UPDATE|DELETE\s+FROM)\s*[\'"]\s*\.\s*\$this->addDatabasePrefix\(\s*[\'"]item_link_file[\'"]/i',
        // query builders and DQL
        '/->(update|delete)\(\s*(ItemLinkFile::class|[\'"]item_link_file[\'"])/',
        '/(UPDATE|DELETE)\s+App\\\\Entity\\\\ItemLinkFile/',
    ];

    public function testOnlyTheFileDeleterLetsGoOfFileLinks(): void
    {
        $root = \dirname(__DIR__, 3);
        $actual = [];

        foreach (['src', 'legacy'] as $dir) {
            /** @var \SplFileInfo $file */
            foreach (new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($root.'/'.$dir, RecursiveDirectoryIterator::SKIP_DOTS)
            ) as $file) {
                if (!$file->isFile() || 'php' !== $file->getExtension() || str_contains($file->getPathname(), '/Migrations/')) {
                    continue;
                }

                $contents = (string) file_get_contents($file->getPathname());
                foreach (self::WRITE_PATTERNS as $pattern) {
                    if (preg_match($pattern, $contents)) {
                        $actual[] = str_replace($root.'/', '', $file->getPathname());
                        break;
                    }
                }
            }
        }

        sort($actual);
        $allowlist = self::ALLOWLIST;
        sort($allowlist);

        self::assertSame(
            [],
            array_values(array_diff($actual, $allowlist)),
            "New file(s) write item_link_file directly. Let go of links through\n"
            .'App\\Files\\FileDeleter::detachFromItem() instead.'
        );
        self::assertSame(
            [],
            array_values(array_diff($allowlist, $actual)),
            'These allowlisted files no longer write item_link_file. Remove them from ALLOWLIST.'
        );
    }
}
