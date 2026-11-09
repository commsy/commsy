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

namespace App\Rubric\Dates;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Where the calendar files uploaded for an import into a room wait. The form
 * hands the name of an upload back, so a name is only taken when it names an
 * upload for that room.
 */
final readonly class ICalImportUploads
{
    public function __construct(
        #[Autowire(param: 'kernel.project_dir')]
        private string $projectDir,
    ) {
    }

    /**
     * Keeps the file for an import into the room and gives back its name.
     */
    public function keep(UploadedFile $file, int $roomId): string
    {
        $name = $roomId.'_'.date('Ymdhis').'_'.$file->getClientOriginalName();
        $file->move($this->directory(), $name);

        return $name;
    }

    /**
     * The upload for the room the name stands for; none for a name that
     * leads elsewhere.
     */
    public function uploadAt(int $roomId, string $name): ?string
    {
        if ($name !== basename($name) || !str_starts_with($name, $roomId.'_')) {
            return null;
        }

        $real = realpath($this->directory().'/'.$name);
        $directory = realpath($this->directory());
        if (false === $real || false === $directory || dirname($real) !== $directory || !is_file($real)) {
            return null;
        }

        return $real;
    }

    private function directory(): string
    {
        return $this->projectDir.'/var/temp';
    }
}
