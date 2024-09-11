<?php

/*
 * This file is part of CommSy.
 *
 * (c) Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
 * Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
 * Edouard Simon, Monique Strauss, Jose Mauel Gonzalez Vazquez, Johannes Schultze
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace App\Office;

use Exception;
use Monolog\Attribute\WithMonologChannel;
use PhpOffice\PhpPresentation\PhpPresentation;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpWord\IOFactory as WordIOFactory;
use PhpOffice\PhpSpreadsheet\IOFactory as SpreadsheetIOFactory;
use PhpOffice\PhpPresentation\IOFactory as PresentationIOFactory;
use PhpOffice\PhpWord\PhpWord;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\File;

#[WithMonologChannel('commsy')]
final readonly class OfficeFileFactory
{
    public function __construct(
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @throws Exception
     */
    public function create(string $type): File {
        try {
            $this->logger->debug('create file');
            $writer = match ($type) {
                'word' => WordIOFactory::createWriter(new PhpWord(), 'Word2007'),
                'spreadsheet' => SpreadsheetIOFactory::createWriter(new Spreadsheet(), SpreadsheetIOFactory::WRITER_XLSX),
                'presentation' => PresentationIOFactory::createWriter(new PhpPresentation(), 'PowerPoint2007'),
            };

            $extension = match ($type) {
                'word' => 'docx',
                'spreadsheet' => 'xlsx',
                'presentation' => 'pptx',
            };

            $fs = new Filesystem();
            $tempFile = $fs->tempnam('/tmp', 'office_', ".$extension");

            $writer->save($tempFile);

            return new File($tempFile);
        } catch (Exception $e) {
            $this->logger->error('Could not create temporary file: ' . $e->getMessage());
            throw $e;
        }
    }
}
