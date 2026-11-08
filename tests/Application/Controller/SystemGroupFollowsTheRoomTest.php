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
use App\Repository\LabelRepository;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Application\AbstractApplicationTestCase;
use Tests\Story\AccountStory;
use Zenstruck\Foundry\Attribute\WithStory;

/**
 * Every project room has a group of all members that follows its
 * membership; no one joins or leaves it by hand.
 */
#[WithStory(AccountStory::class)]
final class SystemGroupFollowsTheRoomTest extends AbstractApplicationTestCase
{
    /** @return iterable<string, array{string}> */
    public static function actions(): iterable
    {
        yield 'leave' => ['leave'];
        yield 'join' => ['join'];
    }

    #[DataProvider('actions')]
    public function testNoOneJoinsOrLeavesTheGroupOfAllMembersByHand(string $action): void
    {
        /** @var Account $account */
        $account = AccountStory::get('account');
        $portalId = (int) $account->getPortal()?->getId();
        $this->loginAsUser($portalId, $account->getUsername(), $account->getPlainPassword());
        $roomId = $this->createRoom($portalId, 'Raum mit allen Mitgliedern');
        $group = self::getContainer()->get(LabelRepository::class)->findLabelsByContextIdAndNameAndType($roomId, 'ALL', 'group')[0] ?? null;
        self::assertNotNull($group);
        $members = $this->membersOf($group->getItemId());
        self::assertGreaterThan(0, $members);

        $this->client->request('GET', "/room/$roomId/group/{$group->getItemId()}/$action");

        self::assertLessThan(500, $this->client->getResponse()->getStatusCode());
        self::assertSame($members, $this->membersOf($group->getItemId()));
    }

    private function membersOf(int $groupId): int
    {
        return (int) self::getContainer()->get(Connection::class)->fetchOne(
            "SELECT COUNT(*) FROM link_items WHERE deletion_date IS NULL
                AND ((first_item_id = :group AND second_item_type = 'user') OR (second_item_id = :group AND first_item_type = 'user'))",
            ['group' => $groupId],
        );
    }
}
