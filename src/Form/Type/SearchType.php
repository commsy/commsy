<?php
namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type as Types;

class SearchType extends AbstractType
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
                    'placeholder' => 'All my workspaces...',
                    'class' => 'uk-search-field',
                ],
                'required' => false,
            ])
            ->add('selectedContext', Types\HiddenType::class, [
                'label' => false,
            ])
            ->add('submit', Types\SubmitType::class, [
                'attr' => [
                    'class' => 'uk-button-primary',
                ],
                'label' => 'Search',
            ])
            // the hidden `current_context` button will be clicked automatically (via instant_results.html.twig)
            // when the corresponding 'Search in this room' entry gets selected from the instant results dropdown
            ->add('current_context', Types\SubmitType::class, [
                'attr' => [
                    'class' => 'uk-hidden',
                ],
                'label' => 'Search in this room',
                'validation_groups' => 'false',
            ])
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
            ->setDefaults([
                'csrf_protection'    => false,
                'method'             => 'get',
                'translation_domain' => 'search',
            ])
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
        return 'search';
    }
}