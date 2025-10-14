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

namespace App\Form\Type\Item;

use App\Form\Model\Tags;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class ItemTagsType extends AbstractType
{
    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('tags', TextType::class, [
                'required' => false,
                'label' => false,
                'autocomplete' => true,
                'tom_select_options' => [
                    'create' => true,
                    'createOnBlur' => true,
                    'delimiter' => ',',
                ],
                'autocomplete_url' => $this->urlGenerator->generate('app_hashtag_all', [
                    'roomId' => $options['roomId'],
                ]),
                'attr' => [
                    'data-controller' => 'custom-autocomplete',
                ],
                'constraints' => $options['mandatoryTags'] ?
                    [new NotBlank(message: 'Please select at least one hashtag')] :
                    [],
            ]);

        $builder->get('tags')
            ->addModelTransformer(new CallbackTransformer(
                function ($tagsAsArray): string {
                    // Transform the array to a string
                    return implode(',', $tagsAsArray);
                },
                function ($tagsAsString): array {
                    // Transform the string back to an array
                    return $tagsAsString ? explode(',', $tagsAsString) : [];
                }
            ))
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefaults([
                'data_class' => Tags::class,
            ])
            ->setRequired(['roomId', 'mandatoryTags'])
            ->setAllowedTypes('roomId', 'int')
            ->setAllowedTypes('mandatoryTags', 'bool')
        ;
    }
}
