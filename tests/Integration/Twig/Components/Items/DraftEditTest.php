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

namespace Tests\Integration\Twig\Components\Items;

use App\Entity\Account;
use App\Entity\Room;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;
use Tests\Factory\MaterialFactory;
use Tests\Factory\RoomFactory;
use Tests\Factory\RoomUserFactory;
use Tests\Story\AccountStory;
use Zenstruck\Foundry\Attribute\WithStory;

/**
 * Pins {@see \App\Twig\Components\Items\DraftEdit}: the inline
 * tag/category save coordinator for draft items.
 *
 *  - mount with a valid itemId renders
 *  - cancelDraft returns a redirect to the rubric list / detail
 *    (depending on item type)
 *  - saveDraft emits the right sub-component events for the room's
 *    feature set (buzzwords / tags) and dispatches the browser-side
 *    save event once all expected child events have arrived
 */
#[WithStory(AccountStory::class)]
final class DraftEditTest extends KernelTestCase
{
    use InteractsWithLiveComponents;

    private Account $account;
    private Room $room;
    private User $roomUser;

    protected function setUp(): void
    {
        self::bootKernel();

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

    public function testMountsWithItemIdProp(): void
    {
        $itemId = $this->createMaterialItemId();

        $component = $this->createLiveComponent(
            name: 'Items:DraftEdit',
            data: ['itemId' => $itemId],
        );

        $rendered = $component->render();
        self::assertNotEmpty((string) $rendered);
        self::assertSame($itemId, $component->component()->itemId);
        self::assertSame([], $component->component()->waitForEvents);
    }

    public function testCancelDraftRedirectsToMaterialList(): void
    {
        $itemId = $this->createMaterialItemId();

        $component = $this->createLiveComponent(
            name: 'Items:DraftEdit',
            data: ['itemId' => $itemId],
        );

        $component->call('cancelDraft');

        $response = $component->response();
        self::assertTrue($response->isRedirect(), 'cancelDraft must return a redirect response');
        self::assertStringContainsString(
            sprintf('/room/%d/material', $this->room->getItemId()),
            (string) $response->headers->get('Location'),
        );
    }

    public function testSaveDraftDispatchesBrowserEventWhenRoomHasNoBuzzwordsOrTags(): void
    {
        // Default fresh project room has buzzwords + tags disabled, so
        // saveDraft completes synchronously (no child events to wait
        // for) and immediately dispatches `draft:saved`.
        $itemId = $this->createMaterialItemId();

        $component = $this->createLiveComponent(
            name: 'Items:DraftEdit',
            data: ['itemId' => $itemId],
        );

        $component->call('saveDraft');

        $this->assertComponentDispatchBrowserEvent($component, 'draft:saved');
        self::assertSame(
            [],
            $component->component()->waitForEvents,
            'with no buzzword/tag children to wait for, waitForEvents must stay empty',
        );
    }

    private function createMaterialItemId(): int
    {
        return (int) MaterialFactory::createOne([
            'room' => $this->room,
            'creator' => $this->roomUser,
        ])->getItemId();
    }
}
