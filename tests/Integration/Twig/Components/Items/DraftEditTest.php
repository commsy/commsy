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
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
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
final class DraftEditTest extends WebTestCase
{
    use InteractsWithLiveComponents;

    private Account $account;
    private Room $room;
    private User $roomUser;

    private KernelBrowser $client;

    protected function setUp(): void
    {
        static::ensureKernelShutdown();
        $this->client = static::createClient();
        $this->client->disableReboot();

        $this->account = AccountStory::get('account');
        $portalId = (int) $this->account->getPortal()?->getId();
        $username = $this->account->getUsername();
        // Transient — read it before any further factory clears the unit of work.
        $password = (string) $this->account->getPlainPassword();

        $this->room = RoomFactory::new()->project()->create([
            'contextId' => $portalId,
            'portal' => $this->account->getPortal(),
        ]);
        $this->roomUser = RoomUserFactory::createOne([
            'account' => $this->account,
            'room' => $this->room,
            'status' => 2,
        ]);

        // saveDraft triggers the tag/category saves, so it is guarded by
        // ITEM_EDIT. That needs a real login plus a room visit: the component
        // endpoint carries no roomId, so the legacy context has to come from
        // the session — exactly as it does in a browser.
        $this->client->request('GET', "/login/{$portalId}");
        $this->client->submitForm('login_local', ['email' => $username, 'password' => $password]);
        $this->client->followRedirect();
        $this->client->request('GET', "/room/{$this->room->getItemId()}");
    }

    public function testMountsWithItemIdProp(): void
    {
        $itemId = $this->createMaterialItemId();

        $component = $this->createLiveComponent(
            name: 'Items:DraftEdit',
            data: ['itemId' => $itemId],
            client: $this->client,
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
            client: $this->client,
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
            client: $this->client,
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
