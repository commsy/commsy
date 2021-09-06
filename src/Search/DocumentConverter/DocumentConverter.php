<?php


namespace App\Search\DocumentConverter;


class DocumentConverter
{
    /**
     * @var ConverterManager
     */
    private ConverterManager $converterManager;

    /**
     * File2TextService constructor.
     * @param ConverterManager $converterManager
     */
    public function __construct(ConverterManager $converterManager)
    {
        $this->converterManager = $converterManager;
    }

    /**
     * @param $completeFilePath
     * @return string|null
     */
    public function convert($completeFilePath): ?string
    {
        if (isset($completeFilePath) && !is_file($completeFilePath)) {
            return null;
        }

        $fileArray = pathinfo($completeFilePath);
        $fileExtension = $fileArray['extension'];


        /** @var DocumentConverterInterface $converter */
        $converter = $this->converterManager->getConverter($fileExtension);

        if ($converter) {
            return $converter->convertToText($completeFilePath);
        }

        return null;
    }
}