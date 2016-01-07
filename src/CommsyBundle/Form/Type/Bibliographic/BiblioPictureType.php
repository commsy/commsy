<?php
namespace CommsyBundle\Form\Type\Bibliographic;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class BiblioPictureType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $translationDomain = 'form';

        $builder
            ->add('foto_copyright', 'text', array(
                'label' => 'picture copyright',
                'translation_domain' => $translationDomain,
                ))
            ->add('foto_reason', 'text', array(
                'label' => 'picture reason',
                'translation_domain' => $translationDomain,
                ))
            ->add('foto_date', 'text', array(
                'label' => 'picture date',
                'translation_domain' => $translationDomain,
                'attr' => array(
                    'data-uk-datepicker' => '{format:\'DD.MM.YYYY\'}'
                )
                ))
        ;
    }

    public function getName()
    {
        return 'biblio_picture';
    }

}