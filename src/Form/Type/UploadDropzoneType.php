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
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class UploadDropzoneType extends AbstractType
{
    public function __construct(
        private readonly TranslatorInterface $translator
    ) {
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $jsOptions = [
            'path' => $options['uploadUrl'],
            'errorMessage' => $this->translator->trans('upload error', [], 'error'),
            'noFileIdsMessage' => $this->translator->trans('upload error', [], 'error'),
        ];

        $view->vars['attr']['data-uk-csupload'] = json_encode($jsOptions);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefaults([
                'label' => false,
                'required' => false,
                'translation_domain' => 'material',
                'multiple' => true,
                'errorMessage' => $this->translator->trans('upload error', [], 'error'),
                'noFileIdsMessage' => $this->translator->trans('upload error', [], 'error'),
            ])
            ->setRequired([
                'uploadUrl'
            ]);
    }

    public function getParent(): string
    {
        return FileType::class;
    }
}
