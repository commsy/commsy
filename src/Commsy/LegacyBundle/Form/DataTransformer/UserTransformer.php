<?php
namespace Commsy\LegacyBundle\Form\DataTransformer;

use Commsy\LegacyBundle\Services\LegacyEnvironment;
use Commsy\LegacyBundle\Form\DataTransformer\DataTransformerInterface;

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
            $userData['userId'] = $userItem->getUserId();
            $userData['firstname'] = $userItem->getFirstname();
            $userData['lastname'] = $userItem->getLastname();
            $userData['language'] = $userItem->getLanguage();
            if ($userItem->getAutoSaveStatus() == '1') {
                $userData['autoSaveStatus'] = false;
            } else {
                $userData['autoSaveStatus'] = true;
            }
            $userData['title'] = $userItem->getTitle();
            $userData['dateOfBirth'] = new \DateTime($userItem->getBirthday());
            $userData['email'] = $userItem->getEmail();
            $userData['isEmailVisible'] = $userItem->isEmailVisible();
            $userData['phone'] = $userItem->getTelephone();
            $userData['mobile'] = $userItem->getCellularphone();
            $userData['street'] = $userItem->getStreet();
            $userData['zipCode'] = $userItem->getZipcode();
            $userData['city'] = $userItem->getCity();
            $userData['room'] = $userItem->getRoom();
            $userData['organisation'] = $userItem->getOrganisation();
            $userData['position'] = $userItem->getPosition();
            $userData['icq'] = $userItem->getICQ();
            $userData['msn'] = $userItem->getMSN();
            $userData['skype'] = $userItem->getSkype();
            $userData['yahoo'] = $userItem->getYahoo();
            $userData['jabber'] = $userItem->getJabber();
            $userData['homepage'] = $userItem->getHomepage();
            $userData['description'] = $userItem->getDescription();
            $userData['language'] = $userItem->getLanguage();
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
        if ($userObject) {
            $userObject->setUserId($userData['userId']);
            $userObject->setFirstname($userData['firstname']);
            $userObject->setLastname($userData['lastname']);
            $userObject->setLanguage($userData['language']);
            if ($userData['autoSaveStatus']) {
                $userObject->turnAutoSaveOn();
            } else {
                $userObject->turnAutoSaveOff();
            }
            $userObject->setTitle($userData['title']);
            $userObject->setBirthday($userData['dateOfBirth']->format('Y-m-d'));
            $userObject->setEmail($userData['email']);
            if ($userData['isEmailVisible']) {
                $userObject->setEmailVisible();
            } else {
                $userObject->setEmailNotVisible();
            }
            $userObject->setTelephone($userData['phone']);
            $userObject->setCellularphone($userData['mobile']);
            $userObject->setStreet($userData['street']);
            $userObject->setZipcode($userData['zipCode']);
            $userObject->setCity($userData['city']);
            $userObject->setRoom($userData['room']);
            $userObject->setOrganisation($userData['organisation']);
            $userObject->setPosition($userData['position']);
            $userObject->setICQ($userData['icq']);
            $userObject->setMSN($userData['msn']);
            $userObject->setSkype($userData['skype']);
            $userObject->setYahoo($userData['yahoo']);
            $userObject->setJabber($userData['jabber']);
            $userObject->setHomepage($userData['homepage']);
            $userObject->setDescription($userData['description']);
            $userObject->setLanguage($userData['language']);
        }
        return $userObject;
    }
}