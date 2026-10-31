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

namespace Tests\Unit\Twig\Extension;

use App\Services\LegacyMarkup;
use App\Twig\Extension\MarkupExtension;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Arguments of the CommSy markup come from the entry text and are expanded
 * after the text was sanitized, so they must not open new attributes or
 * link targets.
 */
final class MarkupArgumentEscapingTest extends TestCase
{
    /**
     * @return iterable<string, array{string, string}>
     */
    public static function unsafeMarkup(): iterable
    {
        yield 'link with a script scheme' => ['(:link javascript:alert(1) text=x:)', 'href="javascript:'];
        yield 'link with a script scheme in capitals' => ['(:link JavaScript:alert(1) text=x:)', 'href="javascript:'];
        yield 'link with a data scheme' => ['(:link data:text/html,x text=x:)', 'href="data:'];
        yield 'link target leaving its attribute' => ['(:link https://example.org target=x"onmouseover="alert(1):)', '"onmouseover='];
        yield 'link address leaving its attribute' => ['(:link https://example.org/"onmouseover="alert(1) text=x:)', '"onmouseover='];
        yield 'item target leaving its attribute' => ['(:item 1 target=x"onmouseover="alert(1):)', '"onmouseover='];
        yield 'item target given as entities' => ['(:item 1 target=x&quot;onmouseover=&quot;alert(1):)', '"onmouseover='];
        yield 'image source leaving its attribute' => ['(:image x"onerror="alert(1):)', '"onerror='];
        yield 'image with a script scheme' => ['(:image javascript:alert(1):)', 'src="javascript:'];
        yield 'image alt leaving its attribute' => ['(:image bild.png alt=x"onerror="alert(1):)', '"onerror='];
        yield 'youtube id leaving its attribute' => ['(:youtube abc"onload="alert(1):)', '"onload='];
        yield 'lecture2go id leaving its attribute' => ['(:lecture2go 1"onload="alert(1):)', '"onload='];
        yield 'file name with markup' => ['(:file gefahr.pdf:)', '<b>'];
    }

    public function testRefusedMarkupStaysText(): void
    {
        self::assertSame("<p>(:link javascript:alert(1) text=x:)\r\n</p>", $this->markup('(:link javascript:alert(1) text=x:)'));
    }

    #[DataProvider('unsafeMarkup')]
    public function testAnArgumentCannotInjectMarkup(string $markup, string $forbidden): void
    {
        self::assertStringNotContainsStringIgnoringCase($forbidden, $this->markup($markup));
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function safeMarkup(): iterable
    {
        yield 'https link' => ['(:link https://example.org text=X:)', '<a href="https://example.org">X</a>'];
        yield 'mail link' => ['(:link mailto:person@example.org text=Mail:)', '<a href="mailto:person@example.org">Mail</a>'];
        yield 'ftp link' => ['(:link ftp://example.org text=F:)', '<a href="ftp://example.org">F</a>'];
        yield 'link without scheme' => ['(:link example.org:)', '<a href="http://example.org">http://example.org</a>'];
        yield 'link opening a new window' => ['(:link https://example.org target=_blank text=X:)', '<a href="https://example.org" target="_blank">X</a>'];
        yield 'item opening a new window' => ['(:item 1 target=_blank:)', 'target="_blank">1</a>'];
        yield 'item with an unknown target' => ['(:item 1 target=elsewhere:)', 'target="">1</a>'];
        yield 'item text with entities' => ['(:item 1 text=&lt;b&gt;:)', '>&lt;b&gt;</a>'];
        yield 'image address' => ['(:image https://example.org/a.png:)', '<img src="https://example.org/a.png"/>'];
    }

    #[DataProvider('safeMarkup')]
    public function testHarmlessMarkupStillRenders(string $markup, string $expected): void
    {
        self::assertStringContainsString($expected, $this->markup($markup));
    }

    private function markup(string $input): string
    {
        $router = $this->createMock(RouterInterface::class);
        $router->method('generate')->willReturnCallback(
            static fn (string $name, array $params = []): string => '/route/'.$name.($params !== [] ? '?'.http_build_query($params) : '')
        );

        $translator = $this->createMock(TranslatorInterface::class);
        $translator->method('trans')->willReturnCallback(static fn (string $id): string => '['.$id.']');

        $legacyMarkup = new LegacyMarkup($router, $translator);
        $legacyMarkup->addFiles(['gefahr.pdf' => new readonly class {
            public function getFileID(): int
            {
                return 11;
            }

            public function getFilename(): string
            {
                return 'gefahr.pdf';
            }

            public function getDisplayName(): string
            {
                return '<b>Gefahr</b>';
            }
        }]);

        return (new MarkupExtension(new RequestStack([Request::create('https://commsy.example/')]), $legacyMarkup))->commsyMarkup($input);
    }
}
