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

use Symfony\Component\Routing\RouterInterface;

class DeleteSection implements DeleteInterface
{
    public function __construct(private RouterInterface $router)
    {
    }

    public function delete(\cs_item $item): void
    {
        /** @var \cs_section_item $section */
        $section = $item;

        $material = $section->getLinkedItem();
        $section->delete($material->getVersionID());
    }

    /**
     * @return string|null
     */
    public function getRedirectRoute(\cs_item $item)
    {
        /** @var \cs_section_item $section */
        $section = $item;

        return $this->router->generate('app_material_detail', [
            'roomId' => $section->getContextID(),
            'itemId' => $section->getLinkedItemID(),
        ]);
    }
}
