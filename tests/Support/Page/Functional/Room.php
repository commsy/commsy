<?php
namespace Tests\Support\Page\Functional;

use Tests\Support\FunctionalTester;

class Room
{
    public string $titleField = '#context_title';
    public string $typeField = '#context_type_select input[type=radio]';

    public string $submitButton = '#context_save';

    /**
     * @var FunctionalTester ;
     */
    protected FunctionalTester $functionalTester;

    public function __construct(FunctionalTester $I)
    {
        $this->functionalTester = $I;
    }

    public function create(
        int $portalId,
        string $title,
        /* project / community */ string $type = 'project'
    ): void
    {
        $I = $this->functionalTester;

        $I->amOnRoute('app_room_create', [
            'roomId' => $portalId,
        ]);

        $I->fillField($this->titleField, $title);
        $I->fillField($this->typeField, $type);
        $I->click($this->submitButton);
    }
}