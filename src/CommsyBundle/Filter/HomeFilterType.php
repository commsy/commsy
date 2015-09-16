<?php
namespace CommsyBundle\Filter;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class HomeFilterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if ($options['hasHashtags']) {
            $builder->add('hashtag', 'hashtag_filter', array(
                'label' => false,
            ));
        }
    }

    public function getName()
    {
        return 'home_filter';
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver
            ->setDefaults(array(
                'csrf_protection'   => false,
                'validation_groups' => array('filtering'), // avoid NotBlank() constraint-related message
                'method'            => 'get',
            ))
            ->setRequired(array(
                'hasHashtags',
            ))
        ;
    }
}