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

namespace App\Validator;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;

readonly class UploadSizeValidator
{
    public function __construct(
        private TranslatorInterface $translator,
    ) {
    }

    /**
     * Validates a single uploaded file against the given app-level limit (in MB) and PHP-level errors.
     * Returns a translated error message string, or null if the file is valid.
     */
    public function validateFileSize(UploadedFile $file, int $appLimitMb): ?string
    {
        if (UPLOAD_ERR_INI_SIZE === $file->getError()) {
            $phpLimitMb = $this->getPhpMaxUploadSizeInMegabytes();

            return $this->translator->trans('upload error size limit', ['%max_size%' => $phpLimitMb], 'error');
        }

        if ($appLimitMb > 0 && $file->getSize() > $appLimitMb * 1_048_576) {
            return $this->translator->trans('upload error size limit', ['%max_size%' => $appLimitMb], 'error');
        }

        return null;
    }

    /**
     * Checks whether the request's Content-Length exceeds post_max_size, which would cause PHP
     * to silently discard $_FILES and $_POST entirely.
     */
    public function isPostMaxSizeExceeded(Request $request): bool
    {
        $contentLength = $request->server->getInt('CONTENT_LENGTH', 0);
        if ($contentLength <= 0) {
            return false;
        }

        $postMaxSize = UploadedFile::getMaxFilesize();

        return $postMaxSize > 0 && $contentLength > $postMaxSize;
    }

    /**
     * Returns the PHP-level maximum upload size in megabytes (for use in error messages).
     */
    public function getPhpMaxUploadSizeInMegabytes(): int
    {
        return (int) round(UploadedFile::getMaxFilesize() / 1_048_576);
    }
}
