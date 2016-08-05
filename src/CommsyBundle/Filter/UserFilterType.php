<?php
namespace CommsyBundle\Filter;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

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
            ->add('rubrics', RubricFilterType::class, array(
                'label' => false,
            ))
        ;
     /*   $builder
            ->add('status', 'status_filter', array(
                'label' => false,
            ))
        ;*/

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
                'user' => '2',
                'moderator' => '3',
                'is contact' => 'is contact',
                'reading user' => '4',
            ];
        } else {
            $statusChoices = [
                'moderator' => 'moderator',
            ];
        }
        $builder->add('user_status', ChoiceType::class, array(
            'placeholder' => false,
            'choices' => $statusChoices,
            'attr' => array(
                'onchange' => 'this.form.submit()',
            ),
            'label' => 'status',
            'translation_domain' => 'user',
            'required' => false,
            'expanded' => false,
            'multiple' => false,
            'placeholder' => 'no restrictions',
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