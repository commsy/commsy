<?php
namespace App\Form\DataTransformer;

use App\Services\LegacyEnvironment;
use cs_environment;

class PrivateRoomTransformer extends AbstractTransformer
{
    protected $entity = 'privateroom';

    /**
     * @var cs_environment
     */
    private cs_environment $legacyEnvironment;

    public function __construct(LegacyEnvironment $legacyEnvironment)
    {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
    }

    /**
     * Transforms a cs_room_item object to an array
     *
     * @param \cs_privateroom_item $roomItem
     * @return array
     */
    public function transform($privateRoomItem)
    {
        $privateRoomData = array();
        if ($privateRoomItem) {
            $privateRoomData['newsletterStatus'] = $privateRoomItem->getPrivateRoomNewsletterActivity();
            if ($privateRoomItem->getCSBarShowWidgets() == '1') {
                $privateRoomData['widgetStatus'] = true;
            } else {
                $privateRoomData['widgetStatus'] = false;
            }

            if ($privateRoomItem->getCSBarShowCalendar() == '1') {
                $privateRoomData['calendarStatus'] = true;
            } else {
                $privateRoomData['calendarStatus'] = false;
            }

            if ($privateRoomItem->getCSBarShowStack() == '1') {
                $privateRoomData['stackStatus'] = true;
            } else {
                $privateRoomData['stackStatus'] = false;
            }

            if ($privateRoomItem->getCSBarShowOldRoomSwitcher() == '1') {
                $privateRoomData['switchRoomStatus'] = true;
            } else {
                $privateRoomData['switchRoomStatus'] = false;
            }

            if ($privateRoomItem->getPrivateRoomNewsletterActivity() == 'none') {
                $privateRoomData['newsletterStatus'] = '1';
            } elseif ($privateRoomItem->getPrivateRoomNewsletterActivity() == 'weekly') {
                $privateRoomData['newsletterStatus'] = '2';
            } elseif ($privateRoomItem->getPrivateRoomNewsletterActivity() == 'daily') {
                $privateRoomData['newsletterStatus'] = '3';
            }

            // Portfolio
            $privateRoomData['portfolio'] = $privateRoomItem->isPortfolioEnabled();

            // email to commsy
            $privateRoomData['emailToCommsy'] = $privateRoomItem->getEmailToCommSy();
            $privateRoomData['emailToCommsySecret'] = $privateRoomItem->getEmailToCommSySecret();
        }
        return $privateRoomData;
    }

    /**
     * Applies an array of data to an existing object
     *
     * @param \cs_privateroom_item $roomObject
     * @param array $roomData
     * @return cs_room_item|null
     * @throws TransformationFailedException if room item is not found.
     */
    public function applyTransformation($privateRoomObject, $privateRoomData)
    {
        if ($privateRoomObject) {
            if ($privateRoomData['widgetStatus'] == '1') {
                $privateRoomObject->setCSBarShowWidgets('1');
            } else {
                $privateRoomObject->setCSBarShowWidgets('-1');
            }

            if ($privateRoomData['calendarStatus'] == '1') {
                $privateRoomObject->setCSBarShowCalendar('1');
            } else {
                $privateRoomObject->setCSBarShowCalendar('-1');
            }

            if ($privateRoomData['stackStatus'] == '1') {
                $privateRoomObject->setCSBarShowStack('1');
            } else {
                $privateRoomObject->setCSBarShowStack('-1');
            }

            if ($privateRoomData['switchRoomStatus'] == '1') {
                $privateRoomObject->setCSBarShowOldRoomSwitcher('1');
            } else {
                $privateRoomObject->setCSBarShowOldRoomSwitcher('-1');
            }

            //TODO: Set language in portal / portalProxy
            if (isset($privateRoomData['language'])) {
                $privateRoomObject->setLanguage($privateRoomData['language']);
                $privateRoomObject->_environment->current_context = $privateRoomObject;

//                $this->legacyEnvironment->getCurrentContextItem()->setLanguage($privateRoomData['language']);
//                $portalManager = $this->legacyEnvironment->getPortalManager();
//                $portalManager->saveItem($this->legacyEnvironment->getCurrentContextItem());
//                $this->legacyEnvironment->getCurrentContextItem()->save();
//                $privateRoomObject->getContextItem()->setLanguage($privateRoomData['language']);
            }

            $set_to = 'none';
            if (isset($privateRoomData['newsletterStatus']) && !empty($privateRoomData['newsletterStatus'])) {
                if ($privateRoomData['newsletterStatus'] == '2') $set_to = 'weekly';
                elseif ($privateRoomData['newsletterStatus'] == '3') $set_to = 'daily';
            }
            $privateRoomObject->setPrivateRoomNewsletterActivity($set_to);

            // Portfolio
            $privateRoomObject->setPortfolioEnabled($privateRoomData['portfolio']);

            // email to commsy
            if (isset($privateRoomData['emailToCommsy'])) {
                if ($privateRoomData['emailToCommsy'] == '1') {
                    $privateRoomObject->setEmailToCommSy();
                } else {
                    $privateRoomObject->unsetEmailToCommSy();
                }
            }
            if (isset($privateRoomData['emailToCommsySecret'])) {
                $privateRoomObject->setEmailToCommSySecret($privateRoomData['emailToCommsySecret']);
            } else {
                $privateRoomObject->setEmailToCommSySecret('');
            }
        }
        return $privateRoomObject;
    }
}