<?php
namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;

use App\Form\Type\Custom\DateTimeSelectType;
use App\Form\Type\Custom\DateTimeSelectEngType;
use App\Form\Type\Custom\MandatoryCategoryMappingType;
use App\Form\Type\Custom\MandatoryHashtagMappingType;

class AnnouncementType extends AbstractType
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
            ->add('title', TextType::class, array(
                'constraints' => array(
                    new NotBlank(),
                ),
                'label' => 'title',
                'attr' => array(
                    'placeholder' => $options['placeholderText'],
                    'class' => 'uk-form-width-medium cs-form-title',
                ),
                'translation_domain' => 'announcement',
            ))
            // add custom datetime picker
            ->add('validdate', DateTimeSelectType::class, array(
                'label' => 'valid until',
                'translation_domain' => 'announcement'
            ))
            // add custom datetime picker english
            ->add('validdate_eng', DateTimeSelectEngType::class, array(
                'label' => 'valid until',
                'translation_domain' => 'announcement',
            ))
            ->add('permission', CheckboxType::class, array(
                'label' => 'permission',
                'required' => false,
            ))
            ->add('hidden', CheckboxType::class, array(
                'label' => 'hidden',
                'required' => false,
            ))
            ->add('hiddendate', DateTimeSelectType::class, array(
                'label' => 'hidden until',
            ))
            ->add('hiddendate_eng', DateTimeSelectEngType::class, array(
                'label' => 'hidden until',
            ))
            ->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
                $announcement = $event->getData();
                $form = $event->getForm();
                $formOptions = $form->getConfig()->getOptions();
                if ($announcement['draft']) {
                    if ($announcement['hashtagsMandatory'] && $formOptions['hashtagMappingOptions']) {
                        $form->add('hashtag_mapping', MandatoryHashtagMappingType::class, $formOptions['hashtagMappingOptions']);
                    }
                    if ($announcement['categoriesMandatory'] && $formOptions['categoryMappingOptions']) {
                        $form->add('category_mapping', MandatoryCategoryMappingType::class, $formOptions['categoryMappingOptions']);
                    }
                }
            })
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
            ->setRequired(['placeholderText', 'hashtagMappingOptions', 'categoryMappingOptions'])
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
        return 'announcement';
    }
}