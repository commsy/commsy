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

use FOS\CKEditorBundle\Form\Type\CKEditorType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Contracts\Translation\TranslatorInterface;

class GroupSendType extends AbstractType
{
    public function __construct(private readonly TranslatorInterface $translator)
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
                'constraints' => [
                    new NotBlank(),
                ],
                'label' => false,
                'translation_domain' => 'form',
                'required' => false,
                'config_name' => 'cs_mail_config',
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
            ->add('groups', CollectionType::class, [
                'entry_type' => HiddenType::class,
                'label' => false,
                'required' => true,
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
            ->setRequired(['uploadUrl'])
            ->setAllowedTypes('uploadUrl', 'string')
        ;
    }
}
