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
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Application\AbstractApplicationTestCase;
use Tests\Factory\AccountFactory;
use Tests\Factory\DatesFactory;
use Tests\Factory\DiscussionFactory;
use Tests\Factory\RoomFactory;
use Tests\Factory\RoomUserFactory;
use Tests\Story\AccountStory;
use Zenstruck\Foundry\Attribute\WithStory;

/**
 * A member who may not open an entry that is not activated yet sees it in the
 * list as "deactivated entry", followed by its title when the portal is set to
 * show those titles (its default).
 */
#[WithStory(AccountStory::class)]
class DeactivatedEntryTitleInFeedTest extends AbstractApplicationTestCase
{
    private Account $member;
    private Room $room;
    private User $author;

    public function setUp(): void
    {
        parent::setUp();

        $this->member = AccountStory::get('account');
        $portal = $this->member->getPortal();
        $this->room = RoomFactory::new()->project()->create(['contextId' => $portal->getId(), 'portal' => $portal]);

        // a plain member: neither moderator nor author, so the entry stays closed to them
        RoomUserFactory::new()->asUser()->create(['account' => $this->member, 'room' => $this->room]);

        $authorAccount = AccountFactory::createOne(['activityState' => Account::ACTIVITY_ACTIVE, 'locked' => false]);
        $this->author = RoomUserFactory::new()->asUser()->create(['account' => $authorAccount, 'room' => $this->room]);
    }

    /**
     * @return iterable<string, array{0: string}>
     */
    public static function rubricProvider(): iterable
    {
        yield 'discussion' => ['discussion'];
        yield 'date' => ['date'];
    }

    #[DataProvider('rubricProvider')]
    public function testTheFeedShowsTheTitleOfADeactivatedEntry(string $rubric): void
    {
        $this->createDeactivatedEntry($rubric, 'Noch nicht freigeschaltet');
        $this->loginAsUser($this->member->getPortal()->getId(), $this->member->getUsername(), $this->member->getPlainPassword());

        $roomId = $this->room->getItemId();
        $crawler = $this->client->request('GET', "/room/$roomId/$rubric/feed", [
            "{$rubric}_filter" => ['hide-deactivated-entries' => 'all'],
        ]);

        $this->assertResponseIsSuccessful();
        $heading = $crawler->filter('h4.uk-comment-title');
        $this->assertCount(0, $heading->filter('a'), 'the member may not open the entry, so it is listed without a link');
        $this->assertStringContainsString('Noch nicht freigeschaltet', $heading->text());
    }

    private function createDeactivatedEntry(string $rubric, string $title): void
    {
        $owner = ['room' => $this->room, 'creator' => $this->author, 'title' => $title];
        [$itemId, $table] = match ($rubric) {
            'discussion' => [DiscussionFactory::createOne($owner)->getItemId(), 'discussions'],
            'date' => [DatesFactory::createOne($owner)->getItemId(), 'dates'],
        };

        static::getContainer()->get(EntityManagerInterface::class)->getConnection()
            ->update($table, ['activation_date' => '2999-01-01 00:00:00'], ['item_id' => $itemId]);
    }
}
