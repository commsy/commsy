<?php
namespace CommsyBundle\Form\Type\Bibliographic;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class BiblioChapterType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $builder
            ->add('editor', 'text', array(
                ))
            ->add('publishing_date', 'text', array(
                ))
            ->add('address', 'text', array(
                ))
            ->add('edition', 'text', array(
                ))
            ->add('series', 'text', array(
                ))
            ->add('volume', 'text', array(
                ))
            ->add('isbn', 'text', array(
                ))
            ->add('url', 'text', array(
                ))
            ->add('url_date', 'date', array(
                ))
        ;
    }

    public function getName()
    {
        return 'biblio_chapter';
    }

}