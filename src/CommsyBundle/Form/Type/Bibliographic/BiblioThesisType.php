<?php
namespace CommsyBundle\Form\Type\Bibliographic;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class BiblioThesisType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $translationDomain = 'form';

        $builder
            ->add('editor', 'text', array(
                'label' => 'editor',
                'translation_domain' => $translationDomain,
                ))
            ->add('publishing_date', 'text', array(
                'label' => 'publishing date',
                'translation_domain' => $translationDomain,
                ))
            ->add('thesis_select', 'choice', array(
                'label' => 'thesis kind',
                'translation_domain' => $translationDomain,
                'choices'  => array(
                    'term' => 'term',
                    'bachelor' => 'bachelor',
                    'master' => 'master',
                    'exam' => 'exam',
                    'diploma' => 'diploma',
                    'dissertation' => 'dissertation',
                    'postdoc' => 'postdoc',
                ),
                'choice_translation_domain' => true,
                ))
            ->add('address', 'text', array(
                'label' => 'address',
                'translation_domain' => $translationDomain,
                ))
            ->add('university', 'text', array(
                'label' => 'university',
                'translation_domain' => $translationDomain,
                ))
            ->add('faculty', 'text', array(
                'label' => 'faculty',
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
        return 'biblio_thesis';
    }

}