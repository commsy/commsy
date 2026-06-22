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

namespace Tests\Unit\Legacy;

use App\Legacy\KfcTextHash;
use PHPUnit\Framework\TestCase;

class KfcTextHashTest extends TestCase
{
    public function testWrapsValueWithMatchingMarkersAtBothEnds(): void
    {
        $result = KfcTextHash::renew('hello world');

        self::assertStringStartsWith('<!-- KFC TEXT ', $result);
        self::assertStringEndsWith(' -->', $result);
        self::assertStringContainsString('hello world', $result);
        self::assertSame(2, substr_count($result, '<!-- KFC TEXT '));
    }

    public function testStripsExistingMarkersBeforeRewrapping(): void
    {
        $once = KfcTextHash::renew('content');
        $twice = KfcTextHash::renew($once);

        // markers are not nested: still exactly two, not four
        self::assertSame(2, substr_count($twice, '<!-- KFC TEXT '));
    }

    public function testHashUsesConfiguredSecurityKey(): void
    {
        $original = $GLOBALS['c_security_key'] ?? null;
        $GLOBALS['c_security_key'] = 'secret';

        try {
            $expected = md5('secret'.'plain'.'secret');
            self::assertStringContainsString('<!-- KFC TEXT '.$expected.' -->', KfcTextHash::renew('plain'));
        } finally {
            if (null === $original) {
                unset($GLOBALS['c_security_key']);
            } else {
                $GLOBALS['c_security_key'] = $original;
            }
        }
    }

    public function testHashDefaultsToCommsyKeyWhenUnset(): void
    {
        $original = $GLOBALS['c_security_key'] ?? null;
        unset($GLOBALS['c_security_key']);

        try {
            $expected = md5('commsy'.'plain'.'commsy');
            self::assertStringContainsString('<!-- KFC TEXT '.$expected.' -->', KfcTextHash::renew('plain'));
        } finally {
            if (null !== $original) {
                $GLOBALS['c_security_key'] = $original;
            }
        }
    }
}
