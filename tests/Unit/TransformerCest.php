<?php

namespace Tests\Unit;

use App\Form\DataTransformer\MaterialTransformer;
use Tests\Support\UnitTester;
use Codeception\Stub;
use cs_section_item;


class TransformerCest
{
    public function transformSectionTest(UnitTester $I)
    {
        require_once 'classes/cs_section_item.php';

        /** @var cs_section_item $section */
        $section = Stub::make(cs_section_item::class);

        $msTransformer = $I->grabService(MaterialTransformer::class);
        $dataArray = $msTransformer->transform($section);

        $I->assertNotEmpty($dataArray);
    }
}
