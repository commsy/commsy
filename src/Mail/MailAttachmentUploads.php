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

declare(strict_types=1);

namespace App\Mail;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Where the files uploaded for a mail wait until it is sent. The form hands
 * the path of an upload back, so a path is only taken when it names such an
 * upload.
 */
final readonly class MailAttachmentUploads
{
    private const string NAME = '/^[0-9a-f]{32}\.[A-Za-z0-9]*$/';

    public function __construct(
        #[Autowire(param: 'files_directory')]
        private string $filesDirectory,
    ) {
    }

    /**
     * Keeps the file for a mail and gives back its path.
     */
    public function keep(UploadedFile $file): string
    {
        $name = md5(uniqid('', true)).'.'.$file->guessExtension();
        $file->move($this->directory(), $name);

        return $this->directory().'/'.$name;
    }

    /**
     * The upload the path names; none for a path that leads elsewhere.
     */
    public function uploadAt(string $path): ?string
    {
        if (1 !== preg_match(self::NAME, basename($path))) {
            return null;
        }

        $real = realpath($path);
        $directory = realpath($this->directory());
        if (false === $real || false === $directory || dirname($real) !== $directory || !is_file($real)) {
            return null;
        }

        return $real;
    }

    private function directory(): string
    {
        return $this->filesDirectory.'/temp';
    }
}
