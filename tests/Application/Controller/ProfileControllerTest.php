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
use App\Entity\Room;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Tests\Application\AbstractApplicationTestCase;
use Tests\Factory\AccountFactory;
use Tests\Factory\RoomUserFactory;
use Tests\Story\RoomWithMemberStory;
use Zenstruck\Foundry\Attribute\WithStory;

class ProfileControllerTest extends AbstractApplicationTestCase
{
    #[WithStory(RoomWithMemberStory::class)]
    public function testGeneralSave(): void
    {
        $account = RoomWithMemberStory::get('account');
        $room = RoomWithMemberStory::get('room');
        $roomUser = RoomWithMemberStory::get('roomUser');

        $this->loginAsUser($account->getContextId(), $account->getUsername(), $account->getPlainPassword());

        $roomId = $room->getItemId();
        $itemId = $roomUser->getItemId();

        $this->client->request('GET', "/room/{$roomId}/user/{$itemId}/general");
        $this->assertResponseIsSuccessful();

        $this->client->submitForm('room_profile_general_save', []);
        $this->assertResponseIsSuccessful();
    }

    /**
     * Containment pin for issue #5420: when the user already has a
     * profile picture set, the ProfileImageForm live component flips
     * `useProfileImage` to true on mount and instantiates the
     * UploadDropzoneType subfield via the `addDependent` closure. A
     * mismatch between the form type's option name (`uploadUrl`) and
     * UploadDropzoneType's required option (`upload_url`) crashed the
     * page render here. Keeping this branch covered as the
     * controller-level fallback for live-component scenarios that the
     * Live Component test helper cannot drive past mount-time auth.
     */
    #[WithStory(RoomWithMemberStory::class)]
    public function testGeneralViewWithExistingPictureRendersDropzone(): void
    {
        $account = RoomWithMemberStory::get('account');
        $room = RoomWithMemberStory::get('room');
        $roomUser = RoomWithMemberStory::get('roomUser');

        // Simulate a stored profile picture by writing the legacy
        // USERPICTURE extras marker. Picture filename is not loaded
        // from disk during render — only its non-empty presence flips
        // `useProfileImage`.
        $connection = static::getContainer()
            ->get(\Doctrine\ORM\EntityManagerInterface::class)
            ->getConnection();
        $extras = serialize(['USERPICTURE' => 'placeholder.jpg']);
        $connection->executeStatement(
            'UPDATE user SET extras = :extras WHERE item_id = :id',
            ['extras' => $extras, 'id' => $roomUser->getItemId()],
        );

        $this->loginAsUser($account->getContextId(), $account->getUsername(), $account->getPlainPassword());

        $this->client->request(
            'GET',
            sprintf('/room/%d/user/%d/general', $room->getItemId(), $roomUser->getItemId()),
        );

        $this->assertResponseIsSuccessful(
            'general view must render even when the upload dropzone subfield is active',
        );
    }

    #[WithStory(RoomWithMemberStory::class)]
    public function testAddressSave(): void
    {
        $account = RoomWithMemberStory::get('account');
        $room = RoomWithMemberStory::get('room');
        $roomUser = RoomWithMemberStory::get('roomUser');

        $this->loginAsUser($account->getContextId(), $account->getUsername(), $account->getPlainPassword());

        $roomId = $room->getItemId();
        $itemId = $roomUser->getItemId();

        $this->client->request('GET', "/room/{$roomId}/user/{$itemId}/address");
        $this->assertResponseIsSuccessful();

        $this->client->submitForm('room_profile_address_save', [
            'room_profile_address[street]' => 'Teststreet 1',
            'room_profile_address[city]' => 'Testcity',
            'room_profile_address[zipCode]' => '12345',
        ]);

        $this->assertResponseRedirects("/room/{$roomId}/user/{$itemId}/address");
        $this->client->followRedirect();
        $this->assertResponseIsSuccessful();
    }

    #[WithStory(RoomWithMemberStory::class)]
    public function testContactSave(): void
    {
        $account = RoomWithMemberStory::get('account');
        $room = RoomWithMemberStory::get('room');
        $roomUser = RoomWithMemberStory::get('roomUser');

        $this->loginAsUser($account->getContextId(), $account->getUsername(), $account->getPlainPassword());

        $roomId = $room->getItemId();
        $itemId = $roomUser->getItemId();

        $this->client->request('GET', "/room/{$roomId}/user/{$itemId}/contact");
        $this->assertResponseIsSuccessful();

        $this->client->submitForm('room_profile_contact_save', [
            'room_profile_contact[phone]' => '123456789',
        ]);

        $this->assertResponseRedirects("/room/{$roomId}/user/{$itemId}/contact");
        $this->client->followRedirect();
        $this->assertResponseIsSuccessful();
    }

    #[WithStory(RoomWithMemberStory::class)]
    public function testNotificationsSave(): void
    {
        $account = RoomWithMemberStory::get('account');
        $room = RoomWithMemberStory::get('room');
        $roomUser = RoomWithMemberStory::get('roomUser');

        $this->loginAsUser($account->getContextId(), $account->getUsername(), $account->getPlainPassword());

        $roomId = $room->getItemId();
        $itemId = $roomUser->getItemId();

        $this->client->request('GET', "/room/{$roomId}/user/{$itemId}/notifications");
        $this->assertResponseIsSuccessful();

        $this->client->submitForm('room_profile_save', [
            'room_profile[mail_account]' => true,
            'room_profile[mail_room]' => true,
            'room_profile[mail_item_deleted]' => false,
        ]);
        $this->assertResponseIsSuccessful();
    }

    #[WithStory(RoomWithMemberStory::class)]
    public function testMenuRendersDropdown(): void
    {
        $account = RoomWithMemberStory::get('account');
        $room = RoomWithMemberStory::get('room');

        $this->loginAsUser($account->getContextId(), $account->getUsername(), $account->getPlainPassword());

        $this->client->request('GET', "/room/{$room->getItemId()}/user/dropdownmenu");
        $this->assertResponseIsSuccessful();
    }

    #[WithStory(RoomWithMemberStory::class)]
    public function testDeleteRoomProfilePageRenders(): void
    {
        $account = RoomWithMemberStory::get('account');
        $room = RoomWithMemberStory::get('room');
        $roomUser = RoomWithMemberStory::get('roomUser');

        $this->loginAsUser($account->getContextId(), $account->getUsername(), $account->getPlainPassword());

        $this->client->request(
            'GET',
            "/room/{$room->getItemId()}/user/{$roomUser->getItemId()}/deleteroomprofile"
        );
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
    }

    #[WithStory(RoomWithMemberStory::class)]
    public function testDeleteRoomProfileSubmitWithBlankConfirmShowsError(): void
    {
        $account = RoomWithMemberStory::get('account');
        $room = RoomWithMemberStory::get('room');
        $roomUser = RoomWithMemberStory::get('roomUser');

        $this->loginAsUser($account->getContextId(), $account->getUsername(), $account->getPlainPassword());

        $crawler = $this->client->request(
            'GET',
            "/room/{$room->getItemId()}/user/{$roomUser->getItemId()}/deleteroomprofile"
        );
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('delete_form[confirm_button]')->form();
        $form['delete_form[confirm_field]'] = '';
        $this->client->submit($form);

        // Symfony >=6.2 surfaces invalid form submits as 422.
        $this->assertResponseStatusCodeSame(422);
    }

    #[WithStory(RoomWithMemberStory::class)]
    public function testDeleteRoomProfileLeavesTheRoomAndKeepsIt(): void
    {
        $account = RoomWithMemberStory::get('account');
        $room = RoomWithMemberStory::get('room');
        $roomUser = RoomWithMemberStory::get('roomUser');
        $this->addModerator($room);
        $this->pinGerman($account);

        $this->loginAsUser($account->getContextId(), $account->getUsername(), $account->getPlainPassword());

        $crawler = $this->client->request(
            'GET',
            "/room/{$room->getItemId()}/user/{$roomUser->getItemId()}/deleteroomprofile"
        );
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('delete_form[confirm_button]')->form();
        $form['delete_form[confirm_field]'] = $this->confirmString('delete');
        $this->client->submit($form);

        $this->assertResponseRedirects("/portal/{$account->getContextId()}/enter");
        $this->assertNotNull($this->deletionDate('user', $roomUser->getItemId()), 'room profile is deleted');
        $this->assertNull($this->deletionDate('room', $room->getItemId()), 'room is kept');
    }

    #[WithStory(RoomWithMemberStory::class)]
    public function testLockRoomProfileRejectsTheUserAndKeepsTheRoom(): void
    {
        $account = RoomWithMemberStory::get('account');
        $room = RoomWithMemberStory::get('room');
        $roomUser = RoomWithMemberStory::get('roomUser');
        $this->addModerator($room);
        $this->pinGerman($account);

        $this->loginAsUser($account->getContextId(), $account->getUsername(), $account->getPlainPassword());

        $crawler = $this->client->request(
            'GET',
            "/room/{$room->getItemId()}/user/{$roomUser->getItemId()}/deleteroomprofile"
        );
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('lock_form[confirm_button]')->form();
        $form['lock_form[confirm_field]'] = $this->confirmString('lock');
        $this->client->submit($form);

        $this->assertResponseRedirects("/portal/{$account->getContextId()}/enter");
        $row = $this->connection()->fetchAssociative(
            'SELECT status, deletion_date FROM user WHERE item_id = :id',
            ['id' => $roomUser->getItemId()]
        );
        $this->assertSame(0, (int) $row['status'], 'room profile is locked');
        $this->assertNull($row['deletion_date'], 'room profile is kept');
        $this->assertNull($this->deletionDate('room', $room->getItemId()), 'room is kept');
    }

    /** The story room has no moderator; the profile of its member may only go while it keeps one. */
    private function addModerator(Room $room): void
    {
        RoomUserFactory::new()->asModerator()->create([
            'account' => AccountFactory::createOne([
                'activityState' => Account::ACTIVITY_ACTIVE,
                'locked' => false,
            ]),
            'room' => $room,
        ]);
    }

    /** The word to type, in the language of the page last requested. */
    private function confirmString(string $key): string
    {
        return mb_strtoupper(static::getContainer()->get(TranslatorInterface::class)->trans(
            $key,
            [],
            'profile',
            $this->client->getRequest()->getLocale()
        ));
    }

    /**
     * A random account language can render the page in one language and
     * check the confirmation in another.
     */
    private function pinGerman(Account $account): void
    {
        $this->connection()->update('accounts', ['language' => 'de'], ['id' => $account->getId()]);
    }

    private function connection(): Connection
    {
        return static::getContainer()->get(EntityManagerInterface::class)->getConnection();
    }

    private function deletionDate(string $table, int $itemId): mixed
    {
        return $this->connection()->fetchOne(
            "SELECT deletion_date FROM $table WHERE item_id = :id",
            ['id' => $itemId]
        );
    }
}
