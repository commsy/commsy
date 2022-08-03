<?php
namespace App\Form\Type;

use FOS\CKEditorBundle\Form\Type\CKEditorType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class DiscussionAnswerType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('description', CKEditorType::class, array(
                'config_name' => 'cs_annotation_config',
                'label' => 'Add answer',
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(),
                ],
                'attr' => array(
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
                'label' => 'Answer',
            ))
            ->add('cancel', SubmitType::class, array(
                'attr' => array(
                    'formnovalidate' => '',
                ),
                'label' => 'cancel',
                'validation_groups' => false,
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
            ->setDefined(array('placeholderText', 'categories', 'hashTagPlaceholderText', 'hashtagEditUrl', 'hashtags'))
            ->setDefaults(array('translation_domain' => 'form'))
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
        return 'discussionarticle';
    }
}