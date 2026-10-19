<?php

/*
 * This file is part of CommSy.
 *
 * (c) Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
 * Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
 * Edouard Simon, Monique Strauss, Jose Mauel Gonzalez Vazquez, Johannes Schultze
 */

declare(strict_types=1);

namespace Tests\Unit\Entity;

use App\Entity\Room;
use PHPUnit\Framework\TestCase;

/**
 * A room's usage hints are content its moderation wrote. What the export hands
 * on of them has to be what the room shows: the start page and the rubrics it
 * offers, each where there is a text.
 */
class RoomUsageHintsTest extends TestCase
{
    public function testTheStartPageAndTheOfferedRubricsComeInTheOrderOfTheSettings(): void
    {
        $hints = $this->hints([
            'HOMECONF' => 'material_show,announcement_hide',
            'USAGE_INFO_HEADER' => ['HOME' => 'Willkommen', 'ANNOUNCEMENT' => 'Neues', 'MATERIAL' => 'Lesestoff'],
            'USAGE_INFO_TEXT' => [
                'ANNOUNCEMENT' => '<p>Bitte lesen.</p>',
                'HOME' => '<p>Hallo.</p>',
                'MATERIAL' => '<p>Alles zur Sitzung.</p>',
            ],
        ]);

        $this->assertSame([
            ['rubric' => 'home', 'title' => 'Willkommen', 'text' => '<p>Hallo.</p>'],
            ['rubric' => 'material', 'title' => 'Lesestoff', 'text' => '<p>Alles zur Sitzung.</p>'],
            ['rubric' => 'announcement', 'title' => 'Neues', 'text' => '<p>Bitte lesen.</p>'],
        ], $hints);
    }

    /**
     * Switching a rubric off leaves its hint in `extras`, where no page shows it
     * any more. Keys no rubric is called by stay out the same way.
     */
    public function testAHintOfARubricTheRoomDoesNotOfferStaysOut(): void
    {
        $hints = $this->hints([
            'HOMECONF' => 'material_show',
            'USAGE_INFO_TEXT' => ['MATERIAL' => '<p>Da.</p>', 'TODO' => '<p>Abgeschaltet.</p>', '0' => 'Rest'],
        ]);

        $this->assertSame(['material'], array_column($hints, 'rubric'));
    }

    /**
     * Saving the settings stores a title for every rubric, with a text or not.
     */
    public function testATitleWithoutATextIsNoHint(): void
    {
        $hints = $this->hints([
            'HOMECONF' => 'material_show',
            'USAGE_INFO_HEADER' => ['HOME' => 'Nutzungshinweise', 'MATERIAL' => 'Nutzungshinweise'],
            'USAGE_INFO_TEXT' => ['MATERIAL' => ''],
        ]);

        $this->assertSame([], $hints);
    }

    public function testAHintWithoutATitleSaysSo(): void
    {
        $hints = $this->hints(['USAGE_INFO_TEXT' => ['HOME' => '<p>Hallo.</p>']]);

        $this->assertSame([['rubric' => 'home', 'title' => null, 'text' => '<p>Hallo.</p>']], $hints);
    }

    /**
     * Without settings of its own a room offers the default of its type, and
     * shows the hints of those rubrics.
     */
    public function testARoomWithoutRubricSettingsShowsTheHintsOfItsTypesRubrics(): void
    {
        $hints = $this->hints([
            'USAGE_INFO_TEXT' => [
                'HOME' => '<p>Hallo.</p>',
                'MATERIAL' => '<p>Lesestoff.</p>',
                'PROJECT' => '<p>Nur in Gemeinschaftsräumen.</p>',
            ],
        ]);

        $this->assertSame(['home', 'material'], array_column($hints, 'rubric'));
    }

    /**
     * @param array<string, mixed> $extras
     *
     * @return list<array{rubric: string, title: string|null, text: string}>
     */
    private function hints(array $extras): array
    {
        return (new Room())->setType('project')->setExtras($extras)->getUsageHints();
    }
}
