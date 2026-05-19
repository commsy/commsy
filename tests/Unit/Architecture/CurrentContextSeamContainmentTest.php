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
 * Step 5 — containment and success metric for the CurrentContextResolver
 * refactor (the context/portal counterpart of
 * {@see CurrentUserSeamContainmentTest}).
 *
 * The legacy context/portal read seam — getCurrentContextID(),
 * getCurrentPortalID(), getCurrentContextItem(), getCurrentPortalItem()
 * on cs_environment — was driven down from 24+ src/ files (127 item
 * call sites plus the pure-id sites) to the three entries below. New
 * application code must use {@see \App\Services\CurrentContextResolver}
 * instead.
 *
 * The remaining three are intentional and tracked:
 *   - the resolver itself (the seam implementation that delegates to
 *     the legacy environment until Schloss 3 replaces the legacy item
 *     type),
 *   - the legacy permission bridge (a deliberate legacy boundary,
 *     deferred — mirrors the LegacySoftDeleteBridge allowlisting in
 *     CurrentUserSeamContainmentTest),
 *   - RoomViewChecker, which only quotes the legacy cs_project_item::
 *     maySee pseudocode in a docblock (documentation, no executable
 *     call).
 *
 * Strict equality is intentional — it is the ratchet AND the live
 * metric:
 *   - a NEW file referencing the legacy context/portal seam fails the
 *     test (regrowth blocked; use CurrentContextResolver),
 *   - removing the last reference from an allowlisted file also fails
 *     (the seam shrank — trim the list so the metric stays honest; the
 *     diff is the progress record).
 */
final class CurrentContextSeamContainmentTest extends TestCase
{
    /**
     * src/ files still allowed to reference the legacy context/portal
     * read seam (relative to src/).
     *
     * @var string[]
     */
    private const ALLOWLIST = [
        'Services/CurrentContextResolver.php',
        'Security/Permission/Legacy/LegacyPermissionBridge.php',
        'Room/RoomViewChecker.php',
    ];

    private const SEAM_PATTERNS = [
        '->getCurrentContextID(',
        '->getCurrentPortalID(',
        '->getCurrentContextItem(',
        '->getCurrentPortalItem(',
    ];

    public function testLegacyContextSeamDoesNotRegrow(): void
    {
        $srcDir = \dirname(__DIR__, 3).'/src';
        $actual = [];

        /** @var \SplFileInfo $file */
        foreach (new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($srcDir, RecursiveDirectoryIterator::SKIP_DOTS)
        ) as $file) {
            if (!$file->isFile() || 'php' !== $file->getExtension()) {
                continue;
            }

            $contents = (string) file_get_contents($file->getPathname());
            foreach (self::SEAM_PATTERNS as $pattern) {
                if (str_contains($contents, $pattern)) {
                    $actual[] = str_replace($srcDir.'/', '', $file->getPathname());
                    break;
                }
            }
        }

        sort($actual);
        $allowlist = self::ALLOWLIST;
        sort($allowlist);

        $newOffenders = array_diff($actual, $allowlist);
        self::assertSame(
            [],
            array_values($newOffenders),
            "New src/ file(s) reference the legacy context/portal seam.\n"
            ."Use App\\Services\\CurrentContextResolver instead — do not regrow the seam:\n  "
            .implode("\n  ", $newOffenders)
        );

        $shrunk = array_diff($allowlist, $actual);
        self::assertSame(
            [],
            array_values($shrunk),
            "The seam shrank (good!) — these allowlisted files no longer\n"
            ."reference the legacy context/portal seam. Remove them from\n"
            ."ALLOWLIST so the metric stays honest (the diff records the\n"
            ."progress):\n  "
            .implode("\n  ", $shrunk)
        );
    }
}
