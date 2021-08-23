<?php

/*
 * This file was created by developers working at BitBag
 * Do you need more information about us and what we do? Visit our https://bitbag.io website!
 * We are hiring developers from all over the world. Join us and start your new, exciting adventure and become part of us: https://bitbag.io/career
*/

declare(strict_types=1);

namespace BitBag\SyliusByrdShippingExportPlugin\Twig;

use BitBag\SyliusShippingExportPlugin\Repository\ShippingGatewayRepositoryInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class ByrdExtension extends AbstractExtension
{
    /** @var ShippingGatewayRepositoryInterface */
    private $shippingGatewayRepository;

    public function __construct(ShippingGatewayRepositoryInterface $shippingGatewayRepository)
    {
        $this->shippingGatewayRepository = $shippingGatewayRepository;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('byrd_auto_mapping_state', [$this, 'isAutoMappingState']),
        ];
    }

    public function isAutoMappingState(): bool
    {
        $gateway = $this->shippingGatewayRepository->findOneByCode('byrd');
        if ($gateway === null) {
            return false;
        }

        $config = $gateway->getConfig();

        return isset($config['auto_sku_matching']) && $config['auto_sku_matching'];
    }
}
