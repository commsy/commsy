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

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MailtextsType extends AbstractType
{
    /**
     * Builds the form.
     *
     * @param FormBuilderInterface $builder The form builder
     * @param array                $options The options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('userIndexFilterChoice', ChoiceType::class, [
                'choices' => [
                    'No email text' => '',
                    '----------------' => '',
                    'Salutation' => 'MAIL_BODY_HELLO',
                    'Goodbye' => 'MAIL_BODY_CIAO',
                    '-----------------' => '',
                    'Delete user id(s)' => 'MAIL_BODY_USER_ACCOUNT_DELETE',
                    'Lock user id(s)' => 'MAIL_BODY_USER_ACCOUNT_LOCK',
                    'Activate user id(s)' => 'MAIL_BODY_USER_STATUS_USER',
                    'Status moderator' => 'MAIL_BODY_USER_STATUS_MODERATOR',
                    'Make contact' => 'MAIL_BODY_USER_MAKE_CONTACT_PERSON',
                    'Remove contact' => 'MAIL_BODY_USER_UNMAKE_CONTACT_PERSON',
                    'Password expires' => 'EMAIL_BODY_PASSWORD_EXPIRATION_SOON',
                    'Password is expired' => 'EMAIL_BODY_PASSWORD_EXPIRATION',
                    'Change password' => 'MAIL_BODY_USER_PASSWORD_CHANGE',
                    '------------------' => '',
                    'Lock room' => 'MAIL_BODY_ROOM_LOCK',
                    'Unlock room' => 'MAIL_BODY_ROOM_UNLOCK',
                    'Delete room' => 'MAIL_BODY_ROOM_DELETE',
                    'Create room' => 'MAIL_BODY_ROOM_OPEN',
                    '-------------------' => 28,
                    'Lock room after X days' => 'EMAIL_INACTIVITY_ROOM_LOCK_UPCOMING_BODY',
                    'Delete room after X day' => 'EMAIL_INACTIVITY_ROOM_DELETE_UPCOMING_BODY',
                    '--------------------' => 29,
                    'Lock userid in X days' => 'EMAIL_INACTIVITY_LOCK_NEXT_BODY',
                    'Userid was locked' => 'EMAIL_INACTIVITY_LOCK_NOW_BODY',
                    'Delete userid in X days' => 'EMAIL_INACTIVITY_DELETE_NEXT_BODY',
                    'Userid was deleted' => 'EMAIL_INACTIVITY_DELETE_NOW_BODY',
                ],
                'required' => true,
                'label' => 'Mailtexts',
                'translation_domain' => 'portal',
            ])
            ->add('loadMailTexts', SubmitType::class, [
                'label' => 'Load mail texts',
                'translation_domain' => 'portal',
            ])
            ->add('contentGerman', TextareaType::class, [
                'label' => 'Content german',
                'required' => false,
                'translation_domain' => 'portal',
                'attr' => [
                    'rows' => 10,
                ],
            ])
            ->add('resetContentGerman', CheckboxType::class, [
                'label' => 'Reset',
                'required' => false,
            ])
            ->add('contentEnglish', TextareaType::class, [
                'label' => 'Content english',
                'required' => false,
                'translation_domain' => 'portal',
                'attr' => [
                    'rows' => 10,
                ],
            ])
            ->add('resetContentEnglish', CheckboxType::class, [
                'label' => 'Reset',
                'required' => false,
            ])
            ->add('save', SubmitType::class, [
                'label' => 'save',
                'translation_domain' => 'form',
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
