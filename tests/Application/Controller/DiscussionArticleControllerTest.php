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
use App\Services\LegacyEnvironment;
use App\Utils\DiscussionService;
use Doctrine\ORM\EntityManagerInterface;
use Tests\Application\AbstractApplicationTestCase;
use Tests\Story\AccountStory;
use Zenstruck\Foundry\Attribute\WithStory;

#[WithStory(AccountStory::class)]
class DiscussionArticleControllerTest extends AbstractApplicationTestCase
{
    private Account $account;
    private int $roomId;

    public function setUp(): void
    {
        parent::setUp();

        $this->account = AccountStory::get('account');
        $this->loginAsUser(
            $this->account->getContextId(),
            $this->account->getUsername(),
            $this->account->getPlainPassword()
        );
        $this->roomId = $this->createRoom($this->account->getContextId(), 'Testraum');
    }

    public function testXhrDeleteAcceptsXmlHttpRequest(): void
    {
        // With selectAll=true and no matching items the delete action iterates
        // over an empty set and returns a JSON response — this exercises the
        // full guarded controller path for a room member.
        $this->client->request(
            'POST',
            "/room/{$this->roomId}/discussion_article/xhr/delete",
            [
                'action' => 'delete',
                'selectAll' => 'true',
            ],
            [],
            ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest']
        );

        $this->assertResponseIsSuccessful();
    }

    public function testXhrDeleteRemovesAnAnswer(): void
    {
        $discussionId = $this->createPublishedDiscussion();
        $crawler = $this->client->request('GET', "/room/{$this->roomId}/discussion/{$discussionId}/answerform");
        $form = $crawler->selectButton('discussion_answer[save]')->form();
        $form['discussion_answer[description]'] = 'Zu löschende Antwort';
        $this->client->submit($form);
        $connection = self::getContainer()->get(EntityManagerInterface::class)->getConnection();
        $articleId = (int) $connection->fetchOne('SELECT item_id FROM discussionarticles WHERE discussion_id = ?', [$discussionId]);
        $this->assertGreaterThan(0, $articleId);

        $this->client->request(
            'POST',
            "/room/{$this->roomId}/discussion_article/xhr/delete",
            ['action' => 'delete', 'positiveItemIds' => [$articleId]],
            [],
            ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest']
        );

        $this->assertResponseIsSuccessful();
        $this->assertNotNull($connection->fetchOne('SELECT deletion_date FROM discussionarticles WHERE item_id = ?', [$articleId]));
    }

    public function testDeleteRejectsNonXhrRequest(): void
    {
        $this->client->request(
            'POST',
            "/room/{$this->roomId}/discussion_article/xhr/delete",
            [
                'action' => 'delete',
                'selectAll' => 'true',
            ]
        );

        // The XHR `condition` on the route means a plain POST cannot match it.
        $this->assertResponseStatusCodeSame(404);
    }

    public function testXhrDeleteRedirectsAnonymousToLogin(): void
    {
        $this->logout();

        $this->client->request(
            'POST',
            "/room/{$this->roomId}/discussion_article/xhr/delete",
            [
                'action' => 'delete',
                'selectAll' => 'true',
            ],
            [],
            ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest']
        );

        // The class-level ITEM_ENTER gate turns anonymous callers away at the
        // door instead of letting the request reach the delete action.
        $this->assertResponseRedirects("/login/{$this->account->getContextId()}");
    }

    private function createPublishedDiscussion(): int
    {
        $env = self::getContainer()->get(LegacyEnvironment::class)->getEnvironment();
        $env->setCurrentContextID($this->roomId);

        $discussion = self::getContainer()->get(DiscussionService::class)->getNewDiscussion();
        $discussion->setTitle('Diskussion');
        $discussion->setDescription('');
        $discussion->setDraftStatus(0);
        $discussion->setPrivateEditing('0');
        $discussion->save();

        return (int) $discussion->getItemID();
    }
}
