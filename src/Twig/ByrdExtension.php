<?php

/*
 * This file has been created by developers from BitBag.
 * Feel free to contact us once you face any issues or want to start
 * You can find more information about us on https://bitbag.io and write us
 * an email on hello@bitbag.io.
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
        if (null === $gateway) {
            return false;
        }

        $config = $gateway->getConfig();

        return isset($config['auto_sku_matching']) && $config['auto_sku_matching'];
    }
}
