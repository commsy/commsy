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

namespace App\Utils;

use App\Entity\Files;
use App\Services\LegacyEnvironment;
use cs_file_item;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class FileService
{
    public function __construct(
        private LegacyEnvironment $legacyEnvironment,
        private ParameterBagInterface $parameterBag
    ) {
    }

    public function makeAbsolute(Files $file): string
    {
        $projectDir = $this->parameterBag->get('kernel.project_dir');

        return $projectDir . '/' . $file->getFilepath();
    }

    public function getFile($fileId): ?cs_file_item
    {
        $fileManager = $this->legacyEnvironment->getEnvironment()->getFileManager();

        return $fileManager->getItem($fileId);
    }

    public function getNewFile()
    {
        $fileManager = $this->legacyEnvironment->getEnvironment()->getFileManager();

        return $fileManager->getNewItem();
    }
}
