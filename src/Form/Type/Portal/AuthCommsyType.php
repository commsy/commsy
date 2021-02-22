<?php


namespace App\Form\Type\Portal;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AuthCommsyType extends AbstractType
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
                    'Guest' => 'guest',
                ],
                'required' => true,
                'label' => 'Source',
                'mapped' => false,
                'data' => 'commsy',
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
            ->add('changeUserID', ChoiceType::class, [
                'choices' => [
                    'Yes' => '1',
                    'No' => '0'
                ],
                'placeholder' => false,
                'expanded' => true,
                'label' => 'Change user ID',
                'translation_domain' => 'portal',
                'required' => false,
            ])
            ->add('changeIdentification', ChoiceType::class, [
                'choices' => [
                    'Yes' => '1',
                    'No' => '0'
                ],
                'placeholder' => false,
                'expanded' => true,
                'label' => 'Change identification',
                'translation_domain' => 'portal',
                'required' => false,
            ])
            ->add('changePassword', ChoiceType::class, [
                'choices' => [
                    'Yes' => '1',
                    'No' => '0'
                ],
                'placeholder' => false,
                'expanded' => true,
                'label' => 'Change password',
                'translation_domain' => 'portal',
                'required' => false,
            ])
            ->add('createIdentifiaction', ChoiceType::class, [
                'choices' => [
                    'Yes' => '1',
                    'No' => '0',
                    'After invitation' => '2',
                ],
                'placeholder' => false,
                'expanded' => true,
                'label' => 'Create identification',
                'translation_domain' => 'portal',
                'required' => false,
            ])
            ->add('deleteIdentifiaction', ChoiceType::class, [
                'choices' => [
                    'Yes' => '1',
                    'No' => '0'
                ],
                'placeholder' => false,
                'expanded' => true,
                'label' => 'Delete identification',
                'translation_domain' => 'portal',
                'required' => false,
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
            ->add('mailRegEx', TextType::class, [
                'label' => 'Mail regular expression',
                'translation_domain' => 'portal',
                'required' => false,
                'help' => 'Please including delimiter and modifier',
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