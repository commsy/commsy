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

namespace App\Form\Extension;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class UploadFileTypeExtension extends AbstractTypeExtension
{
    public function __construct(
        #[Autowire('%commsy.upload.max_file_size%')]
        private readonly int $maxFileSize,
        #[Autowire('%commsy.upload.max_email_attachment_size%')]
        private readonly int $maxEmailAttachmentSize,
        private readonly TranslatorInterface $translator,
    ) {
    }

    /**
     * Returns the class of the type being extended.
     */
    public static function getExtendedTypes(): iterable
    {
        return [FileType::class];
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefined(['upload_url', 'upload_limit_mb']);
        $resolver->setDefault('email_attachment', false);
        $resolver->setAllowedTypes('email_attachment', 'bool');
    }

    /**
     * Pass the currently defined maximum file upload size to the view.
     */
    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $maxUploadFileSize = $this->resolveMaxUploadSize($options);

        if (!empty($maxUploadFileSize)) {
            $view->vars['max_upload_size'] = $maxUploadFileSize;
        }

        if (isset($options['upload_url'])) {
            $view->vars['attr']['data-upload'] = json_encode(['path' => $options['upload_url']]);
        }
    }

    /**
     * Runs after all buildView calls are complete — including child types like UploadDropzoneType.
     * Builds or enriches the data-uk-csupload JSON with all upload configuration.
     */
    public function finishView(FormView $view, FormInterface $form, array $options): void
    {
        $uploadUrl = $options['upload_url'] ?? null;
        $hasCsupload = isset($view->vars['attr']['data-uk-csupload']);

        if (!$uploadUrl && !$hasCsupload) {
            return;
        }

        $maxUploadFileSize = $this->resolveMaxUploadSize($options);
        $maxFileUploads = (int) (ini_get('max_file_uploads') ?: 20);

        $csuploadData = $hasCsupload
            ? json_decode($view->vars['attr']['data-uk-csupload'], true) ?? []
            : [];

        if ($uploadUrl) {
            $csuploadData['path'] ??= $uploadUrl;
        }

        $csuploadData += [
            'errorMessage' => $this->translator->trans('upload error', [], 'error'),
            'noFileIdsMessage' => $this->translator->trans('upload error', [], 'error'),
            'maxFileUploads' => $maxFileUploads,
            'fileLimitMessage' => $this->translator->trans('upload error file limit', [
                '%max_limit%' => $maxFileUploads,
            ], 'error'),
        ];

        if (!empty($maxUploadFileSize)) {
            $csuploadData['maxFileSizeMb'] = $maxUploadFileSize;
            $csuploadData['fileSizeLimitMessage'] = $this->translator->trans(
                'upload error size limit',
                ['%max_size%' => $maxUploadFileSize],
                'error'
            );
        }

        $view->vars['attr']['data-uk-csupload'] = json_encode($csuploadData);
    }

    private function resolveMaxUploadSize(array $options): float
    {
        if (isset($options['upload_limit_mb']) && $options['upload_limit_mb'] > 0) {
            return (float) $options['upload_limit_mb'];
        }

        if (!empty($options['email_attachment']) && $this->maxEmailAttachmentSize > 0) {
            return (float) $this->maxEmailAttachmentSize;
        }

        if ($this->maxFileSize > 0) {
            return (float) $this->maxFileSize;
        }

        return round(UploadedFile::getMaxFilesize() / 1_048_576);
    }
}
