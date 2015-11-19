<?php
namespace CommsyBundle\Form\Type\Bibliographic;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class BiblioWebsiteType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $translationDomain = 'form';

        $builder
            ->add('editor', 'text', array(
                'label' => 'editor',
                'translation_domain' => $translationDomain,
                ))
            ->add('url', 'text', array(
                'label' => 'url',
                'translation_domain' => $translationDomain,
                ))
            ->add('url_date', 'date', array(
                'label' => 'visited',
                'translation_domain' => $translationDomain,
                'required' => false,
                ))
        ;
    }

    public function getName()
    {
        return 'biblio_website';
    }

}