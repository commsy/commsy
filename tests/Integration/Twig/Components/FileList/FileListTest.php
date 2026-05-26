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

use App\Entity\Account;
use App\Entity\Room;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;
use Tests\Factory\MaterialFactory;
use Tests\Factory\RoomFactory;
use Tests\Factory\RoomUserFactory;
use Tests\Integration\Concerns\PrimesSession;
use Tests\Story\AccountStory;
use Zenstruck\Foundry\Attribute\WithStory;

/**
 * Pins {@see \App\Twig\Components\FileList\FileList}: the multi-file
 * inline editor for material/announcement/… attachments. Covers the
 * PostMount refresh path and the four writable filter props.
 *
 * The IsGranted-guarded LiveActions (renameFile / removeFile /
 * createOfficeFile / refreshFiles) need a real user and are exercised
 * through the controller-level suites.
 */
#[WithStory(AccountStory::class)]
final class FileListTest extends KernelTestCase
{
    use InteractsWithLiveComponents;
    use PrimesSession;

    private Account $account;
    private Room $room;
    private User $roomUser;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->primeSession();

        $this->account = AccountStory::get('account');
        $this->room = RoomFactory::new()->project()->create([
            'contextId' => $this->account->getPortal()?->getId(),
            'portal' => $this->account->getPortal(),
        ]);
        $this->roomUser = RoomUserFactory::createOne([
            'account' => $this->account,
            'room' => $this->room,
            'status' => 2,
        ]);
    }

    public function testMountsAndPostMountSeedsFileCounters(): void
    {
        $itemId = $this->createMaterialItemId();

        $component = $this->createLiveComponent(
            name: 'FileList:FileList',
            data: ['itemId' => $itemId],
        );

        $rendered = $component->render();
        self::assertNotEmpty((string) $rendered);

        $instance = $component->component();
        self::assertSame($itemId, $instance->itemId);
        self::assertSame(0, $instance->imageCount, 'fresh material has no images');
        self::assertSame(0, $instance->fileCount, 'fresh material has no files');
        self::assertSame([], $instance->files);
    }

    /**
     * @return iterable<string, array{string, scalar}>
     */
    public static function writableFilterProvider(): iterable
    {
        yield 'filterFileExtensions image' => ['filterFileExtensions', 'image'];
        yield 'filterFileExtensions all'   => ['filterFileExtensions', 'all'];
        yield 'filterFileName partial'     => ['filterFileName', 'sample'];
        yield 'imageLimit off'             => ['imageLimit', false];
        yield 'fileLimit off'              => ['fileLimit', false];
    }

    /**
     * @dataProvider writableFilterProvider
     */
    public function testWritableFilterPropTriggersReRender(string $prop, $value): void
    {
        $itemId = $this->createMaterialItemId();

        $component = $this->createLiveComponent(
            name: 'FileList:FileList',
            data: ['itemId' => $itemId],
        );

        $component->set($prop, $value);

        self::assertSame(
            $value,
            $component->component()->{$prop},
            sprintf('%s must round-trip through LiveProp', $prop),
        );

        $rendered = $component->render();
        self::assertNotEmpty((string) $rendered);
    }

    private function createMaterialItemId(): int
    {
        return (int) MaterialFactory::createOne([
            'room' => $this->room,
            'creator' => $this->roomUser,
        ])->getItemId();
    }
}
