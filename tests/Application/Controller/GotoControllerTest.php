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
use App\Entity\User;
use Tests\Application\AbstractApplicationTestCase;
use Tests\Factory\AnnouncementFactory;
use Tests\Factory\MaterialFactory;
use Tests\Factory\SectionFactory;
use Tests\Story\RoomWithMemberStory;
use Zenstruck\Foundry\Attribute\WithStory;

#[WithStory(RoomWithMemberStory::class)]
class GotoControllerTest extends AbstractApplicationTestCase
{
    private Account $account;
    private int $portalId;
    private string $username;
    private string $password;

    public function setUp(): void
    {
        parent::setUp();

        $this->account = RoomWithMemberStory::get('account');
        // plainPassword is transient and does not survive a later factory
        // call clearing the unit of work, so capture the credentials now.
        $this->portalId = $this->account->getPortal()->getId();
        $this->username = $this->account->getUsername();
        $this->password = $this->account->getPlainPassword();
    }

    private function login(): void
    {
        $this->loginAsUser($this->portalId, $this->username, $this->password);
    }

    public function testGotoRedirectsToRoomHomeForProjectItem(): void
    {
        $this->login();

        /** @var Room $room */
        $room = RoomWithMemberStory::get('room');

        $this->client->request('GET', "/goto/{$room->getItemId()}");

        $this->assertResponseRedirects("/room/{$room->getItemId()}");
    }

    public function testGotoRedirectsToDetailForRubricItem(): void
    {
        $this->login();

        /** @var Room $room */
        $room = RoomWithMemberStory::get('room');
        /** @var User $creator */
        $creator = RoomWithMemberStory::get('roomUser');

        $announcement = AnnouncementFactory::createOne([
            'room' => $room,
            'creator' => $creator,
        ]);

        $this->client->request('GET', "/goto/{$announcement->getItemId()}");

        $this->assertResponseRedirects(
            "/room/{$room->getItemId()}/announcement/{$announcement->getItemId()}"
        );
    }

    public function testGotoReturns404ForUnknownItem(): void
    {
        $this->login();

        $this->client->request('GET', '/goto/'.PHP_INT_MAX);

        $this->assertResponseStatusCodeSame(404);
    }

    /**
     * Sub-entries such as sections have no detail route of their own, so the
     * caller is sent to the containing room rather than to a route name that
     * does not exist.
     */
    public function testGotoFallsBackToRoomForItemWithoutDetailRoute(): void
    {
        $this->login();

        /** @var Room $room */
        $room = RoomWithMemberStory::get('room');
        /** @var User $creator */
        $creator = RoomWithMemberStory::get('roomUser');

        $section = SectionFactory::createOne([
            'room' => $room,
            'creator' => $creator,
            'material' => MaterialFactory::createOne(['room' => $room, 'creator' => $creator]),
        ]);

        $this->client->request('GET', "/goto/{$section->getItemId()}");

        $this->assertResponseRedirects("/room/{$room->getItemId()}");
    }

    /**
     * The redirect target names the item's type and its room, so resolving
     * happens only once the caller is authenticated.
     */
    public function testGotoDoesNotRevealItemTypeOrRoomToAnonymousCallers(): void
    {
        /** @var Room $room */
        $room = RoomWithMemberStory::get('room');
        /** @var User $creator */
        $creator = RoomWithMemberStory::get('roomUser');

        $announcement = AnnouncementFactory::createOne([
            'room' => $room,
            'creator' => $creator,
        ]);

        $this->client->request('GET', "/goto/{$announcement->getItemId()}");

        $location = (string) $this->client->getResponse()->headers->get('Location');
        self::assertStringNotContainsString('announcement', $location);
        self::assertStringNotContainsString((string) $room->getItemId(), $location);
    }

    /**
     * Before login a known and an unknown id must produce the same answer,
     * so that nothing about the id is conveyed either way.
     */
    public function testGotoAnswersAnonymousCallersIdenticallyForKnownAndUnknownIds(): void
    {
        /** @var Room $room */
        $room = RoomWithMemberStory::get('room');

        $this->client->request('GET', "/goto/{$room->getItemId()}");
        $known = $this->client->getResponse();
        $knownStatus = $known->getStatusCode();
        $knownLocation = (string) $known->headers->get('Location');

        $this->client->request('GET', '/goto/'.PHP_INT_MAX);
        $unknown = $this->client->getResponse();

        self::assertSame($knownStatus, $unknown->getStatusCode());
        self::assertSame($knownLocation, (string) $unknown->headers->get('Location'));
    }

    /**
     * The deep link has to survive the login: the URL remembered while
     * unauthenticated is now /goto/{id} itself, so signing in returns the
     * caller here and the forward to the item happens then.
     */
    public function testDeepLinkSurvivesLogin(): void
    {
        /** @var Room $room */
        $room = RoomWithMemberStory::get('room');
        /** @var User $creator */
        $creator = RoomWithMemberStory::get('roomUser');

        $announcement = AnnouncementFactory::createOne([
            'room' => $room,
            'creator' => $creator,
        ]);
        $deepLink = "/goto/{$announcement->getItemId()}";

        // Anonymous hit stores the target path and bounces to the login form.
        $this->client->request('GET', $deepLink);
        $this->assertResponseRedirects();

        // Sign in directly rather than via loginAsUser(), which asserts the
        // portal landing page — the whole point here is that the stored
        // target path wins over it.
        $this->client->request('GET', "/login/{$this->portalId}");
        $this->assertResponseIsSuccessful();
        $this->client->submitForm('login_local', [
            'email' => $this->username,
            'password' => $this->password,
        ]);

        self::assertStringEndsWith(
            $deepLink,
            (string) $this->client->getResponse()->headers->get('Location'),
            'login should return the caller to the deep link, not the portal landing page'
        );

        // Following it now resolves the item.
        $this->client->followRedirect();
        $this->assertResponseRedirects(
            "/room/{$room->getItemId()}/announcement/{$announcement->getItemId()}"
        );
    }
}
