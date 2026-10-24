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

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Application\AbstractApplicationTestCase;
use Tests\Story\AccountStory;
use Zenstruck\Foundry\Attribute\WithStory;

/**
 * Every rubric deletes an entry through the list delete action, which the
 * detail pages share; the system group of all members is kept.
 */
#[WithStory(AccountStory::class)]
class RubricListDeletionTest extends AbstractApplicationTestCase
{
    private int $roomId;

    public function setUp(): void
    {
        parent::setUp();

        $account = AccountStory::get('account');
        $portalId = (int) $account->getPortal()->getId();
        $this->loginAsUser($portalId, $account->getUsername(), $account->getPlainPassword());
        $this->roomId = $this->createRoom($portalId, 'Projekt');
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function rubrics(): iterable
    {
        yield 'announcement' => ['announcement', 'announcement'];
        yield 'date' => ['date', 'dates'];
        yield 'discussion' => ['discussion', 'discussions'];
        yield 'group' => ['group', 'labels'];
        yield 'material' => ['material', 'materials'];
        yield 'todo' => ['todo', 'todos'];
        yield 'topic' => ['topic', 'labels'];
    }

    #[DataProvider('rubrics')]
    public function testAnEntryIsDeleted(string $rubric, string $table): void
    {
        // a new date starts from a moment, as the create button of the list passes it
        $this->client->request('GET', "/room/$this->roomId/$rubric/create" . ('date' === $rubric ? '/now' : ''));
        $this->client->followRedirect();
        $itemId = (int) $this->client->getRequest()->attributes->get('itemId');
        $this->assertGreaterThan(0, $itemId);
        $this->connection()->update('items', ['draft' => 0], ['item_id' => $itemId]);

        $this->delete($rubric, $itemId);

        $this->assertResponseIsSuccessful();
        $this->assertNotNull($this->connection()->fetchOne("SELECT deletion_date FROM $table WHERE item_id = ?", [$itemId]));
    }

    public function testTheSystemGroupIsKept(): void
    {
        $systemGroupId = (int) $this->connection()->fetchOne(
            "SELECT item_id FROM labels WHERE context_id = ? AND type = 'group' AND name = 'ALL' AND deletion_date IS NULL",
            [$this->roomId],
        );
        $this->assertGreaterThan(0, $systemGroupId);

        $this->delete('group', $systemGroupId);

        $this->assertResponseIsSuccessful();
        $this->assertNull($this->connection()->fetchOne('SELECT deletion_date FROM labels WHERE item_id = ?', [$systemGroupId]));
    }

    private function delete(string $rubric, int $itemId): void
    {
        $this->client->request(
            'POST',
            "/room/$this->roomId/$rubric/xhr/delete",
            ['action' => 'delete', 'positiveItemIds' => [$itemId]],
            [],
            ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest'],
        );
    }

    private function connection(): Connection
    {
        return static::getContainer()->get(EntityManagerInterface::class)->getConnection();
    }
}
