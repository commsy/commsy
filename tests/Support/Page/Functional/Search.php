<?php

namespace Tests\Support\Page\Functional;

use Tests\Support\FunctionalTester;

class Search
{
    public string $searchQueryField = '#search_phrase';
    public string $searchSubmitButton = '#search_submit';

    /**
     * @var FunctionalTester ;
     */
    protected FunctionalTester $functionalTester;

    public function __construct(FunctionalTester $I)
    {
        $this->functionalTester = $I;
    }

    public function performSearch(int $roomId, string $query) {
        $I = $this->functionalTester;

        $I->amOnRoute('app_search_results', [
            'roomId' => $roomId,
        ]);

        $I->fillField($this->searchQueryField, $query);
        $I->click($this->searchSubmitButton);
    }
}