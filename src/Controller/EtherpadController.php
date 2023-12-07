<?php

/*
 * This file is part of CommSy.
 *
 * (c) Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
 * Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
 * Edouard Simon, Monique Strauss, Jose Mauel Gonzalez Vazquez, Johannes Schultze
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace App\Controller;

use App\Services\EtherpadService;
use App\Services\LegacyEnvironment;
use App\Utils\MaterialService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class EtherpadController extends AbstractController
{
    public function index(
        int $materialId,
        int $roomId,
        Request $request,
        MaterialService $materialService,
        EtherpadService $etherpadService,
        LegacyEnvironment $legacyEnvironment
    ): Response {
        $currentUser = $legacyEnvironment->getEnvironment()->getCurrentUserItem();

        $material = $materialService->getMaterial($materialId);

        // Init etherpad
        $client = $etherpadService->getClient();
        $author = $client->createAuthorIfNotExistsFor($currentUser->getItemId(), $currentUser->getFullname());

        $group = $client->createGroupIfNotExistsFor($roomId);

        // id lookup
        $pads = $client->listPads($group->groupID);

        // If a pad for the current material does not exist, create one
        if (null !== $material) {
            if (!$material->getEtherpadEditorID() || !in_array($material->getEtherpadEditorID(), $pads->padIDs)) {
                // plain material id vs. material id + random string?
                $pad = $client->createGroupPad($group->groupID, $materialId, '');

                $material->setEtherpadEditorID($pad->padID);
                $material->save();

                // Set content
                if (!empty($material->getDescription())) {
                    $client->setHTML($material->getEtherpadEditorID(), $material->getDescription());
                }
            }
        }

        // create etherpad session with author and group
        $timestamp = time() + (60 * 60 * 24);
        $session = $client->createSession($group->groupID, $author->authorID, $timestamp);
        setcookie('sessionID', (string) $session->sessionID, ['expires' => $timestamp, 'path' => '/', 'domain' => '.'.$request->getHost()]);

        $fs = new Filesystem();
        $baseUrl = $etherpadService->getBaseUrl();

        if (!$fs->isAbsolutePath($baseUrl)) {
            $baseUrl = $request->getBaseUrl().'/'.$baseUrl;
        }

        return $this->render('etherpad/index.html.twig', [
            'materialId' => $materialId,
            'etherpadId' => $material->getEtherpadEditorID(),
            'baseUrl' => $baseUrl,
        ]);
    }
}
