<?php
namespace CommsyBundle\Form\Type\Bibliographic;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class BiblioNewspaperType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $builder
            ->add('editor', 'text', array(
                ))
            ->add('publishing_date', 'text', array(
                ))
            ->add('journal', 'text', array(
                ))
            ->add('pages', 'text', array(
                ))
            ->add('number', 'text', array(
                ))
            ->add('address', 'text', array(
                ))
            ->add('publisher', 'text', array(
                ))
            ->add('url', 'text', array(
                ))
            ->add('url_date', 'date', array(
                ))
        ;
    }

    public function getName()
    {
        return 'biblio_newspaper';
    }

}