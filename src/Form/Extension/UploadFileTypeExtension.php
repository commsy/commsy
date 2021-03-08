<?php

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
     * @return string The class of the type being extended.
     */
    public static function getExtendedTypes(): iterable
    {
        return [FileType::class];
    }

    /**
     * Pass the currently defined maximum file upload size to the view
     *
     * @param FormView $view
     * @param FormInterface $form
     * @param array $options
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
     * Returns the maximum file size (in megabytes) that's allowed by this server for any file upload
     * @return float
     */
    private function getMaxUploadSizeInMegabytes(): float
    {
        $maxUploadSizeInBytes = $this->getConfigValueInBytes('upload_max_filesize');
        $maxUploadSizeInMegabytes = round($maxUploadSizeInBytes / 1048576);

        return $maxUploadSizeInMegabytes;
    }

    /**
     * For a PHP configuration key whose value describes a size in (kilo/mega)bytes, returns the value in bytes.
     * Returns 0.0 on failure.
     * @param string $configName The PHP configuration key whose size value shall be retrieved via `ini_get`.
     * Note that the value must resolve to a number or a number followed by a one-letter suffix (like "1k" or "2M").
     * @return float
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
        switch ($suffix) {
            case 'k':
                $value *= 1024;
                break;
            case 'm':
                $value *= 1048576;
                break;
        }

        return $value;
    }
}
