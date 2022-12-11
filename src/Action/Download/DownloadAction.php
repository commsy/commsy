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

namespace App\Action\Download;

use App\Action\ActionInterface;
use App\Utils\DownloadService;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class DownloadAction implements ActionInterface
{
    public function __construct(private DownloadService $downloadService)
    {
    }

    public function execute(\cs_room_item $roomItem, array $items): Response
    {
        $ids = [];
        foreach ($items as $item) {
            $ids[] = $item->getItemID();
        }

        $zipFile = $this->downloadService->zipFile($roomItem->getItemId(), $ids);

        $response = new BinaryFileResponse($zipFile);
        $response->deleteFileAfterSend(true);

        $filename = 'CommSy_Save.zip';
        $contentDisposition = $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $filename);
        $response->headers->set('Content-Disposition', $contentDisposition);

        return $response;
    }
}
