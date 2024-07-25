<?php

/*
 * This file has been created by developers from BitBag.
 * Feel free to contact us once you face any issues or want to start
 * You can find more information about us on https://bitbag.io and write us
 * an email on hello@bitbag.io.
 */

declare(strict_types=1);

namespace BitBag\SyliusByrdShippingExportPlugin\Api\Client;

use BitBag\SyliusShippingExportPlugin\Entity\ShippingGatewayInterface;
use Sylius\Component\Core\Model\OrderInterface;

interface ByrdHttpClientInterface
{
    public function createShipment(
        OrderInterface $order,
        ShippingGatewayInterface $shippingGateway,
    ): void;

    public function filterProductsBySku(
        ?string $sku,
        ShippingGatewayInterface $shippingGateway,
    ): array;
}
