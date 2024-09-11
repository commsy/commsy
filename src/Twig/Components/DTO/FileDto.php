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

namespace App\Twig\Components\DTO;

use Symfony\Component\DependencyInjection\Attribute\Exclude;

#[Exclude]
class FileDto
{
    public int $fileId;

    public int $contextId;

    public string $extension;

    public string $filename;

    public string $filenameNoExt;

    public int $fileSize;
}
