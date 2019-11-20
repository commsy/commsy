<?php
namespace App\Filter;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class UserFilterType extends AbstractType
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
            ->add('user_search', TextType::class, [
                'label' => 'Name',
                'translation_domain' => 'room',
                'label_attr' => array(
                    'class' => 'uk-form-label',
                ),
                'attr' => [
                    'placeholder' => 'search-user-filter-placeholder',
                    'class' => 'cs-form-horizontal-full-width',
                ],
                'required' => false,
            ])

            ->add('rubrics', RubricFilterType::class, array(
                'label' => false,
            ))
        ;

        if ($options['hasHashtags']) {
            $builder->add('hashtag', HashTagFilterType::class, array(
                'label' => false,
            ));
        }

        if ($options['hasCategories']) {
            $builder->add('category', CategoryFilterType::class, array(
                'label' => false,
            ));
        }
        
        if ($options['isModerator']) {
            $statusChoices = [
                'is blocked' => '0',
                'is applying' => '1',
                'user' => '8',
                'moderator' => '3',
                'is contact' => 'is contact',
                'reading user' => '4',
            ];
        } else {
            $statusChoices = [
                'moderator' => '3',
            ];
        }
        $builder->add('user_status', ChoiceType::class, array(
            'placeholder' => false,
            'choices' => $statusChoices,
            'label' => 'status',
            'translation_domain' => 'user',
            'required' => false,
            'expanded' => false,
            'multiple' => false,
            'placeholder' => 'no restrictions',
        ));
        $builder
            ->add('submit', SubmitType::class, array(
                'attr' => array(
                    'class' => 'uk-button-primary',
                ),
                'label' => 'Filter',
                'translation_domain' => 'form',
            ));
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
        return 'user_filter';
    }

    /**
     * Configures the options for this type.
     * 
     * @param  OptionsResolver $resolver The resolver for the options
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setDefaults(array(
                'csrf_protection'   => false,
                'validation_groups' => array('filtering'), // avoid NotBlank() constraint-related message
                'method'            => 'get',
            ))
            ->setRequired(array(
                'hasHashtags',
                'hasCategories',
                'isModerator'
            ))
        ;
    }
}