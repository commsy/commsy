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

namespace Tests\Integration\Editor;

use FOS\CKEditorBundle\Config\CKEditorConfigurationInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Comments and discussion articles get a reduced editor: basic styles and
 * links, but no tables, media or other content blocks.
 */
final class ReducedEditorToolbarTest extends KernelTestCase
{
    public function testTheReducedEditorOffersLinks(): void
    {
        $buttons = $this->buttonsOf('cs_annotation_config');

        self::assertContains('Link', $buttons);
        self::assertContains('Unlink', $buttons);
    }

    public function testTheReducedEditorOffersNoContentBlocks(): void
    {
        $buttons = $this->buttonsOf('cs_annotation_config');

        self::assertNotContains('Table', $buttons);
        self::assertNotContains('HorizontalRule', $buttons);
        self::assertNotContains('Commsyimage', $buttons);
    }

    /**
     * @return list<string>
     */
    private function buttonsOf(string $configName): array
    {
        self::bootKernel();
        $configuration = self::getContainer()->get('fos_ck_editor.configuration');
        self::assertInstanceOf(CKEditorConfigurationInterface::class, $configuration);

        $toolbar = $configuration->getToolbar($configuration->getConfig($configName)['toolbar']);

        return array_merge(...array_map(static fn (array|string $group): array => (array) $group, $toolbar));
    }
}
