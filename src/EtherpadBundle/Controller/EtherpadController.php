<?php

namespace EtherpadBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

class EtherpadController extends Controller
{
    /**
     * @Template()
     */
    public function indexAction($materialId, $roomId)
    {
        $materialService = $this->get('commsy_legacy.material_service');
        $etherpadService = $this->get('commsy.etherpad_service');
        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();

        $material = $materialService->getMaterial($materialId);

        $currentUser = $legacyEnvironment->getCurrentUserItem();

        # Init etherpad
        $client = $etherpadService->getClient();
        $author = $client->createAuthorIfNotExistsFor($currentUser->getItemId());

        $group = $client->createGroupIfNotExistsFor($roomId);

        # If pad does not exist, create one
        // $pad = $client->createGroupPad($group->groupID, $materialId);

        // $material->setEtherpadEditorID($pad->padID);
        // $materialItem->save();
        
        # create etherpad session with author and group
        $timestamp = time() + (60 * 60 * 24);
        $session = $client->createSession($group->groupID, $author->authorID, $timestamp);
        setcookie('sessionID', $session->sessionID, $timestamp, '/');

        return array('materialId' => $materialId, 'etherpadId' => $material->getEtherpadEditorID());
    }
}
