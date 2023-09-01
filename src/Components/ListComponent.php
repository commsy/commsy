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

use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;
use Symfony\UX\TwigComponent\Attribute\PreMount;

#[AsTwigComponent('v2:list')]
final class ListComponent
{
    public bool $striped = true;

    public iterable $elements;

    #[PreMount]
    public function preMount(array $data): array
    {
        $resolver = new OptionsResolver();

        $resolver->setRequired([
            'elements',
        ]);

        $resolver->setDefaults([
            'striped' => true,
        ]);

        return $resolver->resolve($data);
    }
}
