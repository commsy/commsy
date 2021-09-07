<?php


namespace App\Search\DocumentConverter;


interface DocumentConverterInterface
{

    public function convertToText(string $completeFilePath): ?string;

    public function supportsFormat(string $fileExtension): bool;

}