<?php
namespace Commsy\LegacyBundle\Form\DataTransformer;

use Commsy\LegacyBundle\Services\LegacyEnvironment;
use Commsy\LegacyBundle\Form\DataTransformer\DataTransformerInterface;

class TodoTransformer implements DataTransformerInterface
{
    private $legacyEnvironment;

    public function __construct(LegacyEnvironment $legacyEnvironment)
    {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
    }

    /**
     * Transforms a cs_todo_item object to an array
     *
     * @param cs_todo_item $dateItem
     * @return array
     */
    public function transform($todoItem)
    {
        $todoData = array();

        if ($todoItem) {
            $todoData['title'] = $todoItem->getTitle();
            $todoData['description'] = $todoItem->getDescription();
            $todoData['permission'] = $todoItem->isPrivateEditing();
        }

        return $todoData;
    }

    /**
     * Applies an array of data to an existing object
     *
     * @param object $todoObject
     * @param array $todoData
     * @return cs_todo_item|null
     * @throws TransformationFailedException if room item is not found.
     */
    public function applyTransformation($todoObject, $todoData)
    {
        $todoObject->setTitle($todoData['title']);
        $todoObject->setDescription($todoData['description']);
        
        if ($todoData['permission']) {
            $todoObject->setPrivateEditing('0');
        } else {
            $todoObject->setPrivateEditing('1');
        }

        return $todoObject;
    }
}