<?php


namespace App\DocumentConverter;


class WordConverter extends AbstractDocumentConverter
{
    protected $formatsAllowed = ['doc', 'docx'];

    public function convertToText(string $completeFilePath): ?string
    {
        $fileArray = pathinfo($completeFilePath);
        $fileExtension = $fileArray['extension'];
        if ($fileExtension == "doc" || $fileExtension == "docx") {
            if ($fileExtension == "doc") {
                return $this->readDoc($completeFilePath);
            } else {
                return $this->readDocx($completeFilePath);
            }
        }
    }

    private function readDoc($completeFilePath): ?string
    {
        $fileHandle = fopen($completeFilePath, "r");
        $line = @fread($fileHandle, filesize($completeFilePath));
        $lines = explode(chr(0x0D), $line);
        $outText = "";
        foreach ($lines as $thisline) {
            $pos = strpos($thisline, chr(0x00));
            if (($pos !== FALSE) || (strlen($thisline) == 0)) {
            } else {
                $outText .= $thisline . " ";
            }
        }
        $outText = preg_replace("/[^a-zA-Z0-9\s\,\.\-\n\r\t@\/\_\(\)]/", "", $outText);
        return $outText;
    }

    private function readDocx($completeFilePath): ?string
    {
        $content = '';

        $zip = zip_open($completeFilePath);
        if (!$zip || is_numeric($zip)) return false;

        while ($zip_entry = zip_read($zip)) {
            if (zip_entry_open($zip, $zip_entry) == FALSE) continue;
            if (zip_entry_name($zip_entry) != "word/document.xml") continue;
            $content .= zip_entry_read($zip_entry, zip_entry_filesize($zip_entry));
            zip_entry_close($zip_entry);
        }

        zip_close($zip);

        $content = str_replace('</w:r></w:p></w:tc><w:tc>', " ", $content);
        $content = str_replace('</w:r></w:p>', "\r\n", $content);
        $content = strip_tags($content);
        return $content;
    }
}