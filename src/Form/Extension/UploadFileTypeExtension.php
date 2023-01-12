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

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

class UploadFileTypeExtension extends AbstractTypeExtension
{
    /**
     * Returns the class of the type being extended.
     *
     * @return string the class of the type being extended
     */
    public static function getExtendedTypes(): iterable
    {
        return [FileType::class];
    }

    /**
     * Pass the currently defined maximum file upload size to the view.
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $maxUploadFileSize = $this->getMaxUploadSizeInMegabytes();

        if (!empty($maxUploadFileSize)) {
            // set a "max_upload_size" variable that will be available when rendering this field
            $view->vars['max_upload_size'] = $maxUploadFileSize;
        }
    }

    /**
     * Returns the maximum file size (in megabytes) that's allowed by this server for any file upload.
     */
    private function getMaxUploadSizeInMegabytes(): float
    {
        $maxUploadSizeInBytes = $this->getConfigValueInBytes('upload_max_filesize');
        $maxUploadSizeInMegabytes = round($maxUploadSizeInBytes / 1_048_576);

        return $maxUploadSizeInMegabytes;
    }

    /**
     * For a PHP configuration key whose value describes a size in (kilo/mega)bytes, returns the value in bytes.
     * Returns 0.0 on failure.
     *
     * @param string $configName The PHP configuration key whose size value shall be retrieved via `ini_get`.
     *                           Note that the value must resolve to a number or a number followed by a one-letter suffix (like "1k" or "2M").
     */
    private function getConfigValueInBytes(string $configName): float
    {
        $value = ini_get($configName);
        if (empty($value)) {
            return 0.0;
        }

        // if necessary, convert to a number in bytes
        $value = trim($value);
        $suffix = strtolower($value[strlen($value) - 1]);
        $value = intval($value);
        return match ($suffix) {
            'k' => $value * 1024,
            'm' => $value * 1_048_576,
            default => $value,
        };
    }
}
