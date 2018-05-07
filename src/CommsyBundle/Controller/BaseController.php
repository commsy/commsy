<?php
/**
 * Created by PhpStorm.
 * User: cschoenf
 * Date: 27.01.18
 * Time: 11:40
 */

namespace CommsyBundle\Controller;


use CommsyBundle\Action\ActionFactory;
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

        $action = $this->get('commsy.action.factory')->make($action);
        $responsePayload = $action->executeAction($items);

        return new JsonDataResponse($responsePayload);











        // handle actions
        $translator = $this->get('translator');

        switch ($action) {

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