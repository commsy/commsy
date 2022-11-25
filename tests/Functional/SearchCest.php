<?php

namespace Tests\Functional;

use App\Services\LegacyEnvironment;
use Tests\Support\Page\Functional\Search;
use Tests\Support\Step\Functional\User;

class SearchCest
{
    private int $roomId;

    public function _before(User $I)
    {
        $symfony = $I->grabSymfony();
        $symfony->runSymfonyConsoleCommand('fos:elastica:reset', ['--no-interaction' => true]);

        $room = $I->haveProjectRoom('My room');
        $this->roomId = $room->getItemID();

        /** @var LegacyEnvironment $legacyEnvironment */
        $legacyEnvironment = $I->grabService(LegacyEnvironment::class);
        $materialManager = $legacyEnvironment->getEnvironment()->getMaterialManager();

        $titles = ['Aufgabe A', 'Aufgabe B', 'Aufgabe C', 'Aufgabe D'];

        foreach ($titles as $title) {
            $material = $materialManager->getNewItem();
            $material->setTitle($title);
            $material->save();
        }
    }

    public function findTitleExact(User $I, Search $search)
    {
        $search->performSearch($this->roomId, 'Aufgabe A');
        $I->see('Aufgabe A', 'ul#search-feed article h4 a');
    }

    public function findTitlePrefix(User $I, Search $search)
    {
        $search->performSearch($this->roomId, 'Auf');
        $I->see('Aufgabe A', 'ul#search-feed article h4 a');
    }
}
