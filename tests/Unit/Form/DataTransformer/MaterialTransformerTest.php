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

namespace Tests\Unit\Form\DataTransformer;

use App\Form\DataTransformer\MaterialTransformer;
use App\Services\LegacyEnvironment;
use cs_section_item;
use PHPUnit\Framework\TestCase;


class MaterialTransformerTest extends TestCase
{
    public function testTransformSection(): void
    {
        /** @var cs_section_item $section */
        $section = $this->createStub(cs_section_item::class);
        $environment = $this->createStub(LegacyEnvironment::class);

        $transformer = new MaterialTransformer($environment);
        $dataArray = $transformer->transform($section);

        $this->assertNotEmpty($dataArray);
    }
}
