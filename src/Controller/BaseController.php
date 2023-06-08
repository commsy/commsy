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

use App\Action\Mark\CategorizeAction;
use App\Action\Mark\HashtagAction;
use App\Form\Type\XhrActionOptionsType;
use App\Form\Type\XhrCategorizeActionOptionsType;
use App\Http\JsonHTMLResponse;
use App\Services\LegacyEnvironment;
use App\Utils\ItemService;
use App\Utils\LabelService;
use App\Utils\ReaderService;
use App\Utils\RoomService;
use cs_environment;
use cs_item;
use cs_room_item;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Service\Attribute\Required;
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
     * @var LabelService
     */
    protected $labelService;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    #[Required]
    public function setItemService(ItemService $itemService): void
    {
        $this->itemService = $itemService;
    }

    #[Required]
    public function setLegacyEnvironment(LegacyEnvironment $legacyEnvironment): void
    {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
    }

    #[Required]
    public function setReaderService(ReaderService $readerService): void
    {
        $this->readerService = $readerService;
    }

    #[Required]
    public function setLabelService(LabelService $labelService): void
    {
        $this->labelService = $labelService;
    }

    #[Required]
    public function setTranslator(TranslatorInterface $translator): void
    {
        $this->translator = $translator;
    }

    #[Required]
    public function setEventDispatcher(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    #[Required]
    public function setRoomService(
        RoomService $service
    ) {
        $this->roomService = $service;
    }

    /**
     * @return mixed
     *
     * @throws Exception
     */
    public function handleCategoryActionOptions(
        Request $request,
        CategorizeAction $action,
        int $roomId
    ) {
        $categories = $this->labelService->getCategories($roomId, true);

        // NOTE: CategorizeAction.ts extracts the chosen choices and XHRAction->execute() stores them as request 'payload'
        $payload = $request->request->get('payload', []);
        $choices = $payload['choices'] ?? [];

        // provide a form with custom form options that are required for this action
        $form = $this->createForm(XhrCategorizeActionOptionsType::class, $choices, [
            'label' => $this->translator->trans('categories', [], 'room'),
            'choices' => $categories,
        ]);

        // the request doesn't have the typical structure required by handleRequest() so we handle the request manually
        if ($request->isMethod(Request::METHOD_POST) && !empty($choices)) {
            $form->submit(['choices' => $choices]);

            if ($form->isSubmitted() && $form->isValid()) {
                $categoryChoices = $form->get('choices')->getData();
                $action->setCategoryIds($categoryChoices);

                // execute action
                $room = $this->getRoom($roomId);
                $items = $this->getItemsForActionRequest($room, $request);

                return $action->execute($room, $items);
            }
        }

        return new JsonHTMLResponse($this->renderView('marked/category.html.twig', [
            'form' => $form->createView(),
        ]));
    }

    /**
     * @return mixed
     *
     * @throws Exception
     */
    public function handleHashtagActionOptions(
        Request $request,
        HashtagAction $action,
        int $roomId
    ) {
        $hashtags = $this->labelService->getHashtags($roomId);

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
     * @throws Exception
     */
    protected function getItemsForActionRequest(
        cs_room_item $room,
        Request $request): array
    {
        // input processing
        if (!$request->request->has('action')) {
            throw new Exception('no action provided');
        }
        $action = $request->request->get('action');

        $selectAll = false;
        if ($request->request->has('selectAll')) {
            $selectAll = 'true' === $request->request->get('selectAll');
        }

        $positiveItemIds = [];
        $negativeItemIds = [];
        if (!$selectAll) {
            if (!$request->request->has('positiveItemIds')) {
                throw new Exception('select all is not set, but no "positiveItemIds" were provided');
            }

            $positiveItemIds = $request->request->all('positiveItemIds');
        } else {
            if ($request->request->has('negativeItemIds')) {
                $negativeItemIds = $request->request->all('negativeItemIds');
            }
        }

        // TODO: This is a workaround for copying a single entry from detail view when accessing as external viewer
        // The implementation of getItemsByFilterConditions() should not rely on the context if we already know
        // the exact item ids we are working with
        if (!empty($positiveItemIds) && $positiveItemIds[0]) {
            $itemTemp = $positiveItemIds[0];
            $itemTemp = $this->itemService->getTypedItem($itemTemp);
            if ($itemTemp->getContextID() !== $room->getItemID()) {
                $room = $this->getRoom($itemTemp->getContextID());
            }
        }

        // determine items to proceed on
        /** @var cs_item[] $items */
        $items = $this->getItemsByFilterConditions($request, $room, $selectAll, $positiveItemIds);
        if ($selectAll) {
            $items = array_filter($items, fn (cs_item $item) => !in_array($item->getItemId(), $negativeItemIds));
        }

        return $items;
    }

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
