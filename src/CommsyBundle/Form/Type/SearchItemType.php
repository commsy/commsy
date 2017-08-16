<?php
namespace CommsyBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type as Types;

class SearchItemType extends AbstractType
{
    /**
     * Builds the form.
     * This method is called for each type in the hierarchy starting from the top most type.
     * Type extensions can further modify the form.
     * 
     * @param  FormBuilderInterface $builder The form builder
     * @param  array                $options The options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('phrase', Types\SearchType::class, [
                'attr' => [
                    'placeholder' => 'Search in room...',
                    'class' => 'uk-search-field',
                ],
                'required' => false,
                'translation_domain' => 'search',
            ])
//            ->add('type', Types\ChoiceType::class, [
//                'choices' => [
//                    'a' => 'Rubrik A',
//                    'b' => 'Rubrik B',
//                ]
//            ])
//            ->add('submit', Types\SubmitType::class, [
//                'attr' => [
//                    'class' => 'uk-button-primary',
//                ],
//                'label' => 'Search',
//                'translation_domain' => 'search',
//            ])
        ;
    }

    /**
     * Configures the options for this type.
     * 
     * @param  OptionsResolver $resolver The resolver for the options
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setRequired([])
        ;
    }

    /**
     * Returns the prefix of the template block name for this type.
     * The block prefix defaults to the underscored short class name with the "Type" suffix removed
     * (e.g. "UserProfileType" => "user_profile").
     * 
     * @return string The prefix of the template block name
     */
    public function getBlockPrefix()
    {
        return 'search_item';
    }
}