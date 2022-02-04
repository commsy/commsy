<?php

namespace App\Form\Type\Account;

use App\Services\LegacyEnvironment;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Validator\Constraints\UserPassword;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotCompromisedPassword;
use Symfony\Component\Validator\Constraints\Regex;

class ChangePasswordType extends AbstractType
{
    private $legacyEnvironment;

    public function __construct(LegacyEnvironment $legacyEnvironment)
    {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
    }

    /**
     * Builds the form.
     * This method is called for each type in the hierarchy starting from the top most type.
     * Type extensions can further modify the form.
     *
     * @param FormBuilderInterface $builder The form builder
     * @param array $options The options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('old_password', PasswordType::class, [
                'label' => 'currentPassword',
                'required' => true,
                'constraints' => [
                    new UserPassword(),
                ],
            ])
            ->add('new_password', RepeatedType::class, [
                'type' => PasswordType::class,
                'invalid_message' => 'Passwords do not match',
                'label' => 'newPassword',
                'options' => [
                    'required' => true
                ],
                'first_options' => [
                    'label' => 'newPassword',
                    'constraints' => [
                        new NotBlank(),
                        new NotCompromisedPassword(),
                        new Length([
                            'min' => 8,
                            'minMessage' => 'Your password must be at least {{ limit }} characters long.',
                            'allowEmptyString' => false,
                            ]),
                        new Regex([
                            'pattern' => '/(*UTF8)[\p{Ll}\p{Lm}\p{Lo}]/', // any lowercase/modifier/other Unicode letters
                            'message' => 'Your password must contain at least one lowercase character.',
                            ]),
                        new Regex([
                            'pattern' => '/(*UTF8)[\p{Lu}\p{Lt}]/', // any upper/title case Unicode letters
                            'message' => 'Your password must contain at least one uppercase character.',
                            ]),
                        new Regex([
                            'pattern' => '/[[:punct:]]/', // any printing characters excluding letters, digits & space
                            'message' => 'Your password must contain at least one special character.',
                        ]),
                        new Regex([
                            'pattern' => '/\p{Nd}/', // any decimal numbers
                            'message' => 'Your password must contain at least one numeric character.',
                        ])
                    ],
                ],
                'second_options' => [
                    'label' => 'newPasswordConfirm'
                ],
            ])
            ->add('save', SubmitType::class, [
                'label' => 'save',
                'translation_domain' => 'form',
                'attr' => [
                    'class' => 'uk-button-primary',
                ],
            ]);
    }

    /**
     * Configures the options for this type.
     *
     * @param OptionsResolver $resolver The resolver for the options
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setDefaults(['translation_domain' => 'profile']);
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
        return 'profile_changepassword';
    }
}
