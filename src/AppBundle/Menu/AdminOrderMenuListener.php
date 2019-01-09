<?php
/**
 * Created by PhpStorm.
 * User: beathan
 * Date: 08.06.2018
 * Time: 16:16
 */

namespace AppBundle\Menu;


use Sylius\Bundle\UiBundle\Menu\Event\MenuBuilderEvent;
use Sylius\Component\Core\Model\OrderInterface;


final class AdminOrderMenuListener
{
    /**
     * @param MenuBuilderEvent $event
     */
    public function addAdminMenuItems(MenuBuilderEvent $event): void
    {
        $menu = $event->getMenu();
        /** @var OrderInterface $order */
        $order = $event->getOrder();

        $newSubmenu = $menu
            ->addChild('new')
            ->setLabel('sylius.ui.order_requests')
            ->setAttribute('type','dropdown')
        ;

        $newSubmenu
            ->addChild('order_request_payment',['route' => 'sylius_admin_order_request',
                'routeParameters' => ['id' => $order->getId(),'action' =>'order_request_payment']])
            ->setLabel('sylius.ui.order_request_payment')
            ->setAttribute('type','link');
        $newSubmenu
            ->addChild('order_request_ring_size',['route' => 'sylius_admin_order_request',
                'routeParameters' => ['id' => $order->getId(),'action' =>'order_request_ring_size']])
            ->setLabel('sylius.ui.order_request_ring_size')
            ->setAttribute('type','transition');
        $newSubmenu
            ->addChild('order_request_belt_color',['route' => 'sylius_admin_order_request',
                'routeParameters' => ['id' => $order->getId(),'action' =>'order_request_belt_color']])
            ->setLabel('sylius.ui.order_request_belt_color')
            ->setAttribute('type','transition');
        $newSubmenu
            ->addChild('order_request_belt_length',['route' => 'sylius_admin_order_request',
                'routeParameters' => ['id' => $order->getId(),'action' =>'order_request_belt_length']])
            ->setLabel('sylius.ui.order_request_belt_length')
            ->setAttribute('type','transition');
        $newSubmenu
            ->addChild('order_request_waist_length',['route' => 'sylius_admin_order_request',
                'routeParameters' => ['id' => $order->getId(),'action' =>'order_request_waist_length']])
            ->setLabel('sylius.ui.order_request_waist_length')
            ->setAttribute('type','link');
        $newSubmenu
            ->addChild('order_request_waist_length_and_belt',['route' => 'sylius_admin_order_request',
                'routeParameters' => ['id' => $order->getId(),'action' =>'order_request_waist_length_and_belt']])
            ->setLabel('sylius.ui.order_request_waist_length_and_belt')
            ->setAttribute('type','link');
        $newSubmenu
            ->addChild('order_request_waist_length_and_belt_color',['route' => 'sylius_admin_order_request',
                'routeParameters' => ['id' => $order->getId(),'action' =>'order_request_waist_length_and_belt_color']])
            ->setLabel('sylius.ui.order_request_waist_length_and_belt_color')
            ->setAttribute('type','link')
        ;

        if ($order->getState()!='in_production' && $order->getState()!='fulfilled' && $order->getState()!='cancelled') {
            $menu
                ->addChild('in_progress',['route' => 'sylius_admin_order_in_progress',
                    'routeParameters' => ['id' => $order->getId()]])
                ->setLabel('Ok')
                ->setLabelAttribute('icon','clipboard check')
                ->setAttribute('type','link');
            ;

            $menu_order = array_keys($menu->getChildren());
            $menu_order[0] = 'in_progress';
            $menu_order[3] = 'order_history';
            $menu->reorderChildren($menu_order);
        }


    }
}