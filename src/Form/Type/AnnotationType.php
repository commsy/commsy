<?php
namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Validator\Constraints as Assert;

use FOS\CKEditorBundle\Form\Type\CKEditorType;

class AnnotationType extends AbstractType
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
        $builder
            ->add('description', CKEditorType::class, array(
                'config_name' => 'cs_annotation_config',
                'label' => 'Add new annotation',
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(),
                ],
                'attr' => array(
                    'placeholder' => 'annotation',
                    'class' => 'uk-form-width-large',
                ),
                'label_attr' => array(
                    'style' => 'font-weight: bold;'
                ),
                'translation_domain' => 'item',
                'input_sync' => true,
            ))
            ->add('save', SubmitType::class, array(
                'attr' => array(
                    'class' => 'uk-button-primary',
                ),
                'label' => 'save',
            ))
            ->add('cancel', SubmitType::class, array(
                'attr' => array(
                    'formnovalidate' => 'formnovalidate',
                ),
                'label' => 'Back',
                'validation_groups' => 'false',
            ))
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
            ->setDefaults([
                'translation_domain' => 'form',
            ])
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
        return 'annotation';
    }
}