<?php


namespace App\DocumentConverter;


class TxtConverter extends AbstractDocumentConverter
{
    protected $formatsAllowed = ['txt'];
    public function convertToText(string $completeFilePath): ?string
    {
        $content = @file_get_contents($completeFilePath, FILE_BINARY);
        if (empty($content))
            return null;

        return $content;
    }
}