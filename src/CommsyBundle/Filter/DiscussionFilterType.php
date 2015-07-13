<?php
namespace CommsyBundle\Filter;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class DiscussionFilterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('activated', 'filter_checkbox', array(
                'attr' => array(
                    'onchange' => 'this.form.submit()',
                ),
                'translation_domain' => 'form',
            ))
            ->add('rubrics', 'rubric_filter', array(
                'label' => false,
            ))
        ;
    }

    public function getName()
    {
        return 'discussion_filter';
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