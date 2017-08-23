<?php
namespace CommsyBundle\Form\Type\Context;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProjectType extends AbstractType
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
        $translationDomain = 'form';

        $builder->add('master_template', ChoiceType::class, [
                'choices' => $options['templates'],
                'preferred_choices' => $options['preferredChoices'],
                'placeholder' => 'Choose a template',
                'required' => false,
                'mapped' => false,
                'label' => 'Template',
            ]);
        if (!empty($options['times'])) {
            $builder->add('time_interval', ChoiceType::class, [
                'choices' => $options['times'],
                'required' => false,
                'mapped' => false,
                'expanded' => true,
                'multiple' => true,
                'label' => $options['timesDisplay'],
                'translation_domain' => 'room',
            ]);
        }
        $builder->add('community_rooms', ChoiceType::class, [
                'choices' => $options['communities'],
                'required' => $options['linkCommunitiesMandantory'],
                'mapped' => false,
                'multiple' => true,
                'expanded' => false,
                'label' => 'Community rooms',
                'translation_domain' => 'settings',
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
            ->setRequired(['types', 'templates', 'preferredChoices', 'timesDisplay', 'times', 'communities', 'linkCommunitiesMandantory'])
            ->setDefaults(array('translation_domain' => 'form'))
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
        return 'project';
    }

}