<?php

namespace App\Twig\Components;

use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;
use Symfony\UX\TwigComponent\Attribute\PreMount;

#[AsTwigComponent('v3:dropdown')]
final class DropdownComponent
{
    public string $buttonSize = 'mini';

    public string $buttonStyle = 'secondary';

    public string $icon = 'more-vertical';

    public string $title = '';

    public string $toggleType = 'button';

    #[PreMount]
    public function preMount(array $data): array
    {
        $resolver = new OptionsResolver();

        $resolver->setDefaults([
            'buttonSize' => 'small',
            'buttonStyle' => 'secondary',
            'icon' => 'ellipsis-v',
            'title' => '',
            'toggleType' => 'button',
        ]);

        $resolver->setAllowedValues('buttonSize', ['', 'small', 'large']);
        $resolver->setAllowedValues('buttonStyle', ['default', 'primary', 'secondary', 'danger', 'text', 'link']);
        $resolver->setAllowedValues('toggleType', ['button', 'link']);

        return $resolver->resolve($data);
    }
}
