<?php


namespace App\Search\DocumentConverter;


use Exception;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Writer\HTML;

class WordConverter extends AbstractDocumentConverter
{
    protected array $formatsAllowed = ['doc', 'docx'];

    public function convertToText(string $completeFilePath): ?string
    {
        if (!file_exists($completeFilePath)) {
            return null;
        }

        $word = null;
        $fileArray = pathinfo($completeFilePath);
        $fileExtension = $fileArray['extension'];

        /** @var HTML $htmlWriter */
        try {
            if ($fileExtension == "doc" || $fileExtension == "docx") {
                if ($fileExtension == "doc") {
                    $word = IOFactory::load($completeFilePath, 'MsDoc');
                } else {
                    $word = IOFactory::load($completeFilePath);
                }
            }

            $htmlWriter = IOFactory::createWriter($word, 'HTML');
            $content = $htmlWriter->getWriterPart('Body')->write();

            return strip_tags($content);
        } catch (Exception $e) {
            return null;
        }
    }
}