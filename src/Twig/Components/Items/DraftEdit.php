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

namespace App\Twig\Components\Items;

use App\Utils\ItemService;
use cs_context_item;
use cs_item;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveListener;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentToolsTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent]
final class DraftEdit extends AbstractController
{
    use DefaultActionTrait;
    use ComponentToolsTrait;

    #[LiveProp]
    public int $itemId;

    #[LiveProp]
    public array $waitForEvents = [];

    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly ItemService $itemService,
    ) {
    }

    #[LiveAction]
    public function saveDraft(): void
    {
        $item = $this->itemService->getItem($this->itemId);

        /** @var cs_context_item $room */
        $room = $item->getContextItem();

        /**
         * If the item has mandatory tags or categories, we emit an event to the respective component
         * to trigger a save operation. We wait for those to "answer" by an event themselves before
         * dispatching a browser event triggering the usual form submission.
         */
        $this->waitForEvents = [];
        if ($room->withBuzzwords() && $room->isBuzzwordMandatory()) {
            $this->waitForEvents[] = 'Tag:saved';
            $this->emit('DraftEdit:save', componentName: 'Links:TagComponent');
        }

        if ($room->withTags() && $room->isTagMandatory()) {
            $this->waitForEvents[] = 'Category:saved';
            $this->emit('DraftEdit:save', componentName: 'Links:CategoryComponent');
        }

        $this->dispatchIfResolved($item);
    }

    #[LiveListener('Links:TagComponent:saved')]
    public function onTagsSaved(): void
    {
        $this->waitForEvents = array_filter($this->waitForEvents, fn (string $event) => $event === 'Tag:saved');
        $item = $this->itemService->getItem($this->itemId);
        $this->dispatchIfResolved($item);
    }

    #[LiveListener('Links:CategoryComponent:saved')]
    public function onCategoriesSaved(): void
    {
        $this->waitForEvents = array_filter($this->waitForEvents, fn (string $event) => $event === 'Category:saved');
        $item = $this->itemService->getItem($this->itemId);
        $this->dispatchIfResolved($item);
    }

    #[LiveAction]
    public function cancelDraft(): RedirectResponse
    {
        $item = $this->itemService->getItem($this->itemId);
        $this->emit('draftCanceled');

        /** @noinspection PhpRouteMissingInspection */
        return match ($item->getItemType()) {
            'section', 'step', 'article' => $this->redirectToRoute("app_{$item->getItemType()}_detail", [
                'roomId' => $item->getContextID(),
                'itemId' => $item->getItemID(),
            ]),
            default => $this->redirectToRoute("app_{$item->getItemType()}_list", [
                'roomId' => $item->getContextID(),
            ]),
        };
    }

    private function dispatchIfResolved(cs_item $item): void
    {
        if (empty($this->waitForEvents)) {
            $this->dispatchBrowserEvent('draft:saved', [
                'itemType' => $item->getItemType(),
                'undraftUrl' => $this->urlGenerator->generate('app_item_undraft', [
                    'roomId' => $item->getContextID(),
                    'itemId' => $item->getItemID(),
                ])

            ]);
        }
    }
}
