<?php
/**
 * Created by PhpStorm.
 * User: cschoenf
 * Date: 27.01.18
 * Time: 11:40
 */

namespace App\Controller;


use App\Action\ActionFactory;
use App\Action\Mark\HashtagAction;
use App\Form\Type\XhrActionOptionsType;
use App\Http\JsonHTMLResponse;
use App\Services\LegacyEnvironment;
use App\Utils\ItemService;
use App\Utils\ReaderService;
use App\Utils\RoomService;
use cs_environment;
use cs_item;
use cs_room_item;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;


abstract class BaseController extends AbstractController
{

    /**
     * @var ItemService
     */
    protected $itemService;

    /**
     * @var cs_environment
     */
    protected $legacyEnvironment;

    /**
     * @var RoomService
     */
    protected $roomService;

    /**
     * @var ReaderService
     */
    protected $readerService;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @required
     * @param ItemService $itemService
     */
    public function setItemService(ItemService $itemService): void
    {
        $this->itemService = $itemService;
    }

    /**
     * @required
     * @param LegacyEnvironment $legacyEnvironment
     */
    public function setLegacyEnvironment(LegacyEnvironment $legacyEnvironment): void
    {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
    }

    /**
     * @required
     * @param ReaderService $readerService
     */
    public function setReaderService(ReaderService $readerService): void
    {
        $this->readerService = $readerService;
    }

    /**
     * @required
     * @param TranslatorInterface $translator
     */
    public function setTranslator(TranslatorInterface $translator): void
    {
        $this->translator = $translator;
    }

    /**
     * @required
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function setEventDispatcher(EventDispatcherInterface $eventDispatcher){
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @required
     * @param RoomService $service
     */
    public function setRoomService(
        RoomService $service
    ) {
        $this->roomService = $service;
    }

    /**
     * @param Request $request
     * @param HashtagAction $action
     * @param ItemController $itemController
     * @param int $roomId
     * @return mixed
     * @throws Exception
     */
    public function handleHashtagActionOptions(
        Request $request,
        HashtagAction $action,
        ItemController $itemController,
        int $roomId
    ) {
        $hashtags = $itemController->getHashtags($roomId, $this->legacyEnvironment);

        // NOTE: HashtagAction.ts extracts the chosen choices and XHRAction->execute() stores them as request 'payload'
        $payload = $request->request->get('payload', []);
        $choices = $payload['choices'] ?? [];

        // provide a form with custom form options that are required for this action
        $form = $this->createForm(XhrActionOptionsType::class, $choices, [
            'label' => $this->translator->trans('hashtags', [], 'room'),
            'choices' => $hashtags,
        ]);

        // the request doesn't have the typical structure required by handleRequest() so we handle the request manually
        if ($request->isMethod(Request::METHOD_POST) && !empty($choices)) {
            $form->submit(['choices' => $choices]);

            if ($form->isSubmitted() && $form->isValid()) {
                $hashtagChoices = $form->get('choices')->getData();
                $action->setHashtagIds($hashtagChoices);

                // execute action
                $room = $this->getRoom($roomId);
                $items = $this->getItemsForActionRequest($room, $request);

                return $action->execute($room, $items);
            }
        }

        return new JsonHTMLResponse($this->renderView('marked/hashtag.html.twig', [
            'form' => $form->createView(),
        ]));
    }

    /**
     * @param cs_room_item $room
     * @param Request $request
     * @return array
     * @throws Exception
     */
    protected function getItemsForActionRequest(
        cs_room_item $room,
        Request $request) : array
    {
        // input processing
        if (!$request->request->has('action')) {
            throw new Exception('no action provided');
        }
        $action = $request->request->get('action');

        $selectAll = false;
        if ($request->request->has('selectAll')) {
            $selectAll = $request->request->get('selectAll') === 'true';
        }

        $positiveItemIds = [];
        $negativeItemIds = [];
        if (!$selectAll) {
            if (!$request->request->has('positiveItemIds')) {
                throw new Exception('select all is not set, but no "positiveItemIds" were provided');
            }

            $positiveItemIds = $request->request->get('positiveItemIds');
        } else {
            if ($request->request->has('negativeItemIds')) {
                $negativeItemIds = $request->request->get('negativeItemIds');
            }
        }

        // determine items to proceed on
        /** @var cs_item[] $items */
        $items = $this->getItemsByFilterConditions($request, $room, $selectAll, $positiveItemIds);
        if ($selectAll) {
            $items = array_filter($items, function (cs_item $item) use ($negativeItemIds) {
                return !in_array($item->getItemId(), $negativeItemIds);
            });
        }

        return $items;
    }

    /**
     * @param int $roomId
     * @return cs_room_item
     */
    protected function getRoom(int $roomId): cs_room_item
    {
        /** @var cs_room_item $roomItem */
        $roomItem = $this->roomService->getRoomItem($roomId);

        if (!$roomItem) {
            throw $this->createNotFoundException('The requested room does not exist');
        }

        return $roomItem;
    }

    abstract protected function getItemsByFilterConditions(Request $request, $roomItem, $selectAll, $itemIds = []);
}