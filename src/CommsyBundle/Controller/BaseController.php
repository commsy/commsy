<?php
/**
 * Created by PhpStorm.
 * User: cschoenf
 * Date: 27.01.18
 * Time: 11:40
 */

namespace CommsyBundle\Controller;


use CommsyBundle\Http\JsonDataResponse;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

abstract class BaseController extends Controller
{
    /**
     * @throws \Exception
     */
    public function feedActionAction($roomId, Request $request)
    {
        // input processing
        if (!$request->request->has('action')) {
            throw new \Exception('no action provided');
        }
        $action = $request->request->get('action');

        $selectAll = false;
        if ($request->request->has('selectAll')) {
            $selectAll = $request->request->get('selectAll') === 'true';
        }

        $itemIds = [];
        if (!$selectAll) {
            if (!$request->request->has('itemIds')) {
                throw new \Exception('select all is not set, but no ids were provided');
            }

            $itemIds = $request->request->get('itemIds');
        }

        $roomService = $this->get('commsy_legacy.room_service');

        /** @var \cs_room_item $roomItem */
        $roomItem = $roomService->getRoomItem($roomId);

        if (!$roomItem) {
            throw $this->createNotFoundException('The requested room does not exist');
        }

        // determine items to proceed on
        /** @var \cs_item[] $items */
        $items = $this->getItemsByFilterConditions($request, $roomItem, $selectAll, $itemIds);

        // handle actions
        $translator = $this->get('translator');

        switch ($action) {
            case 'markread':
                $itemService = $this->get('commsy_legacy.item_service');
                $itemService->markRead($items);

                $message = '<i class=\'uk-icon-justify uk-icon-medium uk-icon-check-square-o\'></i> ' . $translator->transChoice('marked %count% entries as read', count($items), array('%count%' => count($items)));

                break;

            case 'copy':
                $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();
                $sessionItem = $legacyEnvironment->getSessionItem();

                $currentClipboardIds = array();
                if ($sessionItem->issetValue('clipboard_ids')) {
                    $currentClipboardIds = $sessionItem->getValue('clipboard_ids');
                }

                foreach ($items as $item) {
                    if (!in_array($item->getItemID(), $currentClipboardIds)) {
                        $currentClipboardIds[] = $item->getItemID();
                        $sessionItem->setValue('clipboard_ids', $currentClipboardIds);
                    }
                }

                $sessionManager = $legacyEnvironment->getSessionManager();
                $sessionManager->save($sessionItem);

                return new JsonDataResponse([
                    'message' => '<i class=\'uk-icon-justify uk-icon-medium uk-icon-copy\'></i> ' . $translator->transChoice('%count% copied entries', count($items), [
                        '%count%' => count($items),
                    ]),
                    'count' => count($currentClipboardIds),
                ]);

                break;

            case 'save':
                $downloadService = $this->get('commsy_legacy.download_service');

                $ids = [];
                foreach ($items as $item) {
                    $ids[] = $item->getItemID();
                }

                $zipFile = $downloadService->zipFile($roomId, $ids);

                $response = new BinaryFileResponse($zipFile);
                $response->deleteFileAfterSend(true);

                $filename = 'CommSy_Save.zip';
                $contentDisposition = $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $filename);
                $response->headers->set('Content-Disposition', $contentDisposition);

                return $response;

                break;

            case 'delete':
                $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();
                $currentUser = $legacyEnvironment->getCurrentUserItem();
                foreach ($items as $item) {
                    if ($item->mayEdit($currentUser)) {
                        $item->delete();
                    }
                }

                $message = '<i class=\'uk-icon-justify uk-icon-medium uk-icon-trash-o\'></i> ' . $translator->transChoice('%count% deleted entries', count($items), array('%count%' => count($items)));

                break;

            default:
                $message = '<i class=\'uk-icon-justify uk-icon-medium uk-icon-bolt\'></i> ' . $translator->trans('action error');
                break;
        }

        return new JsonDataResponse([
            'message' => $message,
        ]);
    }

    abstract protected function getItemsByFilterConditions(Request $request, $roomItem, $selectAll, $itemIds = []);
}