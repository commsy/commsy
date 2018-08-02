<?php
namespace CommsyBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Validator\Constraints\NotBlank;

use FOS\CKEditorBundle\Form\Type\CKEditorType;

use CommsyBundle\Utils\MailAssistant;

use CommsyBundle\Validator\Constraints\SendRecipientsConstraint;

class SendType extends AbstractType
{
    private $mailAssistant;

    public function __construct(MailAssistant $mailAssistant) {
        $this->mailAssistant = $mailAssistant;
    }

    /**
     * Builds the form.
     * This method is called for each type in the hierarchy starting from the top most type.
     * Type extensions can further modify the form.
     * 
     * @param  FormBuilderInterface $builder The form builder
     * @param  array                $options The options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $mailAssistant = $this->mailAssistant;

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
            ])
            ->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($options, $mailAssistant) {
                $form = $event->getForm();

                if(isset($options['item']) && !empty($options['item'])) {
                    $item = $options['item'];
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
                                'required' => true,
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
                } else if(isset($options['users']) && !empty($options['users'])) {
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
                'constraints' => array(
                    new SendRecipientsConstraint(),
                ),
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
     * @param  OptionsResolver $resolver The resolver for the options
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setRequired('item')
            ->setAllowedTypes('item', 'cs_item')
            ->setDefaults([
                'users' => [],
                'item' => null,
            ])
        ;
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
        return 'send';
    }
}