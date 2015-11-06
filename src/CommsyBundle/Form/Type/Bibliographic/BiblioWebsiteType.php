<?php
namespace CommsyBundle\Form\Type\Bibliographic;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class BiblioWebsiteType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $builder
            ->add('editor', 'text', array(
                ))
            ->add('url', 'text', array(
                ))
            ->add('url_date', 'date', array(
                ))
        ;
    }

    public function getName()
    {
        return 'biblio_website';
    }

}