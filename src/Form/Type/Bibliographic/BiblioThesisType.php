<?php
namespace App\Form\Type\Bibliographic;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class BiblioThesisType extends AbstractType
{
    /**
     * Builds the form.
     * This method is called for each type in the hierarchy starting from the top most type.
     * Type extensions can further modify the form.
     * 
     * @param  FormBuilderInterface $builder The form builder
     * @param  array                $options The options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $translationDomain = 'form';

        $builder
            ->add('author', TextType::class, array(
                'label' => 'author',
                'translation_domain' => $translationDomain,
            ))
            ->add('publishing_date', TextType::class, array(
                'label' => 'publishing date',
                'translation_domain' => $translationDomain,
            ))
            ->add('thesis_kind', ChoiceType::class, array(
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
            ->add('address', TextType::class, array(
                'label' => 'address',
                'translation_domain' => $translationDomain,
            ))
            ->add('university', TextType::class, array(
                'label' => 'university',
                'translation_domain' => $translationDomain,
            ))
            ->add('faculty', TextType::class, array(
                'label' => 'faculty',
                'translation_domain' => $translationDomain,
                'required' => false,
            ))
            ->add('editor', TextType::class, array(
                'label' => 'editor',
                'translation_domain' => $translationDomain,
                'required' => false,
            ))
            ->add('url', TextType::class, array(
                'label' => 'url',
                'translation_domain' => $translationDomain,
                'required' => false,
            ))
            ->add('url_date', TextType::class, array(
                'label' => 'url date',
                'translation_domain' => $translationDomain,
                'required' => false,
                'attr' => array(
                    'data-uk-datepicker' => '{format:\'DD.MM.YYYY\'}'
                )
            ))
        ;
    }

    /**
     * Returns the prefix of the template block name for this type.
     * The block prefix defaults to the underscored short class name with the "Type" suffix removed
     * (e.g. "UserProfileType" => "user_profile").
     * 
     * @return string The prefix of the template block name
     */
    public function getBlockPrefix()
    {
        return 'biblio_thesis';
    }

}