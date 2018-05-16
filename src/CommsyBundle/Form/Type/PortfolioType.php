<?php
namespace CommsyBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

use CommsyBundle\Form\Type\Custom\DateTimeSelectType;

use CommsyBundle\Form\Type\Event\AddBibliographicFieldListener;
use CommsyBundle\Form\Type\Custom\MandatoryCategoryMappingType;
use CommsyBundle\Form\Type\Custom\MandatoryHashtagMappingType;

class PortfolioType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('title', TextType::class, array(
                'label' => 'title',
                'attr' => array(
                    'placeholder' => $options['placeholderText'],
                    'class' => 'uk-form-width-medium cs-form-title',
                ),
                'translation_domain' => 'portfolio',
                'required' => false,
            ))
            ->add('description', TextareaType::class, [
                'attr' => [
                    'rows' => 10,
                    'cols' => 100,
                    'placeholder' => $options['placeholderDescription'],
                ],
                'required' => false,
            ])
            ->add('use_as_template', CheckboxType::class, array(
                'label' => 'use as template',
                'translation_domain' => 'portfolio',
                'required' => false,
            ))
            ->add('allow_template_access', TextType::class, array(
                'label' => 'allow template access',
                'translation_domain' => 'portfolio',
                'required' => false,
            ))
            ->add('allow_read_access', TextType::class, array(
                'label' => 'allow read access',
                'translation_domain' => 'portfolio',
                'required' => false,
            ))
            ->add('save', SubmitType::class, array(
                'attr' => array(
                    'class' => 'uk-button-primary',
                ),
                'label' => 'save',
            ))
            ->add('cancel', SubmitType::class, array(
                'attr' => array(
                    'formnovalidate' => '',
                ),
                'label' => 'cancel',
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
            ->setRequired(['placeholderText', 'placeholderDescription'])
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
        return 'portfolio';
    }
}