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
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DomCrawler\Field\ChoiceFormField;
use Symfony\Component\Filesystem\Filesystem;
use Tests\Application\AbstractApplicationTestCase;
use Tests\Factory\RoomFactory;
use Tests\Factory\RoomUserFactory;
use Tests\Story\RoomWithMemberStory;
use Zenstruck\Foundry\Attribute\WithStory;

/**
 * The picture of the room profile applies to every room of the account: a
 * switch to the initials clears it everywhere and removes its copies.
 */
final class ProfileImageAcrossRoomsTest extends AbstractApplicationTestCase
{
    /** @var list<string> directories written by the test */
    private array $directories = [];

    protected function tearDown(): void
    {
        (new Filesystem())->remove($this->directories);
        parent::tearDown();
    }

    #[WithStory(RoomWithMemberStory::class)]
    public function testSwitchingToTheInitialsClearsThePictureInEveryRoom(): void
    {
        /** @var Account $account */
        $account = RoomWithMemberStory::get('account');
        /** @var Room $room */
        $room = RoomWithMemberStory::get('room');
        /** @var User $roomUser */
        $roomUser = RoomWithMemberStory::get('roomUser');

        $otherProject = RoomFactory::createOne([
            'contextId' => $account->getContextId(),
            'portal' => $account->getPortal(),
            'type' => RoomType::Project->value,
        ]);
        $groupRoom = RoomFactory::createOne([
            'contextId' => $account->getContextId(),
            'portal' => $account->getPortal(),
            'type' => RoomType::GroupRoom->value,
        ]);
        $users = [
            'project room' => $roomUser,
            'other project room' => RoomUserFactory::createOne(['account' => $account, 'room' => $otherProject]),
            'group room' => RoomUserFactory::createOne(['account' => $account, 'room' => $groupRoom]),
        ];
        $pictures = [];
        foreach ($users as $where => $user) {
            $pictures[$where] = $this->givePicture($user, (int) $account->getContextId());
        }

        $this->loginAsUser($account->getContextId(), $account->getUsername(), $account->getPlainPassword());
        $crawler = $this->client->request('GET', "/room/{$room->getItemId()}/user/{$roomUser->getItemId()}/general");
        $this->assertResponseIsSuccessful();
        $form = $crawler->selectButton('room_profile_general_save')->form();
        $useProfileImage = $form['room_profile_general[useProfileImage]'];
        self::assertInstanceOf(ChoiceFormField::class, $useProfileImage);
        $useProfileImage->untick();
        // the live component re-renders without the upload field once the box is cleared
        $form->remove('room_profile_general[upload]');
        $this->client->submit($form);
        $this->assertResponseIsSuccessful();

        $observed = [];
        foreach ($users as $where => $user) {
            $observed[$where] = ['setting' => $this->userPicture((int) $user->getItemId()), 'file' => file_exists($pictures[$where])];
        }
        self::assertSame(array_fill_keys(array_keys($users), ['setting' => '', 'file' => false]), $observed);
    }

    /**
     * Sets a picture as the room profile does and writes its file.
     */
    private function givePicture(User $user, int $portalId): string
    {
        $contextId = (int) $user->getContextId();
        $name = "cid{$contextId}_{$user->getUserId()}.png";
        $this->connection()->update('user', ['extras' => serialize(['USERPICTURE' => $name])], ['item_id' => $user->getItemId()]);

        $directory = static::getContainer()->get(FilesDirectory::class)->contextDirectory($portalId, $contextId);
        (new Filesystem())->dumpFile("$directory/$name", 'picture');
        $this->directories[] = $directory;

        return "$directory/$name";
    }

    private function userPicture(int $itemId): string
    {
        $extras = $this->connection()->fetchOne('SELECT extras FROM user WHERE item_id = ?', [$itemId]);
        $values = \is_string($extras) && '' !== $extras ? unserialize($extras, ['allowed_classes' => false]) : [];

        return \is_array($values) ? (string) ($values['USERPICTURE'] ?? '') : '';
    }

    private function connection(): Connection
    {
        return static::getContainer()->get(EntityManagerInterface::class)->getConnection();
    }
}
