<?php
namespace CommsyBundle\Filter;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class MaterialFilterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('activated', 'filter_checkbox', array(
                'translation_domain' => 'form',
            ))
            ->add('rubrics', 'rubric_filter', array(
                'translation_domain' => 'form',
            ))
            ->add('save', 'submit', array(
                'attr' => array(
                    'class' => 'uk-button-primary',
                ),
                'label' => 'Filtern',
                'translation_domain' => 'form',
            ))
        ;
    }

    public function getName()
    {
        return 'material_filter';
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'csrf_protection'   => false,
            'validation_groups' => array('filtering'), // avoid NotBlank() constraint-related message
            'method'            => 'get',
        ));
    }
}