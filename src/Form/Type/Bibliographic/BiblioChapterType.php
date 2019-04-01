<?php
namespace App\Form\Type\Bibliographic;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class BiblioChapterType extends AbstractType
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
            ->add('editor', TextType::class, array(
                'label' => 'editor',
                'translation_domain' => $translationDomain,
                ))
            ->add('publishing_date', TextType::class, array(
                'label' => 'publishing date',
                'translation_domain' => $translationDomain,
                ))
            ->add('address', TextType::class, array(
                'label' => 'address',
                'translation_domain' => $translationDomain,
                ))
            ->add('pages', TextType::class, array(
                'label' => 'pages',
                'translation_domain' => $translationDomain,
            ))
            ->add('edition', TextType::class, array(
                'label' => 'edition',
                'translation_domain' => $translationDomain,
                'required' => false,
                ))
            ->add('series', TextType::class, array(
                'label' => 'series',
                'translation_domain' => $translationDomain,
                'required' => false,
                ))
            ->add('volume', TextType::class, array(
                'label' => 'volume',
                'translation_domain' => $translationDomain,
                'required' => false,
                ))
            ->add('isbn', TextType::class, array(
                'label' => 'isbn',
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
        return 'biblio_chapter';
    }

}