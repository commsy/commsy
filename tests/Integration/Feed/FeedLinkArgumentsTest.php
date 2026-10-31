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
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Arguments of link markup in a feed description cannot open new attributes
 * or link targets.
 */
final class FeedLinkArgumentsTest extends KernelTestCase
{
    /**
     * @return iterable<string, array{string, string}>
     */
    public static function unsafeMarkup(): iterable
    {
        yield 'link with a script scheme' => ['(:link javascript:alert(1) text=x:)', 'href="javascript:'];
        yield 'link with a script scheme in capitals' => ['(:link JavaScript:alert(1) text=x:)', 'href="javascript:'];
        yield 'link with a data scheme' => ['(:link data:text/html,x text=x:)', 'href="data:'];
        yield 'link address leaving its attribute' => ['(:link https://example.org/"onmouseover="alert(1) text=x:)', '"onmouseover='];
        yield 'link address as its text leaving the element' => ['(:link https://example.org/<b>x:)', '<b>'];
        yield 'link target leaving its attribute' => ['(:link https://example.org target=x"onmouseover="alert(1):)', '"onmouseover='];
        yield 'item target leaving its attribute' => ['(:item 1 target=x"onmouseover="alert(1):)', '"onmouseover='];
    }

    #[DataProvider('unsafeMarkup')]
    public function testAnArgumentCannotInjectMarkup(string $markup, string $forbidden): void
    {
        self::assertStringNotContainsStringIgnoringCase($forbidden, $this->format($markup));
    }

    public function testAWebLinkStillRenders(): void
    {
        self::assertSame("<a href=\"https://example.org\">X</a>\r\n", $this->format('(:link https://example.org text=X:)'));
        self::assertSame("<a href=\"mailto:a@example.org\">Mail</a>\r\n", $this->format('(:link mailto:a@example.org text=Mail:)'));
    }

    private function format(string $text): string
    {
        self::bootKernel();

        return self::getContainer()->get(LegacyEnvironment::class)->getEnvironment()->getTextConverter()->textFullHTMLFormatting($text);
    }
}
