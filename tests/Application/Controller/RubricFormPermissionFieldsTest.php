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
