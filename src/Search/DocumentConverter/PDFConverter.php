<?php


namespace App\Search\DocumentConverter;


use Smalot\PdfParser\Parser;

class PDFConverter extends AbstractDocumentConverter
{
    /**
     * @var Parser
     */
    private Parser $parser;

    public function __construct()
    {
        $this->parser = new Parser();
    }

    protected array $formatsAllowed = ['pdf'];

    public function convertToText(string $completeFilePath): ?string
    {
        if (!file_exists($completeFilePath)) {
            return null;
        }

        try {
            $pdf = $this->parser->parseFile($completeFilePath);
            return $pdf->getText();
        } catch (\Exception $e) {
        }

        return null;
    }
}