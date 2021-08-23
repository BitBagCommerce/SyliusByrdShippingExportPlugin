<?php

/*
 * This file was created by developers working at BitBag
 * Do you need more information about us and what we do? Visit our https://bitbag.io website!
 * We are hiring developers from all over the world. Join us and start your new, exciting adventure and become part of us: https://bitbag.io/career
*/

declare(strict_types=1);

namespace BitBag\SyliusByrdShippingExportPlugin\Menu;

use Knp\Menu\ItemInterface;
use Sylius\Bundle\UiBundle\Menu\Event\MenuBuilderEvent;

final class ByrdMenuListener
{
    public function buildMenu(MenuBuilderEvent $menuBuilderEvent): void
    {
        /** @var ItemInterface $menu */
        $menu = $menuBuilderEvent->getMenu();

        /** @var ItemInterface $menu */
        $menu = $menu->getChild('catalog');

        $menu->addChild('byrd', [
            'route' => 'bitbag_sylius_byrd_shipping_export_plugin_admin_byrd_product_mapping_index',
        ])
        ->setLabel('bitbag_sylius_byrd_shipping_export_plugin.ui.byrd_product_mapping_label')
        ->setLabelAttribute('icon', 'cogs');
    }
}
