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

use App\Utils\MailAssistant;
use App\Validator\Constraints\SendRecipientsConstraint;
use FOS\CKEditorBundle\Form\Type\CKEditorType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Contracts\Translation\TranslatorInterface;

class SendType extends AbstractType
{
    public function __construct(private readonly MailAssistant $mailAssistant, private readonly TranslatorInterface $translator)
    {
    }

    /**
     * Builds the form.
     * This method is called for each type in the hierarchy starting from the top most type.
     * Type extensions can further modify the form.
     *
     * @param FormBuilderInterface $builder The form builder
     * @param array                $options The options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $mailAssistant = $this->mailAssistant;

        $uploadErrorMessage = $this->translator->trans('upload error', [], 'error');
        $noFileIdsMessage = $this->translator->trans('upload error', [], 'error');

        $builder
            ->add('subject', TextType::class, [
                'constraints' => [
                    new NotBlank(),
                ],
                'label' => 'Subject',
                'translation_domain' => 'mail',
                'required' => true,
                'attr' => [
                    'placeholder' => 'Subject',
                ],
            ])
            ->add('message', CKEditorType::class, [
                'label' => false,
                'translation_domain' => 'form',
                'required' => true,
                'config_name' => 'cs_mail_config',
            ])
            ->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($options, $mailAssistant) {
                $form = $event->getForm();

                if (isset($options['item']) && !empty($options['item'])) {
                    $item = $options['item'];
                    if ('date' === $item->getType()) {
                        $form
                            ->add('send_to_attendees', ChoiceType::class, [
                                'label' => 'Send to attendees',
                                'choices' => [
                                    'Yes' => true,
                                    'No' => false,
                                ],
                                'expanded' => true,
                                'translation_domain' => 'mail',
                                'choice_translation_domain' => 'form',
                                'required' => true,
                                'data' => false,
                            ])
                        ;
                    }

                    if ('todo' === $item->getType()) {
                        $form
                            ->add('send_to_assigned', ChoiceType::class, [
                                'label' => 'Send to assigned',
                                'choices' => [
                                    'Yes' => true,
                                    'No' => false,
                                ],
                                'expanded' => true,
                                'translation_domain' => 'mail',
                                'choice_translation_domain' => 'form',
                                'required' => true,
                            ])
                        ;
                    }

                    if ($mailAssistant->showGroupAllRecipients($item)) {
                        $form
                            ->add('send_to_group_all', ChoiceType::class, [
                                'label' => 'Send to all room members',
                                'choices' => [
                                    'Yes' => true,
                                    'No' => false,
                                ],
                                'expanded' => true,
                                'translation_domain' => 'mail',
                                'choice_translation_domain' => 'form',
                                'required' => true,
                            ])
                        ;
                    }

                    if ($mailAssistant->showGroupRecipients($item)) {
                        $groups = $mailAssistant->getGroupChoices($item);

                        $form
                            ->add('send_to_groups', ChoiceType::class, [
                                'label' => 'Send to groups',
                                'choices' => $groups,
                                'expanded' => true,
                                'multiple' => true,
                                'translation_domain' => 'mail',
                                'choice_translation_domain' => 'form',
                                'required' => false,
                            ])
                        ;
                    } elseif ($mailAssistant->showInstitutionRecipients($item)) {
                        $institutions = $mailAssistant->getInstitutionChoices($item);

                        $form
                            ->add('send_to_institutions', ChoiceType::class, [
                                'label' => 'Send to institution',
                                'choices' => $institutions,
                                'expanded' => true,
                                'multiple' => true,
                                'translation_domain' => 'mail',
                                'choice_translation_domain' => 'form',
                                'required' => true,
                            ])
                        ;
                    }

                    if ($mailAssistant->showAllMembersRecipients($item)) {
                        $form
                            ->add('send_to_all', ChoiceType::class, [
                                'label' => 'Send to all room members',
                                'choices' => [
                                    'Yes' => true,
                                    'No' => false,
                                ],
                                'expanded' => true,
                                'translation_domain' => 'mail',
                                'choice_translation_domain' => 'form',
                                'required' => true,
                            ])
                        ;
                    }
                } elseif (isset($options['users']) && !empty($options['users'])) {
                    $users = $options['users'];
                    $form
                        ->add('send_to_selected', ChoiceType::class, [
                            'label' => 'Send to selected room members',
                            'choices' => [
                                'Yes' => true,
                                'No' => false,
                            ],
                            'expanded' => true,
                            'translation_domain' => 'mail',
                            'choice_translation_domain' => 'form',
                            'required' => true,
                        ])
                    ;
                }
            })
            ->add('send_to_creator', ChoiceType::class, [
                'label' => 'Send to creator',
                'choices' => [
                    'Yes' => true,
                    'No' => false,
                ],
                'expanded' => true,
                'translation_domain' => 'mail',
                'choice_translation_domain' => 'form',
                'required' => true,
            ])
            ->add('copy_to_sender', ChoiceType::class, [
                'label' => 'Copy to sender',
                'choices' => [
                    'Yes' => true,
                    'No' => false,
                ],
                'expanded' => true,
                'translation_domain' => 'mail',
                'choice_translation_domain' => 'form',
                'required' => true,
            ])
            ->add('additional_recipients', CollectionType::class, [
                'label' => 'Additional recipients',
                'entry_type' => EmailType::class,
                'entry_options' => [
                    'attr' => [
                        'class' => 'uk-form-width-medium',
                    ],
                    'required' => false,
                    'label' => false,
                ],
                'allow_add' => true,
                'allow_delete' => true,
                'prototype' => true,
                'required' => false,
                'translation_domain' => 'mail',
                'constraints' => [new SendRecipientsConstraint()],
            ])
            ->add('upload', FileType::class, [
                'attr' => [
                    'data-uk-csupload' => '{"path": "'.$options['uploadUrl'].'", "errorMessage": "'.$uploadErrorMessage.'", "noFileIdsMessage": "'.$noFileIdsMessage.'"}',
                ],
                'required' => false,
                'multiple' => true,
                'label' => 'Attachments',
                'translation_domain' => 'mail',
            ])
            ->add('files', CollectionType::class, [
                'allow_add' => true,
                'entry_type' => CheckedFileType::class,
                'entry_options' => [
                ],
                'label' => false,
            ])
            ->add('save', SubmitType::class, [
                'attr' => [
                    'class' => 'uk-button-primary',
                ],
                'label' => 'Send',
                'translation_domain' => 'mail',
            ])
            ->add('cancel', SubmitType::class, [
                'attr' => [
                    'formnovalidate' => 'formnovalidate',
                ],
                'label' => 'cancel',
                'translation_domain' => 'form',
                'validation_groups' => false,
            ])
        ;
    }

    /**
     * Configures the options for this type.
     *
     * @param OptionsResolver $resolver The resolver for the options
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setRequired(['item', 'uploadUrl'])
            ->setAllowedTypes('item', 'cs_item')
            ->setAllowedTypes('uploadUrl', 'string')
            ->setDefaults([
                'users' => [],
                'item' => null,
            ])
        ;
    }
}
