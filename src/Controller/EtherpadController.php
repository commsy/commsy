<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;

class EtherpadController extends Controller
{
    /**
     * @Template()
     */
    public function indexAction($materialId, $roomId, Request $request)
    {
        $materialService = $this->get('commsy_legacy.material_service');
        $etherpadService = $this->get('commsy.etherpad_service');
        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();

        $material = $materialService->getMaterial($materialId);

        $currentUser = $legacyEnvironment->getCurrentUserItem();

        # Init etherpad
        $client = $etherpadService->getClient();
        $author = $client->createAuthorIfNotExistsFor($currentUser->getItemId(), $currentUser->getFullname());

        $group = $client->createGroupIfNotExistsFor($roomId);

        // id lookup
        $pads = $client->listPads($group->groupID);

        # If a pad for the current material does not exist, create one
        if (!$material->getEtherpadEditorID() || !in_array($material->getEtherpadEditorID(), $pads->padIDs)) {

            // plain material id vs. material id + random string?
            $pad = $client->createGroupPad($group->groupID, $materialId, '');

            $material->setEtherpadEditorID($pad->padID);
            $material->save();

            // if etherpadid is already set, but pad doesnt exist
            // if ($material->getEtherpadEditorID()) {
            //     // set material description
            //     $client->setText($pad->padID, $material->getDescription());
                
            // }
        }
        
        # create etherpad session with author and group
        $timestamp = time() + (60 * 60 * 24);
        $session = $client->createSession($group->groupID, $author->authorID, $timestamp);
        setcookie('sessionID', $session->sessionID, $timestamp, '/', '.' . $request->getHost());

        $fs = new Filesystem();
        $baseUrl = $etherpadService->getBaseUrl();

        if (!$fs->isAbsolutePath($baseUrl)) {
            $baseUrl = $request->getBaseUrl() . '/' . $baseUrl;
        }

        return [
            'materialId' => $materialId, 
            'etherpadId' => $material->getEtherpadEditorID(),
            'baseUrl' => $baseUrl,
        ];
    }
}
