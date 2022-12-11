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

use App\Services\LegacyEnvironment;
use App\Utils\ItemService;
use Elastica\Pipeline;
use Elastica\Processor\AttachmentProcessor;
use Elastica\Processor\ForeachProcessor;
use Elastica\Processor\RemoveProcessor;
use Elastica\Request;
use FOS\ElasticaBundle\Event\PostIndexResetEvent;
use FOS\ElasticaBundle\Event\PostTransformEvent;
use FOS\ElasticaBundle\Index\IndexManager;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Mime\MimeTypes;

class ElasticaSubscriber implements EventSubscriberInterface
{
    private \cs_environment $legacyEnvironment;

    /**
     * ElasticCustomPropertyListener constructor.
     */
    public function __construct(
        LegacyEnvironment $legacyEnvironment,
        private ItemService $itemService,
        private IndexManager $indexManager,
        private ParameterBagInterface $parameterBag
    ) {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
    }

    public static function getSubscribedEvents(): array
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
        if (404 === $response->getStatus()) {
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
                ],
            ];
            $index->getClient()->request(
                "_ingest/pipeline/$attachmentPipelineId",
                Request::PUT,
                json_encode($pipelineDefinition)
            );

//            $pipeline->create();
        }
    }

    /**
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
            $ref = new \ReflectionClass($event->getObject());
            $rubric = strtolower(rtrim($ref->getShortName(), 's'));
            $event->getDocument()->set('rubric', $rubric);
        }
    }

    /**
     * @throws InvalidArgumentException
     */
    private function addHashtags(PostTransformEvent $event)
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
     * @throws InvalidArgumentException
     */
    private function addAnnotations(PostTransformEvent $event)
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

    private function getBase64ContentofAllFiles(\cs_list $files): array
    {
        $filesBase64 = [];

        foreach ($files as $legacyFile) {
            /** @var \cs_file_item $legacyFile */
            if (!$legacyFile->isDeleted()) {
                $fileInfo = pathinfo($this->parameterBag->get('kernel.project_dir').'/'.$legacyFile->getFilepath());
                $dirname = $fileInfo['dirname'];
                $basename = $fileInfo['basename'];

                if (!file_exists($dirname.'/'.$basename)) {
                    continue;
                }

                $finder = new Finder();
                $finder
                    ->files()
                    ->size('<= 25M')
                    ->name($fileInfo['basename'])
                    ->in($fileInfo['dirname']);

                if ($finder->hasResults()) {
                    $results = iterator_to_array($finder);
                    /** @var SplFileInfo $splFile */
                    $splFile = current($results);

                    // If the file has a whitelisted mime-type index the content, otherwise just the filename
                    $mimeTypes = new MimeTypes();
                    $mimeType = $mimeTypes->guessMimeType($splFile->getPathname());

                    $indexInfo = [
                        'filename' => $legacyFile->getFileName(),
                        'filename_no_ext' => pathinfo($legacyFile->getFileName(), PATHINFO_FILENAME),
                        'data' => '',
                    ];
                    if (in_array($mimeType, self::VALID_MIMES)) {
                        $content = base64_encode($splFile->getContents());
                        if (!empty($content)) {
                            $indexInfo['data'] = $content;
                        }
                    }

                    $filesBase64[] = $indexInfo;
                }
            }
        }

        return $filesBase64;
    }

    public function addDiscussionArticles($event)
    {
        $discussionManager = $this->legacyEnvironment->getDiscussionManager();
        $discussion = $discussionManager->getItem($event->getObject()->getItemId());

        if ($discussion) {
            $articles = $discussion->getAllArticles();
            if ($articles->isNotEmpty()) {
                $articleContents = [];

                foreach ($articles as $article) {
                    if (!$article->isDeleted() && !$article->isDraft()) {
//                        $files = $article->getFileList();
//                        $filesBase64 = $this->getBase64ContentofAllFiles($files);

                        $articleContents[] = [
                            'subject' => $article->getSubject(),
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

                    foreach ($steps as $step) {
                        if (!$step->isDeleted() && !$step->isDraft()) {
//                            $files = $step->getFileList();
//                            $filesBase64 = $this->getBase64ContentofAllFiles($files);
                            $stepContents[] = [
                                'title' => $step->getTitle(),
                                'description' => $step->getDescription(),
//                                'files' => $filesBase64,
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

    public function addSections($event)
    {
        $materialManager = $this->legacyEnvironment->getMaterialManager();
        $material = $materialManager->getItem($event->getObject()->getItemId());

        if ($material) {
            $sections = $material->getSectionList();
            if ($sections->isNotEmpty()) {
                $sectionContents = [];

                foreach ($sections as $section) {
                    if (!$section->isDeleted() && !$section->isDraft()) {
//                        $files = $section->getFileList();
//                        $filesBase64 = $this->getBase64ContentofAllFiles($files);
                        $sectionContents[] = [
                            'title' => $section->getTitle(),
                            'description' => $section->getDescription(),
//                            'files' => $filesBase64,
                        ];
                    }
                }

                if (!empty($sectionContents)) {
                    $event->getDocument()->set('steps', $sectionContents);
                }
            }
        }
    }

    public function addParentRoomIds($event)
    {
        $roomManager = $this->legacyEnvironment->getRoomManager();
        $room = $roomManager->getItem($event->getObject()->getItemId());

        if ($room instanceof \cs_project_item) {
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

    public function addCreator($event)
    {
        $item = $this->itemService->getTypedItem($event->getObject()->getItemId());

        if ($item) {
            if (CS_USER_TYPE !== $item->getItemType()) {
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

    public function addModifier($event)
    {
        $item = $this->itemService->getTypedItem($event->getObject()->getItemId());

        if ($item) {
            if (CS_USER_TYPE !== $item->getItemType()) {
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
