<?php

namespace App\Components;

use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;
use Symfony\UX\TwigComponent\Attribute\PreMount;

#[AsTwigComponent('v2:dropdown')]
final class DropdownComponent
{
    public string $buttonSize = 'mini';

    public string $icon = 'ellipsis-v';

    public string $title = '';

    public string $toggleType = 'button';

    #[PreMount]
    public function preMount(array $data): array
    {
        $resolver = new OptionsResolver();

        $resolver->setDefaults([
            'class' => 'uk-dropdown',
            'buttonSize' => 'mini',
            'icon' => 'ellipsis-v',
            'title' => '',
            'toggleType' => 'button',
        ]);

        $resolver->setAllowedValues('buttonSize', ['', 'mini', 'small', 'large']);
        $resolver->setAllowedValues('toggleType', ['button', 'link']);

        return $resolver->resolve($data);
    }
}
