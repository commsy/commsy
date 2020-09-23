<?php


namespace App\Form\Type\Portal;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AuthShibbolethType extends AbstractType
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
            ->add('typeChoice', ChoiceType::class, [
                'choices'  => [
                    'CommSy' => 'commsy',
                    'LDAP' => 'ldap',
                    'Shibboleth' => 'shibboleth',
                ],
                'required' => true,
                'label' => 'Source',
                'translation_domain' => 'portal',
            ])
            ->add('type', SubmitType::class, [
                'attr' => [
                    'class' => 'uk-button-primary',
                    'formnovalidate' => '',
                ],
                'label' => 'Select',
                'translation_domain' => 'portal',
                'validation_groups' => false,
            ])
            ->add('title', TextType::class, [
                'label' => 'Title',
                'translation_domain' => 'portal',
                'required' => true,
            ])
            ->add('default', ChoiceType::class, [
                'choices' => [
                    'Yes' => '1',
                    'No' => '0'
                ],
                'placeholder' => false,
                'expanded' => true,
                'label' => 'Default',
                'translation_domain' => 'portal',
                'required' => false,
            ])
            ->add('available', ChoiceType::class, [
                'choices' => [
                    'Yes' => '1',
                    'No' => '0'
                ],
                'placeholder' => false,
                'expanded' => true,
                'label' => 'Available',
                'translation_domain' => 'portal',
                'required' => false,
            ])
            ->add('directLogin', CheckboxType::class, [
                'label' => 'Direct login',
                'translation_domain' => 'portal',
                'required' => false,
            ])
            ->add('sessionInitiatorURL', TextType::class, [
                'label' => 'Session initiatior URL',
                'required' => true,
                'translation_domain' => 'portal',
            ])
            ->add('sessionLogoutURL', TextType::class, [
                'label' => 'Session logout URL',
                'required' => true,
                'translation_domain' => 'portal',
            ])
            ->add('changePasswordURL', TextType::class, [
                'label' => 'Change password URL',
                'required' => true,
                'translation_domain' => 'portal',
            ])
            ->add('userName', TextType::class, [
                'label' => 'User name',
                'required' => true,
                'translation_domain' => 'portal',
            ])
            ->add('firstName', TextType::class, [
                'label' => 'First name',
                'required' => false,
                'translation_domain' => 'portal',
            ])
            ->add('lastName', TextType::class, [
                'label' => 'Last name',
                'required' => false,
                'translation_domain' => 'portal',
            ])
            ->add('mail', TextType::class, [
                'label' => 'Mail',
                'required' => true,
                'translation_domain' => 'portal',
            ])
            ->add('identityProviderUpdates', CheckboxType::class, [
                'label' => 'Update data from the identity provider',
                'translation_domain' => 'portal',
                'required' => false,
            ])
            ->add('contactTelephone', TextType::class, [
                'label' => 'Contact telephone',
                'required' => false,
                'translation_domain' => 'portal',
            ])
            ->add('contactMail', TextType::class, [
                'label' => 'Contact mail',
                'required' => false,
                'translation_domain' => 'portal',
            ])
            ->add('changePasswordURL', TextType::class, [
                'label' => 'URL for changing a password',
                'required' => false,
                'translation_domain' => 'portal',
            ])
            ->add('userMayCreateRooms', ChoiceType::class, [
                'choices' => [
                    'Yes' => '1',
                    'No' => '0'
                ],
                'placeholder' => false,
                'expanded' => true,
                'label' => 'Users may create rooms',
                'translation_domain' => 'portal',
                'required' => true,
            ])
            ->add('save', SubmitType::class, [
                'label' => 'Save',
                'translation_domain' => 'portal',
            ])
        ;
    }

    /**
     * Configures the options for this type.
     *
     * @param OptionsResolver $resolver The resolver for the options
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'translation_domain' => 'portal',
        ]);
    }
}