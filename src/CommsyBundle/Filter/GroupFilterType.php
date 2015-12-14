<?php
namespace CommsyBundle\Filter;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class GroupFilterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('activated', 'filter_checkbox', array(
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


        // $builder
        //     ->add('title', 'text', array(
        //         'constraints' => array(
        //             new NotBlank(),
        //         ),
        //         'label' => false,
        //         'attr' => array(
        //             'placeholder' => 'New Category',
        //             'class' => 'uk-form-width-medium',
        //         ),
        //         'translation_domain' => 'category',
        //     ))
        //     ->add('save', 'submit', array(
        //         'attr' => array(
        //             'class' => 'uk-button-primary',
        //         ),
        //         'label' => 'Add',
        //         'translation_domain' => 'form',
        //     ))
        // ;
    }

    public function getName()
    {
        return 'group_filter';
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