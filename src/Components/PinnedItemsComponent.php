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

        return $resolver->resolve($data);
    }

    public function getPinnedItems(): iterable
    {
        $items = $this->itemRepository->getPinnedItemsByRoomId($this->roomId);

        $typedItems = array_map(fn ($item) => $this->itemService->getTypedItem($item->getItemID()), $items);

        return $typedItems;
    }
}
