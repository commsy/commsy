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

class AnnotationCreator extends Creator
{
    public function canCreate($rubric)
    {
        return 'annotation' === $rubric;
    }

    public function getTitle($item)
    {
        $linkedItem = $item->getLinkedItem();

        return $this->translator->trans('Comment %title% on: %linked_title%', [
            '%title%' => $item->getTitle(),
            '%linked_title%' => $linkedItem->getTitle(),
        ], 'rss');
    }

    public function getDescription($item)
    {
        return $this->textConverter->textFullHTMLFormatting($item->getDescription());
    }

    public function getLink($item)
    {
        $linkedItem = $item->getLinkedItem();

        $routeName = 'app_'.$linkedItem->getType().'_detail';

        return $this->router->generate($routeName, [
            'roomId' => $linkedItem->getContextId(),
            'itemId' => $linkedItem->getItemId(),
        ], UrlGeneratorInterface::ABSOLUTE_URL);
    }
}
