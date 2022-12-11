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

namespace App\Controller\Api;

use App\Entity\Server;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class GetServerAnnouncement extends AbstractController
{
    public function __invoke(Server $data): array
    {
        $server = $data;

        return [
            'enabled' => $server->hasAnnouncementEnabled(),
            'title' => $server->getAnnouncementTitle(),
            'severity' => $server->getAnnouncementSeverity(),
            'text' => $server->getAnnouncementText(),
        ];
    }
}
