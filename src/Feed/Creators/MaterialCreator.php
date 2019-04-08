<?php

namespace App\Feed\Creators;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class MaterialCreator extends Creator
{
    public function canCreate($rubric)
    {
        return $rubric === 'material';
    }

    public function getTitle($item)
    {
        return $this->translator->trans('Material: %title%', ['%title%' => $item->getTitle()], 'rss');
    }

    public function getDescription($item)
    {
        if ($this->isGuestAccess) {
            if (!$item->isWorldPublic()) {
                return $this->translator('Not visible', [], 'rss');
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