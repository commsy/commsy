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
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\File;

#[WithMonologChannel('commsy')]
final readonly class OfficeFileFactory
{
    public function __construct(
        private LoggerInterface $logger,
        #[Autowire('%kernel.project_dir%')]
        private string $projectDir,
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

            $template = $this->projectDir . '/src/Resources/office/blank.' . $extension;
            if (!is_file($template)) {
                throw new Exception("Blank office template not found: $template");
            }

            $fs = new Filesystem();
            $tempFile = $fs->tempnam('/tmp', 'office_', ".$extension");
            $fs->copy($template, $tempFile, true);

            return new File($tempFile);
        } catch (Exception $e) {
            $this->logger->error('Could not create temporary file: ' . $e->getMessage());
            throw $e;
        }
    }
}
