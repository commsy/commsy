<?php
namespace CommsyBundle\Form\Type\Bibliographic;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class BiblioDocManagementType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $translationDomain = 'form';

        $builder
            ->add('editor', 'text', array(
                'label' => 'editor',
                'translation_domain' => $translationDomain,
                ))
            ->add('maintainer', 'text', array(
                'label' => 'maintainer',
                'translation_domain' => $translationDomain,
                ))
            ->add('version', 'text', array(
                'label' => 'version',
                'translation_domain' => $translationDomain,
                ))
            ->add('url_date', 'text', array(
                'label' => 'url date',
                'translation_domain' => $translationDomain,
                'attr' => array(
                    'data-uk-datepicker' => '{format:\'DD.MM.YYYY\'}'
                )
                ))
        ;
    }

    public function getName()
    {
        return 'biblio_docmanagement';
    }

}