<?php

namespace App\Components;

use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;
use Symfony\UX\TwigComponent\Attribute\PreMount;

#[AsTwigComponent('v3:card')]
final class CardComponent
{
    public bool $padding = true;

    public string $severity = '';

    public bool $sticky = false;

    public string $style = 'default';

    public string $title = '';

    #[PreMount]
    public function preMount(array $data): array
    {
        $resolver = new OptionsResolver();

        $resolver->setDefaults([
            'padding' => true,
            'severity' => '',
            'sticky' => false,
            'style' => 'default',
            'title' => '',
        ]);

        $resolver->setAllowedValues('padding', [true, false]);
        $resolver->setAllowedValues('severity', ['', 'warning', 'danger']);
        $resolver->setAllowedValues('sticky', [true, false]);
        $resolver->setAllowedValues('style', ['default', 'primary', 'secondary']);

        return $resolver->resolve($data);
    }
}
