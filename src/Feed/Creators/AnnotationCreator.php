<?php

namespace App\Feed\Creators;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class AnnotationCreator extends Creator
{
    public function canCreate($rubric)
    {
        return $rubric === 'annotation';
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

        $routeName = 'commsy_' . $linkedItem->getType() . '_detail';
        return $this->router->generate($routeName, [
            'roomId' => $linkedItem->getContextId(),
            'itemId' => $linkedItem->getItemId(),
        ], UrlGeneratorInterface::ABSOLUTE_URL);
    }
}