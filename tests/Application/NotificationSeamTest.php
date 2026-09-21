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

namespace Tests\Application;

use App\Entity\Account;
use App\Entity\Room;
use App\Entity\User;
use App\Enum\EntryAction;
use App\Message\NotifyNewEntryMessage;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Messenger\Transport\InMemoryTransport;
use Tests\Factory\AnnouncementFactory;
use Tests\Factory\MaterialFactory;
use Tests\Story\RoomWithMemberStory;
use Zenstruck\Foundry\Attribute\WithStory;

/**
 * The seam between the application and the notifications: three controllers
 * announce what a person just did, and everything else hangs off those three
 * lines. Both sides of the seam are covered elsewhere — the subscriber turns an
 * event into a message, the manager fans a message out — but nothing proved
 * that the controllers still emit the event at all. Delete one of those lines
 * and, without these tests, the notifications simply stop while the suite stays
 * green.
 */
#[WithStory(RoomWithMemberStory::class)]
class NotificationSeamTest extends AbstractApplicationTestCase
{
    private Account $account;
    private Room $room;
    private User $roomUser;
    private int $roomId;

    public function setUp(): void
    {
        parent::setUp();

        $this->account = RoomWithMemberStory::get('account');
        $this->room = RoomWithMemberStory::get('room');
        $this->roomUser = RoomWithMemberStory::get('roomUser');
        $this->roomId = $this->room->getItemId();

        $this->loginAsUser(
            $this->account->getPortal()->getId(),
            $this->account->getUsername(),
            $this->account->getPlainPassword()
        );
    }

    public function testPublishingAnEntryAnnouncesIt(): void
    {
        $material = MaterialFactory::createOne(['room' => $this->room, 'creator' => $this->roomUser]);

        $this->client->request(
            'GET',
            "/room/{$this->roomId}/item/{$material->getItemId()}/undraft",
            [], [], ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest']
        );
        $this->assertResponseIsSuccessful();

        $signal = $this->signalFor($material->getItemId());
        self::assertNotNull($signal, 'publishing an entry must announce it');
        self::assertSame(EntryAction::Created, $signal->action);
    }

    public function testAttachingAFileCountsAsAnEdit(): void
    {
        $material = MaterialFactory::createOne(['room' => $this->room, 'creator' => $this->roomUser]);

        $this->client->request(
            'POST',
            "/room/{$this->roomId}/attach/{$material->getItemId()}",
            [],
            ['files' => [$this->tempUpload()]]
        );
        $this->assertResponseIsSuccessful();

        $signal = $this->signalFor($material->getItemId());
        self::assertNotNull($signal, 'an attachment changes the entry and must be announced');
        self::assertSame(EntryAction::Edited, $signal->action);
    }

    public function testAnnotatingAnnouncesTheParentEntry(): void
    {
        $announcement = AnnouncementFactory::createOne(['room' => $this->room, 'creator' => $this->roomUser]);

        $crawler = $this->client->request(
            'GET',
            "/room/{$this->roomId}/announcement/{$announcement->getItemId()}"
        );
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('annotation[save]')->form();
        $form['annotation[description]'] = 'Frisch annotiert';
        $this->client->submit($form);

        // The annotation is reported on the entry it belongs to, not on itself.
        $signal = $this->signalFor($announcement->getItemId());
        self::assertNotNull($signal, 'an annotation must be announced on its parent');
        self::assertSame(EntryAction::Annotated, $signal->action);
    }

    private function signalFor(int $sourceItemId): ?NotifyNewEntryMessage
    {
        /** @var InMemoryTransport $transport */
        $transport = self::getContainer()->get('messenger.transport.async');

        foreach ($transport->getSent() as $envelope) {
            $message = $envelope->getMessage();
            if ($message instanceof NotifyNewEntryMessage && $message->sourceItemId === $sourceItemId) {
                return $message;
            }
        }

        return null;
    }

    private function tempUpload(): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'commsy_test_');
        file_put_contents($path, 'attached');

        return new UploadedFile(path: $path, originalName: 'attachment.txt', mimeType: 'text/plain', error: null, test: true);
    }
}
