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

namespace App\EventSubscriber;

use App\Event\ItemDeletedEvent;
use App\Event\ItemReindexEvent;
use App\Item\ItemType;
use App\Message\ReindexItem;
use App\Message\RemoveItemFromIndex;
use App\Search\ItemIndexer;
use App\Services\LegacyEnvironment;
use App\Utils\ItemService;
use cs_environment;
use cs_discussion_item;
use cs_file_item;
use cs_item;
use cs_material_item;
use cs_project_item;
use cs_todo_item;
use Elastica\Pipeline;
use Elastica\Processor\AttachmentProcessor;
use Elastica\Processor\ForeachProcessor;
use FOS\ElasticaBundle\Event\PostIndexResetEvent;
use FOS\ElasticaBundle\Event\PostTransformEvent;
use FOS\ElasticaBundle\Index\IndexManager;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Psr\Cache\InvalidArgumentException;
use ReflectionClass;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Mime\MimeTypes;

class ElasticaSubscriber implements EventSubscriberInterface
{
    private readonly cs_environment $legacyEnvironment;

    /**
     * ElasticCustomPropertyListener constructor.
     */
    public function __construct(
        LegacyEnvironment $legacyEnvironment,
        private readonly ItemService $itemService,
        private readonly IndexManager $indexManager,
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
        private readonly ItemIndexer $itemIndexer,
        private readonly MessageBusInterface $messageBus,
    ) {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
    }

    public static function getSubscribedEvents(): array
    {
        return [
            PostIndexResetEvent::class => 'prepareIngestPipeline',
            PostTransformEvent::class => 'addCustomProperty',
            ItemDeletedEvent::NAME => 'onItemDeleted',
            ItemReindexEvent::class => 'onItemReindex',
        ];
    }

    /**
     * Queues a reindex of a single item.
     *
     * Handing this to the worker keeps a rejected document — an attachment Tika
     * cannot parse, say — from reaching the request that saved the item, and lets a
     * transient Elasticsearch outage be retried instead of silently skipped.
     */
    public function onItemReindex(ItemReindexEvent $event): void
    {
        $item = $event->getItem();
        if (!$this->itemIndexer->isReindexable($item->getItemType())) {
            return;
        }

        $this->messageBus->dispatch(new ReindexItem((int) $item->getItemID(), $item->getItemType()));
    }

    /**
     * Queues removal of the deleted item's document.
     */
    public function onItemDeleted(ItemDeletedEvent $event): void
    {
        $item = $event->getItem();
        if (!$this->itemIndexer->isRemovable($item->getItemType())) {
            return;
        }

        $this->messageBus->dispatch(new RemoveItemFromIndex((int) $item->getItemID(), $item->getItemType()));
    }

    /**
     * Writes the attachment pipeline on every index reset, so a changed definition
     * reaches existing installations too. Putting a pipeline is idempotent.
     *
     * Tika reads the base64 data of each attachment; only the extracted text is kept.
     */
    public function prepareIngestPipeline(PostIndexResetEvent $event): void
    {
        $index = $this->indexManager->getIndex($event->getIndex());

        $attachmentProcessor = new AttachmentProcessor('_ingest._value.data');
        $attachmentProcessor->setTargetField('_ingest._value.attachment');
        $attachmentProcessor->setProperties(['content']);
        $attachmentProcessor->setIndexedChars(self::ATTACHMENT_INDEXED_CHARS);
        $attachmentProcessor->setParam('remove_binary', true);
        // files indexed by name only carry no data
        $attachmentProcessor->setIgnoreMissing(true);

        $foreachAttachmentProcessor = new ForeachProcessor('attachments', $attachmentProcessor);
        $foreachAttachmentProcessor->setIgnoreMissing(true);

        $pipeline = new Pipeline($index->getClient());
        $pipeline->setId('attachment');
        $pipeline->setDescription('Pipeline for attachments');
        $pipeline->addProcessor($foreachAttachmentProcessor);
        $pipeline->create();
    }

    /**
     * @throws InvalidArgumentException
     */
    public function addCustomProperty(PostTransformEvent $event): void
    {
        $fields = $event->getFields();

        if (isset($fields['rubric'])) {
            $this->addRubric($event);
        }

        if (isset($fields['hashtags'])) {
            $this->addHashtags($event);
        }

        if (isset($fields['tags'])) {
            $this->addTags($event);
        }

        if (isset($fields['annotations'])) {
            $this->addAnnotations($event);
        }

        if (isset($fields['attachments'])) {
            $this->addAttachments($event);
        }

        if (isset($fields['context'])) {
            $this->addContext($event);
        }

        if (isset($fields['discussionarticles'])) {
            $this->addDiscussionArticles($event);
        }

        if (isset($fields['steps'])) {
            $this->addSteps($event);
        }

        if (isset($fields['sections'])) {
            $this->addSections($event);
        }

        if (isset($fields['parentId'])) {
            $this->addParentRoomIds($event);
        }

        if (isset($fields['creator'])) {
            $this->addCreator($event);
        }

        if (isset($fields['modifier'])) {
            $this->addModifier($event);
        }
    }

    private function addRubric(PostTransformEvent $event): void
    {
        $item = $this->itemService->getTypedItem($event->getObject()->getItemId());

        if ($item) {
            // TODO: Consider using proper types / constants to transform an object to its rubric name
            $ref = new ReflectionClass($event->getObject());
            $rubric = strtolower(rtrim($ref->getShortName(), 's'));
            $event->getDocument()->set('rubric', $rubric);
        }
    }

    /**
     * @throws InvalidArgumentException
     */
    private function addHashtags(PostTransformEvent $event): void
    {
        $item = $this->itemService->getTypedItem($event->getObject()->getItemId());

        if ($item) {
            $hashtags = $item->getBuzzwordList();
            if ($hashtags->isNotEmpty()) {
                $objectHashtags = [];

                foreach ($hashtags as $hashtag) {
                    if (!$hashtag->isDeleted()) {
                        $objectHashtags[] = $hashtag->getName();
                    }
                }

                if (!empty($objectHashtags)) {
                    $event->getDocument()->set('hashtags', $objectHashtags);
                }
            }
        }
    }

    /**
     * @throws InvalidArgumentException
     */
    private function addTags(PostTransformEvent $event): void
    {
        $item = $this->itemService->getTypedItem($event->getObject()->getItemId());

        if ($item) {
            // when building the index from the CLI command, the context ID is not populated, thus we set it here explicitly
            $found = $this->setLegacyContext($item->getContextID());
            if ($found) {
                $tags = $item->getTagList();
                if ($tags->isNotEmpty()) {
                    $objectTags = [];

                    foreach ($tags as $tag) {
                        if (!$tag->isDeleted()) {
                            $objectTags[] = $tag->getTitle();
                        }
                    }

                    if (!empty($objectTags)) {
                        $event->getDocument()->set('tags', $objectTags);
                    }
                }
            }
        }
    }

    /**
     * @throws InvalidArgumentException
     */
    private function addContext(PostTransformEvent $event): void
    {
        $item = $this->itemService->getTypedItem($event->getObject()->getItemId());

        if ($item) {
            $context = $item->getContextItem();
            if ($context) {
                $event->getDocument()->set('context', [
                    'itemId' => $context->getItemId(),
                    'title' => $context->getTitle(),
                ]);
            }
        }
    }

    /**
     * @throws InvalidArgumentException
     */
    private function addAnnotations(PostTransformEvent $event): void
    {
        $item = $this->itemService->getTypedItem($event->getObject()->getItemId());

        if ($item) {
            $annotations = $item->getAnnotationList();
            if ($annotations->isNotEmpty()) {
                $objectTags = [];

                foreach ($annotations as $annotation) {
                    if (!$annotation->isDeleted()) {
                        $objectTags[] = $annotation->getDescription();
                    }
                }

                if (!empty($objectTags)) {
                    $event->getDocument()->set('annotations', $objectTags);
                }
            }
        }
    }

    /**
     * Indexes the files of the entry together with those of its sub-entries
     * (discussion articles, todo steps, material sections): a hit lands on the
     * entry either way, so they share its `attachments` field.
     */
    private function addAttachments(PostTransformEvent $event): void
    {
        $item = $this->itemService->getTypedItem($event->getObject()->getItemId());
        if (!$item) {
            return;
        }

        $attachments = [];
        foreach ([$item, ...$this->subEntriesOf($item)] as $entry) {
            foreach ($entry->getFileList() as $file) {
                /** @var cs_file_item $file */
                $attachment = $this->attachmentOf($file);
                if (null !== $attachment) {
                    // a file linked to the entry and to a sub-entry is indexed once
                    $attachments[$file->getFileID()] = $attachment;
                }
            }
        }

        if ([] !== $attachments) {
            $event->getDocument()->set('attachments', array_values($attachments));
        }
    }

    /**
     * The sub-entries whose text is indexed with the entry, by the same rules.
     *
     * @return cs_item[]
     */
    private function subEntriesOf(cs_item $item): array
    {
        $subEntries = match (true) {
            $item instanceof cs_discussion_item => $item->getAllArticles(),
            // the CLI populate has no context set, which the step list needs
            $item instanceof cs_todo_item => $this->setLegacyContext($item->getContextID())
                ? $item->getStepItemList()
                : null,
            $item instanceof cs_material_item => $item->getSectionList(),
            default => null,
        };

        $result = [];
        foreach ($subEntries ?? [] as $subEntry) {
            if (!$subEntry->isDeleted() && !$subEntry->isDraft()) {
                $result[] = $subEntry;
            }
        }

        return $result;
    }

    /**
     * Every file is findable by its name. Its content goes along, base64 encoded
     * for the attachment pipeline, only for the text-bearing types and sizes Tika
     * is asked to read.
     *
     * @return array{filename: string, filename_no_ext: string, data?: string}|null
     */
    private function attachmentOf(cs_file_item $file): ?array
    {
        if ($file->isDeleted()) {
            return null;
        }

        $path = $this->projectDir.'/'.$file->getFilepath();
        if (!is_file($path)) {
            return null;
        }

        $attachment = [
            'filename' => (string) $file->getFileName(),
            'filename_no_ext' => pathinfo((string) $file->getFileName(), PATHINFO_FILENAME),
        ];

        $size = filesize($path);
        if (false !== $size && $size > 0 && $size <= self::MAX_CONTENT_FILE_SIZE
            && in_array(MimeTypes::getDefault()->guessMimeType($path), self::VALID_MIMES, true)
        ) {
            $content = file_get_contents($path);
            if (false !== $content) {
                $attachment['data'] = base64_encode($content);
            }
        }

        return $attachment;
    }

    public function addDiscussionArticles($event): void
    {
        $discussionManager = $this->legacyEnvironment->getDiscussionManager();
        $discussion = $discussionManager->getItem($event->getObject()->getItemId());

        if ($discussion) {
            $articles = $discussion->getAllArticles();
            if ($articles->isNotEmpty()) {
                $articleContents = [];

                foreach ($articles as $article) {
                    if (!$article->isDeleted() && !$article->isDraft()) {
                        $articleContents[] = [
                            'description' => $article->getDescription(),
                        ];
                    }
                }

                if (!empty($articleContents)) {
                    $event->getDocument()->set('discussionarticles', $articleContents);
                }
            }
        }
    }

    /**
     * @throws InvalidArgumentException
     */
    public function addSteps($event): void
    {
        $todoManager = $this->legacyEnvironment->getTodosManager();
        $todo = $todoManager->getItem($event->getObject()->getItemId());

        if ($todo) {
            // when building the index from the CLI command, the context ID is not populated, thus we set it here explicitly
            $found = $this->setLegacyContext($todo->getContextID());
            if ($found) {
                $steps = $todo->getStepItemList();
                if ($steps->isNotEmpty()) {
                    $stepContents = [];

                    foreach ($steps as $step) {
                        if (!$step->isDeleted() && !$step->isDraft()) {
                            $stepContents[] = [
                                'title' => $step->getTitle(),
                                'description' => $step->getDescription(),
                            ];
                        }
                    }

                    if (!empty($stepContents)) {
                        $event->getDocument()->set('steps', $stepContents);
                    }
                }
            }
        }
    }

    /**
     * Writes into `sections`, the field the material mapping declares.
     * This wrote `steps` — the field name of {@see addSteps()}, which it
     * was copied from — so the text landed in a dynamically created field
     * with the standard analyzer instead. See #5455.
     */
    public function addSections($event): void
    {
        $materialManager = $this->legacyEnvironment->getMaterialManager();
        $material = $materialManager->getItem($event->getObject()->getItemId());

        if ($material) {
            $sections = $material->getSectionList();
            if ($sections->isNotEmpty()) {
                $sectionContents = [];

                foreach ($sections as $section) {
                    if (!$section->isDeleted() && !$section->isDraft()) {
                        $sectionContents[] = [
                            'title' => $section->getTitle(),
                            'description' => $section->getDescription(),
                        ];
                    }
                }

                if (!empty($sectionContents)) {
                    $event->getDocument()->set('sections', $sectionContents);
                }
            }
        }
    }

    public function addParentRoomIds($event): void
    {
        $roomManager = $this->legacyEnvironment->getRoomManager();
        $room = $roomManager->getItem($event->getObject()->getItemId());

        if ($room instanceof cs_project_item) {
            $communityRooms = $room->getCommunityList();

            if ($communityRooms->isNotEmpty()) {
                $parentIds = [];

                foreach ($communityRooms as $communityRoom) {
                    $parentIds[] = $communityRoom->getItemId();
                }

                if (!empty($parentIds)) {
                    $event->getDocument()->set('parentId', $parentIds);
                }
            }
        }
    }

    public function addCreator($event): void
    {
        $item = $this->itemService->getTypedItem($event->getObject()->getItemId());

        if ($item) {
            if (ItemType::User->value !== $item->getItemType()) {
                return;
            }

            $userManager = $this->legacyEnvironment->getUserManager();
            $user = $userManager->getItem($item->getItemID());

            $creator = $user->getCreatorItem();
            if (!$creator) {
                // NOTE: this condition also applies to the root user item which has creator ID 99 and no
                // matching user item in the database (which would thus cause an EntityNotFoundException)
                return;
            }

            $creatorProperties = [
                'firstName' => $creator->getFirstname(),
                'lastName' => $creator->getLastname(),
                'fullName' => $creator->getFullName(),
            ];
            $event->getDocument()->set('creator', $creatorProperties);
        }
    }

    public function addModifier($event): void
    {
        $item = $this->itemService->getTypedItem($event->getObject()->getItemId());

        if ($item) {
            if (ItemType::User->value !== $item->getItemType()) {
                return;
            }

            $userManager = $this->legacyEnvironment->getUserManager();
            $user = $userManager->getItem($item->getItemID());

            $modifier = $user->getModificatorItem();
            if (!$modifier) {
                // NOTE: this condition also applies to the root user item which has modifier ID 99 and no
                // matching user item in the database (which would thus cause an EntityNotFoundException)
                return;
            }

            $modifierProperties = [
                'firstName' => $modifier->getFirstname(),
                'lastName' => $modifier->getLastname(),
                'fullName' => $modifier->getFullName(),
            ];
            $event->getDocument()->set('modifier', $modifierProperties);
        }
    }

    private function setLegacyContext($contextId): bool
    {
        $contextItem = $this->itemService->getTypedItem($contextId);

        if ($contextItem) {
            $this->legacyEnvironment->setCurrentContextID($contextId);
            $this->legacyEnvironment->setCurrentContextItem($contextItem);

            return true;
        }

        return false;
    }

    /**
     * Files above this size are indexed by name only. Each one travels base64
     * encoded, a third larger, inside the indexing request.
     */
    private const int MAX_CONTENT_FILE_SIZE = 25 * 1024 * 1024;

    /**
     * Characters Tika extracts per file; Elasticsearch stops at 100,000 by default,
     * which leaves most of a long document unsearchable. Extraction runs on the
     * Elasticsearch heap, so this stays bounded rather than unlimited (-1).
     */
    private const int ATTACHMENT_INDEXED_CHARS = 1_000_000;

    private const VALID_MIMES = [
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/msexcel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/mspowerpoint',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'application/pdf',
        'application/vnd.oasis.opendocument.text',
        'application/vnd.oasis.opendocument.spreadsheet',
        'application/vnd.oasis.opendocument.presentation',
        'text/html',
        'text/plain',
        'text/rtf',
    ];
}
