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

        $titles = [
            'Aufgabe A',
            'Aufgabe B',
            'Aufgabe C',
            'Schifffahrtsmuseum',
            'Hörverstehen',
            'Willkommen auf dem Gymnasium!',
            'Hausaufgaben: bitte bis heute Abend erledigen',
            'Arbeiterunfallversicherungsgesetz',
        ];

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

    public function findLongTitlePrefix(User $I, Search $search)
    {
        $search->performSearch($this->roomId, 'Schifffahrt');
        $I->see('Schifffahrtsmuseum', 'ul#search-feed article h4 a');
    }

    public function findTitleWithUmlautExact(User $I, Search $search)
    {
        $search->performSearch($this->roomId, 'Hörverstehen');
        $I->see('Hörverstehen', 'ul#search-feed article h4 a');
    }

    public function findTitleWithUmlautPrefix(User $I, Search $search)
    {
        $search->performSearch($this->roomId, 'Hör');
        $I->see('Hörverstehen', 'ul#search-feed article h4 a');
    }

    public function findTitleWithUmlautPrefixTransliterated(User $I, Search $search)
    {
        $search->performSearch($this->roomId, 'Hoer');
        $I->see('Hörverstehen', 'ul#search-feed article h4 a');
    }

    public function findWordInMultiWordTitle(User $I, Search $search)
    {
        $search->performSearch($this->roomId, 'Abend');
        $I->see('Hausaufgaben: bitte bis heute Abend erledigen', 'ul#search-feed article h4 a');
    }

    public function findWordInMultiWordTitleWithPunctuation(User $I, Search $search)
    {
        $search->performSearch($this->roomId, 'Gymnasium');
        $I->see('Willkommen auf dem Gymnasium!', 'ul#search-feed article h4 a');
    }

    // TODO: allow for prefix matches with very long prefixes (>20 chars)
//    public function findVeryLongTitlePrefix(User $I, Search $search)
//    {
//        $search->performSearch($this->roomId, 'Arbeiterunfallversicherung');
//        $I->see('Arbeiterunfallversicherungsgesetz', 'ul#search-feed article h4 a');
//    }
}
