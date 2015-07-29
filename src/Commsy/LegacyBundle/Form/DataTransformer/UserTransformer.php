<?php
namespace Commsy\LegacyBundle\Form\DataTransformer;

use Commsy\LegacyBundle\Services\LegacyEnvironment;
use CommSy\LegacyBundle\Form\DataTransformer\DataTransformerInterface;

class UserTransformer implements DataTransformerInterface
{
    private $legacyEnvironment;

    public function __construct(LegacyEnvironment $legacyEnvironment)
    {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
    }

    /**
     * Transforms a cs_room_item object to an array
     *
     * @param cs_room_item $roomItem
     * @return array
     */
    public function transform($userItem)
    {
        $userData = array();

        if ($userItem) {
            $userData['itemId'] = $userItem->getItemId();
            $userData['userId'] = $userItem->getUserId();
        }

        return $userData;
    }

    /**
     * Applies an array of data to an existing object
     *
     * @param object $roomObject
     * @param array $roomData
     * @return cs_room_item|null
     * @throws TransformationFailedException if room item is not found.
     */
    public function applyTransformation($userObject, $userData)
    {
        return $userObject;
    }
}