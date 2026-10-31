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

namespace Tests\Integration\Feed;

use App\Services\LegacyEnvironment;
use misc_text_converter;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Bracketed numbers in an entry description stay plain text in the feed, as
 * they do on the entry page.
 */
final class FeedDescriptionFormattingTest extends KernelTestCase
{
    private misc_text_converter $textConverter;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->textConverter = self::getContainer()->get(LegacyEnvironment::class)->getEnvironment()->getTextConverter();
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function bracketedNumbers(): iterable
    {
        yield 'a small number on its own' => ['Siehe [1].'];
        yield 'a small number after a word' => ['Kapitel[3] lesen'];
        yield 'an entry id on its own' => ['Eintrag [123456]'];
        yield 'an entry id after a word' => ['Eintrag[123456]'];
    }

    #[DataProvider('bracketedNumbers')]
    public function testABracketedNumberStaysPlainText(string $description): void
    {
        // The formatting ends every text with a line break.
        self::assertSame($description, rtrim($this->textConverter->textFullHTMLFormatting($description)));
    }
}
