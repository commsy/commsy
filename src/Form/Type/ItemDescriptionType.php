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
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ItemDescriptionType extends AbstractType
{
    public function __construct(
        #[Autowire('%commsy.upload.max_file_size%')]
        private readonly int $maxFileSize,
    ) {
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
        $builder
            ->add('description', CKEditorType::class, [
                'config_name' => $options['configName'],
                'label' => 'Description',
                'attr' => [
                    'placeholder' => 'Description',
                    'class' => 'uk-form-width-large ckeditor-upload',
                    'data-cs-filelisturl' => '{"path": "'.$options['filelistUrl'].'"}',
                ],
                'config' => [
                    // NOTE: the form-based editor upload method has to be set explicitly, since CKEditor >=4.9.0 uses 'xhr'
                    // as its default upload method; see https://ckeditor.com/docs/ckeditor4/latest/guide/dev_file_browser_api.html
                    'filebrowserUploadMethod' => 'form',
                    'filebrowserUploadUrl' => $options['uploadUrl'],
                    'maxUploadSize' => $this->getEffectiveMaxUploadSizeInBytes(),
                ],
                'translation_domain' => 'material',
                'required' => false,
            ])
        ;

        if (!isset($options['attr']['unsetRecurrence'])) {
            $builder
                ->add('save', SubmitType::class, [
                    'attr' => [
                        'class' => 'uk-button-primary',
                    ],
                    'label' => 'save',
                ])
                ->add('cancel', SubmitType::class, [
                    'attr' => [
                        'formnovalidate' => '',
                    ],
                    'label' => 'cancel',
                ])
            ;
        } else {
            $builder
                ->add('saveThisDate', SubmitType::class, [
                    'attr' => [
                        'class' => 'uk-button-primary',
                        ],
                    'label' => 'saveThisDate',
                    'translation_domain' => 'date',
                ])
                ->add('saveAllDates', SubmitType::class, [
                    'attr' => [
                        'class' => 'uk-button-primary',
                        ],
                    'label' => 'saveAllDates',
                    'translation_domain' => 'date',
                ])
                ->add('cancel', SubmitType::class, [
                    'attr' => [
                        'formnovalidate' => '',
                        ],
                    'label' => 'cancel',
                ])
            ;
        }
    }

    /**
     * Configures the options for this type.
     *
     * @param OptionsResolver $resolver The resolver for the options
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setRequired(['itemId', 'configName', 'uploadUrl', 'filelistUrl'])
            ->setDefaults([
                'translation_domain' => 'form',
                'lock_protection' => true,
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
    public function getBlockPrefix(): string
    {
        return 'itemDescription';
    }

    /**
     * Returns the effective maximum upload size in bytes: the app-level limit if configured, otherwise the PHP ini limit.
     */
    private function getEffectiveMaxUploadSizeInBytes(): int
    {
        if ($this->maxFileSize > 0) {
            return $this->maxFileSize * 1_048_576;
        }

        return UploadedFile::getMaxFilesize();
    }
}
