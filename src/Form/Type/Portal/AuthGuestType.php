<?php


namespace App\Form\Type\Portal;

use FOS\CKEditorBundle\Form\Type\CKEditorType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AuthGuestType extends AbstractType
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
                    'Guest' => 'guest',
                ],
                'required' => true,
                'label' => 'Source',
                'mapped' => false,
                'data' => 'guest',
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