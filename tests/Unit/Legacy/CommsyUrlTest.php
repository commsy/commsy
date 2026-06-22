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

use App\Legacy\CommsyUrl;
use PHPUnit\Framework\TestCase;

class CommsyUrlTest extends TestCase
{
    private array $getBackup;
    private mixed $entryPointBackup;

    protected function setUp(): void
    {
        $this->getBackup = $_GET;
        $this->entryPointBackup = $GLOBALS['c_single_entry_point'] ?? null;

        // $c_single_entry_point is never set anywhere in the code base, so the
        // realistic case is an unset global, which yields relative "?cid=..." URLs.
        unset($GLOBALS['c_single_entry_point'], $_GET['mode'], $_GET['download']);
    }

    protected function tearDown(): void
    {
        $_GET = $this->getBackup;
        if (null === $this->entryPointBackup) {
            unset($GLOBALS['c_single_entry_point']);
        } else {
            $GLOBALS['c_single_entry_point'] = $this->entryPointBackup;
        }
    }

    public function testBuildsRelativeUrlWhenNoEntryPointConfigured(): void
    {
        // Real production behaviour: $c_single_entry_point is undefined.
        self::assertSame(
            '<a href="?cid=5&amp;mod=material&amp;fct=detail&amp;iid=3">My Title</a>',
            CommsyUrl::ahref(5, 'material', 'detail', ['iid' => 3], 'My Title')
        );
    }

    public function testConfiguredEntryPointIsUsedWhenSet(): void
    {
        $GLOBALS['c_single_entry_point'] = 'commsy.php';

        self::assertSame(
            '<a href="commsy.php?cid=5&amp;mod=material&amp;fct=detail&amp;iid=3">My Title</a>',
            CommsyUrl::ahref(5, 'material', 'detail', ['iid' => 3], 'My Title')
        );
    }

    public function testEmptyParameterArrayAddsNoQueryParameters(): void
    {
        self::assertSame(
            '<a href="?cid=5&amp;mod=material&amp;fct=detail">My Title</a>',
            CommsyUrl::ahref(5, 'material', 'detail', [], 'My Title')
        );
    }

    public function testFragmentIsAppended(): void
    {
        self::assertSame(
            '<a href="?cid=7&amp;mod=discussion&amp;fct=detail#anchor3">Jump</a>',
            CommsyUrl::ahref(7, 'discussion', 'detail', [], 'Jump', fragment: 'anchor3')
        );
    }

    public function testRendersTitleAndTargetAttributesAndStripsTitleTags(): void
    {
        $result = CommsyUrl::ahref(5, 'content', 'detail', [], 'Text', title: '<b>Hover</b>', target: '_blank');

        self::assertSame('<a href="?cid=5&amp;mod=content&amp;fct=detail" title="Hover" target="_blank">Text</a>', $result);
    }

    public function testPrintModeReturnsPlainLinkText(): void
    {
        $_GET['mode'] = 'print';

        self::assertSame('My Title', CommsyUrl::ahref(5, 'material', 'detail', ['iid' => 3], 'My Title'));
    }

    public function testPrintModeStillRendersLinkForZipDownload(): void
    {
        $_GET['mode'] = 'print';
        $_GET['download'] = 'zip';

        self::assertSame(
            '<a href="?cid=5&amp;mod=material&amp;fct=detail">My Title</a>',
            CommsyUrl::ahref(5, 'material', 'detail', [], 'My Title')
        );
    }
}
