<?php

namespace App\EventListener;

use App\Services\File2TextService;
use App\Services\LegacyEnvironment;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

use FOS\ElasticaBundle\Event\TransformEvent;
use Symfony\Component\HttpKernel\KernelInterface;

class ElasticCustomPropertyListener implements EventSubscriberInterface
{
    private $legacyEnvironment;

    private $itemCache = [];
    /**
     * @var File2TextService
     */
    private $file2TextService;

    private $projectDir;

    public function __construct(LegacyEnvironment $legacyEnvironment, File2TextService $file2TextService, KernelInterface $kernel)
    {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
        $this->file2TextService = $file2TextService;
        $this->projectDir = $kernel->getProjectDir();
    }

    public static function getSubscribedEvents()
    {
        return [
            TransformEvent::POST_TRANSFORM => 'addCustomProperty'
        ];
    }

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

    private function addTags(TransformEvent $event)
    {
        $item = $this->getItemCached($event->getObject()->getItemId());

        if ($item) {
            // when building the index from the CLI command, the context ID is not populated, thus we set it here explicitly
            $this->legacyEnvironment->setCurrentContextID($item->getContextID());

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
                        if (round($fileSize / 1024) < 25) {
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
                        if(!empty($contentPlain)){
                            $filesPlain[] = $contentPlain;
                        }
                    }
                    $file = $files->getNext();
                }
            }
            $event->getDocument()->set('filesRaw', $filesPlain);
        }
    }


    public function getPlainContentofAllFiles($files){
        $filesPlain = [];

        /** @var \cs_file_item $file */
        foreach($files as $file){
            if (!$file->isDeleted()) {
                $fileName = $this->projectDir . '/' . $file->getFilepath();
                $contentPlain = $this->file2TextService->convert($fileName);
                if(!empty($contentPlain)){
                    $filesPlain[] = $contentPlain;
                }
            }
        }

        return $filesPlain;

    }

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
                            'filesRaw'=> $filesPlain,
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

    public function addSteps($event)
    {
        $todoManager = $this->legacyEnvironment->getTodoManager();
        $todo = $todoManager->getItem($event->getObject()->getItemId());

        if ($todo) {
            // when building the index from the CLI command, the context ID is not populated, thus we set it here explicitly
            $this->legacyEnvironment->setCurrentContextID($todo->getContextID());

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
                            'filesRaw'=> $filesPlain,
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
                            'filesRaw'=> $filesPlain,
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

    public function addParentRoomIds($event)
    {
        $roomManager = $this->legacyEnvironment->getRoomManager();
        $room = $roomManager->getItem($event->getObject()->getItemId());

        if ($room) {
            if ($room instanceof \cs_project_item) {
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

    public function addCreator($event)
    {
        $item = $this->getItemCached($event->getObject()->getItemId());

        if ($item) {
            if ($item->getItemType() !== CS_USER_TYPE){
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
        $item = $this->getItemCached($event->getObject()->getItemId());

        if ($item) {
            if ($item->getItemType() !== CS_USER_TYPE){
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

    private function getItemCached($itemId): ?\cs_item
    {
        // cache wiping
        if (sizeof($this->itemCache) >= 10000) {
            $this->itemCache = [];
        }

        // cache hit
        if (isset($this->itemCache[$itemId])) {
            return $this->itemCache[$itemId];
        }

        // cache miss
        $itemManager = $this->legacyEnvironment->getItemManager();
        $item = $itemManager->getItem($itemId);

        if ($item) {
            $this->itemCache[$itemId] = $item;
            return $item;
        }

        return null;
    }
}