<?php

/*
 * This file was created by developers working at BitBag
 * Do you need more information about us and what we do? Visit our https://bitbag.io website!
 * We are hiring developers from all over the world. Join us and start your new, exciting adventure and become part of us: https://bitbag.io/career
*/

declare(strict_types=1);

namespace spec\BitBag\SyliusByrdShippingExportPlugin\Entity;

use BitBag\SyliusByrdShippingExportPlugin\Entity\ByrdProductMapping;
use PhpSpec\ObjectBehavior;
use Sylius\Component\Core\Model\ProductInterface;

class ByrdProductMappingSpec extends ObjectBehavior
{
    function let(ProductInterface $product): void
    {
        $this->setProduct($product);
        $this->setByrdProductSku('SKU-123');
    }

    function it_is_initializable(): void
    {
        $this->shouldHaveType(ByrdProductMapping::class);
    }

    function it_returns_product(ProductInterface $product): void
    {
        $this->getProduct()->shouldReturn($product);
    }

    function it_returns_byrd_sku(): void
    {
        $this->getByrdProductSku()->shouldReturn('SKU-123');
    }
}
