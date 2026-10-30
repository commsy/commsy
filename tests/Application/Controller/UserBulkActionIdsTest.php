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
use Tests\Application\AbstractApplicationTestCase;
use Tests\Factory\AccountFactory;
use Tests\Factory\RoomFactory;
use Tests\Factory\RoomUserFactory;
use Tests\Story\RoomWithMemberStory;
use Zenstruck\Foundry\Attribute\WithStory;

/**
 * A bulk action of the person list acts on the posted profiles only: an id
 * that is no number selects nobody, and a selection without a valid id
 * selects nobody either.
 */
#[WithStory(RoomWithMemberStory::class)]
final class UserBulkActionIdsTest extends AbstractApplicationTestCase
{
    private Room $room;
    private User $roomUser;

    public function setUp(): void
    {
        parent::setUp();

        /** @var Account $account */
        $account = RoomWithMemberStory::get('account');
        $this->room = RoomWithMemberStory::get('room');
        $this->roomUser = RoomWithMemberStory::get('roomUser');

        // a profile elsewhere that a widened selection would reach
        $elsewhere = RoomFactory::new()->project()->create(['contextId' => $account->getPortal()?->getId(), 'portal' => $account->getPortal()]);
        RoomUserFactory::createOne([
            'account' => AccountFactory::createOne(['portal' => $account->getPortal(), 'authSource' => $account->getAuthSource()]),
            'room' => $elsewhere,
        ]);

        $this->loginAsUser((int) $account->getPortal()?->getId(), $account->getUsername(), $account->getPlainPassword());
    }

    public function testAnIdThatIsNoNumberSelectsNobody(): void
    {
        self::assertSame([(string) $this->roomUser->getItemId()], $this->recipientsOf([(string) $this->roomUser->getItemId(), '0) OR (1=1']));
    }

    public function testASelectionWithoutAValidIdSelectsNobody(): void
    {
        self::assertSame([], $this->recipientsOf(['0) OR (1=1']));
    }

    /**
     * @param list<string> $positiveItemIds
     *
     * @return list<string> the profiles the mail form addresses
     */
    private function recipientsOf(array $positiveItemIds): array
    {
        $crawler = $this->client->request('POST', "/room/{$this->room->getItemId()}/user/sendMultiple", [
            'action' => 'sendmail',
            'positiveItemIds' => $positiveItemIds,
        ]);
        $this->assertResponseIsSuccessful();

        return $crawler->filter('input[name^="user_send[users]"]')->each(static fn ($input): string => (string) $input->attr('value'));
    }
}
