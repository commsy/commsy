<?php
namespace CommsyBundle\Filter;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class AnnouncementFilterType extends AbstractType
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

        if ($options['hasHashtags']) {
            $builder->add('hashtag', 'hashtag_filter', array(
                'label' => false,
            ));
        }

        if ($options['hasCategories']) {
            $builder->add('category', 'category_filter', array(
                'label' => false,
            ));
        }
    }

    public function getName()
    {
        return 'announcement_filter';
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
                'hasCategories'
            ))
        ;
    }
}