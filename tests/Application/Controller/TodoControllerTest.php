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
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Contracts\Translation\TranslatorInterface;
use Tests\Application\AbstractApplicationTestCase;
use Tests\Story\AccountStory;
use Zenstruck\Foundry\Attribute\WithStory;

#[WithStory(AccountStory::class)]
class TodoControllerTest extends AbstractApplicationTestCase
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

    public function testCreate(): void
    {
        $this->client->request('GET', "/room/$this->roomId/todo/create");
        $this->client->followRedirect();
        $this->assertResponseIsSuccessful();
    }

    public function testDetail(): void
    {
        $this->client->request('GET', "/room/$this->roomId/todo/create");
        $this->assertResponseRedirects();

        $this->client->followRedirect();
        $this->assertResponseIsSuccessful();

        $itemId = (int) $this->client->getRequest()->attributes->get('itemId');

        $this->client->request('GET', "/room/$this->roomId/todo/$itemId");
        $this->assertResponseIsSuccessful();

        // Forbidden
        $this->logout();
        $this->client->request('GET', "/room/$this->roomId/todo/$itemId");
        $this->assertResponseRedirects("/login/{$this->account->getContextId()}");
    }

    public function testEdit(): void
    {
        $this->client->request('GET', "/room/$this->roomId/todo/create");
        $this->assertResponseRedirects();

        $this->client->followRedirect();
        $this->assertResponseIsSuccessful();

        $itemId = (int) $this->client->getRequest()->attributes->get('itemId');

        $this->client->request('GET', "/room/$this->roomId/todo/$itemId/edit");
        $this->assertResponseIsSuccessful();
    }

    public function testEditSubmitUpdatesTitle(): void
    {
        [$itemId, $crawler] = $this->createTodoAndOpenEdit();

        $form = $crawler->selectButton('todo[save]')->form();
        $form['todo[title]'] = 'frische-aufgabe';
        $this->client->submit($form);

        $this->assertResponseRedirects();
        $crawler = $this->client->followRedirect();
        $this->assertResponseIsSuccessful();

        // post-save view (app_todo_save) renders the persisted title
        $this->assertStringContainsString('frische-aufgabe', $crawler->html());

        $this->assertGreaterThan(0, $itemId);
    }

    public function testEditSubmitWithBlankTitleShowsError(): void
    {
        [$itemId, $crawler] = $this->createTodoAndOpenEdit();

        $form = $crawler->selectButton('todo[save]')->form();
        $form['todo[title]'] = '';
        $this->client->submit($form);

        // Symfony >=6.2: invalid form submit = 422 Unprocessable Entity.
        // The todo edit template renders the title via form_widget without
        // a sibling form_errors call, so field-level errors aren't in the
        // DOM here — 422 is the canonical signal that the save was blocked.
        $this->assertResponseStatusCodeSame(422);

        // sanity: $itemId was used to ensure no static-analysis warning
        $this->assertGreaterThan(0, $itemId);
    }

    public function testFeed(): void
    {
        $this->client->request('GET', "/room/$this->roomId/todo/feed");
        $this->assertResponseIsSuccessful();
    }

    public function testList(): void
    {
        $this->client->request('GET', "/room/$this->roomId/todo");
        $this->assertResponseIsSuccessful();
    }

    /**
     * A new task has no due date: its form offers none and saving it
     * unchanged keeps it without one.
     */
    public function testANewTaskStaysWithoutDueDate(): void
    {
        [$itemId, $crawler] = $this->createTodoAndOpenEdit();

        $form = $crawler->selectButton('todo[save]')->form();
        self::assertSame('', $form['todo[due_date][date]']->getValue());
        self::assertSame('', $form['todo[due_date][time]']->getValue());
        $form['todo[title]'] = 'ohne-frist';
        $this->client->submit($form);

        $this->assertResponseRedirects();
        self::assertNull($this->dueDateOf($itemId));
    }

    public function testADueDateWithoutTimeIsDueAtMidnight(): void
    {
        [$itemId, $crawler] = $this->createTodoAndOpenEdit();

        $form = $crawler->selectButton('todo[save]')->form();
        $form['todo[title]'] = 'mit-frist';
        $form['todo[due_date][date]'] = $this->formatted(new \DateTimeImmutable('2030-03-04'), $crawler);
        $form['todo[due_date][time]'] = '';
        $this->client->submit($form);

        $this->assertResponseRedirects();
        self::assertSame('2030-03-04 00:00:00', $this->dueDateOf($itemId));
    }

    public function testAnEmptiedDueDateIsRemoved(): void
    {
        [$itemId, $crawler] = $this->createTodoAndOpenEdit();
        $form = $crawler->selectButton('todo[save]')->form();
        $form['todo[title]'] = 'mit-frist';
        $form['todo[due_date][date]'] = $this->formatted(new \DateTimeImmutable('2030-03-04'), $crawler);
        $form['todo[due_date][time]'] = '10:30';
        $this->client->submit($form);
        self::assertSame('2030-03-04 10:30:00', $this->dueDateOf($itemId));

        $crawler = $this->client->request('GET', "/room/$this->roomId/todo/$itemId/edit");
        $form = $crawler->selectButton('todo[save]')->form();
        $form['todo[title]'] = 'frist-weg';
        $form['todo[due_date][date]'] = '';
        $form['todo[due_date][time]'] = '';
        $this->client->submit($form);

        $this->assertResponseRedirects();
        self::assertNull($this->dueDateOf($itemId));
    }

    public function testATaskWithoutDueDateShowsNone(): void
    {
        [$itemId, $crawler] = $this->createTodoAndOpenEdit();
        $form = $crawler->selectButton('todo[save]')->form();
        $form['todo[title]'] = 'ohne-frist';
        $this->client->submit($form);
        self::assertNull($this->dueDateOf($itemId));

        $crawler = $this->client->request('GET', "/room/$this->roomId/todo/$itemId");

        $this->assertResponseIsSuccessful();
        // the page speaks the language of the account, whichever that is
        $translator = self::getContainer()->get(TranslatorInterface::class);
        $labels = array_map(
            static fn (string $locale): string => sprintf('contains(normalize-space(.), "%s")', $translator->trans('due date', [], 'todo', $locale)),
            ['de', 'en'],
        );
        $value = $crawler->filterXPath(sprintf('//td[strong[%s]]/following-sibling::td[1]', implode(' or ', $labels)));
        self::assertGreaterThan(0, $value->count());
        self::assertSame('', trim($value->first()->text('')));
    }

    private function dueDateOf(int $itemId): ?string
    {
        $date = self::getContainer()->get(Connection::class)->fetchOne('SELECT date FROM todos WHERE item_id = ?', [$itemId]);

        return false === $date ? null : $date;
    }

    /** The date picker of the form uses the format of the page language. */
    private function formatted(\DateTimeImmutable $date, Crawler $crawler): string
    {
        $picker = (string) $crawler->filter('#todo_due_date_date')->attr('data-uk-datepicker');

        return $date->format(str_contains($picker, 'MM/DD/YYYY') ? 'm/d/Y' : 'd.m.Y');
    }

    /**
     * Creates a fresh todo via the /create route (which redirects to detail),
     * then explicitly loads its /edit page. Returns [itemId, crawlerOnEditPage].
     *
     * @return array{0: int, 1: \Symfony\Component\DomCrawler\Crawler}
     */
    private function createTodoAndOpenEdit(): array
    {
        $this->client->request('GET', "/room/$this->roomId/todo/create");
        $this->assertResponseRedirects();
        $this->client->followRedirect();
        $itemId = (int) $this->client->getRequest()->attributes->get('itemId');

        $crawler = $this->client->request('GET', "/room/$this->roomId/todo/$itemId/edit");
        $this->assertResponseIsSuccessful();

        return [$itemId, $crawler];
    }
}
