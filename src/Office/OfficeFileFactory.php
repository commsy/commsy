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
            $extension = match ($type) {
                'word' => 'docx',
                'spreadsheet' => 'xlsx',
                'presentation' => 'pptx',
            };

            $fs = new Filesystem();
            $tempFile = $fs->tempnam('/tmp', 'office_', ".$extension");

            return new File($tempFile);
        } catch (Exception $e) {
            $this->logger->error('Could not create temporary file: ' . $e->getMessage());
            throw $e;
        }
    }
}
