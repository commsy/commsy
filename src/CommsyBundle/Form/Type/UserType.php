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
use Symfony\Component\Form\Extension\Core\Type\FileType;

use CommsyBundle\Form\Type\Custom\DateSelectType;

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
            ->add('dateOfBirth', DateSelectType::class, array(
                'label'    => 'dateOfBirth',
                'translation_domain' => 'user',
                'required' => false,
            ))
            ->add('emailRoom', TextType::class, array(
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
            ->add('hideEmailInThisRoom', CheckboxType::class, array(
                'label' => 'hideEmailInThisRoom',
                'required' => false,
                'translation_domain' => 'user',
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
            ->add('zipCode', TextType::class, array(
                'label' => 'zipCode',
                'attr' => array(
                    'placeholder' => 'zipCode',
                    'class' => '',
                ),
                'translation_domain' => 'user',
                'required' => false,
            ))
            ->add('city', TextType::class, array(
                'label' => 'city',
                'attr' => array(
                    'placeholder' => 'city',
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
            ->add('skype', TextType::class, array(
                'label' => 'skype',
                'attr' => array(
                    'placeholder' => 'skype',
                    'class' => '',
                ),
                'translation_domain' => 'user',
                'required' => false,
            ))
            ->add('language', ChoiceType::class, array(
                'placeholder' => false,
                'choices' => array(
                    'browser' => 'browser',
                    'german' => 'german',
                    'english' => 'english',
                ),
                'label' => 'language',
                'translation_domain' => 'user',
                'required' => false,
                'expanded' => false,
                'multiple' => false
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
            ->setRequired(['uploadUrl'])
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