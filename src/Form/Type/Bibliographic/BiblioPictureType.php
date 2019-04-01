<?php
namespace App\Form\Type\Bibliographic;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class BiblioPictureType extends AbstractType
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
            ->add('foto_copyright', TextType::class, array(
                'label' => 'picture copyright',
                'translation_domain' => $translationDomain,
                ))
            ->add('foto_reason', TextType::class, array(
                'label' => 'picture reason',
                'translation_domain' => $translationDomain,
                ))
            ->add('foto_date', TextType::class, array(
                'label' => 'picture date',
                'translation_domain' => $translationDomain,
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
        return 'biblio_picture';
    }

}