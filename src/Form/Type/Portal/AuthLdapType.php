<?php


namespace App\Form\Type\Portal;

use FOS\CKEditorBundle\Form\Type\CKEditorType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AuthLdapType extends AbstractType
{

    /**
     * Builds the form.
     *
     * @param FormBuilderInterface $builder The form builder
     * @param array $options The options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('typeChoice', ChoiceType::class, [
                'choices' => [
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
                'help' => 'Pre select this authentication in the login box (deselect all others)'
            ])
            ->add('enabled', CheckboxType::class, [
                'label' => 'Available',
                'required' => false,
            ])
            ->add('serverUrl', UrlType::class, [
                'attr' => [
                    'placeholder' => 'ldaps://my-server:636',
                ],
                'label' => 'Server URL',
                'help' => 'ldaps://example.org',
                'default_protocol' => 'ldaps://',
            ])
            ->add('uidKey', TextType::class, [
                'attr' => [
                    'placeholder' => 'sAMAccountName',
                ],
                'label' => 'UID Key: Username',
                'help' => 'The entry’s key to use as its UID.',
            ])
            ->add('baseDn', TextType::class, [
                'attr' => [
                    'placeholder' => 'dc=example,dc=com',
                ],
                'label' => 'Base DN',
                'help' => 'The base DN for the directory.',
            ])
            ->add('searchDn', TextType::class, [
                'attr' => [
                    'placeholder' => 'cn=read-only-admin,dc=example,dc=com',
                ],
                'label' => 'Search DN',
                'help' => 'The read-only user’s DN, which will be used to authenticate against the LDAP server to fetch the user’s information.',
            ])
            ->add('searchPassword', TextType::class, [
                'label' => 'Search Password',
                'help' => 'The read-only user’s password, which will be used to authenticate against the LDAP server to fetch the user’s information.',
            ])
            ->add('authDn', TextType::class, [
                'attr' => [
                    'placeholder' => 'uid={username},dc=example,dc=com',
                ],
                'label' => 'Auth DN',
                'help' => 'This key defines the form of the string used to compose the DN of the user, from the username. The {username} string is replaced by the actual username of the person trying to authenticate.',
            ])
            ->add('createRoom', CheckboxType::class, [
                'label' => 'Users may create rooms',
                'required' => false,
            ])
            ->add('save', SubmitType::class, [
                'label' => 'Save',
            ]);
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