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

use FeedIo\Feed\Item;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Contracts\Translation\TranslatorInterface;

abstract class Creator
{
    protected $isGuestAccess = false;
    protected $textConverter;
    protected $translator;
    protected $router;

    public function createItem($item)
    {
        if ($item->isDeleted() || $item->isNotActivated()) {
            return null;
        }

        $feedItem = new Item();

        $feedItem->setTitle($this->getTitle($item));
        $feedItem->setLastModified(new \DateTime($item->getModificationDate()));

        if ($this->generateAuthor($item)) {
            $feedItem->set('author', $this->getAuthor($item));
        }

        $feedItem->setDescription($this->getDescription($item));
        $feedItem->setLink($this->getLink($item));

        return $feedItem;
    }

    public function setGuestAccess($isGuestAccess)
    {
        $this->isGuestAccess = $isGuestAccess;
    }

    public function setTextConverter($textConverter)
    {
        $this->textConverter = $textConverter;
    }

    public function setTranslator(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function setRouter(Router $router)
    {
        $this->router = $router;
    }

    private function generateAuthor($item)
    {
        $contextItem = $item->getContextItem();
        $modifierItem = $item->getModificatorItem();

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
