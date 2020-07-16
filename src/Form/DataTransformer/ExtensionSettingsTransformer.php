<?php
namespace App\Form\DataTransformer;

use App\Utils\RoomService;
use App\Services\LegacyEnvironment;

use App\Services\MediawikiService;

class ExtensionSettingsTransformer implements DataTransformerInterface
{
    private $legacyEnvironment;
    private $roomService;
    private $mediaWiki;

    private $mediaWikiEnabled = false;

    public function __construct(LegacyEnvironment $legacyEnvironment, RoomService $roomService, MediawikiService $mediawiki, $mediawikiEnabled)
    {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
        $this->roomService = $roomService;
        $this->mediaWiki = $mediawiki;

        $this->mediaWikiEnabled = $mediawikiEnabled;
    }

    /**
     * Transforms a cs_room_item object to an array
     *
     * @param cs_room_item $roomItem
     * @return array
     */
    public function transform($roomItem)
    {
        $roomData = array();

        if ($roomItem) {

            $translator = $this->legacyEnvironment->getTranslationObject();

            $roomData['assessment'] = $roomItem->isAssessmentActive();
            $roomData['workflow'] = array();

            // traffic light options
            $traffic_light = array();
            $traffic_light['status_view']= $roomItem->withWorkflowTrafficLight();
            $traffic_light['default_status'] = $roomItem->getWorkflowTrafficLightDefault();
            if($roomItem->getWorkflowTrafficLightTextGreen() != ''){
               $traffic_light['green_text'] = $roomItem->getWorkflowTrafficLightTextGreen();
            } else {
               $traffic_light['green_text'] = $translator->getMessage('COMMON_WORKFLOW_TRAFFIC_LIGHT_TEXT_GREEN_DEFAULT');
            }
            if($roomItem->getWorkflowTrafficLightTextYellow() != ''){
               $traffic_light['yellow_text'] = $roomItem->getWorkflowTrafficLightTextYellow();
            } else {
               $traffic_light['yellow_text'] = $translator->getMessage('COMMON_WORKFLOW_TRAFFIC_LIGHT_TEXT_YELLOW_DEFAULT');
            }
            if($roomItem->getWorkflowTrafficLightTextRed() != ''){
               $traffic_light['red_text'] = $roomItem->getWorkflowTrafficLightTextRed();
            } else {
               $traffic_light['red_text'] = $translator->getMessage('COMMON_WORKFLOW_TRAFFIC_LIGHT_TEXT_RED_DEFAULT');
            }

            $roomData['workflow']['traffic_light'] = $traffic_light;
            $roomData['workflow']['resubmission'] = $roomItem->withWorkflowResubmission();
            $roomData['workflow']['validity'] = $roomItem->withWorkflowValidity();
            $roomData['workflow']['reader'] = $roomItem->withWorkflowReader();
            $roomData['workflow']['reader_group'] = ($roomItem->getWorkflowReaderGroup() === '1');
            $roomData['workflow']['reader_person'] = ($roomItem->getWorkflowReaderPerson() === '1');
            $roomData['workflow']['resubmission_show_to'] = $roomItem->getWorkflowReaderShowTo();

            $roomData['wikiEnabled'] = $roomItem->isWikiEnabled();
            $roomData['createUserRooms'] = ($roomItem->isProjectRoom()) ? $roomItem->getShouldCreateUserRooms() : false;
        }

        return $roomData;
    }

    /**
     * Save extension settings
     *
     * @param object $roomObject
     * @param array $roomData
     * @return cs_room_item|null
     * @throws TransformationFailedException if room item is not found.
     */
    public function applyTransformation($roomObject, $roomData)
    {
        if ( isset($roomData['dates_status']) ) {
            $roomObject->setDatesPresentationStatus($roomData['dates_status']);
        }

        if($roomData['assessment']) {
            $roomObject->setAssessmentActive();
        } else {
            $roomObject->setAssessmentInactive();
        }

        if ($roomObject->isProjectRoom()) {
            $roomObject->setShouldCreateUserRooms($roomData['createUserRooms']);
        }

        $isset_workflow = false;

        $workflow = $roomData['workflow'];

        $traffic_light = $workflow['traffic_light'];

        if ( $traffic_light['status_view'] ) {
           $roomObject->setWithWorkflowTrafficLight();
           $isset_workflow = true;
        } else {
           $roomObject->setWithoutWorkflowTrafficLight();
        }

        if ( $workflow['resubmission']) {
           $roomObject->setWithWorkflowResubmission();
           $isset_workflow = true;
        } else {
           $roomObject->setWithoutWorkflowResubmission();
        }

        if ( $workflow['validity'] ) {
           $roomObject->setWithWorkflowValidity();
           $isset_workflow = true;
        } else {
           $roomObject->setWithoutWorkflowValidity();
        }

        if ( $workflow['reader']) {
           $roomObject->setWithWorkflowReader();
           $isset_workflow = true;
        } else {
           $roomObject->setWithoutWorkflowReader();
        }

        if($isset_workflow){
           $roomObject->setWithWorkflow();
        } else {
           $roomObject->setWithoutWorkflow();
        }

        $roomObject->setWorkflowTrafficLightDefault($traffic_light['default_status']);

        if ( isset($traffic_light['green_text']) and !empty($traffic_light['green_text'])) {
           $roomObject->setWorkflowTrafficLightTextGreen($traffic_light['green_text']);
        }
        if ( isset($traffic_light['yellow_text']) and !empty($traffic_light['yellow_text'])) {
           $roomObject->setWorkflowTrafficLightTextYellow($traffic_light['yellow_text']);
        }
        if ( isset($traffic_light['red_text']) and !empty($traffic_light['red_text'])) {
           $roomObject->setWorkflowTrafficLightTextRed($traffic_light['red_text']);
        }

        if ( $workflow['reader_group'] ) {
           $roomObject->setWithWorkflowReaderGroup();
        } else {
           $roomObject->setWithoutWorkflowReaderGroup();
        }
        if ( $workflow['reader_person'] ) {
           $roomObject->setWithWorkflowReaderPerson();
        } else {
           $roomObject->setWithoutWorkflowReaderPerson();
        }

        if ( isset($workflow['resubmission_show_to']) and !empty($workflow['resubmission_show_to'])) {
           $roomObject->setWorkflowReaderShowTo($workflow['resubmission_show_to']);
        }

        if ($this->mediaWikiEnabled) {
            if ($roomData['wikiEnabled']) {
                if ($this->mediaWiki->enableWiki($roomObject->getItemID())) {
                    $roomObject->setWikiEnabled(true);
                } else if ($this->mediaWiki->isWikiEnabled($roomObject->getItemID())) {
                    $roomObject->setWikiEnabled(true);
                } else {
                    $roomObject->setWikiEnabled(false);
                }
            } else {
                if ($this->mediaWiki->disableWiki($roomObject->getItemID())) {
                    $roomObject->setWikiEnabled(false);
                } else if (!$this->mediaWiki->isWikiEnabled($roomObject->getItemID())) {
                    $roomObject->setWikiEnabled(false);
                } else {
                    $roomObject->setWikiEnabled(true);
                }
            }
        }

        return $roomObject;
    }
}
