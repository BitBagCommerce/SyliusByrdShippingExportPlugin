<?php

/*
 * This file was created by developers working at BitBag
 * Do you need more information about us and what we do? Visit our https://bitbag.io website!
 * We are hiring developers from all over the world. Join us and start your new, exciting adventure and become part of us: https://bitbag.io/career
*/

declare(strict_types=1);

namespace BitBag\SyliusByrdShippingExportPlugin\Api\ByrdRequest;

use BitBag\SyliusShippingExportPlugin\Entity\ShippingGatewayInterface;
use Sylius\Component\Core\Model\OrderInterface;

interface CreateShipmentByrdRequestInterface extends AbstractByrdRequestInterface
{
    public function setOrder(
        OrderInterface $order
    ): void;

    public function setShippingGateway(
        ShippingGatewayInterface $shippingGateway
    ): void;
}
