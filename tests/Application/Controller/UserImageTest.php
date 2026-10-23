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

namespace Tests\Application\Controller;

use App\Entity\Account;
use App\Entity\Room;
use App\Entity\User;
use App\Files\FilesDirectory;
use App\Room\RoomType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Tests\Application\AbstractApplicationTestCase;
use Tests\Factory\RoomFactory;
use Tests\Story\RoomWithMemberStory;
use Zenstruck\Foundry\Attribute\WithStory;

/**
 * The picture and the initials of a person are only served to someone
 * signed in, and the picture is taken from the person's own room.
 */
final class UserImageTest extends AbstractApplicationTestCase
{
    /** @var list<string> directories written by the test */
    private array $directories = [];

    protected function tearDown(): void
    {
        (new Filesystem())->remove($this->directories);
        parent::tearDown();
    }

    #[WithStory(RoomWithMemberStory::class)]
    public function testNothingIsServedWithoutSigningIn(): void
    {
        /** @var Room $room */
        $room = RoomWithMemberStory::get('room');
        /** @var User $user */
        $user = RoomWithMemberStory::get('roomUser');

        foreach (['image', 'initials'] as $kind) {
            $this->client->request('GET', "/room/{$room->getItemId()}/user/{$user->getItemId()}/$kind");
            self::assertTrue($this->client->getResponse()->isRedirect(), "$kind must send to the login");
        }

        $this->client->request('GET', '/room/user/guestimage');
        $this->assertResponseIsSuccessful();
    }

    #[WithStory(RoomWithMemberStory::class)]
    public function testThePictureIsTakenFromThePersonsOwnRoom(): void
    {
        /** @var Account $account */
        $account = RoomWithMemberStory::get('account');
        /** @var Room $room */
        $room = RoomWithMemberStory::get('room');
        /** @var User $user */
        $user = RoomWithMemberStory::get('roomUser');
        $otherRoom = RoomFactory::createOne([
            'contextId' => $account->getContextId(),
            'portal' => $account->getPortal(),
            'type' => RoomType::Project->value,
        ]);
        $this->givePicture($user, (int) $account->getContextId());

        $this->loginAsUser($account->getContextId(), $account->getUsername(), $account->getPlainPassword());
        $inOwnRoom = $this->fetch("/room/{$room->getItemId()}/user/{$user->getItemId()}/image");
        $viaOtherRoom = $this->fetch("/room/{$otherRoom->getItemId()}/user/{$user->getItemId()}/image");
        $initials = $this->fetch("/room/{$room->getItemId()}/user/{$user->getItemId()}/initials");

        self::assertNotSame($initials, $inOwnRoom, 'the picture, not the initials');
        self::assertSame($inOwnRoom, $viaOtherRoom);
    }

    #[WithStory(RoomWithMemberStory::class)]
    public function testAnIdThatIsNoPersonIsNotFound(): void
    {
        /** @var Account $account */
        $account = RoomWithMemberStory::get('account');
        /** @var Room $room */
        $room = RoomWithMemberStory::get('room');

        $this->loginAsUser($account->getContextId(), $account->getUsername(), $account->getPlainPassword());
        foreach (['image', 'initials'] as $kind) {
            $this->client->request('GET', "/room/{$room->getItemId()}/user/{$room->getItemId()}/$kind");
            $this->assertResponseStatusCodeSame(404, $kind);
        }
    }

    #[WithStory(RoomWithMemberStory::class)]
    public function testTheImageLibraryServesNothingFromTheFilesDirectory(): void
    {
        /** @var Account $account */
        $account = RoomWithMemberStory::get('account');
        /** @var User $user */
        $user = RoomWithMemberStory::get('roomUser');
        $relativePath = static::getContainer()->get(FilesDirectory::class)->relativeOf(
            $this->givePicture($user, (int) $account->getContextId())
        );

        $this->client->request('GET', "/media/cache/resolve/commsy_user_image/$relativePath");

        $this->assertResponseStatusCodeSame(404);
    }

    private function fetch(string $path): string
    {
        $this->client->request('GET', $path);
        $this->assertResponseIsSuccessful();

        return (string) $this->client->getInternalResponse()->getContent();
    }

    /**
     * Sets a picture as the room profile does and writes a real image.
     */
    private function givePicture(User $user, int $portalId): string
    {
        $contextId = (int) $user->getContextId();
        $name = "cid{$contextId}_{$user->getUserId()}.png";
        static::getContainer()->get(EntityManagerInterface::class)->getConnection()
            ->update('user', ['extras' => serialize(['USERPICTURE' => $name])], ['item_id' => $user->getItemId()]);

        $directory = static::getContainer()->get(FilesDirectory::class)->contextDirectory($portalId, $contextId);
        (new Filesystem())->mkdir($directory);
        $image = imagecreatetruecolor(240, 240);
        imagefill($image, 0, 0, (int) imagecolorallocate($image, 200, 30, 30));
        imagepng($image, "$directory/$name");
        $this->directories[] = $directory;

        return "$directory/$name";
    }
}
