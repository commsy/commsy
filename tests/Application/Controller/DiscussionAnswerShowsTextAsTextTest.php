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
use Doctrine\DBAL\Connection;
use Tests\Application\AbstractApplicationTestCase;
use Tests\Factory\DiscussionArticleFactory;
use Tests\Factory\DiscussionFactory;
use Tests\Story\RoomWithMemberStory;
use Zenstruck\Foundry\Attribute\WithStory;

/**
 * An answer to a discussion is shown as the HTML it was cleaned to: text
 * that only reads like markup stays text.
 */
#[WithStory(RoomWithMemberStory::class)]
final class DiscussionAnswerShowsTextAsTextTest extends AbstractApplicationTestCase
{
    public function testMarkupWrittenAsTextIsShownAsText(): void
    {
        $html = $this->answerShownAs('<p>&lt;script&gt;document.title=3&lt;/script&gt;</p>');

        self::assertStringNotContainsString('<script>document.title=3', $html);
        self::assertStringContainsString('&lt;script&gt;document.title=3', $html);
    }

    public function testMarkupOfTheAnswerIsShown(): void
    {
        $html = $this->answerShownAs('<p>Gute <strong>Frage</strong></p>');

        self::assertStringContainsString('Gute <strong>Frage</strong>', $html);
    }

    private function answerShownAs(string $description): string
    {
        /** @var Account $account */
        $account = RoomWithMemberStory::get('account');
        /** @var Room $room */
        $room = RoomWithMemberStory::get('room');
        /** @var User $member */
        $member = RoomWithMemberStory::get('roomUser');
        $owner = ['room' => $room, 'creator' => $member];
        $discussion = DiscussionFactory::createOne($owner + ['title' => 'Kodierfrage']);
        $answer = DiscussionArticleFactory::createOne($owner + ['discussion' => $discussion]);
        self::getContainer()->get(Connection::class)
            ->update('discussionarticles', ['description' => $description], ['item_id' => $answer->getItemId()]);
        $this->loginAsUser((int) $account->getPortal()?->getId(), $account->getUsername(), $account->getPlainPassword());

        $this->client->request('GET', "/room/{$room->getItemId()}/discussion/{$discussion->getItemId()}");

        self::assertResponseIsSuccessful();

        return (string) $this->client->getResponse()->getContent();
    }
}
