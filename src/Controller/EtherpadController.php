<?php

namespace App\Controller;

use App\Services\EtherpadService;
use App\Services\LegacyEnvironment;
use App\Utils\MaterialService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;

class EtherpadController extends AbstractController
{
    /**
     * @Template()
     * @param Request $request
     * @param MaterialService $materialService
     * @param EtherpadService $etherpadService
     * @param LegacyEnvironment $environment
     * @param int $materialId
     * @param int $roomId
     * @return array
     */
    public function indexAction(
        Request $request,
        MaterialService $materialService,
        EtherpadService $etherpadService,
        LegacyEnvironment $environment,
        int $materialId,
        int $roomId
    ) {
        $legacyEnvironment = $environment->getEnvironment();
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
        setcookie('sessionID', $session->sessionID, $timestamp, '/');

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
