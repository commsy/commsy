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

namespace App\Action\Delete;

use App\Rubric\Material\MaterialDeleter;
use App\Services\CurrentUserResolver;
use cs_item;
use cs_section_item;
use Symfony\Component\Routing\RouterInterface;

/**
 * Thin wrapper around {@see MaterialDeleter::deleteSection()} — removes
 * the section for the current material version only.
 */
class DeleteSection implements DeleteInterface
{
    public function __construct(
        private readonly RouterInterface $router,
        private readonly MaterialDeleter $materialDeleter,
        private readonly CurrentUserResolver $currentUserResolver,
    ) {
    }

    public function delete(cs_item $item): void
    {
        /** @var cs_section_item $section */
        $section = $item;

        $deleterId = (int) ($this->currentUserResolver->getUser()?->getItemId() ?? 0);
        $materialVersionId = (int) $section->getLinkedItem()->getVersionID();

        $this->materialDeleter->deleteSection(
            (int) $section->getItemId(),
            $deleterId,
            $materialVersionId,
        );
    }

    public function getRedirectRoute(cs_item $item): ?string
    {
        /** @var cs_section_item $section */
        $section = $item;

        return $this->router->generate('app_material_detail', [
            'roomId' => $section->getContextID(),
            'itemId' => $section->getLinkedItemID(),
        ]);
    }
}
