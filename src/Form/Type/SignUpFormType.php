<?php

namespace App\Form\Type;

use App\Entity\Account;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ButtonType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class SignUpFormType extends AbstractType
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('firstname', TextType::class, [
                'label' => 'registration.firstname',
                'attr' => [
                    'placeholder' => $this->translator->trans('registration.firstname', [], 'registration'),
                ],
            ])
            ->add('lastname', TextType::class, [
                'label' => 'registration.lastname',
                'attr' => [
                    'placeholder' => $this->translator->trans('registration.lastname', [], 'registration'),
                ],
            ])
            ->add('username', TextType::class, [
                'label' => 'registration.username',
                'attr' => [
                    'placeholder' => $this->translator->trans('registration.username', [], 'registration'),
                ],
            ])
            ->add('email', RepeatedType::class, [
                'type' => EmailType::class,
                'first_options'  => [
                    'label' => 'registration.email',
                    'attr' => [
                        'placeholder' => $this->translator->trans('registration.email', [], 'registration'),
                    ]
                ],
                'second_options' => [
                    'label' => false,
                    'attr' => [
                        'placeholder' => $this->translator->trans('registration.email_confirm', [], 'registration'),
                    ]
                ]
            ])
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'first_options'  => [
                    'label' => 'registration.password',
                    'attr' => [
                        'placeholder' => $this->translator->trans('registration.password', [], 'registration'),
                    ]
                ],
                'second_options' => [
                    'label' => false,
                    'attr' => [
                        'placeholder' => $this->translator->trans('registration.password_confirm', [], 'registration'),
                    ]
                ],
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'registration.submit',
                'attr' => [
                    'class' => 'uk-button-primary uk-width-medium',
                ]
            ])
            ->add('cancel', SubmitType::class, [
                'label' => 'registration.cancel',
                'attr' => [
                    'class' => 'uk-button-default uk-width-medium',
                    'formnovalidate' => '',
                ],
                'validation_groups' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Account::class,
            'translation_domain' => 'registration',
            'validation_groups' => ['Default', 'registration'],
        ]);
    }
}
