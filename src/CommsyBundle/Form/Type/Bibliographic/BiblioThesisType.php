<?php
namespace CommsyBundle\Form\Type\Bibliographic;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class BiblioThesisType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $builder
            ->add('editor', 'text', array(
                ))
            ->add('publishing_date', 'text', array(
                ))
            ->add('biblio_select', 'choice', array(
                'choices'  => array(
                    'term' => 'term',
                    'bachelor' => 'bachelor',
                    'master' => 'master',
                    'exam' => 'exam',
                    'diploma' => 'diploma',
                    'dissertation' => 'dissertation',
                    'postdoc' => 'postdoc',
                )
                ))
            ->add('address', 'text', array(
                ))
            ->add('university', 'text', array(
                ))
            ->add('faculty', 'text', array(
                ))
            ->add('url', 'text', array(
                ))
            ->add('url_date', 'date', array(
                ))
        ;
    }

    public function getName()
    {
        return 'biblio_thesis';
    }

}