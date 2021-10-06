<?php

namespace App\Tests\Unit;

use App\Form\DataTransformer\MaterialTransformer;
use App\Tests\UnitTester;


class TransformerCest
{
    public function _before(UnitTester $I)
    {
    }

    public function _after(UnitTester $I)
    {
    }
    
    public function transformSectionTest(UnitTester $I)
    {
        $legacyEnvironment = $I->grabService('commsy_legacy.environment')->getEnvironment();

        $sectionManager = $legacyEnvironment->getSectionManager();
        $section = $sectionManager->getNewItem();

        $I->assertInstanceOf(\cs_section_item::class, $section);

        $msTransformer = $I->grabService(MaterialTransformer::class);
        $dataArray = $msTransformer->transform($section);

        $I->assertNotEmpty($dataArray);
    }
}
