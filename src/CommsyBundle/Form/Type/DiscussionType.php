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

use CommsyBundle\Form\Type\Custom\DateTimeSelectType;
use CommsyBundle\Form\Type\Custom\MandatoryCategoryMappingType;
use CommsyBundle\Form\Type\Custom\MandatoryHashtagMappingType;

class DiscussionType extends AbstractType
{
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
                'translation_domain' => 'material',
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
            ->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
                $discussion = $event->getData();
                $form = $event->getForm();
                $formOptions = $form->getConfig()->getOptions();
                if ($discussion['draft']) {
                    if ($discussion['hashtagsMandatory'] && $formOptions['hashtagMappingOptions']) {
                        $form->add('hashtag_mapping', MandatoryHashtagMappingType::class, $formOptions['hashtagMappingOptions']);
                    }
                    if ($discussion['categoriesMandatory'] && $formOptions['categoryMappingOptions']) {
                        $form->add('category_mapping', MandatoryCategoryMappingType::class, $formOptions['categoryMappingOptions']);
                    }
                }

                if ($discussion['external_viewer_enabled']) {
                    $form->add('external_viewer', TextType::class, [
                        'required' => false,
                    ]);
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
        return 'discussion';
    }
}