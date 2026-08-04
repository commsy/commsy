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
use Doctrine\ORM\EntityManagerInterface;
use Tests\Application\AbstractApplicationTestCase;
use Tests\Factory\AccountFactory;
use Tests\Factory\RoomUserFactory;
use Tests\Story\AccountStory;
use Zenstruck\Foundry\Attribute\WithStory;

/**
 * The rubric edit forms gate three fields behind "am I the creator, or am I a
 * moderator here?":
 *
 *   permission  — restrict editing to the creator (private editing)
 *   hidden      — hide the entry
 *   hiddendate  — hide it until a given date
 *
 * Six form types share that gate (DateType, AnnouncementType, MaterialType,
 * DiscussionType, TopicType, TodoType). Material stands in for all of them
 * here; the gate is a copy of the same two-part condition in each.
 *
 * Why this needs a test: the moderator half of the condition was dead from
 * 7bc62e133 (2024-04-05, #5124) until it was repaired. That commit moved the
 * forms off LegacyEnvironment and translated
 *
 *     $formData['creatorId'] === $currentUser->getItemID() || $currentUser->isModerator()
 *
 * into two voter calls, but asked ItemVoter for MODERATE without a subject.
 * ItemVoter resolves its subject first and falls through to `return false`
 * when there is none, so the second half was a constant false for everyone
 * except root — moderators silently lost the three fields on entries they did
 * not create. 10.4 and 10.5 are affected, 10.3 is not.
 *
 * The negative side (a regular member gets no moderator grant) is covered at
 * voter level in UserVoterTest; it cannot be reached through the edit form,
 * because private editing already denies such a member the edit route.
 */
#[WithStory(AccountStory::class)]
final class RubricFormPermissionFieldsTest extends AbstractApplicationTestCase
{
    /**
     * Set explicitly because Account::$plainPassword is not a column — it is
     * gone once the entity has been through persist/refresh, so the login
     * helper needs a literal.
     */
    private const MEMBER_PASSWORD = 'rubrik-test-pw';

    private Account $moderator;
    private string $moderatorPassword;
    private int $roomId;

    public function setUp(): void
    {
        parent::setUp();

        // The account that creates the room becomes its moderator.
        $this->moderator = AccountStory::get('account');
        // Read once, while it is still there — see MEMBER_PASSWORD.
        $this->moderatorPassword = (string) $this->moderator->getPlainPassword();
        $this->loginAsModerator();
        $this->roomId = $this->createRoom($this->portalId(), 'Rubrikrechte');
    }

    /**
     * The regression: a room moderator opening an entry somebody else created
     * must get the three fields.
     */
    public function testRoomModeratorSeesThePermissionFieldsOnAnEntryTheyDidNotCreate(): void
    {
        $author = $this->createRoomMember();

        $this->logout();
        $this->loginAsUser(
            $this->portalId(),
            $author->getUsername(),
            self::MEMBER_PASSWORD
        );
        $itemId = $this->createMaterial();

        $this->logout();
        $this->loginAsModerator();

        $this->client->request('GET', "/room/{$this->roomId}/material/{$itemId}/edit");
        $this->assertResponseIsSuccessful();

        $this->assertSelectorExists('input[name="material[permission]"]');
        $this->assertSelectorExists('input[name="material[hidden]"]');
    }

    /**
     * Control: the creator half of the condition. This one never broke — it
     * keeps the test honest about what the moderator case adds.
     */
    public function testCreatorSeesThePermissionFieldsOnTheirOwnEntry(): void
    {
        $author = $this->createRoomMember();

        $this->logout();
        $this->loginAsUser(
            $this->portalId(),
            $author->getUsername(),
            self::MEMBER_PASSWORD
        );

        $itemId = $this->createMaterial();

        $this->client->request('GET', "/room/{$this->roomId}/material/{$itemId}/edit");
        $this->assertResponseIsSuccessful();

        $this->assertSelectorExists('input[name="material[permission]"]');
        $this->assertSelectorExists('input[name="material[hidden]"]');
    }

    /**
     * Answers a question the gate raises: creating an entry goes through a
     * draft item, so ITEM_OWN already holds on the create form and the fields
     * are there even for a plain member. Creating was never affected by the
     * dead moderator branch.
     */
    public function testCreatingAnEntryShowsTheFieldsBecauseTheDraftBelongsToTheCreator(): void
    {
        $author = $this->createRoomMember();

        $this->logout();
        $this->loginAsUser(
            $this->portalId(),
            $author->getUsername(),
            self::MEMBER_PASSWORD
        );

        $this->client->request('GET', "/room/{$this->roomId}/material/create");
        $this->assertResponseRedirects();
        $this->client->followRedirect();
        $this->assertResponseIsSuccessful();

        $itemId = (int) $this->client->getRequest()->attributes->get('itemId');
        $this->client->request('GET', "/room/{$this->roomId}/material/{$itemId}/edit");

        $this->assertSelectorExists('input[name="material[permission]"]');
    }

    private function portalId(): int
    {
        return $this->moderator->getPortal()?->getId() ?? 0;
    }

    private function loginAsModerator(): void
    {
        $this->loginAsUser(
            $this->portalId(),
            $this->moderator->getUsername(),
            $this->moderatorPassword
        );
    }

    /**
     * The consequence of the gate, and the reason it matters beyond a missing
     * checkbox: the save path reads $data['permission'] WITHOUT checking that
     * the field was part of the form (six of the seven rubric transformers do;
     * TodoTransformer is the exception). A missing key is falsy, and falsy means
     * "not locked" — so saving would silently unlock the entry.
     *
     * A moderator editing somebody else's LOCKED entry is exactly the
     * combination that was reachable while the moderator branch of the gate was
     * dead (see the class docblock): allowed to edit, but no field. This pins
     * that the lock survives their save.
     */
    public function testModeratorSavingAForeignLockedEntryKeepsTheLock(): void
    {
        $author = $this->createRoomMember();

        $this->logout();
        $this->loginAsUser($this->portalId(), $author->getUsername(), self::MEMBER_PASSWORD);
        $itemId = $this->createMaterial();
        $this->lockMaterial($itemId);
        self::assertTrue($this->materialIsLocked($itemId), 'precondition: the entry is locked');

        $this->logout();
        $this->loginAsModerator();

        $crawler = $this->client->request('GET', "/room/{$this->roomId}/material/{$itemId}/edit");
        $this->assertResponseIsSuccessful();
        $form = $crawler->selectButton('material[save]')->form();
        $form['material[title]'] = 'von der Moderation angefasst';
        $this->client->submit($form);

        // Without this the test would pass on a rejected submit, which is how a
        // guard that never runs still looks green.
        $this->assertResponseRedirects();

        self::assertTrue(
            $this->materialIsLocked($itemId),
            'a moderator saving a foreign entry must not drop its edit lock'
        );
    }

    /**
     * The only combination in which somebody who cannot see the field still
     * gets to save: neither creator nor moderator, and the entry is unlocked so
     * the edit route lets them through.
     *
     * Note what this does NOT establish. The unguarded read in the transformers
     * would treat a missing key as "not locked" — which for an unlocked entry is
     * also the correct answer, so the outcome is identical whether the key
     * survives or not. The test pins that the save works and leaves the lock
     * state alone; it cannot distinguish the two. Distinguishing them needs a
     * locked entry saved without the field, and that combination is not
     * reachable through the gate: a locked entry only admits its creator and
     * moderators, and both of those do get the field.
     */
    public function testMemberWithoutThePermissionFieldCanSaveAnUnlockedEntry(): void
    {
        $author = $this->createRoomMember();
        $bystander = $this->createRoomMember();

        $this->logout();
        $this->loginAsUser($this->portalId(), $author->getUsername(), self::MEMBER_PASSWORD);
        $itemId = $this->createMaterial();
        self::assertFalse($this->materialIsLocked($itemId), 'precondition: the entry is not locked');

        $this->logout();
        $this->loginAsUser($this->portalId(), $bystander->getUsername(), self::MEMBER_PASSWORD);

        $crawler = $this->client->request('GET', "/room/{$this->roomId}/material/{$itemId}/edit");
        $this->assertResponseIsSuccessful();
        self::assertSelectorNotExists(
            'input[name="material[permission]"]',
            'neither creator nor moderator — the field must be absent here'
        );

        $form = $crawler->selectButton('material[save]')->form();
        $form['material[title]'] = 'von einem Mitglied angefasst';
        $this->client->submit($form);

        $this->assertResponseRedirects();
        self::assertFalse(
            $this->materialIsLocked($itemId),
            'the lock state must survive a save by somebody who never saw the field'
        );
    }


    /**
     * Ticks the edit lock through the form, as the creator would.
     */
    private function lockMaterial(int $itemId): void
    {
        $crawler = $this->client->request('GET', "/room/{$this->roomId}/material/{$itemId}/edit");
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('material[save]')->form();
        // A fresh draft has no title yet, and NotBlank would reject the submit
        // with 422 before the transformer ever runs.
        $form['material[title]'] = 'gesperrter Eintrag';
        $form['material[permission]']->tick();
        $this->client->submit($form);
        $this->assertResponseRedirects();
    }

    /**
     * cs_item::setPrivateEditing() writes the `public` column despite its name,
     * and isPrivateEditing() is `public != 1`. Read straight from the table so
     * no legacy cache can answer instead.
     */
    private function materialIsLocked(int $itemId): bool
    {
        $public = static::getContainer()->get(EntityManagerInterface::class)
            ->getConnection()
            ->fetchOne('SELECT public FROM materials WHERE item_id = ?', [$itemId]);

        return 1 !== (int) $public;
    }

    /**
     * Adds a second, regular (status 2) member to the room. Created while the
     * moderator is still logged in so the membership exists before the member
     * ever authenticates.
     */
    private function createRoomMember(): Account
    {
        $portal = $this->moderator->getPortal();

        $account = AccountFactory::createOne([
            'portal' => $portal,
            'authSource' => $portal?->getAuthSources()->first(),
            'activityState' => Account::ACTIVITY_ACTIVE,
            'locked' => false,
            'plainPassword' => self::MEMBER_PASSWORD,
        ]);

        $room = static::getContainer()->get(EntityManagerInterface::class)
            ->getRepository(Room::class)
            ->findOneBy(['itemId' => $this->roomId]);
        self::assertInstanceOf(Room::class, $room, 'the room created in setUp must be loadable');

        RoomUserFactory::createOne([
            'account' => $account,
            'room' => $room,
            'status' => 2,
        ]);

        return $account;
    }

    /**
     * Creates a material through the create route (which drafts the item and
     * redirects to it) and returns its item id.
     */
    private function createMaterial(): int
    {
        $this->client->request('GET', "/room/{$this->roomId}/material/create");
        $this->assertResponseRedirects();
        $this->client->followRedirect();
        $this->assertResponseIsSuccessful();

        $itemId = (int) $this->client->getRequest()->attributes->get('itemId');
        self::assertGreaterThan(0, $itemId, 'the create route must yield a draft item id');

        return $itemId;
    }
}
