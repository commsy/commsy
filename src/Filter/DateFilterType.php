<?php

namespace App\Filter;

use App\Form\Type\Custom\DateSelectType;
use Lexik\Bundle\FormFilterBundle\Filter\Form\Type as Filters;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DateFilterType extends AbstractType
{
    /**
     * Builds the form.
     * This method is called for each type in the hierarchy starting from the top most type.
     * Type extensions can further modify the form.
     *
     * @param FormBuilderInterface $builder The form builder
     * @param array $options The options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('submit', SubmitType::class, [
                'attr' => [
                    'class' => 'uk-button uk-button-mini',
                ],
                'label' => 'Filter',
                'translation_domain' => 'form',
            ])
            ->add('hide-deactivated-entries', Filters\ChoiceFilterType::class, [
                'choices' => [
                    'only activated' => 'only_activated',
                    'only deactivated' => 'only_deactivated',
                    'no restrictions' => 'all',
                ],
                'translation_domain' => 'form',
                'placeholder' => false,
            ])
            ->add('hide-past-dates', Filters\CheckboxFilterType::class, [
                'label_attr' => [
                    'class' => 'uk-form-label',
                ],
            ])
            ->add('date-from', DateSelectType::class, [
                'required' => false,
            ])
            ->add('date-until', DateSelectType::class, [
                'required' => false,
            ])
            ->add('rubrics', RubricFilterType::class, [
                'label' => false,
            ])
            ->add('participant', ParticipantFilterType::class, [
                'label' => false,
            ])
            ->add('calendar', CalendarFilterType::class, [
                'label' => false,
            ]);
        if ($options['hasCategories']) {
            $builder->add('category', CategoryFilterType::class, [
                'label' => false,
            ]);
        }
        if ($options['hasHashtags']) {
            $builder->add('hashtag', HashTagFilterType::class, [
                'label' => false,
            ]);
        }
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
        return 'date_filter';
    }

    /**
     * Configures the options for this type.
     *
     * @param OptionsResolver $resolver The resolver for the options
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setDefaults([
                'csrf_protection' => false,
                'validation_groups' => ['filtering'], // avoid NotBlank() constraint-related message
                'method' => 'get',
                'translation_domain' => 'form',
            ])
            ->setRequired([
                'hasHashtags',
                'hasCategories',
            ]);
    }
}
