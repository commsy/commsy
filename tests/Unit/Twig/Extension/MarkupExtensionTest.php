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
 * Characterization test for the display-side markup pipeline.
 *
 * Records what commsyMarkup() produces today so the CKEditor 5 migration can
 * prove it did not change. This pins present behaviour including its defects -
 * it is not a specification of desired behaviour. Two known defects are
 * asserted deliberately and documented at the tests that cover them.
 */
class MarkupExtensionTest extends TestCase
{
    private const string HOST = 'https://commsy.example';
    private const string GOTO = self::HOST.'/route/app_goto_goto?itemId=123';
    private const string FILE = self::HOST.'/route/app_file_getfile';

    #[DataProvider('wikiMarkupCases')]
    #[DataProvider('legacyMarkupCases')]
    #[DataProvider('commentMarkerCases')]
    #[DataProvider('urlDetectionCases')]
    #[DataProvider('editorContentCases')]
    public function testPipelineOutputIsUnchanged(string $input, string $expected): void
    {
        self::assertSame($expected, $this->markup($input));
    }

    /**
     * Wiki markup is recognised at line starts, so any editor that normalises
     * whitespace or rewraps lines silently disables it: the text survives, the
     * rendering does not.
     *
     * @return array<string, array{string, string}>
     */
    public static function wikiMarkupCases(): array
    {
        return [
            'wiki heading level 1' => ['!Titel', "<p><h4>Titel</h4>\r\n\r\n</p>"],
            'wiki heading level 2' => ['!!Titel', "<p><h3>Titel</h3>\r\n\r\n</p>"],
            'wiki heading level 3' => ['!!!Titel', "<p><h2>Titel</h2>\r\n\r\n</p>"],
            'wiki unordered list' => ["- Punkt A\n- Punkt B", "<p><ul>\n<li>Punkt A</li>\n\r\n<li>Punkt B</li>\n\r\n</ul>\n</p>"],
            'wiki ordered list' => ["# Eins\n# Zwei", "<p><ol>\n<li>Eins</li>\n\r\n<li>Zwei</li>\n\r\n</ol>\n</p>"],
            'wiki horizontal rule' => ['---', "<p>\n<hr/>\n\r\n</p>"],
            'wiki bold' => ['Das ist *fett* hier', "<p>Das ist <b>fett</b> hier\r\n</p>"],
            'wiki italic' => ['Das ist _kursiv_ hier', "<p>Das ist <i>kursiv</i> hier\r\n</p>"],
            'wiki escaped bang' => ['\!kein Titel', "<p>&excl;kein Titel\r\n</p>"],
            'wiki mixed block' => [
                "!Titel\n- A\n- B\n---\nEnde",
                "<p><h4>Titel</h4>\r\n\r\n<ul>\n<li>A</li>\n\r\n<li>B</li>\n\r\n</ul>\n\n<hr/>\n\r\nEnde\r\n</p>",
            ],
        ];
    }

    /**
     * CommSy legacy markup is stored as plain text and expanded here, not by
     * the editor, so it has to pass through any editor untouched.
     *
     * @return array<string, array{string, string}>
     */
    public static function legacyMarkupCases(): array
    {
        return [
            'legacy item plain' => ['(:item 123:)', '<p><a href="'.self::GOTO."\" target=\"\">123</a>\r\n</p>"],
            'legacy item with text' => ["(:item 123 text='Ziel':)", '<p><a href="'.self::GOTO."\" target=\"\">Ziel</a>\r\n</p>"],
            'legacy item newwin' => ['(:item 123 newwin:)', '<p><a href="'.self::GOTO."\" target=\"_blank\">123</a>\r\n</p>"],
            'legacy link' => ["(:link https://example.org text='X':)", "<p><a href=\"https://example.org\">X</a>\r\n</p>"],
            'legacy youtube' => [
                '(:youtube abc123:)',
                "<p><div class=\"ckeditor-commsy-video\" data-type=\"youtube\"><iframe allowfullscreen frameborder=\"0\" src=\"https://www.youtube.com/embed/abc123\"></iframe></div>\r\n</p>",
            ],
            'legacy mp3 deprecated' => [
                '(:mp3 tondatei.mp3:)',
                "<p><div class=\"uk-alert\" data-uk-alert><a href=\"\" class=\"uk-alert-close uk-close\"></a><p>[deprecated markup]</p></div>\r\n</p>",
            ],
            'legacy file known' => ['(:file bericht.pdf:)', '<p><a href="'.self::FILE."?fileId=11\">Bericht</a>\r\n</p>"],
            'legacy image known' => [
                '(:image bild.png:)',
                '<p><div class="ckeditor-commsy-image"><img src="'.self::FILE."?fileId=22&disposition=inline\"/></div>\r\n</p>",
            ],
            'legacy image unknown' => [
                '(:image fehlt.png:)',
                "<p><div class=\"ckeditor-commsy-image\"><img src=\"fehlt.png\"/></div>\r\n</p>",
            ],
        ];
    }

    /**
     * The "KFC TEXT" comments are md5 markers written by KfcTextHash. Nothing
     * verifies them; interpreteLinks() only swaps them for a placeholder so a
     * URL directly in front of one still gets linked. CKEditor 5 discards HTML
     * comments unless HtmlComment is loaded.
     *
     * @return array<string, array{string, string}>
     */
    public static function commentMarkerCases(): array
    {
        $marker = '<!-- KFC TEXT abc123 -->';

        return [
            'kfc marker around plain text' => [
                $marker.'Hallo Welt'.$marker,
                '<p>'.$marker.'Hallo Welt'.$marker."\r\n</p>",
            ],
            'kfc marker around trailing url' => [
                $marker.'Siehe https://example.org'.$marker,
                '<p>'.$marker.'Siehe '.self::anchor('https://example.org').$marker."\r\n</p>",
            ],
        ];
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function urlDetectionCases(): array
    {
        return [
            'trailing url without marker' => [
                'Siehe https://example.org',
                '<p>Siehe '.self::anchor('https://example.org')."\r\n</p>",
            ],
            // Establishes that '<' already terminates a URL, so removing the
            // KFC placeholder swap cannot change how a URL in front of '<!--'
            // is detected.
            'url followed by angle bracket' => [
                'Siehe https://example.org<p>x</p>',
                '<p>Siehe '.self::anchor('https://example.org')."<p>x</p>\r\n</p>",
            ],
            'bare url' => [
                'Text https://example.org/a/b?c=1 Text',
                '<p>Text '.self::anchor('https://example.org/a/b?c=1')." Text\r\n</p>",
            ],
            'www url' => [
                'Text www.example.org Text',
                "<p>Text <a href=\"http://www.example.org\" target=\"_blank\" title=\"www.example.org\">www.example.org</a> Text\r\n</p>",
            ],
            'mailto' => [
                'Mail an person@example.org bitte',
                "<p>Mail an <a href=\"mailto:person@example.org\">person@example.org</a> bitte\r\n</p>",
            ],
            'existing anchor untouched' => [
                '<a href="https://example.org">Link</a>',
                "<p><a href=\"https://example.org\">Link</a>\r\n</p>",
            ],
        ];
    }

    /**
     * Markup stored by the CKEditor 4 custom plugins.
     *
     * @return array<string, array{string, string}>
     */
    public static function editorContentCases(): array
    {
        return [
            'ck4 video widget' => [
                '<div class="ckeditor-commsy-video" data-type="commsy"><video controls height="604" src="/file/3" width="100%"></video></div>',
                '<p><div class="ckeditor-commsy-video" data-type="commsy"><video controls height="604" src="'.self::HOST."/file/3\" width=\"100%\"></video></div>\r\n</p>",
            ],
            'ck4 mathjax span' => [
                '<p><span class="math-tex">\(x^2\)</span></p>',
                "<p><p><span class=\"math-tex\">\\(x^2\\)</span></p>\r\n</p>",
            ],
            'root relative src' => [
                '<img src="/file/7" alt="x">',
                '<p><img src="'.self::HOST."/file/7\" alt=\"x\">\r\n</p>",
            ],
        ];
    }

    /**
     * Defect, pinned on purpose: the iframe's width attribute is never closed,
     * so stored (:lecture2go markup expands to malformed HTML. Fixing it is a
     * separate change; this only records the present behaviour.
     */
    public function testLecture2GoMarkupIsMalformed(): void
    {
        $result = $this->markup('(:lecture2go 999:)');

        self::assertStringContainsString('width="100%>', $result);
        self::assertStringNotContainsString('width="100%">', $result);
    }

    /**
     * Defect, pinned on purpose: the guard in commsyMarkup() compares an int to
     * a bool with !==, so it is always true. Every value gets wrapped in <p>,
     * even one that already starts with a block element or a paragraph.
     */
    public function testEveryValueGetsWrappedInAParagraph(): void
    {
        self::assertSame("<p><p>schon ein Absatz</p>\r\n</p>", $this->markup('<p>schon ein Absatz</p>'));
        self::assertSame("<p><h4>Titel</h4>\r\n\r\n</p>", $this->markup('!Titel'));
    }

    public function testEmptyInputStillYieldsAParagraph(): void
    {
        self::assertSame("<p>\r\n</p>", $this->markup(''));
    }

    private static function anchor(string $url): string
    {
        return '<a href="'.$url.'" target="_blank" title="'.$url.'">'.$url.'</a>';
    }

    private function markup(string $input): string
    {
        return $this->makeExtension()->commsyMarkup($input);
    }

    private function makeExtension(): MarkupExtension
    {
        $router = $this->createMock(RouterInterface::class);
        $router->method('generate')->willReturnCallback(
            static function (string $name, array $params = []): string {
                $query = $params !== [] ? '?'.http_build_query($params) : '';

                return '/route/'.$name.$query;
            }
        );

        $translator = $this->createMock(TranslatorInterface::class);
        $translator->method('trans')->willReturnCallback(
            static fn (string $id): string => '['.$id.']'
        );

        $legacyMarkup = new LegacyMarkup($router, $translator);
        $legacyMarkup->addFiles([
            'bericht.pdf' => $this->file(11, 'bericht.pdf', 'Bericht'),
            'bild.png' => $this->file(22, 'bild.png', 'Bild'),
        ]);

        $requestStack = new RequestStack([Request::create(self::HOST.'/')]);

        return new MarkupExtension($requestStack, $legacyMarkup);
    }

    /**
     * Minimal stand-in for the file objects LegacyMarkup receives from
     * ItemService::getItemFileList(). Duck-typed on purpose.
     */
    private function file(int $fileId, string $filename, string $displayName): object
    {
        return new readonly class($fileId, $filename, $displayName) {
            public function __construct(
                private int $fileId,
                private string $filename,
                private string $displayName,
            ) {
            }

            public function getFileID(): int
            {
                return $this->fileId;
            }

            public function getFilename(): string
            {
                return $this->filename;
            }

            public function getDisplayName(): string
            {
                return $this->displayName;
            }
        };
    }
}
