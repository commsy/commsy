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

namespace App\Twig\Components;

use App\Twig\Components\Model\FeedItemInterface;
use App\Twig\Dto\FeedItemDto;
use cs_item;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;
use Symfony\UX\TwigComponent\Attribute\PreMount;

#[AsTwigComponent('v3:feed')]
final class FeedComponent
{
    public bool $loadMore = true;
    public string $id;

    /**
     * @var cs_item[]
     */
    public array $items;

    public array $readerList;

    #[PreMount]
    public function preMount(array $data): array
    {
        $resolver = new OptionsResolver();

        $resolver->setDefaults(['loadMore' => true]);
        $resolver->setRequired(['id', 'items', 'readerList']);
        //$resolver->setAllowedTypes('items', 'FeedItemInterface[]');

        return $resolver->resolve($data);
    }
}
