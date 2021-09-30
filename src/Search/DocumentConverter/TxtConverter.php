<?php


namespace App\Search\DocumentConverter;


class TxtConverter extends AbstractDocumentConverter
{
    protected array $formatsAllowed = ['txt'];

    public function convertToText(string $completeFilePath): ?string
    {
        if (!file_exists($completeFilePath)) {
            return null;
        }

        return file_get_contents($completeFilePath);
    }
}