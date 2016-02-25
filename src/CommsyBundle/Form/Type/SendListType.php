<?php
namespace CommsyBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;

use Symfony\Component\OptionsResolver\OptionsResolver;

use Symfony\Component\Validator\Constraints\NotBlank;

use CommsyBundle\Utils\MailAssistant;

class SendListType extends AbstractType
{
    private $mailAssistant;

    public function __construct(MailAssistant $mailAssistant) {
        $this->mailAssistant = $mailAssistant;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $mailAssistant = $this->mailAssistant;

        $builder
            ->add('subject', 'text', [
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
            ->add('message', 'textarea', [
                'label' => 'Message',
                'translation_domain' => 'form',
                'required' => true,
                'attr' => array('cols' => '80', 'rows' => '10'),
            ])
            ->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($options, $mailAssistant) {
                $form = $event->getForm();
                /* $item = $options['item'];

                if ($item->getType() == 'date') {
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
                            'choices_as_values' => true,
                        ])
                    ;
                }

                if ($item->getType() == 'todo') {
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
                            'choices_as_values' => true,
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
                            'choices_as_values' => true,
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
                            'required' => true,
                            'choices_as_values' => true,
                        ])
                    ;
                } else if ($mailAssistant->showInstitutionRecipients($item)) {
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
                            'choices_as_values' => true,
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
                            'choices_as_values' => true,
                        ])
                    ;
                } */
            })
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
                'choices_as_values' => true,
            ])
            /* ->add('additional_recipients', CollectionType::class, [
                'label' => 'Additional recipients',
                'entry_type' => EmailType::class,
                'entry_options' => [
                    'required' => false,
                    'label' => false,
                ],
                'allow_add' => true,
                'allow_delete' => true,
                'prototype' => true,
                'required' => false,
                'translation_domain' => 'mail',
            ]) */
            ->add('entries', HiddenType::class, array(
                'data' => '',
            ))
            ->add('save', 'submit', [
                'label' => 'Send',
                'translation_domain' => 'mail',
            ])
            ->add('cancel', 'submit', [
                'label' => 'cancel',
                'translation_domain' => 'form'
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        /* $resolver
            ->setRequired('item')
            ->setAllowedTypes('item', 'cs_item')
        ; */
    }

    public function getName()
    {
        return 'sendList';
    }
}