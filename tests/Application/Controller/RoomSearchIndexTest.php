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

namespace Tests\Application\Controller;

use App\Entity\Account;
use App\Message\ReindexItem;
use Symfony\Component\Messenger\Transport\InMemory\InMemoryTransport;
use Tests\Application\AbstractApplicationTestCase;
use Tests\Story\AccountStory;
use Zenstruck\Foundry\Attribute\WithStory;

/**
 * A room is queued for the search index when it is created and when its
 * settings are saved, so the room search finds it under its current title.
 */
#[WithStory(AccountStory::class)]
class RoomSearchIndexTest extends AbstractApplicationTestCase
{
    private int $portalId;

    public function setUp(): void
    {
        parent::setUp();

        /** @var Account $account */
        $account = AccountStory::get('account');
        $this->portalId = (int) $account->getContextId();
        $this->loginAsUser($this->portalId, $account->getUsername(), $account->getPlainPassword());
    }

    public function testCreatingARoomQueuesItForTheIndex(): void
    {
        $roomId = $this->createRoom($this->portalId, 'Neuer Raum');

        self::assertGreaterThan(0, $this->reindexCount($roomId, 'project'));
    }

    public function testSavingTheRoomSettingsQueuesItForTheIndex(): void
    {
        $roomId = $this->createRoom($this->portalId, 'Alter Titel');

        $crawler = $this->client->request('GET', "/room/{$roomId}/settings/general");
        self::assertResponseIsSuccessful();
        $this->client->submit($crawler->selectButton('general_settings[save]')->form(), [
            'general_settings[title]' => 'Neuer Titel',
        ]);
        self::assertResponseRedirects("/room/{$roomId}/settings/general");

        self::assertGreaterThan(0, $this->reindexCount($roomId, 'project'));
    }

    private function reindexCount(int $itemId, string $itemType): int
    {
        $transport = self::getContainer()->get('messenger.transport.async');
        self::assertInstanceOf(InMemoryTransport::class, $transport);

        $reindexed = array_filter(
            $transport->getSent(),
            static fn ($envelope): bool => $envelope->getMessage() instanceof ReindexItem
                && $envelope->getMessage()->getItemId() === $itemId
                && $envelope->getMessage()->getItemType() === $itemType,
        );

        return count($reindexed);
    }
}
