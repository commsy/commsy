<?php


namespace App\Services;


use App\DocumentConverter\ConverterManager;
use App\DocumentConverter\DocumentConverterInterface;

class File2TextService
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


    public function convert($completeFilePath)
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