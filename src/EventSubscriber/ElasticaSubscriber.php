<?php

namespace App\EventSubscriber;

use App\Services\LegacyEnvironment;
use App\Utils\ItemService;
use cs_environment;
use cs_file_item;
use cs_list;
use cs_project_item;
use Elastica\Pipeline;
use Elastica\Processor\AttachmentProcessor;
use Elastica\Processor\ForeachProcessor;
use Elastica\Processor\RemoveProcessor;
use Elastica\Request;
use FOS\ElasticaBundle\Event\PostIndexResetEvent;
use FOS\ElasticaBundle\Event\PostTransformEvent;
use FOS\ElasticaBundle\Index\IndexManager;
use Psr\Cache\InvalidArgumentException;
use ReflectionClass;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Mime\MimeTypes;

class ElasticaSubscriber implements EventSubscriberInterface
{
    /**
     * @var cs_environment
     */
    private cs_environment $legacyEnvironment;

    /**
     * @var ItemService
     */
    private ItemService $itemService;

    /**
     * @var IndexManager
     */
    private IndexManager $indexManager;

    /**
     * @var ParameterBagInterface
     */
    private ParameterBagInterface $parameterBag;

    /**
     * ElasticCustomPropertyListener constructor.
     * @param LegacyEnvironment $legacyEnvironment
     * @param ItemService $itemService
     * @param IndexManager $indexManager
     * @param ParameterBagInterface $parameterBag
     */
    public function __construct(
        LegacyEnvironment $legacyEnvironment,
        ItemService $itemService,
        IndexManager $indexManager,
        ParameterBagInterface $parameterBag
    ) {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
        $this->itemService = $itemService;
        $this->indexManager = $indexManager;
        $this->parameterBag = $parameterBag;
    }

    public static function getSubscribedEvents()
    {
        return [
            PostIndexResetEvent::class => 'prepareIngestPipeline',
            PostTransformEvent::class => 'addCustomProperty',
        ];
    }

    public function prepareIngestPipeline(PostIndexResetEvent $event)
    {
        $index = $this->indexManager->getIndex($event->getIndex());
        $pipeline = new Pipeline($index->getClient());

        $attachmentPipelineId = 'attachment';

        $response = $pipeline->getPipeline($attachmentPipelineId);
        if ($response->getStatus() === 404) {
            $pipeline->setId($attachmentPipelineId);
            $pipeline->setDescription('Pipeline for attachments');

            $attachmentProcessor = new AttachmentProcessor('_ingest._value.data');
            $attachmentProcessor->setTargetField('_ingest._value.attachment');
            $foreachAttachmentProcessor = new ForeachProcessor('attachments', $attachmentProcessor);
            $foreachAttachmentProcessor->setIgnoreMissing(true);
//            $pipeline->addProcessor($foreachAttachmentProcessor);

            $removeProcessor = new RemoveProcessor('_ingest._value.data');
            $foreachRemoveProcessor = new ForeachProcessor('attachments', $removeProcessor);
            $foreachRemoveProcessor->setIgnoreMissing(true);
//            $pipeline->addProcessor($foreachRemoveProcessor);

            /*
             * TODO: This is a workaround (see https://github.com/ruflin/Elastica/issues/1810), because ruflin/elastica
             * does not support creating multiple processors with the same name yet. It will override the previously
             * added processors.
             */
            $pipelineDefinition = [
                'description' => 'Pipeline for attachments',
                'processors' => [
                    $foreachAttachmentProcessor->toArray(),
                    $foreachRemoveProcessor->toArray(),
                ]
            ];
            $index->getClient()->request(
                "_ingest/pipeline/{$attachmentPipelineId}",
                Request::PUT,
                json_encode($pipelineDefinition)
            );

//            $pipeline->create();
        }
    }

    /**
     * @param PostTransformEvent $event
     * @throws InvalidArgumentException
     */
    public function addCustomProperty(PostTransformEvent $event)
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

    private function addRubric(PostTransformEvent $event)
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
     * @param PostTransformEvent $event
     * @throws InvalidArgumentException
     */
    private function addHashtags(PostTransformEvent $event)
    {
        $item = $this->itemService->getTypedItem($event->getObject()->getItemId());

        if ($item) {
            $hashtags = $item->getBuzzwordList();
            if ($hashtags->isNotEmpty()) {
                $objectHashtags = [];

                $hashtag = $hashtags->getFirst();
                while ($hashtag) {
                    if (!$hashtag->isDeleted()) {
                        $objectHashtags[] = $hashtag->getName();
                    }

                    $hashtag = $hashtags->getNext();
                }

                if (!empty($objectHashtags)) {
                    $event->getDocument()->set('hashtags', $objectHashtags);
                }
            }
        }
    }

    /**
     * @param PostTransformEvent $event
     * @throws InvalidArgumentException
     */
    private function addTags(PostTransformEvent $event)
    {
        $item = $this->itemService->getTypedItem($event->getObject()->getItemId());

        if ($item) {
            // when building the index from the CLI command, the context ID is not populated, thus we set it here explicitly
            $found = $this->setLegacyContext($item->getContextID());
            if ($found) {
                $tags = $item->getTagList();
                if ($tags->isNotEmpty()) {
                    $objectTags = [];

                    $tag = $tags->getFirst();
                    while ($tag) {
                        if (!$tag->isDeleted()) {
                            $objectTags[] = $tag->getTitle();
                        }

                        $tag = $tags->getNext();
                    }

                    if (!empty($objectTags)) {
                        $event->getDocument()->set('tags', $objectTags);
                    }
                }
            }
        }
    }

    /**
     * @param PostTransformEvent $event
     * @throws InvalidArgumentException
     */
    private function addContext(PostTransformEvent $event)
    {
        $item = $this->itemService->getTypedItem($event->getObject()->getItemId());

        if ($item) {
            $context = $item->getContextItem();
            if ($context) {
                $event->getDocument()->set('context', [
                    'title' => $context->getTitle(),
                ]);
            }
        }
    }

    /**
     * @param PostTransformEvent $event
     * @throws InvalidArgumentException
     */
    private function addAnnotations(PostTransformEvent $event)
    {
        $item = $this->itemService->getTypedItem($event->getObject()->getItemId());

        if ($item) {
            $annotations = $item->getAnnotationList();
            if ($annotations->isNotEmpty()) {
                $objectTags = [];

                $annotation = $annotations->getFirst();
                while ($annotation) {
                    if (!$annotation->isDeleted()) {
                        $objectTags[] = $annotation->getDescription();
                    }

                    $annotation = $annotations->getNext();
                }

                if (!empty($objectTags)) {
                    $event->getDocument()->set('annotations', $objectTags);
                }
            }
        }
    }

    /**
     * @param PostTransformEvent $event
     */
    private function addAttachments(PostTransformEvent $event)
    {
        $item = $this->itemService->getTypedItem($event->getObject()->getItemId());

        if ($item) {
            $files = $item->getFileList();
            $filesBase64 = $this->getBase64ContentofAllFiles($files);

            if (!empty($filesBase64)) {
                $event->getDocument()->set('attachments', $filesBase64);
            }
        }
    }

    /**
     * @param cs_list $files
     * @return array
     */
    private function getBase64ContentofAllFiles(cs_list $files): array
    {
        $filesBase64 = [];

        foreach ($files as $legacyFile) {
            /** @var cs_file_item $legacyFile */
            if (!$legacyFile->isDeleted()) {
                /** @noinspection MissingService */
                $fileInfo = pathinfo($this->parameterBag->get('kernel.project_dir') . '/' . $legacyFile->getFilepath());
                $dirname = $fileInfo['dirname'];
                $basename = $fileInfo['basename'];

                if (!file_exists($dirname . '/' . $basename)) {
                    continue;
                }

                $finder = new Finder();
                $finder
                    ->files()
                    ->size('<= 25M')
                    ->name($fileInfo['basename'])
                    ->in($fileInfo['dirname'])
                    ->filter(function(SplFileInfo $file) {
                        $mimeTypes = new MimeTypes();
                        $mimeType = $mimeTypes->guessMimeType($file->getPathname());

                        return in_array($mimeType, self::VALID_MIMES);
                    });

                if ($finder->hasResults()) {
                    $results = iterator_to_array($finder);
                    /** @var SplFileInfo $splFile */
                    $splFile = current($results);

                    $content = base64_encode($splFile->getContents());
                    if (!empty($content)) {
                        $filesBase64[] = [
                            'filename' => $legacyFile->getFilename(),
                            'data' => $content,
                        ];
                    }
                }
            }
        }

        return $filesBase64;
    }

    /**
     * @param $event
     */
    public function addDiscussionArticles($event)
    {
        $discussionManager = $this->legacyEnvironment->getDiscussionManager();
        $discussion = $discussionManager->getItem($event->getObject()->getItemId());

        if ($discussion) {
            $articles = $discussion->getAllArticles();
            if ($articles->isNotEmpty()) {
                $articleContents = [];

                $article = $articles->getFirst();
                while ($article) {
                    if (!$article->isDeleted() && !$article->isDraft()) {
                        $files = $article->getFileList();
//                        $filesBase64 = $this->getBase64ContentofAllFiles($files);

                        $articleContents[] = [
                            'subject' => $article->getSubject(),
                            'description' => $article->getDescription(),
//                            'files' => $filesBase64,
                        ];
                    }

                    $article = $articles->getNext();
                }

                if (!empty($articleContents)) {
                    $event->getDocument()->set('discussionarticles', $articleContents);
                }
            }
        }
    }

    /**
     * @param $event
     * @throws InvalidArgumentException
     */
    public function addSteps($event)
    {
        $todoManager = $this->legacyEnvironment->getTodoManager();
        $todo = $todoManager->getItem($event->getObject()->getItemId());

        if ($todo) {
            // when building the index from the CLI command, the context ID is not populated, thus we set it here explicitly
            $found = $this->setLegacyContext($todo->getContextID());
            if ($found) {
                $steps = $todo->getStepItemList();
                if ($steps->isNotEmpty()) {
                    $stepContents = [];

                    $step = $steps->getFirst();
                    while ($step) {
                        if (!$step->isDeleted() && !$step->isDraft()) {
                            $files = $step->getFileList();
//                            $filesBase64 = $this->getBase64ContentofAllFiles($files);
                            $stepContents[] = [
                                'title' => $step->getTitle(),
                                'description' => $step->getDescription(),
//                                'files' => $filesBase64,
                            ];
                        }

                        $step = $steps->getNext();
                    }

                    if (!empty($stepContents)) {
                        $event->getDocument()->set('steps', $stepContents);
                    }
                }
            }
        }
    }

    /**
     * @param $event
     */
    public function addSections($event)
    {
        $materialManager = $this->legacyEnvironment->getMaterialManager();
        $material = $materialManager->getItem($event->getObject()->getItemId());

        if ($material) {
            $sections = $material->getSectionList();
            if ($sections->isNotEmpty()) {
                $sectionContents = [];

                $section = $sections->getFirst();
                while ($section) {
                    if (!$section->isDeleted() && !$section->isDraft()) {
                        $files = $section->getFileList();
//                        $filesBase64 = $this->getBase64ContentofAllFiles($files);
                        $sectionContents[] = [
                            'title' => $section->getTitle(),
                            'description' => $section->getDescription(),
//                            'files' => $filesBase64,
                        ];
                    }

                    $section = $sections->getNext();
                }

                if (!empty($sectionContents)) {
                    $event->getDocument()->set('steps', $sectionContents);
                }
            }
        }
    }

    /**
     * @param $event
     */
    public function addParentRoomIds($event)
    {
        $roomManager = $this->legacyEnvironment->getRoomManager();
        $room = $roomManager->getItem($event->getObject()->getItemId());

        if ($room instanceof cs_project_item) {
            $communityRooms = $room->getCommunityList();

            if ($communityRooms->isNotEmpty()) {
                $parentIds = [];

                $communityRoom = $communityRooms->getFirst();

                while ($communityRoom) {
                    $parentIds[] = $communityRoom->getItemId();

                    $communityRoom = $communityRooms->getNext();
                }

                if (!empty($parentIds)) {
                    $event->getDocument()->set('parentId', $parentIds);
                }
            }
        }
    }

    /**
     * @param $event
     */
    public function addCreator($event)
    {
        $item = $this->itemService->getTypedItem($event->getObject()->getItemId());

        if ($item) {
            if ($item->getItemType() !== CS_USER_TYPE) {
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

    /**
     * @param $event
     */
    public function addModifier($event)
    {
        $item = $this->itemService->getTypedItem($event->getObject()->getItemId());

        if ($item) {
            if ($item->getItemType() !== CS_USER_TYPE) {
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

    /**
     * @param $contextId
     * @return bool
     */
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

    private const VALID_MIMES = [
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/msexcel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/mspowerpoint',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'application/pdf',
        'text/plain',
    ];
}