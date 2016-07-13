<?php
namespace CommsyBundle\Filter;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Lexik\Bundle\FormFilterBundle\Filter\Form\Type as Filters;

class RoomFilterType extends AbstractType
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
            ->add('membership', Filters\CheckboxFilterType::class, [
                'attr' => [
                    'onchange' => 'this.form.submit()',
                ],
                'mapped' => false,
                'translation_domain' => 'room',
            ])
            ->add('archived', Filters\CheckboxFilterType::class, [
                'apply_filter' => false, // disable filter
                'attr' => [
                    'onchange' => 'this.form.submit()',
                ],
                'mapped' => false,
                'translation_domain' => 'room',
            ])
            ->add('type', Filters\ChoiceFilterType::class, [
                'attr' => [
                    'onchange' => 'this.form.submit()',
                ],
                'choices' => [
                    'Project Rooms' => 'project',
                    'Community Rooms' => 'community',
                ],
                'placeholder' => 'All',
                'translation_domain' => 'room',
            ]);
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
        return 'room_filter';
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
        ;
    }
}