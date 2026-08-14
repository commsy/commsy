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

use App\Etherpad\EtherpadException;
use App\Etherpad\MaterialPad;
use App\Services\EtherpadService;
use App\Services\LegacyEnvironment;
use Psr\Log\LoggerInterface;
use App\Utils\MaterialService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class EtherpadController extends AbstractController
{
    public function __construct(private readonly LoggerInterface $logger)
    {
    }

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
        if (null === $material) {
            throw $this->createNotFoundException('no material found for id '.$materialId);
        }

        // A pad service that is down or refuses a call must not take the page
        // with it: the section says so and the rest stays usable.
        try {
            $client = $etherpadService->getClient();
            $authorId = $client->createAuthorIfNotExistsFor(
                (string) $currentUser->getItemId(),
                $currentUser->getFullname()
            );

            // Group and pad are derived from the material, never remembered,
            // so they cannot drift from the item they belong to.
            $pad = MaterialPad::locate($client, $materialId);

            if (!in_array($pad->padId, $client->listPads($pad->groupId), true)) {
                try {
                    $client->createGroupPad($pad->groupId, MaterialPad::padName());
                } catch (EtherpadException $e) {
                    // Someone else created it between the listing and now.
                    if (!$e->meansPadAlreadyExists()) {
                        throw $e;
                    }
                }

                // Seed the fresh pad from what the material currently holds.
                if (!empty($material->getDescription())) {
                    $client->setHtml($pad->padId, $material->getDescription());
                }
            }

            // The session covers a whole group, which is why the group holds
            // this one material only — the right to be here was checked for
            // it alone.
            $timestamp = time() + (60 * 60 * 24);
            $sessionId = $client->createSession($pad->groupId, $authorId, $timestamp);
        } catch (EtherpadException $e) {
            $this->logger->error('Etherpad is unavailable, showing the pad section as failed', [
                'materialId' => $materialId,
                'exception' => $e,
            ]);

            return $this->render('etherpad/unavailable.html.twig');
        }

        setcookie('sessionID', (string) $sessionId, [
            'expires' => $timestamp,
            'path' => '/', 'domain' => '.' . $request->getHost(),
        ]);

        $fs = new Filesystem();
        $baseUrl = $etherpadService->getBaseUrl();

        if (!$fs->isAbsolutePath($baseUrl)) {
            $baseUrl = $request->getBaseUrl() . '/' . $baseUrl;
        }

        return $this->render('etherpad/index.html.twig', [
            'materialId' => $materialId,
            'etherpadId' => $pad->padId,
            'baseUrl' => $baseUrl,
        ]);
    }
}
