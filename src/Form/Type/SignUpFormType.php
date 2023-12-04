<?php

/*
 * This file is part of CommSy.
 *
 * (c) Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
 * Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
 * Edouard Simon, Monique Strauss, Jose Mauel Gonzalez Vazquez, Johannes Schultze
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace App\Form\Type;

use App\Entity\Account;
use App\Entity\Portal;
use App\Repository\TranslationRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Contracts\Translation\TranslatorInterface;

class SignUpFormType extends AbstractType
{
    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly TranslationRepository $translationRepository,
        private readonly RequestStack $requestStack
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Portal $portal */
        $portal = $options['portal'];

        $usernameHelp = $this->translationRepository
            ->findOneByContextAndKey($portal->getId(), 'REGISTRATION_USERNAME_HELP')
            ->getTranslationForLocale($this->requestStack->getCurrentRequest()->getLocale());

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
                'help' => $usernameHelp,
            ])
            ->add('email', RepeatedType::class, [
                'type' => EmailType::class,
                'first_options' => [
                    'label' => 'registration.email',
                    'attr' => [
                        'placeholder' => $this->translator->trans('registration.email', [], 'registration'),
                    ],
                ],
                'second_options' => [
                    'label' => false,
                    'attr' => [
                        'placeholder' => $this->translator->trans('registration.email_confirm', [], 'registration'),
                    ],
                ],
            ])
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'first_options' => [
                    'label' => 'registration.password',
                    'attr' => [
                        'placeholder' => $this->translator->trans('registration.password', [], 'registration'),
                    ],
                ],
                'second_options' => [
                    'label' => false,
                    'attr' => [
                        'placeholder' => $this->translator->trans('registration.password_confirm', [], 'registration'),
                    ],
                    'help' => 'registration.password_help',
                ],
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'registration.submit',
                'attr' => [
                    'class' => 'uk-button-primary uk-width-medium',
                ],
            ])
            ->add('cancel', SubmitType::class, [
                'label' => 'registration.cancel',
                'attr' => [
                    'class' => 'uk-button-default uk-width-medium',
                    'formnovalidate' => '',
                ],
                'validation_groups' => false,
            ]);

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($portal) {
            /** @var Portal $portal */
            if (!$portal->hasAGBEnabled()) {
                return;
            }

            $form = $event->getForm();

            $form->add('touAccept', CheckboxType::class, [
                'label' => false,
                'mapped' => false,
                'constraints' => new NotBlank(),
            ]);
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefaults([
                'data_class' => Account::class,
                'translation_domain' => 'registration',
                'validation_groups' => ['Default', 'registration'],
            ])
            ->setRequired(['portal'])
            ->setAllowedTypes('portal', [Portal::class]);
    }
}
