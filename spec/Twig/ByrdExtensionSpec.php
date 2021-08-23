<?php

/*
 * This file was created by developers working at BitBag
 * Do you need more information about us and what we do? Visit our https://bitbag.io website!
 * We are hiring developers from all over the world. Join us and start your new, exciting adventure and become part of us: https://bitbag.io/career
*/

declare(strict_types=1);

namespace spec\BitBag\SyliusByrdShippingExportPlugin\Twig;

use BitBag\SyliusByrdShippingExportPlugin\Twig\ByrdExtension;
use BitBag\SyliusShippingExportPlugin\Entity\ShippingGatewayInterface;
use BitBag\SyliusShippingExportPlugin\Repository\ShippingGatewayRepositoryInterface;
use PhpSpec\ObjectBehavior;
use Twig\Extension\AbstractExtension;

class ByrdExtensionSpec extends ObjectBehavior
{
    function let(
        ShippingGatewayRepositoryInterface $shippingGatewayRepository
    ): void {
        $this->beConstructedWith(
            $shippingGatewayRepository
        );
    }

    function it_is_initializable(): void
    {
        $this->shouldHaveType(ByrdExtension::class);
        $this->shouldHaveType(AbstractExtension::class);
    }

    function it_returns_one_function(): void
    {
        $this->getFunctions()->shouldHaveCount(1);
        $this->getFunctions()->shouldBeArray();
    }

    function it_returns_automapping_state_false_when_no_gateway_found(
        ShippingGatewayRepositoryInterface $shippingGatewayRepository
    ): void {
        $shippingGatewayRepository->findOneByCode('byrd')->willReturn(null);

        $this->isAutoMappingState()->shouldReturn(false);
    }

    function it_returns_automapping_state_false_when_no_key_set_in_configuration(
        ShippingGatewayRepositoryInterface $shippingGatewayRepository,
        ShippingGatewayInterface $shippingGateway
    ): void {
        $shippingGatewayRepository->findOneByCode('byrd')->willReturn($shippingGateway);

        $shippingGateway->getConfig()->willReturn([]);

        $this->isAutoMappingState()->shouldReturn(false);
    }

    function it_returns_automapping_state_false_when_key_set_to_false_in_configuration(
        ShippingGatewayRepositoryInterface $shippingGatewayRepository,
        ShippingGatewayInterface $shippingGateway
    ): void {
        $shippingGatewayRepository->findOneByCode('byrd')->willReturn($shippingGateway);

        $shippingGateway->getConfig()->willReturn([
            'auto_sku_matching' => false,
        ]);

        $this->isAutoMappingState()->shouldReturn(false);
    }

    function it_returns_automapping_state_true_when_key_set_to_true_in_configuration(
        ShippingGatewayRepositoryInterface $shippingGatewayRepository,
        ShippingGatewayInterface $shippingGateway
    ): void {
        $shippingGatewayRepository->findOneByCode('byrd')->willReturn($shippingGateway);

        $shippingGateway->getConfig()->willReturn([
            'auto_sku_matching' => true,
        ]);

        $this->isAutoMappingState()->shouldReturn(true);
    }
}
