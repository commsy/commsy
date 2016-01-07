<?php
namespace CommsyBundle\Form\Type\Bibliographic;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class BiblioArticleType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $translationDomain = 'form';

        $builder
            ->add('author', 'text', array(
                'attr' => array(
                    'class' => 'uk-flex',
                ),
                'label' => 'author',
                'translation_domain' => $translationDomain,
            ))
            ->add('publishing_date', 'text', array(
                'label' => 'publishing date',
                'translation_domain' => $translationDomain,
                ))
            ->add('editor', 'text', array(
                'label' => 'editor',
                'translation_domain' => $translationDomain,
                ))
            ->add('booktitle', 'text', array(
                'label' => 'booktitle',
                'translation_domain' => $translationDomain,
                ))
            ->add('publisher', 'text', array(
                'label' => 'publisher',
                'translation_domain' => $translationDomain,
                ))
            ->add('address', 'text', array(
                'label' => 'address',
                'translation_domain' => $translationDomain,
                ))
            ->add('edition', 'text', array(
                'label' => 'edition',
                'translation_domain' => $translationDomain,
                ))
            ->add('series', 'text', array(
                'label' => 'series',
                'translation_domain' => $translationDomain,
                'required' => false,
                ))
            ->add('volume', 'text', array(
                'label' => 'volume',
                'translation_domain' => $translationDomain,
                'required' => false,
                ))
            ->add('isbn', 'text', array(
                'label' => 'isbn',
                'translation_domain' => $translationDomain,
                'required' => false,
                ))
            ->add('url', 'text', array(
                'label' => 'url',
                'translation_domain' => $translationDomain,
                'required' => false,
                ))
            ->add('url_date', 'text', array(
                'label' => 'url date',
                'translation_domain' => $translationDomain,
                'required' => false,
                'attr' => array(
                    'data-uk-datepicker' => '{format:\'DD.MM.YYYY\'}'
                )
                ))
        ;
    }

    public function getName()
    {
        return 'biblio_article';
    }

}