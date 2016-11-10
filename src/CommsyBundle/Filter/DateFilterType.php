<?php
namespace CommsyBundle\Filter;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

use Lexik\Bundle\FormFilterBundle\Filter\Form\Type as Filters;

use CommsyBundle\Form\Type\Custom\DateSelectType;

class DateFilterType extends AbstractType
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
            ->add('activated', Filters\CheckboxFilterType::class, array(
                'attr' => array(
                    'onchange' => 'this.form.submit()',
                ),
                'translation_domain' => 'form',
                'label_attr' => array(
                    'class' => 'uk-form-label',
                ),
            ))
            ->add('past-dates', Filters\CheckboxFilterType::class, array(
                'attr' => array(
                    'onchange' => 'this.form.submit()',
                ),
                'label' => 'past-dates',
                'translation_domain' => 'form',
                'label_attr' => array(
                    'class' => 'uk-form-label',
                ),
            ))
            ->add('date-from', DateSelectType::class, array(
                'label' => 'date-from',
                'translation_domain' => 'form',
                'required' => false,
            ))
            ->add('date-until', DateSelectType::class, array(
                'label' => 'date-until',
                'translation_domain' => 'form',
                'required' => false,
            ))
            ->add('rubrics', RubricFilterType::class, array(
                'label' => false,
            ))
            ->add('participant', ParticipantFilterType::class, array(
                'label' => false,
            ))
        ;
        if ($options['hasCategories']) {
            $builder->add('category', CategoryFilterType::class, array(
                'label' => false,
            ));
        }
        if ($options['hasHashtags']) {
            $builder->add('hashtag', HashTagFilterType::class, array(
                'label' => false,
            ));
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
                'hasCategories'
            ))
        ;
    }
}