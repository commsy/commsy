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
use App\Entity\SavedSearch;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Tests\Application\AbstractApplicationTestCase;
use Tests\Story\AccountStory;
use Tests\Support\Search\FakeSearchServer;
use Zenstruck\Foundry\Attribute\WithStory;

/**
 * Pins what the search page makes of the answer of the search server: the
 * stand-in server is told its hits.
 */
#[WithStory(AccountStory::class)]
final class SearchControllerTest extends AbstractApplicationTestCase
{
    /** The portal profile of another person; saved searches name it without a foreign key. */
    private const int SOMEONE_ELSE = 999_999_999;

    private FakeSearchServer $server;
    private int $roomId;

    public function setUp(): void
    {
        parent::setUp();

        /** @var Account $account */
        $account = AccountStory::get('account');
        $portalId = $account->getContextId();
        $this->loginAsUser($portalId, $account->getUsername(), $account->getPlainPassword());
        $this->roomId = $this->createRoom($portalId, 'Suchraum');
        $this->server = self::getContainer()->get(FakeSearchServer::class);
        $this->server->answerWith([]);
    }

    public function testDeletingAViewRemovesIt(): void
    {
        $this->results(['phrase' => 'Fund', 'selectedSavedSearchTitle' => 'Meine Ansicht', 'save' => '']);
        self::assertResponseRedirects();
        $view = $this->savedSearches()->findOneBy(['title' => 'Meine Ansicht']);
        self::assertNotNull($view);
        $viewId = (int) $view->getId();

        $this->results(['selectedSavedSearch' => (string) $viewId, 'delete' => '']);

        self::assertResponseRedirects();
        self::getContainer()->get(EntityManagerInterface::class)->clear();
        self::assertNull($this->savedSearches()->find($viewId));
    }

    public function testTheViewOfSomeoneElseCannotBeDeleted(): void
    {
        $view = (new SavedSearch())
            ->setAccountId(self::SOMEONE_ELSE)
            ->setTitle('Fremd')
            ->setSearchUrl('/room/1/search/results?search_filter%5Bphrase%5D=Fremd');
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $entityManager->persist($view);
        $entityManager->flush();
        $viewId = (int) $view->getId();

        $this->results(['selectedSavedSearch' => (string) $viewId, 'delete' => '']);

        $entityManager->clear();
        self::assertNotNull($this->savedSearches()->find($viewId));
    }

    /**
     * @param array<string, string> $filter
     */
    private function results(array $filter): \Symfony\Component\DomCrawler\Crawler
    {
        return $this->client->request('GET', "/room/{$this->roomId}/search/results", ['search_filter' => $filter]);
    }

    /**
     * @return EntityRepository<SavedSearch>
     */
    private function savedSearches(): EntityRepository
    {
        return self::getContainer()->get(EntityManagerInterface::class)->getRepository(SavedSearch::class);
    }
}
