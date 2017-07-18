<?php

namespace CommsyBundle\EventListener;

use Commsy\LegacyBundle\Services\LegacyEnvironment;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

use FOS\ElasticaBundle\Event\TransformEvent;

class ElasticCustomPropertyListener implements EventSubscriberInterface
{
    private $legacyEnvironment;

    public function __construct(LegacyEnvironment $legacyEnvironment)
    {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
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
    }

    private function addHashtags(TransformEvent $event)
    {
        $itemManager = $this->legacyEnvironment->getItemManager();
        $item = $itemManager->getItem($event->getObject()->getItemId());

        $hashtags = $item->getBuzzwordList();
        if ($hashtags->isNotEmpty()) {
            $objectHashtags = [];

            $hashtag = $hashtags->getFirst();
            while ($hashtag) {
                $objectHashtags[] = $hashtag->getName();

                $hashtag = $hashtags->getNext();
            }

            if (!empty($objectHashtags)) {
                $event->getDocument()->set('hashtags', $objectHashtags);
            }
        }
    }

    private function addTags(TransformEvent $event)
    {
        $itemManager = $this->legacyEnvironment->getItemManager();
        $item = $itemManager->getItem($event->getObject()->getItemId());

        $tags = $item->getTagList();
        if ($tags->isNotEmpty()) {
            $objectTags = [];

            $tag = $tags->getFirst();
            while ($tag) {
                $objectTags[] = $tag->getTitle();

                $tag = $tags->getNext();
            }

            if (!empty($objectTags)) {
                $event->getDocument()->set('tags', $objectTags);
            }
        }
    }

    private function addAnnotations(TransformEvent $event)
    {
        $itemManager = $this->legacyEnvironment->getItemManager();
        $item = $itemManager->getItem($event->getObject()->getItemId());

        $annotations = $item->getAnnotationList();
        if ($annotations->isNotEmpty()) {
            $objectTags = [];

            $annotation = $annotations->getFirst();
            while ($annotation) {
                $objectTags[] = $annotation->getDescription();

                $annotation = $annotations->getNext();
            }

            if (!empty($objectTags)) {
                $event->getDocument()->set('annotations', $objectTags);
            }
        }
    }
}