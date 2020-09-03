<?php


namespace App\Services;


use App\DocumentConverter\ConverterManager;
use App\DocumentConverter\DocumentConverterInterface;

class File2TextService
{

    private $fileName;
    private $multibyte = 4; // Use setUnicode(TRUE|FALSE)
    private $convertQuotes = ENT_QUOTES; // ENT_COMPAT (double-quotes), ENT_QUOTES (Both), ENT_NOQUOTES (None)
    private $showProgress = false; // TRUE if you have problems with time-out
    private $decodedText = '';
    /**
     * @var ConverterManager
     */
    private $converterManager;

    /**
     * File2TextService constructor.
     * @param $converterManager
     */
    public function __construct(ConverterManager $converterManager)
    {
        $this->converterManager = $converterManager;
    }


    public function convert($completeFilePath){
        if (isset($completeFilePath) && !file_exists($completeFilePath)) {
            return null;
        }

        $fileArray = pathinfo($completeFilePath);
        $fileExtension = $fileArray['extension'];


        /** @var DocumentConverterInterface $converter */
        $converter = $this->converterManager->getConverter($fileExtension);

        if($converter){
            return $converter->convertToText($completeFilePath);
        }

        return null;
    }

}