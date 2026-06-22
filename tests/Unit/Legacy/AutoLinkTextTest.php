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

use App\Legacy\AutoLinkText;
use PHPUnit\Framework\TestCase;

/**
 * Characterises the exact legacy chunkText()/spezial_chunkURL() behaviour
 * (captured by running the original functions) so the extraction stays faithful.
 */
class AutoLinkTextTest extends TestCase
{
    public function testShortTextIsReturnedUnchanged(): void
    {
        self::assertSame('short link text', AutoLinkText::shorten('short link text'));
    }

    public function testLongUrlWithoutSpacesIsHardCutToLength(): void
    {
        self::assertSame(
            'http://example.com/a/very/long/path/that/clea ...',
            AutoLinkText::shorten('http://example.com/a/very/long/path/that/clearly/exceeds/fortyfive/chars')
        );
    }

    public function testLongSentenceIsCutAtWordBoundary(): void
    {
        self::assertSame(
            'The quick brown fox jumps over the lazy dog ...',
            AutoLinkText::shorten('The quick brown fox jumps over the lazy dog again and again')
        );
    }

    public function testCommsyTagIsKeptIntactWhenCutFallsInside(): void
    {
        self::assertSame(
            'some intro text here padding padding (:item 42 inner:) ...',
            AutoLinkText::shorten('some intro text here padding padding (:item 42 inner:) trailing words beyond')
        );
    }

    public function testNewlinesAreReplacedWithSpaces(): void
    {
        self::assertSame(
            'first line here is already quite long ...',
            AutoLinkText::shorten("first line here is already quite long enough\nsecond line follows after break")
        );
    }

    public function testShortenAnchorTextWrapsTheShortenedGroupOne(): void
    {
        self::assertSame('">short link text</a>', AutoLinkText::shortenAnchorText(['ignored full match', 'short link text']));
    }
}
