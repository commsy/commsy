<?php

namespace App\Feed\Creators;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class SectionCreator extends Creator
{
    public function canCreate($rubric)
    {
        return $rubric === 'section';
    }

    public function getTitle($item)
    {
        $linkedItem = $item->getLinkedItem();

        return $this->translator->trans('Section %title% of: %linked_title%', [
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

        return $this->router->generate('app_material_detail', [
            'roomId' => $linkedItem->getContextId(),
            'itemId' => $linkedItem->getItemId(),
        ], UrlGeneratorInterface::ABSOLUTE_URL);
    }
}