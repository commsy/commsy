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

namespace Tests\Integration\Search;

use App\Entity\Room;
use App\Entity\User;
use App\EventSubscriber\ElasticaSubscriber;
use Elastica\Document;
use FOS\ElasticaBundle\Event\PostTransformEvent;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Yaml\Yaml;
use Tests\Factory\DiscussionArticleFactory;
use Tests\Factory\DiscussionFactory;
use Tests\Factory\MaterialFactory;
use Tests\Factory\SectionFactory;
use Tests\Factory\StepFactory;
use Tests\Factory\TodoFactory;
use Tests\Story\RoomWithMemberStory;
use Zenstruck\Foundry\Attribute\WithStory;

/**
 * Closes the loop between the code that builds a search document and the
 * mapping that declares its fields.
 *
 * Sub-entries have no index of their own — their text is embedded in the
 * parent's document, under a field name each index declares separately
 * (`sections` for material, `steps` for todo, `discussionarticles` for
 * discussion). `addSections()` wrote `steps`, the name of the method it
 * was copied from, so section text landed in a field the material mapping
 * does not declare and Elasticsearch created it dynamically: no
 * `html_strip`, no stemming, no ngrams on the title.
 *
 * Nothing caught it, because a mismatch is invisible from either side
 * alone — the code writes happily, the mapping stays empty. These tests
 * read the mapping and assert the code writes into it.
 */
final class EmbeddedSubEntryFieldTest extends KernelTestCase
{
    private ElasticaSubscriber $subscriber;
    private Room $room;
    private User $roomUser;

    #[WithStory(RoomWithMemberStory::class)]
    public function testMaterialEmbedsSectionsUnderTheDeclaredField(): void
    {
        $material = MaterialFactory::createOne(['room' => $this->room, 'creator' => $this->roomUser]);
        SectionFactory::createOne([
            'room' => $this->room,
            'creator' => $this->roomUser,
            'material' => $material,
            'title' => 'Abschnittstitel',
            'description' => '<p>Abschnittsinhalt</p>',
        ]);

        $document = $this->transform('addSections', $material);

        $this->assertDeclaredBy('commsy_material', 'sections');
        self::assertTrue($document->has('sections'), 'section text must go into the declared field');
        self::assertFalse($document->has('steps'), 'and not into the todo index\'s field');
        self::assertSame(
            [['title' => 'Abschnittstitel', 'description' => '<p>Abschnittsinhalt</p>']],
            $document->get('sections')
        );
    }

    #[WithStory(RoomWithMemberStory::class)]
    public function testTodoEmbedsStepsUnderTheDeclaredField(): void
    {
        $todo = TodoFactory::createOne(['room' => $this->room, 'creator' => $this->roomUser]);
        StepFactory::createOne([
            'room' => $this->room, 'creator' => $this->roomUser, 'todo' => $todo,
        ]);

        $document = $this->transform('addSteps', $todo);

        $this->assertDeclaredBy('commsy_todo', 'steps');
        self::assertTrue($document->has('steps'));
        self::assertFalse($document->has('sections'));
    }

    #[WithStory(RoomWithMemberStory::class)]
    public function testDiscussionEmbedsArticlesUnderTheDeclaredField(): void
    {
        $discussion = DiscussionFactory::createOne(['room' => $this->room, 'creator' => $this->roomUser]);
        DiscussionArticleFactory::createOne([
            'room' => $this->room, 'creator' => $this->roomUser, 'discussion' => $discussion,
        ]);

        $document = $this->transform('addDiscussionArticles', $discussion);

        $this->assertDeclaredBy('commsy_discussion', 'discussionarticles');
        self::assertTrue($document->has('discussionarticles'));
    }

    protected function setUp(): void
    {
        self::bootKernel();

        $this->subscriber = self::getContainer()->get(ElasticaSubscriber::class);
        $this->room = RoomWithMemberStory::get('room');
        $this->roomUser = RoomWithMemberStory::get('roomUser');
    }

    /**
     * Runs one POST_TRANSFORM step against an empty document, the way
     * FOSElastica does while building the entry's document. No
     * Elasticsearch connection involved — `.env.test` has none.
     */
    private function transform(string $method, object $entity): Document
    {
        $event = new PostTransformEvent(new Document(), [], $entity);
        $this->subscriber->{$method}($event);

        return $event->getDocument();
    }

    private function assertDeclaredBy(string $index, string $field): void
    {
        $config = Yaml::parseFile(self::getContainer()->getParameter('kernel.project_dir')
            . '/config/packages/fos_elastica.yaml');

        $properties = $config['fos_elastica']['indexes'][$index]['properties'] ?? [];

        self::assertArrayHasKey(
            $field,
            $properties,
            sprintf('index %s must declare the field %s the subscriber writes', $index, $field)
        );
    }
}
