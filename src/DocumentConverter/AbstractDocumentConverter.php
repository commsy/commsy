<?php


namespace App\DocumentConverter;


abstract class AbstractDocumentConverter implements DocumentConverterInterface
{
    /**
     * @var array defines the formats supported for this converter
     */
    protected $formatsAllowed = [];

    /**
     * Method to check if a format is supported by this converter
     * @param string $fileExtension
     * @return bool
     * @throws \Exception
     */
    public function supportsFormat(string $fileExtension) : bool {
        if(!is_array($this->formatsAllowed)){
            throw new \Exception("The property formatsAllowed must be an array");
        }
        return in_array(str_replace('.', '', $fileExtension), $this->formatsAllowed);
    }
}