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
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Tests\Application\AbstractApplicationTestCase;
use Tests\Story\AccountStory;
use Zenstruck\Foundry\Attribute\WithStory;

/**
 * The detail page of a recurring date deletes either that date alone or the
 * whole series.
 */
#[WithStory(AccountStory::class)]
class DateSeriesDeleteTest extends AbstractApplicationTestCase
{
    private Account $account;
    private int $roomId;

    public function setUp(): void
    {
        parent::setUp();

        $this->account = AccountStory::get('account');
        $this->loginAsUser($this->account->getContextId(), $this->account->getUsername(), $this->account->getPlainPassword());
        $this->roomId = $this->createRoom($this->account->getContextId(), 'Testportal');
    }

    public function testDeletingOneDateOfASeriesKeepsTheOthers(): void
    {
        $originalId = $this->createSeries();
        [, $followUp] = $this->seriesRows($originalId);
        $followUpId = (int) $followUp['item_id'];

        // the delete link of a single date sends the series flag empty
        $this->deleteFromTheDetailPage($followUpId, '');

        foreach ($this->seriesRows($originalId) as $row) {
            self::assertSame((int) $row['item_id'] === $followUpId, null !== $row['deletion_date'], "date {$row['item_id']}");
        }
    }

    public function testDeletingTheSeriesDeletesEveryDate(): void
    {
        $originalId = $this->createSeries();
        [, $followUp] = $this->seriesRows($originalId);

        $this->deleteFromTheDetailPage((int) $followUp['item_id'], 'true');

        self::assertNotContains(null, array_column($this->seriesRows($originalId), 'deletion_date'));
    }

    // ---- helpers

    /**
     * Posts what the delete action of the detail page sends.
     */
    private function deleteFromTheDetailPage(int $itemId, string $series): void
    {
        $this->client->request('POST', "/room/$this->roomId/date/xhr/delete?itemId=$itemId", [
            'action' => 'delete',
            'positiveItemIds' => [$itemId],
            'payload' => ['recurring' => $series],
        ], [], ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest']);
        self::assertResponseIsSuccessful();
    }

    /**
     * Creates a date and turns it into a daily series over the next two days
     * through the date form, as a user would, and lifts the draft.
     */
    private function createSeries(): int
    {
        $this->client->request('GET', "/room/$this->roomId/date/create/now");
        self::assertResponseRedirects();
        $this->client->followRedirect();
        $originalId = (int) $this->client->getRequest()->attributes->get('itemId');

        $startDay = (string) $this->connection()->fetchOne('SELECT start_day FROM dates WHERE item_id = ?', [$originalId]);

        $crawler = $this->client->request('GET', "/room/$this->roomId/date/$originalId/edit");
        self::assertResponseIsSuccessful();
        $form = $crawler->selectButton('date[save]')->form();
        $values = $form->getPhpValues();
        $values['date']['title'] = 'Serie';
        // The sub form is added on submit (AddRecurringFieldListener), so its
        // fields are not in the rendered page and are posted directly.
        $values['date']['recurring_select'] = 'RecurringDailyType';
        $values['date']['recurring_sub'] = [
            'recurrenceDay' => '1',
            'untilDate' => date('d.m.Y', strtotime($startDay.' +2 days')),
        ];
        $this->client->request('POST', $form->getUri(), $values);
        self::assertResponseRedirects();

        // The edit page lifts the draft with a follow-up request (edit.js);
        // for a date that releases the whole series.
        $this->client->request('POST', "/room/$this->roomId/item/$originalId/undraft", [], [], ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest']);
        self::assertResponseIsSuccessful();

        return $originalId;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function seriesRows(int $originalId): array
    {
        return $this->connection()->fetchAllAssociative(
            'SELECT * FROM dates WHERE recurrence_id = ? ORDER BY datetime_start, item_id',
            [$originalId]
        );
    }

    private function connection(): Connection
    {
        return static::getContainer()->get(EntityManagerInterface::class)->getConnection();
    }
}
