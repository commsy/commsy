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

use cs_item;
use DateTime;
use FeedIo\Feed\Item;
use misc_text_converter;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

abstract class Creator implements CreatorInterface
{
    protected bool $isGuestAccess = false;
    protected misc_text_converter $textConverter;
    protected TranslatorInterface $translator;
    protected RouterInterface $router;

    public function createItem($item): ?Item
    {
        if ($item->isDeleted() || $item->isNotActivated()) {
            return null;
        }

        $feedItem = new Item();

        $feedItem->setTitle($this->getTitle($item));
        $feedItem->setLastModified(new DateTime($item->getModificationDate()));

        if ($this->generateAuthor($item)) {
            $feedItem->set('author', $this->getAuthor($item));
        }

        $feedItem->setContent($this->getDescription($item));
        $feedItem->setLink($this->getLink($item));

        return $feedItem;
    }

    public function setGuestAccess($isGuestAccess)
    {
        $this->isGuestAccess = $isGuestAccess;
    }

    public function setTextConverter(misc_text_converter $textConverter)
    {
        $this->textConverter = $textConverter;
    }

    public function setTranslator(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function setRouter(RouterInterface $router)
    {
        $this->router = $router;
    }

    private function generateAuthor(cs_item $item): bool
    {
        $contextItem = $item->getContextItem();
        $modifierItem = $item->getModificatorItem();

        if (!$modifierItem) {
            return false;
        }

        if ($contextItem->isCommunityRoom()) {
            if ($this->isGuestAccess) {
                return $modifierItem->isVisibleForAll();
            }
        }

        return $modifierItem->isEmailVisible();
    }

    public function getAuthor($item)
    {
        $modifierItem = $item->getModificatorItem();
        $modifierEmail = $modifierItem->getEmail();

        return $modifierEmail.' ('.$modifierItem->getFullName().')';
    }

    abstract public function canCreate($rubric);

    abstract public function getTitle($item);

    abstract public function getDescription($item);

    abstract public function getLink($item);
}
