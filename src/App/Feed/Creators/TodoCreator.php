<?php

namespace App\Feed\Creators;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class TodoCreator extends Creator
{
    public function canCreate($rubric)
    {
        return $rubric === 'todo';
    }

    public function getTitle($item)
    {
        return $this->translator->trans('Task: %title% (due date %due_date%)', [
            '%title%' => $item->getTitle(),
            '%due_date%' => date('d.m.Y',strtotime($item->getDate())),
        ], 'rss');
    }

    public function getDescription($item)
    {
        return $this->textConverter->textFullHTMLFormatting($item->getDescription());
    }

    public function getLink($item)
    {
        return $this->router->generate('commsy_todo_detail', [
            'roomId' => $item->getContextId(),
            'itemId' => $item->getItemId(),
        ], UrlGeneratorInterface::ABSOLUTE_URL);
    }
}