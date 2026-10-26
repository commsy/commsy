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
use Symfony\Component\DomCrawler\Field\ChoiceFormField;
use Symfony\Component\DomCrawler\Form;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Tests\Application\AbstractApplicationTestCase;
use Tests\Story\AccountStory;
use Zenstruck\Foundry\Attribute\WithStory;

/**
 * The mail of a task reaches its assigned persons when they are chosen, and
 * the attendees of a date count as recipients only when they are chosen.
 */
#[WithStory(AccountStory::class)]
final class ItemSendRecipientsTest extends AbstractApplicationTestCase
{
    private const string OTHER = 'extern@example.test';

    private Account $account;
    private int $roomId;

    public function setUp(): void
    {
        parent::setUp();

        $this->account = AccountStory::get('account');
        $this->loginAsUser($this->account->getContextId(), $this->account->getUsername(), $this->account->getPlainPassword());
        $this->roomId = $this->createRoom($this->account->getContextId(), 'Versandraum');
    }

    public function testTheAssignedPersonsAloneAreRecipients(): void
    {
        $todoId = $this->entryTakenPartIn('todo');

        $form = $this->sendForm($todoId);
        $this->choose($form['send[send_to_assigned]'], '1');
        $this->client->submit($form);

        self::assertResponseRedirects();
        self::assertNotSame([], $this->mailsTo((string) $this->account->getEmail()));
    }

    public function testTheAssignedPersonsAreReachedNextToOtherRecipients(): void
    {
        $todoId = $this->entryTakenPartIn('todo');

        $form = $this->sendForm($todoId);
        $this->choose($form['send[send_to_assigned]'], '1');
        $form['send[additional_recipients][0]'] = self::OTHER;
        $this->client->submit($form);

        self::assertResponseRedirects();
        self::assertNotSame([], $this->mailsTo((string) $this->account->getEmail()), 'the assigned person');
        self::assertNotSame([], $this->mailsTo(self::OTHER));
    }

    public function testAttendeesNotChosenAreNoRecipients(): void
    {
        $dateId = $this->entryTakenPartIn('date');

        $form = $this->sendForm($dateId);
        $this->choose($form['send[send_to_attendees]'], '0');
        $this->client->submit($form);

        self::assertResponseIsUnprocessable();
        self::assertSame([], self::getMailerMessages());
    }

    /**
     * An entry the logged-in person takes part in.
     */
    private function entryTakenPartIn(string $rubric): int
    {
        $this->client->request('GET', 'date' === $rubric ? "/room/{$this->roomId}/date/create/now" : "/room/{$this->roomId}/{$rubric}/create");
        self::assertResponseRedirects();
        $this->client->followRedirect();
        $itemId = (int) $this->client->getRequest()->attributes->get('itemId');
        $this->client->request('POST', "/room/{$this->roomId}/item/{$itemId}/undraft", [], [], ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest']);

        $this->client->request('GET', "/room/{$this->roomId}/{$rubric}/{$itemId}/participate");
        self::assertResponseRedirects();

        return $itemId;
    }

    /**
     * The send form of the entry, with no group chosen, so that a group's
     * members are no recipients.
     */
    private function sendForm(int $itemId): Form
    {
        $crawler = $this->client->request('GET', "/room/{$this->roomId}/{$itemId}/send");
        self::assertResponseIsSuccessful();
        $form = $crawler->selectButton('send[save]')->form();
        $form['send[subject]'] = 'Betreff';
        foreach ($form->all() as $field) {
            if ($field instanceof ChoiceFormField && str_starts_with($field->getName(), 'send[send_to_groups]')) {
                $field->untick();
            }
        }

        return $form;
    }

    private function choose(mixed $field, string $value): void
    {
        self::assertInstanceOf(ChoiceFormField::class, $field);
        $field->select($value);
    }

    /**
     * @return list<Email>
     */
    private function mailsTo(string $address): array
    {
        return array_values(array_filter(
            self::getMailerMessages(),
            static fn ($mail): bool => $mail instanceof Email && in_array($address, array_map(static fn (Address $to): string => $to->getAddress(), [...$mail->getTo(), ...$mail->getBcc(), ...$mail->getCc()]), true),
        ));
    }
}
