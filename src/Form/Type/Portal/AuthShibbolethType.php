<?php


namespace App\Form\Type\Portal;

use FOS\CKEditorBundle\Form\Type\CKEditorType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
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
                    'Shibboleth' => 'shib',
                ],
                'required' => true,
                'label' => 'Source',
                'mapped' => false,
                'data' => 'shib',
            ])
            ->add('type', SubmitType::class, [
                'attr' => [
                    'class' => 'uk-button-primary',
                    'formnovalidate' => '',
                ],
                'label' => 'Select',
                'validation_groups' => false,
            ])
            ->add('title', TextType::class, [
                'label' => 'Title',
                'required' => true,
            ])
            ->add('description', CKEditorType::class, [
                'label' => 'Description',
                'required' => false,
                'config_name' => 'cs_mail_config',
            ])
            ->add('default', CheckboxType::class, [
                'label' => 'Default',
                'required' => false,
            ])
            ->add('enabled', CheckboxType::class, [
                'label' => 'Available',
                'required' => false,
            ])
            ->add('loginUrl', UrlType::class, [
                'label' => 'Login URL',
                'help' => 'https://sp.example.org/Shibboleth.sso/Login',
            ])
            ->add('logoutUrl', UrlType::class, [
                'label' => 'Logout URL',
                'help' => 'https://sp.example.org/Shibboleth.sso/Logout',
            ])
            ->add('passwordResetURL', UrlType::class, [
                'label' => 'Password reset URL',
                'required' => false,
            ])
            ->add('mappingUsername', TextType::class, [
                'label' => 'Mapping: Username',
                'help' => 'eppn',
            ])
            ->add('mappingFirstname', TextType::class, [
                'label' => 'Mapping: Firstname',
                'help' => 'givenName',
            ])
            ->add('mappingLastname', TextType::class, [
                'label' => 'Mapping: Lastname',
                'help' => 'sn',
            ])
            ->add('mappingEmail', TextType::class, [
                'label' => 'Mapping: Email',
                'help' => 'mail',
            ])
            ->add('createRoom', CheckboxType::class, [
                'label' => 'Users may create rooms',
                'required' => false,
            ])
            ->add('save', SubmitType::class, [
                'label' => 'Save',
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