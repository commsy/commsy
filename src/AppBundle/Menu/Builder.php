<?php
namespace AppBundle\Menu;

use Knp\Menu\FactoryInterface;
use Symfony\Component\DependencyInjection\ContainerAware;
use AppBundle\Event\SidebarMenuEvent;

class Builder extends ContainerAware
{
    /**
     * Builds the sidebar menu
     * 
     * @param FactoryInterface $factory Menu Factory
     * @param  array $options An array of options
     * 
     * @return Knp\Menu\MenuItem The created menu item
     */
    public function sidebarMenu(FactoryInterface $factory, array $options)
    {
        $menu = $factory->createItem('root');

        $menu->addChild('Dashboard', array(
            'route' => 'commsy_dashboard',
            'attributes' => array(
                'icon' => 'home',
            )
        ));

        $menu->addChild('Räume', array(
            'route' => 'commsy_room_list',
            'attributes' => array(
                'icon' => 'cube',
            )
        ));

        // dispatch an event to allow other bundles to integrate new items
        // into this menu
        $this->container->get('event_dispatcher')->dispatch(
            SidebarMenuEvent::EVENT_NAME,
            new SidebarMenuEvent($factory, $menu)
        );

        return $menu;
    }
}