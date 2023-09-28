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

namespace App\Components;

use App\Repository\ItemRepository;
use App\Utils\ItemService;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;
use Symfony\UX\TwigComponent\Attribute\PreMount;

#[AsTwigComponent('pinned_items')]
final class PinnedItemsComponent
{
    public int $roomId;
    public array $itemTypes = [];

    public function __construct(
        private readonly ItemRepository $itemRepository,
        private readonly ItemService $itemService,
    ) {
    }

    #[PreMount]
    public function preMount(array $data): array
    {
        $resolver = new OptionsResolver();

        $resolver->setRequired([
            'roomId',
        ]);

        $resolver->setDefaults([
            'itemTypes' => [],
        ]);

        return $resolver->resolve($data);
    }

    public function getPinnedItems(): iterable
    {
        if (empty($this->itemTypes)) {
            $items = $this->itemRepository->getPinnedItemsByRoomId($this->roomId);
        } else {
            $items = $this->itemRepository->getPinnedItemsByRoomIdAndType($this->roomId, $this->itemTypes);
        }

        $typedItems = array_map(fn ($item) => $this->itemService->getTypedItem($item->getItemID()), $items);

        if (!empty($this->itemTypes)) {
            // for CS_LABEL_TYPE items in $typedItems, filter out label types not given in itemTypes
            $typedItems = array_filter($typedItems, function ($typedItem) {
                if ($typedItem->getType() === CS_LABEL_TYPE && !in_array($typedItem->getLabelType(), $this->itemTypes)) {
                    return false;
                }

                return true;
            });
        }

        return $typedItems;
    }
}
