<?php

namespace App\Controller;

use App\Services\EtherpadService;
use App\Services\LegacyEnvironment;
use App\Utils\MaterialService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;

class EtherpadController extends Controller
{
    /**
     * @Template()
     */
    public function indexAction(
        $materialId,
        $roomId,
        Request $request,
        MaterialService $materialService,
        EtherpadService $etherpadService,
        LegacyEnvironment $legacyEnvironment
    ) {
        $currentUser = $legacyEnvironment->getEnvironment()->getCurrentUserItem();

        $material = $materialService->getMaterial($materialId);

        # Init etherpad
        $client = $etherpadService->getClient();
        $author = $client->createAuthorIfNotExistsFor($currentUser->getItemId(), $currentUser->getFullname());

        $group = $client->createGroupIfNotExistsFor($roomId);

        // id lookup
        $pads = $client->listPads($group->groupID);

        # If a pad for the current material does not exist, create one
        if ($material !== null) {
            if (!$material->getEtherpadEditorID() || !in_array($material->getEtherpadEditorID(), $pads->padIDs)) {
                // plain material id vs. material id + random string?
                $pad = $client->createGroupPad($group->groupID, $materialId, '');

                $material->setEtherpadEditorID($pad->padID);
                $material->save();
            }
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
