<?php
namespace CommsyBundle\Form\Type\Bibliographic;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class BiblioPlainType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $builder
            ->add('author', 'text', array(
                ))
            ->add('publishing_date', 'text', array(
                ))
            // ->add('bib', 'text', array(
            //     // 'constraints' => array(
            //     //     new NotBlank(),
            //     // ),
            //     'label' => ' ',
            //     'attr' => array(
            //         'placeholder' => 'annotation',
            //         'class' => 'uk-form-width-large',
            //     ),
            //     'translation_domain' => 'item',
            // ))
        ;
    }

    public function getName()
    {
        return 'biblio_plain';
    }

}