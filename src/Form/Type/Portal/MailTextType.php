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

namespace App\Form\Type\Portal;

use App\Form\Model\MailText;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ButtonType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MailTextType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('mailText', ChoiceType::class, [
                'placeholder' => 'No email text',
                'choices' => [
                    'General' => [
                        'Salutation' => 'MAIL_BODY_HELLO',
                        'Goodbye' => 'MAIL_BODY_CIAO',
                    ],
                    'Account' => [
                        'Delete user id(s)' => 'MAIL_BODY_USER_ACCOUNT_DELETE',
                        'Lock user id(s)' => 'MAIL_BODY_USER_ACCOUNT_LOCK',
                        'Activate user id(s)' => 'MAIL_BODY_USER_STATUS_USER',
                        'Status moderator' => 'MAIL_BODY_USER_STATUS_MODERATOR',
                        'Make contact' => 'MAIL_BODY_USER_MAKE_CONTACT_PERSON',
                        'Remove contact' => 'MAIL_BODY_USER_UNMAKE_CONTACT_PERSON',
                        'Password expires' => 'EMAIL_BODY_PASSWORD_EXPIRATION_SOON',
                        'Password is expired' => 'EMAIL_BODY_PASSWORD_EXPIRATION',
                        'Change password' => 'MAIL_BODY_USER_PASSWORD_CHANGE',
                    ],
                    'Workspace' => [
                        'Lock room' => 'MAIL_BODY_ROOM_LOCK',
                        'Unlock room' => 'MAIL_BODY_ROOM_UNLOCK',
                        'Delete room' => 'MAIL_BODY_ROOM_DELETE',
                        'Create room' => 'MAIL_BODY_ROOM_OPEN',
                    ],
                    'Deprovisioning' => [
                        'Lock room after X days' => 'EMAIL_INACTIVITY_ROOM_LOCK_UPCOMING_BODY',
                        'Delete room after X day' => 'EMAIL_INACTIVITY_ROOM_DELETE_UPCOMING_BODY',
                        'Lock userid in X days' => 'EMAIL_INACTIVITY_LOCK_NEXT_BODY',
                        'Userid was locked' => 'EMAIL_INACTIVITY_LOCK_NOW_BODY',
                        'Delete userid in X days' => 'EMAIL_INACTIVITY_DELETE_NEXT_BODY',
                        'Userid was deleted' => 'EMAIL_INACTIVITY_DELETE_NOW_BODY',
                    ],
                ],
                'required' => true,
                'label' => 'Mailtexts',
                'translation_domain' => 'portal',
                'attr' => [
                    'data-action' => 'live#action',
                    'data-action-name' => 'select',
                ]
            ])
            ->add('save', SubmitType::class, [
                'label' => 'save',
                'translation_domain' => 'form',
                'attr' => [
                    'data-action' => 'live#action',
                    'data-action-name' => 'prevent|save',
                    'data-loading' => 'addAttribute(disabled)',
                ],
            ])
        ;

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            /** @var ?MailText $data */
            $data = $event->getData();

            $mailText = $data?->getMailText();

            $this->addTranslationFields($event->getForm(), $mailText);
        });

        $builder->get('mailText')->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
            $mailText = $event->getForm()->getData();

            $test = $event->getForm()->getParent();

            /** @var MailText $data */
            $data = $test->getData();
            $data->setContentGerman('some content');
            $event->setData($data);

            $this->addTranslationFields($event->getForm()->getParent(), $mailText);
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => MailText::class,
            'translation_domain' => 'portal',
        ]);
    }

    private function addTranslationFields(FormInterface $form, ?string $mailText): void
    {
        $form
            ->add('contentGerman', TextareaType::class, [
                'label' => 'Content german',
                'disabled' => null === $mailText,
                'required' => false,
                'translation_domain' => 'portal',
            ])
            ->add('resetContentGerman', ButtonType::class, [
                'label' => 'Reset',
                'disabled' => null === $mailText,
                'attr' => [
                    'class' => 'uk-button-danger uk-button-small',
                    'data-action' => 'live#action',
                    'data-action-name' => 'resetContent(lang=de)',
                ],
            ])
            ->add('contentEnglish', TextareaType::class, [
                'label' => 'Content english',
                'disabled' => null === $mailText,
                'required' => false,
                'translation_domain' => 'portal',
            ])
            ->add('resetContentEnglish', ButtonType::class, [
                'label' => 'Reset',
                'disabled' => null === $mailText,
                'attr' => [
                    'class' => 'uk-button-danger uk-button-small',
                    'data-action' => 'live#action',
                    'data-action-name' => 'resetContent(lang=en)',
                ],
            ])
        ;
    }
}
