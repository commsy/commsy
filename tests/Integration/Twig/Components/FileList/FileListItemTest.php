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

namespace Tests\Integration\Twig\Components\FileList;

use App\Twig\Components\DTO\FileDto;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;

/**
 * Pins {@see \App\Twig\Components\FileList\FileListItem}: per-file
 * inline editing component. Pure mount/render coverage — the rename
 * and remove LiveActions sit behind `#[IsGranted]` and are exercised
 * by the controller-level test suite where a real user is logged in.
 */
final class FileListItemTest extends KernelTestCase
{
    use InteractsWithLiveComponents;

    public function testMountsWithFileDtoAndItemId(): void
    {
        $component = $this->createLiveComponent(
            name: 'FileList:FileListItem',
            data: [
                'fileDto' => $this->fileDto(),
                'itemId' => 42,
                'isImage' => false,
            ],
        );

        $rendered = $component->render();
        self::assertNotEmpty((string) $rendered, 'mount must render');
        self::assertSame(42, $component->component()->itemId);
        self::assertFalse($component->component()->renameMode);
        self::assertFalse($component->component()->deleteMode);
    }

    public function testWritablePropFilenameNoExtUpdatesOnLiveProp(): void
    {
        $component = $this->createLiveComponent(
            name: 'FileList:FileListItem',
            data: [
                'fileDto' => $this->fileDto(),
                'itemId' => 42,
            ],
        );

        $component->set('fileDto.filenameNoExt', 'renamed');

        self::assertSame(
            'renamed',
            $component->component()->fileDto->filenameNoExt,
            'the only writable FileDto path must round-trip through LiveProp',
        );

        $rendered = $component->render();
        self::assertNotEmpty((string) $rendered, 're-render after writable prop update must succeed');
    }

    public function testFlagPropsAreNotWritableViaLiveProp(): void
    {
        // renameMode + deleteMode have no writable annotation, so the
        // live hydrator must reject client-side updates — only internal
        // LiveActions are allowed to toggle them.
        $component = $this->createLiveComponent(
            name: 'FileList:FileListItem',
            data: [
                'fileDto' => $this->fileDto(),
                'itemId' => 42,
            ],
        );

        $this->expectException(\Throwable::class);
        $component->set('renameMode', true);
    }

    private function fileDto(): FileDto
    {
        $dto = new FileDto();
        $dto->fileId = 1000;
        $dto->contextId = 9999;
        $dto->extension = 'pdf';
        $dto->filename = 'sample.pdf';
        $dto->filenameNoExt = 'sample';
        $dto->fileSize = 12345;

        return $dto;
    }
}
