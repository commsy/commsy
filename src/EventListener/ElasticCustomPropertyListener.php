<?php

namespace App\EventListener;

use App\Services\File2TextService;
use App\Services\LegacyEnvironment;
use App\Utils\ItemService;
use cs_environment;
use cs_item;
use cs_project_item;
use FOS\ElasticaBundle\Event\TransformEvent;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * Class ElasticCustomPropertyListener
 * @package App\EventListener
 */
class ElasticCustomPropertyListener implements EventSubscriberInterface
{
    /**
     * @var cs_environment
     */
    private cs_environment $legacyEnvironment;

    /**
     * @var File2TextService
     */
    private File2TextService $file2TextService;
    /**
     * @var FilesystemAdapter
     */
    private FilesystemAdapter $cache;

    /**
     * @var string
     */
    private string $projectDir;

    /**
     * @var ItemService
     */
    private ItemService $itemService;

    /**
     * ElasticCustomPropertyListener constructor.
     * @param LegacyEnvironment $legacyEnvironment
     * @param File2TextService $file2TextService
     * @param KernelInterface $kernel
     * @param ItemService $itemService
     */
    public function __construct(
        LegacyEnvironment $legacyEnvironment,
        File2TextService $file2TextService,
        KernelInterface $kernel,
        ItemService $itemService
    ) {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
        $this->file2TextService = $file2TextService;
        $this->projectDir = $kernel->getProjectDir();
        $this->cache = new FilesystemAdapter();
        $this->itemService = $itemService;
    }

    /**
     * @return string[]
     */
    public static function getSubscribedEvents(): array
    {
        return [
            TransformEvent::POST_TRANSFORM => 'addCustomProperty'
        ];
    }

    /**
     * @param TransformEvent $event
     * @throws InvalidArgumentException
     */
    public function addCustomProperty(TransformEvent $event)
    {
        $fields = $event->getFields();

        if (isset($fields['hashtags'])) {
            $this->addHashtags($event);
        }

        if (isset($fields['tags'])) {
            $this->addTags($event);
        }

        if (isset($fields['annotations'])) {
            $this->addAnnotations($event);
        }

        if (isset($fields['files'])) {
            $this->addFilesContent($event);
        }

        if (isset($fields['filesRaw'])) {
            $this->addFilesRawContent($event);
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

    /**
     * @param TransformEvent $event
     * @throws InvalidArgumentException
     */
    private function addHashtags(TransformEvent $event)
    {
        $item = $this->getItemCached($event->getObject()->getItemId());

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
     * @param TransformEvent $event
     * @throws InvalidArgumentException
     */
    private function addTags(TransformEvent $event)
    {
        $item = $this->getItemCached($event->getObject()->getItemId());

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
     * @param TransformEvent $event
     * @throws InvalidArgumentException
     */
    private function addContext(TransformEvent $event)
    {
        $item = $this->getItemCached($event->getObject()->getItemId());

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
     * @param TransformEvent $event
     * @throws InvalidArgumentException
     */
    private function addAnnotations(TransformEvent $event)
    {
        $item = $this->getItemCached($event->getObject()->getItemId());

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
     * @param TransformEvent $event
     * @throws InvalidArgumentException
     */
    private function addFilesContent(TransformEvent $event)
    {
        $item = $this->getItemCached($event->getObject()->getItemId());

        if ($item) {
            $fileContents = [];
            $files = $item->getFileList();
            if ($files->isNotEmpty()) {

                /** @var \cs_file_item $file */
                $file = $files->getFirst();
                while ($file) {
                    if (!$file->isDeleted()) {
                        $fileSize = $file->getFileSize();
                        if ($fileSize > 0 && round($fileSize / 1024) < 25) {
                            $content = $file->getContentBase64();
                            if (!empty($content)) {
                                $fileContents[] = $content;
                            }
                        }
                    }

                    $file = $files->getNext();
                }
            }

            $event->getDocument()->set('files', $fileContents);
        }
    }


    private function addFilesRawContent(TransformEvent $event)
    {
        $item = $this->getItemCached($event->getObject()->getItemId());

        if ($item) {
            $filesPlain = [];
            $files = $item->getFileList();
            if ($files->isNotEmpty()) {

                /** @var \cs_file_item $file */
                $file = $files->getFirst();
                while ($file) {
                    if (!$file->isDeleted()) {
                        $fileName = $this->projectDir . '/' . $file->getFilepath();
                        $contentPlain = $this->file2TextService->convert($fileName);
                        if (!empty($contentPlain)) {
                            $filesPlain[] = $contentPlain;
                        }
                    }
                    $file = $files->getNext();
                }
            }
            $event->getDocument()->set('filesRaw', $filesPlain);
        }
    }


    public function getPlainContentofAllFiles($files)
    {
        $filesPlain = [];

        /** @var \cs_file_item $file */
        foreach ($files as $file) {
            if (!$file->isDeleted()) {
                $fileName = $this->projectDir . '/' . $file->getFilepath();
                $contentPlain = $this->file2TextService->convert($fileName);
                if (!empty($contentPlain)) {
                    $filesPlain[] = $contentPlain;
                }
            }
        }

        return $filesPlain;

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
                        $filesPlain = $this->getPlainContentofAllFiles($files);

                        $articleContents[] = [
                            'subject' => $article->getSubject(),
                            'description' => $article->getDescription(),
                            'filesRaw' => $filesPlain,
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
                            $filesPlain = $this->getPlainContentofAllFiles($files);
                            $stepContents[] = [
                                'title' => $step->getTitle(),
                                'description' => $step->getDescription(),
                                'filesRaw' => $filesPlain,
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
                        $filesPlain = $this->getPlainContentofAllFiles($files);
                        $sectionContents[] = [
                            'title' => $section->getTitle(),
                            'description' => $section->getDescription(),
                            'filesRaw' => $filesPlain,
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

        if ($room) {
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
    }

    /**
     * @param $event
     * @throws InvalidArgumentException
     */
    public function addCreator($event)
    {
        $item = $this->getItemCached($event->getObject()->getItemId());

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
     * @throws InvalidArgumentException
     */
    public function addModifier($event)
    {
        $item = $this->getItemCached($event->getObject()->getItemId());

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
     * @param $itemId
     * @return cs_item|null
     * @throws InvalidArgumentException
     */
    private function getItemCached($itemId): ?cs_item
    {
        return $this->cache->get($itemId, function (ItemInterface $cachedItem) {
            $cachedItem->expiresAfter(60 * 60);

            return $this->itemService->getTypedItem($cachedItem->getKey());
        });
    }

    /**
     * @param $contextId
     * @return bool
     * @throws InvalidArgumentException
     */
    private function setLegacyContext($contextId): bool
    {
        $contextItem = $this->getItemCached($contextId);

        if ($contextItem) {
            $this->legacyEnvironment->setCurrentContextID($contextId);
            $this->legacyEnvironment->setCurrentContextItem($contextItem);

            return true;
        }

        return false;
    }
}