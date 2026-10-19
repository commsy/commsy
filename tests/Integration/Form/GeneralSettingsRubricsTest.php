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

namespace Tests\Integration\Form;

use App\Form\DataTransformer\GeneralSettingsTransformer;
use App\Services\LegacyEnvironment;
use cs_room_item;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Tests\Factory\PortalFactory;
use Tests\Factory\RoomFactory;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

/**
 * The general settings are where a room's rubrics are switched on and off,
 * shown or hidden, and put in order. This pins both directions of the form.
 */
final class GeneralSettingsRubricsTest extends KernelTestCase
{
    use Factories;
    use ResetDatabase;

    /**
     * Rubrics the room offers come first, in its order; those of its type it
     * does not offer follow as off.
     */
    public function testTheFormShowsEveryRubricOfTheRoomType(): void
    {
        $room = $this->room('material_show,todo_hide');

        $rubrics = $this->transformer()->transform($room)['rubrics'];

        $this->assertSame([
            'material' => 'show',
            'todo' => 'hide',
            'announcement' => 'off',
            'date' => 'off',
            'discussion' => 'off',
            'user' => 'off',
            'group' => 'off',
            'topic' => 'off',
        ], $rubrics);
    }

    public function testSavingKeepsTheOrderAndLeavesOutWhatIsOff(): void
    {
        $room = $this->room('material_show,todo_hide');
        $transformer = $this->transformer();

        $data = $transformer->transform($room);
        // The assignment to community rooms asks the portal; it is not what this is about.
        unset($data['community_rooms']);
        $data['rubricOrder'] = 'todo,material,date,user';
        $data['rubrics'] = ['todo' => 'show', 'material' => 'off', 'date' => 'hide', 'user' => 'show'];
        $transformer->applyTransformation($room, $data);

        $this->assertSame('todo_show,date_hide,user_show', $room->getHomeConf());
    }

    private function room(string $configuration): cs_room_item
    {
        self::bootKernel();

        $room = RoomFactory::new()->project()->create([
            'portal' => PortalFactory::createOne(),
            'extras' => ['HOMECONF' => $configuration],
        ]);

        $legacy = self::getContainer()->get(LegacyEnvironment::class)->getEnvironment()
            ->getRoomManager()->getItem($room->getItemId());
        $this->assertInstanceOf(cs_room_item::class, $legacy);

        return $legacy;
    }

    private function transformer(): GeneralSettingsTransformer
    {
        return self::getContainer()->get(GeneralSettingsTransformer::class);
    }
}
