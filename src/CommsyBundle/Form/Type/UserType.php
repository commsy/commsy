<?php
namespace CommsyBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {        
        $builder
            ->add('title', TextType::class, array(
                'label' => 'title',
                'attr' => array(
                    'placeholder' => 'title',
                    'class' => '',
                ),
                'translation_domain' => 'user',
                'required' => false,
            ))
            ->add('dateOfBirth', DateType::class, array(
                'label' => 'dateOfBirth',
                'widget' => 'single_text',
                'format' => 'dd.MM.yyyy',
                'attr' => array(
                    'data-uk-datepicker' => '{format:\'DD.MM.YYYY\'}',
                    'class' => 'cs-form-input-inline',
                ),
                'translation_domain' => 'user',
                'required' => false,
            ))
            ->add('userImage', FileType::class, array(
                'label' => 'userImage',
                'attr' => array(
                    'placeholder' => 'userImage',
                    'class' => '',
                ),
                'translation_domain' => 'user',
                'required' => false,
            ))
            ->add('email', TextType::class, array(
                'constraints' => array(
                    new NotBlank(),
                ),
                'label' => 'email',
                'attr' => array(
                    'placeholder' => 'email',
                    'class' => '',
                ),
                'translation_domain' => 'material',
                'required' => true,
            )) 
            ->add('hideEmail', TextType::class, array( // Checkbox
                'label' => 'hideEmail',
                'attr' => array(
                    'placeholder' => 'hideEmail',
                    'class' => '',
                ),
                'translation_domain' => 'user',
                'required' => false,
            ))
            ->add('phone', TextType::class, array(
                'label' => 'phone',
                'attr' => array(
                    'placeholder' => 'phone',
                    'class' => '',
                ),
                'translation_domain' => 'user',
                'required' => false,
            ))
            ->add('mobile', TextType::class, array(
                'label' => 'mobile',
                'attr' => array(
                    'placeholder' => 'mobile',
                    'class' => '',
                ),
                'translation_domain' => 'user',
                'required' => false,
            ))
            ->add('street', TextType::class, array(
                'label' => 'street',
                'attr' => array(
                    'placeholder' => 'street',
                    'class' => '',
                ),
                'translation_domain' => 'user',
                'required' => false,
            ))
            ->add('areaCode', TextType::class, array(
                'label' => 'areaCode',
                'attr' => array(
                    'placeholder' => 'areaCode',
                    'class' => '',
                ),
                'translation_domain' => 'user',
                'required' => false,
            ))
            ->add('town', TextType::class, array(
                'label' => 'town',
                'attr' => array(
                    'placeholder' => 'town',
                    'class' => '',
                ),
                'translation_domain' => 'user',
                'required' => false,
            ))
            ->add('room', TextType::class, array(
                'label' => 'room',
                'attr' => array(
                    'placeholder' => 'room',
                    'class' => '',
                ),
                'translation_domain' => 'user',
                'required' => false,
            ))
            ->add('organisation', TextType::class, array(
                'label' => 'organisation',
                'attr' => array(
                    'placeholder' => 'organisation',
                    'class' => '',
                ),
                'translation_domain' => 'user',
                'required' => false,
            ))
            ->add('position', TextType::class, array(
                'label' => 'position',
                'attr' => array(
                    'placeholder' => 'position',
                    'class' => '',
                ),
                'translation_domain' => 'user',
                'required' => false,
            ))
            ->add('homepage', TextType::class, array(
                'label' => 'homepage',
                'attr' => array(
                    'placeholder' => 'homepage',
                    'class' => '',
                ),
                'translation_domain' => 'user',
                'required' => false,
            ))
            ->add('msn', TextType::class, array(
                'label' => 'msn',
                'attr' => array(
                    'placeholder' => 'msn',
                    'class' => '',
                ),
                'translation_domain' => 'user',
                'required' => false,
            ))
            ->add('skype', TextType::class, array(
                'label' => 'skype',
                'attr' => array(
                    'placeholder' => 'skype',
                    'class' => '',
                ),
                'translation_domain' => 'user',
                'required' => false,
            ))
            ->add('icq', TextType::class, array(
                'label' => 'icq',
                'attr' => array(
                    'placeholder' => 'icq',
                    'class' => '',
                ),
                'translation_domain' => 'user',
                'required' => false,
            ))
            ->add('yahoo', TextType::class, array(
                'label' => 'yahoo',
                'attr' => array(
                    'placeholder' => 'yahoo',
                    'class' => '',
                ),
                'translation_domain' => 'user',
                'required' => false,
            ))
            ->add('language', ChoiceType::class, array(
                'label' => 'language',
                'attr' => array(
                    'placeholder' => 'language',
                    'class' => '',
                ),
                'translation_domain' => 'user',
                'required' => false,
            ))
        ;
        
        $builder
            ->add('save', SubmitType::class, array(
                'attr' => array(
                    'class' => 'uk-button-primary',
                ),
                'label' => 'save',
                'translation_domain' => 'form',
            ))
            ->add('cancel', SubmitType::class, array(
                'attr' => array(
                    'class' => 'uk-button-primary',
                    'formnovalidate' => '',
                ),
                'label' => 'cancel',
                'translation_domain' => 'form',
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
        return 'user';
    }
}