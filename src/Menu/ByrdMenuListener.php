<?php

/*
 * This file has been created by developers from BitBag.
 * Feel free to contact us once you face any issues or want to start
 * You can find more information about us on https://bitbag.io and write us
 * an email on hello@bitbag.io.
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
