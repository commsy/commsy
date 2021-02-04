<?php


namespace App\Form\Type\Portal;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AuthLdapType extends AbstractType
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
                    'Shibboleth' => 'shib',
                ],
                'required' => true,
                'label' => 'Source',
                'mapped' => false,
                'data' => 'ldap',
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
            ->add('serverAddress', TextType::class, [
                'label' => 'Server',
                'required' => true,
                'translation_domain' => 'portal',
            ])
            ->add('userIdLdapField', TextType::class, [
                'label' => 'UserID (LDAP field)',
                'required' => true,
                'translation_domain' => 'portal',
                'help' => 'In which field are UserID Ldap stored? (samaccountname)',
            ])
            ->add('path', TextType::class, [
                'label' => 'Path name',
                'required' => true,
                'translation_domain' => 'portal',
                'help' => 'Reading rights path',
            ])
            ->add('userName', TextType::class, [
                'label' => 'User name',
                'required' => true,
                'translation_domain' => 'portal',
            ])
            ->add('password', TextType::class, [
                'label' => 'Password',
                'required' => true,
                'translation_domain' => 'portal',
            ])
            ->add('encryption', ChoiceType::class, [
                'choices' => [
                    'None' => '1',
                    'MD5' => '0'
                ],
                'placeholder' => false,
                'expanded' => true,
                'label' => 'Encryption',
                'translation_domain' => 'portal',
                'required' => true,
                'help' => 'Encryption of the passwords'
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
                'required' => false,
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
            'attr' => [
                'novalidate' => 'novalidate',
            ]
        ]);
    }
}