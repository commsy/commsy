<?php

/*
 * This file is part of CommSy.
 *
 * (c) Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
 * Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
 * Edouard Simon, Monique Strauss, Jose Mauel Gonzalez Vazquez, Johannes Schultze
 */

declare(strict_types=1);

namespace Tests\Integration\Twig\Components\FileList;

use App\Entity\Files;
use App\Entity\Room;
use App\Entity\User;
use App\Twig\Components\DTO\FileDto;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;
use Tests\Factory\AccountFactory;
use Tests\Factory\AnnouncementFactory;
use Tests\Factory\FilesFactory;
use Tests\Factory\RoomUserFactory;
use Tests\Story\RoomWithMemberStory;
use Zenstruck\Foundry\Attribute\WithStory;
use Zenstruck\Foundry\Test\Factories;

/**
 * The rename/remove actions name a client-supplied `itemId` as the subject of
 * their permission check while acting on the file carried in the signed props.
 * Both have to refer to the same entry for the check to cover the effect.
 */
#[WithStory(RoomWithMemberStory::class)]
final class FileListItemAuthorizationTest extends WebTestCase
{
    use Factories;
    use InteractsWithLiveComponents;

    private \Symfony\Bundle\FrameworkBundle\KernelBrowser $client;

    protected function setUp(): void
    {
        static::ensureKernelShutdown();
        $this->client = static::createClient();
        $this->client->disableReboot();
    }

    public function testRenameRequiresTheSubjectToBeTheFilesOwnItem(): void
    {
        /** @var Room $room */
        $room = RoomWithMemberStory::get('room');
        /** @var User $owner */
        $owner = RoomWithMemberStory::get('roomUser');

        // An entry the read-only user may look at but must not change.
        $foreignItem = AnnouncementFactory::createOne(['room' => $room, 'creator' => $owner]);

        $file = FilesFactory::createOne(['filename' => 'original.txt']);

        // An ordinary member: may look at the whole room, may only edit what
        // they created. So they hold one entry that passes the check and one
        // that must not be affected by it.
        $ownerAccount = RoomWithMemberStory::get('account');
        $memberPassword = 'member-secret';
        $memberAccount = AccountFactory::createOne([
            'portal' => $ownerAccount->getPortal(),
            'authSource' => $ownerAccount->getAuthSource(),
            'plainPassword' => $memberPassword,
            'activityState' => \App\Entity\Account::ACTIVITY_ACTIVE,
            'locked' => false,
        ]);
        $member = RoomUserFactory::createOne([
            'account' => $memberAccount,
            'room' => $room,
        ]);
        $ownItem = AnnouncementFactory::createOne(['room' => $room, 'creator' => $member]);

        // A real form login: loginUser() sets the token but skips the portal
        // entry that establishes the legacy user context CommSy relies on.
        $portalId = $ownerAccount->getPortal()->getId();
        $this->client->request('GET', "/login/{$portalId}");
        $this->client->submitForm('login_local', [
            'email' => $memberAccount->getUsername(),
            'password' => $memberPassword,
        ]);
        $this->client->followRedirect();

        // Visiting the room primes the legacy context in the session. The
        // component endpoint carries no roomId, so without this ItemVoter has
        // no context — a state a real browser is never in.
        $this->client->request('GET', "/room/{$room->getItemId()}");

        // The premise, asserted rather than assumed: the actor may edit their
        // own entry but not the one the file belongs to.
        $this->client->request('GET', "/room/{$room->getItemId()}/announcement/{$ownItem->getItemId()}/edit");
        self::assertResponseIsSuccessful('actor must be able to edit their own entry');

        $this->client->request('GET', "/room/{$room->getItemId()}/announcement/{$foreignItem->getItemId()}");
        self::assertResponseIsSuccessful('actor must be able to see the foreign entry');

        $this->client->request('GET', "/room/{$room->getItemId()}/announcement/{$foreignItem->getItemId()}/edit");
        self::assertResponseStatusCodeSame(302, 'actor must NOT be able to edit the foreign entry');

        $dto = new FileDto();
        $dto->fileId = $file->getFilesId();
        $dto->contextId = $room->getItemId();
        $dto->filename = 'original.txt';
        $dto->filenameNoExt = 'original';
        $dto->extension = 'txt';
        $dto->fileSize = 10;

        $component = $this->createLiveComponent(
            name: 'FileList:FileListItem',
            data: [
                'itemId' => $foreignItem->getItemId(),
                'fileDto' => $dto,
            ],
            client: $this->client,
        );

        // Name the member's own entry as the subject while the props point at
        // the foreign one. The two disagree, so the action must not run.
        try {
            $component->call('renameFile', ['itemId' => $ownItem->getItemId()]);
            self::fail('renameFile accepted an item id that is not the file owner');
        } catch (AccessDeniedException) {
            // expected
        }

        /** @var EntityManagerInterface $entityManager */
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $entityManager->clear();
        $stored = $entityManager->getRepository(Files::class)->find($file->getFilesId());

        self::assertSame(
            'original.txt',
            $stored?->getFilename(),
            'the file of an entry the member cannot edit must stay untouched'
        );
    }
}
