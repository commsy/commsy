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

use App\Entity\Portal;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class GetPortalAnnouncement extends AbstractController
{
    public function __invoke(Portal $data): array
    {
        $portal = $data;

        return [
            'enabled' => $portal->hasAnnouncementEnabled(),
            'title' => $portal->getAnnouncementTitle(),
            'severity' => $portal->getAnnouncementSeverity(),
            'text' => $portal->getAnnouncementText(),
        ];
    }
}
