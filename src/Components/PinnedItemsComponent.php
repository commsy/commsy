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
        return $this->itemService->getPinnedItems($this->roomId, $this->itemTypes);
    }
}
