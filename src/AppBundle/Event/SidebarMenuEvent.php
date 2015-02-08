<?php
namespace AppBundle\Event;

use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;
use Symfony\Component\EventDispatcher\Event;

class SidebarMenuEvent extends Event
{
    const EVENT_NAME = 'commsy.menu_sidebar';

    private $factory;
    private $menu;

    /**
     * @param Knp\Menu\FactoryInterface $factory
     * @param Knp\Menu\ItemInterface $menu
     */
    public function __construct(FactoryInterface $factory, ItemInterface $menu)
    {
        $this->factory = $factory;
        $this->menu = $menu;
    }

    /**
     * Returns the menu factory
     * 
     * @return Knp\Menu\FactoryInterface
     */
    public function getFactory()
    {
        return $this->factory;
    }

    /**
     * Returns the menu item
     * @return Knp\Menu\ItemInterface
     */
    public function getMenu()
    {
        return $this->menu;
    }
}