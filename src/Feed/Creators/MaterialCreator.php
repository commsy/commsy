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

namespace App\Feed\Creators;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class MaterialCreator extends Creator
{
    public function canCreate($rubric)
    {
        return 'material' === $rubric;
    }

    public function getTitle($item)
    {
        return $this->translator->trans('Material: %title%', ['%title%' => $item->getTitle()], 'rss');
    }

    public function getDescription($item)
    {
        if ($this->isGuestAccess) {
            if (!$item->isWorldPublic()) {
                return $this->translator->trans('Not visible', [], 'rss');
            }
        }

        return $this->textConverter->textFullHTMLFormatting($item->getDescription());
    }

    public function getLink($item)
    {
        return $this->router->generate('app_material_detail', [
            'roomId' => $item->getContextId(),
            'itemId' => $item->getItemId(),
        ], UrlGeneratorInterface::ABSOLUTE_URL);
    }
}
