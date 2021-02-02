<?php

namespace App\Form\Type\Custom;

use App\Form\Type\TreeChoiceType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ButtonType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Count;

class MandatoryCategoryMappingType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('categories', TreeChoiceType::class, [
                'placeholder' => false,
                'choices' => $options['categories'],
                'choice_label' => function ($choice, $key, $value) {
                    // remove the trailing category ID from $key (which was used in ItemController->transformTagArray() to uniquify the key)
                    return implode('_', explode('_', $key, -1));
                },
                'required' => true,
                'expanded' => true,
                'multiple' => true,
                'constraints' => [
                    new Count(['min' => 1, 'minMessage' => "Please select at least one category"]),
                ],
            ])
            ->add('newCategory', TextType::class, [
                'attr' => [
                    'placeholder' => '1',
                ],
                'label' => 'newCategory',
                'required' => false
            ])
            ->add('newCategoryAdd', ButtonType::class, [
                'attr' => [
                    'id' => 'addNewCategory',
                    'data-cs-add-category' => '2',
                ],
                'label' => 'addNewCategory',
            ]);
        ;
    }

    /**
     * Configures the options for this type.
     *
     * @param OptionsResolver $resolver The resolver for the options
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setRequired(['categories'])
            ->setDefaults(['translation_domain' => 'form']);
    }

}
