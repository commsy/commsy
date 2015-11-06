<?php
namespace CommsyBundle\Form\Type\Bibliographic;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class BiblioPictureType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $builder
            ->add('foto_copyright', 'text', array(
                ))
            ->add('foto_reason', 'text', array(
                ))
            ->add('foto_date', 'date', array(
                ))
        ;
    }

    public function getName()
    {
        return 'biblio_picture';
    }

}