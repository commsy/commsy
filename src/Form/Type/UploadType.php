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

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class UploadType extends AbstractType
{
    public function __construct(
        /**
         * The Symfony translator.
         */
        private TranslatorInterface $translator
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
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $uploadErrorMessage = $this->translator->trans('upload error', [], 'error');
        $noFileIdsMessage = $this->translator->trans('upload error', [], 'error');

        $builder
            ->add('files', CollectionType::class, [
                'allow_add' => true,
                'entry_type' => CheckedFileType::class,
                'entry_options' => [
                ],
            ])
            ->add('upload', FileType::class, [
                'label' => 'upload',
                'attr' => [
                    'data-uk-csupload' => '{"path": "'.$options['uploadUrl'].'", "errorMessage": "'.$uploadErrorMessage.'", "noFileIdsMessage": "'.$noFileIdsMessage.'"}',
                ],
                'required' => false,
                'translation_domain' => 'material',
                'multiple' => true,
            ])
            ->add('save', SubmitType::class, [
                'attr' => [
                    'class' => 'uk-button-primary',
                ],
                'label' => 'save',
                'translation_domain' => 'form',
            ])
            ->add('cancel', SubmitType::class, [
                'attr' => [
                    'formnovalidate' => '',
                ],
                'label' => 'cancel',
                'translation_domain' => 'form',
            ])
        ;
    }

    /**
     * Configures the options for this type.
     *
     * @param OptionsResolver $resolver The resolver for the options
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setRequired(['uploadUrl'])
        ;
    }
}
