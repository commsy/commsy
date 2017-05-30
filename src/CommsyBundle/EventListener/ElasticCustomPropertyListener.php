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

    public function addHashtagsProperty(TransformEvent $event)
    {
        $fields = $event->getFields();

        if (isset($fields['hashtags'])) {
            $buzzwordManager = $this->legacyEnvironment->getBuzzwordManager();
            $buzzwordManager->resetLimits();
            $buzzwordManager->setContextLimit($event->getObject()->getContextId());
            $buzzwordManager->setTypeLimit('buzzword');
            $buzzwordManager->select();

            $buzzwordList = $buzzwordManager->get();
            if ($buzzwordList->isNotEmpty()) {
                $objectHashtags = [];

                $buzzword = $buzzwordList->getFirst();
                while ($buzzword) {
                    $linkedIds = $buzzword->getAllLinkedItemIDArrayLabelVersion();
                    if (in_array($event->getObject()->getItemId(), $linkedIds)) {
                        $objectHashtags[] = $buzzword->getName();
                    }

                    $buzzword = $buzzwordList->getNext();
                }

                if (!empty($objectHashtags)) {
                    $event->getDocument()->set('hashtags', $objectHashtags);
                }
            }
        }

        if (isset($fields['tags'])) {
            $itemManager = $this->legacyEnvironment->getItemManager();
            $item = $itemManager->getItem($event->getObject()->getItemId());

            $tagList = $item->getTagList();
            if ($tagList->isNotEmpty()) {
                $objectTags = [];

                $tag = $tagList->getFirst();
                while ($tag) {
                    $objectTags[] = $tag->getTitle();

                    $tag = $tagList->getNext();
                }

                if (!empty($objectTags)) {
                    $event->getDocument()->set('tags', $objectTags);
                }
            }
        }
    }

    public static function getSubscribedEvents()
    {
        return [
            TransformEvent::POST_TRANSFORM => 'addHashtagsProperty'
        ];
    }
}