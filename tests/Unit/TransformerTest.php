<?php

namespace Tests\Unit;

use App\Form\DataTransformer\MaterialTransformer;
use Codeception\Stub;
use cs_section_item;
use Tests\Support\UnitTester;


class TransformerTest
{
    /**
     * @var UnitTester
     */
    protected UnitTester $tester;

    public function transformSectionTest()
    {
        /** @var cs_section_item $section */
        $section = Stub::make(cs_section_item::class);

        $msTransformer = $this->tester->grabService(MaterialTransformer::class);
        $dataArray = $msTransformer->transform($section);

        $this->tester->assertNotEmpty($dataArray);
    }
}
