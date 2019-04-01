<?php
namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type as Types;
use Symfony\Component\Validator\Constraints;

use FOS\CKEditorBundle\Form\Type\CKEditorType;

class PortalTermsType extends AbstractType
{
    /**
     * Builds the form.
     *
     * @param  FormBuilderInterface $builder The form builder
     * @param  array                $options The options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('status' , Types\ChoiceType::class, [
                'label' => 'Show',
                'expanded' => true,
                'multiple' => false,
                'choices' => [
                    'Yes' => 1,
                    'No' => 2,
                ],
                'choice_translation_domain' => 'form',
            ])
            ->add('DE', CKEditorType::class, [
                'inline' => false,
                'attr' => array(
                    'class' => 'uk-form-width-large',
                    'style' => 'width: 100%;',
                ),
                'label' => 'body_de',
                'translation_domain' => 'settings',
            ])
            ->add('EN', CKEditorType::class, [
                'inline' => false,
                'attr' => array(
                    'class' => 'uk-form-width-large',
                    'style' => 'width: 100%;',
                ),
                'label' => 'body_en',
                'translation_domain' => 'settings',
            ])
            ->add('save', Types\SubmitType::class, [
                'label' => 'save',
                'attr' => array(
                    'class' => 'uk-button-primary',
                ),
                'translation_domain' => 'form',
            ])
        ;
    }

    /**
     * Configures the options for this type.
     *
     * @param  OptionsResolver $resolver The resolver for the options
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setRequired([])
            ->setDefaults(array('translation_domain' => 'portal'))
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
        return 'portalterms';
    }
}