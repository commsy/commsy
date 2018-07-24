<?php
/**
 * Created by PhpStorm.
 * User: cschoenf
 * Date: 03.07.18
 * Time: 16:05
 */

namespace CommsyBundle\Action\Download;


use Commsy\LegacyBundle\Utils\DownloadService;
use CommsyBundle\Action\ActionInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class DownloadAction implements ActionInterface
{
    /**
     * @var DownloadService
     */
    private $downloadService;

    public function __construct(DownloadService $downloadService)
    {
        $this->downloadService = $downloadService;
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